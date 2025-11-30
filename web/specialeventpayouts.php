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
    'E3',  // 3rd place
    'E4',  // 4th place
    'E5',  // 5th-6th place
    'E6',  // 7th-8th place
    'E7',  // 9th-12th place
    'E8',  // 13th-16th place
    'E9',  // 17th-24th place
    'E10', // 25th-32nd place
    'E11', // 33rd-48th place
    'E12', // 49th-64th place
    'E13', // 65th-96th place
    'E14', // 97th-128th place
    'E15'  // 129th-256th place
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
    
    // Calculate payouts with added money (limit to top 12 places to fit sheet layout)
    logMessage("Calculating payouts...");
    $calculator = new TournamentPayoutCalculator($entryFee, $playerCount, $addedMoney);
    $allPayouts = $calculator->getPayoutsArray();
    
    // Only use first 12 places (fits in 7 output cells with ties)
    $payoutsArray = [];
    foreach ($allPayouts as $place => $amount) {
        if ($place <= 12) {
            $payoutsArray[$place] = $amount;
        }
    }
    
    logMessage("Using first 12 places from " . count($allPayouts) . " total places");
    
    // Format payouts for output
    $values = [];
    
    // Map place numbers to output cells
    $placeMapping = [
        1 => 0,   // E1 - 1st place
        2 => 1,   // E2 - 2nd place
        3 => 2,   // E3 - 3rd place
        4 => 3,   // E4 - 4th place
        5 => 4,   // E5 - 5th-6th place (use 5)
        7 => 5,   // E6 - 7th-8th place (use 7)
        9 => 6,   // E7 - 9th-12th place (use 9)
        13 => 7,  // E8 - 13th-16th place (use 13)
        17 => 8,  // E9 - 17th-24th place (use 17)
        25 => 9,  // E10 - 25th-32nd place (use 25)
        33 => 10, // E11 - 33rd-48th place (use 33)
        49 => 11, // E12 - 49th-64th place (use 49)
        65 => 12, // E13 - 65th-96th place (use 65)
        97 => 13, // E14 - 97th-128th place (use 97)
        129 => 14 // E15 - 129th-256th place (use 129)
    ];
    
    // Initialize all cells as empty
    for ($i = 0; $i < 15; $i++) {
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
            } elseif ($place == 13) {
                $values[$index] = ['$' . number_format($amount, 2) . ' (13th-16th)'];
            } elseif ($place == 17) {
                $values[$index] = ['$' . number_format($amount, 2) . ' (17th-24th)'];
            } elseif ($place == 25) {
                $values[$index] = ['$' . number_format($amount, 2) . ' (25th-32nd)'];
            } elseif ($place == 33) {
                $values[$index] = ['$' . number_format($amount, 2) . ' (33rd-48th)'];
            } elseif ($place == 49) {
                $values[$index] = ['$' . number_format($amount, 2) . ' (49th-64th)'];
            } elseif ($place == 65) {
                $values[$index] = ['$' . number_format($amount, 2) . ' (65th-96th)'];
            } elseif ($place == 97) {
                $values[$index] = ['$' . number_format($amount, 2) . ' (97th-128th)'];
            } elseif ($place == 129) {
                $values[$index] = ['$' . number_format($amount, 2) . ' (129th-256th)'];
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
