#!/usr/bin/env php
<?php
/**
 * Pi Payout Updater
 * Reads entry fee and player count from Google Sheets
 * Calculates payouts and writes them back (LIMITED TO 12 PLACES)
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
$SHEET_NAME = 'Tournament Players';
$PAYOUTS_SHEET = 'Payouts';

// Input cells
$ENTRY_FEE_CELL = 'Q3'; // From Payouts sheet
$PLAYER_COUNT_RANGE = 'B2:B72'; // Count non-empty cells in Tournament Players

// Output cells (in Tournament Players sheet) - FIXED to 7 cells
$OUTPUT_SHEET = 'Tournament Players';
$OUTPUT_CELLS = [
    'G8',  // 1st place
    'G9',  // 2nd place
    'G10', // 3rd place
    'G11', // 4th place
    'G12', // 5th-6th place
    'G13', // 7th-8th place
    'G14'  // 9th-12th place
];

// Log function
function logMessage($message) {
    $timestamp = date('Y-m-d H:i:s');
    $logFile = '/var/www/html/payout_updater.log';
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
    
    // Get entry fee from Payouts sheet Q3
    $entryFeeRange = "{$PAYOUTS_SHEET}!{$ENTRY_FEE_CELL}";
    $entryFeeResponse = $service->spreadsheets_values->get($SPREADSHEET_ID, $entryFeeRange);
    $entryFeeValues = $entryFeeResponse->getValues();
    $entryFee = null;
    if (!empty($entryFeeValues) && !empty($entryFeeValues[0][0])) {
        $entryFee = floatval($entryFeeValues[0][0]);
    }
    
    // Get player count by counting non-empty cells in B2:B72
    $playerCountRange = "{$SHEET_NAME}!{$PLAYER_COUNT_RANGE}";
    $playerCountResponse = $service->spreadsheets_values->get($SPREADSHEET_ID, $playerCountRange);
    $playerCountValues = $playerCountResponse->getValues();
    $playerCount = 0;
    if (!empty($playerCountValues)) {
        foreach ($playerCountValues as $row) {
            if (!empty($row[0]) && trim($row[0]) !== '') {
                $playerCount++;
            }
        }
    }
    
    logMessage("Read values - Entry Fee: \$$entryFee, Player Count: $playerCount");
    
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
    
    // Calculate payouts - FULL calculation
    logMessage("Calculating payouts...");
    $calculator = new TournamentPayoutCalculator($entryFee, $playerCount);
    $payoutsArray = $calculator->getPayoutsArray();
    
    // CRITICAL: Sort by place number to ensure correct order
    ksort($payoutsArray);
    
    logMessage("Calculated " . count($payoutsArray) . " places total, displaying first 12 in fixed cells");
    
    // Format payouts for output (only first 12 places fit in 7 cells with ties)
    $values = [];
    
    // Map place numbers to output cells
    $placeMapping = [
        1 => 0,  // G8 - 1st place
        2 => 1,  // G9 - 2nd place
        3 => 2,  // G10 - 3rd place
        4 => 3,  // G11 - 4th place
        5 => 4,  // G12 - 5th-6th place (use 5)
        7 => 5,  // G13 - 7th-8th place (use 7)
        9 => 6   // G14 - 9th-12th place (use 9)
    ];
    
    // Initialize all cells as empty
    for ($i = 0; $i < 7; $i++) {
        $values[$i] = [''];
    }
    
    // Fill in payouts - only showing first 12 places from FULL calculation
    foreach ($payoutsArray as $place => $amount) {
        // Only process places 1-12
        if ($place <= 12 && isset($placeMapping[$place])) {
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
