#!/usr/bin/env php
<?php
/**
 * Pi Payout Updater - Special Events with Added Money
 * Reads entry fee, player count, and added money from Google Sheets
 * Calculates payouts and writes them back DYNAMICALLY (all places)
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

// Input cells
$ENTRY_FEE_CELL = 'B2';
$PLAYER_COUNT_RANGE = 'B1';
$ADDED_MONEY_CELL = 'B3';

// Output sheet
$OUTPUT_SHEET = 'Special Event Payout Calculator';

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
    
    // Get entry fee
    $entryFeeRange = "{$SHEET_NAME}!{$ENTRY_FEE_CELL}";
    $entryFeeResponse = $service->spreadsheets_values->get($SPREADSHEET_ID, $entryFeeRange);
    $entryFeeValues = $entryFeeResponse->getValues();
    $entryFee = null;
    if (!empty($entryFeeValues) && !empty($entryFeeValues[0][0])) {
        $entryFee = floatval($entryFeeValues[0][0]);
    }
    
    // Get player count
    $playerCountRange = "{$SHEET_NAME}!{$PLAYER_COUNT_RANGE}";
    $playerCountResponse = $service->spreadsheets_values->get($SPREADSHEET_ID, $playerCountRange);
    $playerCountValues = $playerCountResponse->getValues();
    $playerCount = null;
    if (!empty($playerCountValues) && !empty($playerCountValues[0][0])) {
        $playerCount = floatval($playerCountValues[0][0]);
    }
    
    // Get added money
    $addedMoneyRange = "{$SHEET_NAME}!{$ADDED_MONEY_CELL}";
    $addedMoneyResponse = $service->spreadsheets_values->get($SPREADSHEET_ID, $addedMoneyRange);
    $addedMoneyValues = $addedMoneyResponse->getValues();
    $addedMoney = 0;
    if (!empty($addedMoneyValues) && !empty($addedMoneyValues[0][0])) {
        $addedMoney = floatval($addedMoneyValues[0][0]);
    }
    
    $entryPool = $entryFee * $playerCount;
    $totalPrizePool = $entryPool + $addedMoney;
    
    logMessage("Read values - Entry Fee: \$$entryFee, Player Count: $playerCount, Added Money: \$$addedMoney");
    logMessage("Prize pool - Entries: \$$entryPool + Added: \$$addedMoney = Total: \$$totalPrizePool");
    
    // Clear column E thoroughly (E1:E30 to handle any leftover data)
    $clearRange = "{$OUTPUT_SHEET}!E1:E30";
    $clearBody = new Google\Service\Sheets\ValueRange([
        'values' => array_fill(0, 30, [''])
    ]);
    $service->spreadsheets_values->update(
        $SPREADSHEET_ID,
        $clearRange,
        $clearBody,
        ['valueInputOption' => 'RAW']
    );
    
    logMessage("Cleared column E (E1:E30)");
    
    // Validate inputs
    if (!$entryFee || !$playerCount) {
        logMessage("Missing entry fee or player count - skipping update");
        exit(0);
    }
    
    if ($entryFee <= 0) {
        logMessage("Invalid entry fee - skipping update");
        exit(0);
    }
    
    if ($playerCount < 8) {
        logMessage("Less than 8 players - showing error");
        $errorRange = "{$OUTPUT_SHEET}!E1";
        $errorBody = new Google\Service\Sheets\ValueRange([
            'values' => [['Need 8+ players']]
        ]);
        $service->spreadsheets_values->update(
            $SPREADSHEET_ID,
            $errorRange,
            $errorBody,
            ['valueInputOption' => 'RAW']
        );
        exit(0);
    }
    
    if ($entryFee % 5 != 0) {
        logMessage("Entry fee not divisible by 5 - showing error");
        $errorRange = "{$OUTPUT_SHEET}!E1:E3";
        $errorBody = new Google\Service\Sheets\ValueRange([
            'values' => [['Entry fee must'], ['be divisible'], ['by $5']]
        ]);
        $service->spreadsheets_values->update(
            $SPREADSHEET_ID,
            $errorRange,
            $errorBody,
            ['valueInputOption' => 'RAW']
        );
        exit(0);
    }
    
    // Calculate payouts with added money - NO LIMITING
    logMessage("Calculating payouts...");
    $calculator = new TournamentPayoutCalculator($entryFee, $playerCount, $addedMoney);
    $payoutsArray = $calculator->getPayoutsArray();
    
    // CRITICAL: Sort by place number to ensure correct order
    ksort($payoutsArray);
    
    logMessage("Calculated " . count($payoutsArray) . " places");
    
    // Build output values dynamically
    $outputValues = [];
    $row = 0;
    
    foreach ($payoutsArray as $place => $amount) {
        // Only show the first place in each tie group
        $shouldShow = false;
        $label = '';
        
        if ($place == 1) { $shouldShow = true; $label = '1st'; }
        elseif ($place == 2) { $shouldShow = true; $label = '2nd'; }
        elseif ($place == 3) { $shouldShow = true; $label = '3rd'; }
        elseif ($place == 4) { $shouldShow = true; $label = '4th'; }
        elseif ($place == 5) { $shouldShow = true; $label = '5th-6th'; }
        elseif ($place == 7) { $shouldShow = true; $label = '7th-8th'; }
        elseif ($place == 9) { $shouldShow = true; $label = '9th-12th'; }
        elseif ($place == 13) { $shouldShow = true; $label = '13th-16th'; }
        elseif ($place == 17) { $shouldShow = true; $label = '17th-24th'; }
        elseif ($place == 25) { $shouldShow = true; $label = '25th-32nd'; }
        elseif ($place == 33) { $shouldShow = true; $label = '33rd-48th'; }
        elseif ($place == 49) { $shouldShow = true; $label = '49th-64th'; }
        elseif ($place == 65) { $shouldShow = true; $label = '65th-96th'; }
        elseif ($place == 97) { $shouldShow = true; $label = '97th-128th'; }
        elseif ($place == 129) { $shouldShow = true; $label = '129th-256th'; }
        
        if ($shouldShow) {
            if ($label == '1st' || $label == '2nd' || $label == '3rd' || $label == '4th') {
                $outputValues[] = ['$' . number_format($amount, 2)];
            } else {
                $outputValues[] = ['$' . number_format($amount, 2) . ' (' . $label . ')'];
            }
            $row++;
        }
    }
    
    // Write all values to column E starting at E1
    if (!empty($outputValues)) {
        $outputRange = "{$OUTPUT_SHEET}!E1:E" . count($outputValues);
        $outputBody = new Google\Service\Sheets\ValueRange([
            'values' => $outputValues
        ]);
        $service->spreadsheets_values->update(
            $SPREADSHEET_ID,
            $outputRange,
            $outputBody,
            ['valueInputOption' => 'RAW']
        );
        
        logMessage("Successfully updated " . count($outputValues) . " payout rows");
    }
    
} catch (Exception $e) {
    logMessage("ERROR: " . $e->getMessage());
    exit(1);
}
?>
