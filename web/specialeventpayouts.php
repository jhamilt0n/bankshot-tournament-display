#!/usr/bin/env php
<?php
/**
 * Pi Payout Updater - Special Events with Added Money
 * Reads entry fee, player count, and added money from Google Sheets
 * Calculates payouts and writes them back
 * 
 * Run via cron every minute
 */

require_once '/var/www/html/vendor/autoload.php';
require_once '/var/www/html/tournament_payout_calculator.php';

use Google\Client;
use Google\Service\Sheets;

// Configuration
$CREDENTIALS_PATH = '/var/www/html/google-credentials.json';
$SPREADSHEET_ID = '1MN7r74z3II7pMA_jMK0l03faFTG9KR84wktLNc3a6A0';
$SHEET_NAME = 'Special Event Payout Calculator';

// Input cells - read from source data, not formatted display
$ENTRY_FEE_CELL = 'B2'; // From Special Event Payout Calculator sheet
$PLAYER_COUNT_RANGE = 'B1'; // From Special Event Payout Calculator sheet
$ADDED_MONEY_CELL = 'B3'; // Added money from house/sponsors

// Output cells
$OUTPUT_SHEET = 'Special Event Payout Calculator';
$OUTPUT_CELLS = [
    'E1',  // 1st place
    'E2',  // 2nd place
    'E3', // 3rd place
    'E4', // 4th place
    'E5', // 5th-6th place
    'E6', // 7th-8th place
    'E7'  // 9th-12th place
];

// Log function
function logMessage($message) {
    $timestamp = date('Y-m-d H:i:s');
    $logFile = '/var/www/html/sepayout_updater.log';
    file_put_contents($logFile, "[$timestamp] $message\n", FILE_APPEND);
    echo "[$timestamp] $message\n";
}

try {
    // Initialize Google Client
    $client = new Client();
    $client->setApplicationName('Pi Payout Updater');
    $client->setScopes([Sheets::SPREADSHEETS]);
    $client->setAuthConfig($CREDENTIALS_PATH);
    
    $service = new Sheets($client);
    
    logMessage("Connected to Google Sheets API");
    
    // Get entry fee from Special Event Payout Calculator sheet B2
    $entryFeeRange = "{$SHEET_NAME}!{$ENTRY_FEE_CELL}";
    $entryFeeResponse = $service->spreadsheets_values->get($SPREADSHEET_ID, $entryFeeRange);
    $entryFeeValues = $entryFeeResponse->getValues();
    $entryFee = null;
    if (!empty($entryFeeValues) && !empty($entryFeeValues[0][0])) {
        $entryFee = floatval($entryFeeValues[0][0]);
    }
    
    // Get player count from Special Event Payout Calculator sheet B1
    $playerCountRange = "{$SHEET_NAME}!{$PLAYER_COUNT_RANGE}";
    $playerCountResponse = $service->spreadsheets_values->get($SPREADSHEET_ID, $playerCountRange);
    $playerCountValues = $playerCountResponse->getValues();
    $playerCount = null;
    if (!empty($playerCountValues) && !empty($playerCountValues[0][0])) {
        $playerCount = floatval($playerCountValues[0][0]);
    }
    
    // Get added money from Special Event Payout Calculator sheet B3
    $addedMoneyRange = "{$SHEET_NAME}!{$ADDED_MONEY_CELL}";
    $addedMoneyResponse = $service->spreadsheets_values->get($SPREADSHEET_ID, $addedMoneyRange);
    $addedMoneyValues = $addedMoneyResponse->getValues();
    $addedMoney = 0; // Default to 0 if not specified
    if (!empty($addedMoneyValues) && !empty($addedMoneyValues[0][0])) {
        $addedMoney = floatval($addedMoneyValues[0][0]);
    }
    
    // Calculate prize pools
    $entryPool = $entryFee * $playerCount;
    $totalPrizePool = $entryPool + $addedMoney;
    
    logMessage("Read values - Entry Fee: \$$entryFee, Player Count: $playerCount, Added Money: \$$addedMoney");
    logMessage("Prize pool - Entries: \$$entryPool + Added: \$$addedMoney = Total: \$$totalPrizePool");
    
    // Validate inputs
    if (!$entryFee || !$playerCount) {
        logMessage("Missing entry fee or player count - skipping update");
        clearPayoutCells($service, $SPREADSHEET_ID, $OUTPUT_SHEET, $OUTPUT_CELLS);
        exit(0);
    }
    
    if ($entryFee <= 0) {
        logMessage("Invalid entry fee - skipping update");
        clearPayoutCells($service, $SPREADSHEET_ID, $OUTPUT_SHEET, $OUTPUT_CELLS);
        exit(0);
    }
    
    if ($playerCount < 8) {
        logMessage("Less than 8 players - clearing payouts");
        $values = [
            ['Need 8+ players'],
            [''],
            [''],
            [''],
            [''],
            [''],
            ['']
        ];
        updatePayoutCells($service, $SPREADSHEET_ID, $OUTPUT_SHEET, $OUTPUT_CELLS, $values);
        exit(0);
    }
    
    if ($entryFee % 5 != 0) {
        logMessage("Entry fee not divisible by 5 - showing error");
        $values = [
            ['Entry fee must'],
            ['be divisible'],
            ['by $5'],
            [''],
            [''],
            [''],
            ['']
        ];
        updatePayoutCells($service, $SPREADSHEET_ID, $OUTPUT_SHEET, $OUTPUT_CELLS, $values);
        exit(0);
    }
    
    // Calculate payouts with added money
    logMessage("Calculating payouts...");
    $calculator = new TournamentPayoutCalculator($entryFee, $playerCount, $addedMoney);
    $payoutsArray = $calculator->getPayoutsArray();
    
    // Format payouts for output
    $values = [];
    
    // Map place numbers to output cells
    $placeMapping = [
        1 => 0,  // E1 - 1st place
        2 => 1,  // E2 - 2nd place
        3 => 2,  // E3 - 3rd place
        4 => 3,  // E4 - 4th place
        5 => 4,  // E5 - 5th-6th place (use 5)
        7 => 5,  // E6 - 7th-8th place (use 7)
        9 => 6   // E7 - 9th-12th place (use 9)
    ];
    
    // Initialize all cells as empty
    for ($i = 0; $i < 7; $i++) {
        $values[$i] = [''];
    }
    
    // Fill in payouts
    foreach ($payoutsArray as $place => $amount) {
        if (isset($placeMapping[$place])) {
            $index = $placeMapping[$place];
            
            // Add labels for tied places
            if ($place == 5) {
                $values[$index] = ['$' . number_format($amount, 2) . ' (5th-6th)'];
            } elseif ($place == 7) {
                $values[$index] = ['$' . number_format($amount, 2) . ' (7th-8th)'];
            } elseif ($place == 9) {
                $values[$index] = ['$' . number_format($amount, 2) . ' (9th-12th)'];
            } else {
                $values[$index] = ['$' . number_format($amount, 2)];
            }
        }
    }
    
    // Update Google Sheets
    updatePayoutCells($service, $SPREADSHEET_ID, $OUTPUT_SHEET, $OUTPUT_CELLS, $values);
    
    logMessage("Successfully updated payouts: " . implode(', ', array_map(function($v) { return $v[0]; }, $values)));
    
} catch (Exception $e) {
    logMessage("ERROR: " . $e->getMessage());
    exit(1);
}

/**
 * Update payout cells in Google Sheets
 */
function updatePayoutCells($service, $spreadsheetId, $sheetName, $cells, $values) {
    $updateData = [];
    
    for ($i = 0; $i < count($cells); $i++) {
        $updateData[] = new Google\Service\Sheets\ValueRange([
            'range' => "{$sheetName}!{$cells[$i]}",
            'values' => [$values[$i]]
        ]);
    }
    
    $body = new Google\Service\Sheets\BatchUpdateValuesRequest([
        'valueInputOption' => 'RAW',
        'data' => $updateData
    ]);
    
    $service->spreadsheets_values->batchUpdate($spreadsheetId, $body);
}

/**
 * Clear payout cells
 */
function clearPayoutCells($service, $spreadsheetId, $sheetName, $cells) {
    $values = array_fill(0, count($cells), ['']);
    updatePayoutCells($service, $spreadsheetId, $sheetName, $cells, $values);
}
?>
