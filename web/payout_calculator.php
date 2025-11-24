<?php
/**
 * Dynamic Tournament Payout Calculator
 * Calculates payouts ensuring 100% payout with no admin fees
 * Minimum payout always equals entry fee
 */

function calculatePayouts($playerCount, $entryFee) {
    $totalPot = $playerCount * $entryFee;
    $payouts = [];
    
    // No payouts for less than 8 players
    if ($playerCount < 8) {
        return null;
    }
    
    // Define payout percentages based on player count tiers
    if ($playerCount <= 11) {
        // 2 places
        $percentages = [
            1 => 0.625,  // 62.5% to 1st
            2 => 0.375   // 37.5% to 2nd
        ];
    } elseif ($playerCount <= 15) {
        // 3 places
        $percentages = [
            1 => 0.6111,  // ~61%
            2 => 0.3056,  // ~31%
            3 => 0.0833   // ~8%
        ];
    } elseif ($playerCount <= 25) {
        // 4 places
        $percentages = [
            1 => 0.5833,  // ~58%
            2 => 0.2778,  // ~28%
            3 => 0.0972,  // ~10%
            4 => 0.0417   // ~4%
        ];
    } elseif ($playerCount <= 34) {
        // 5 places (5th/6th split)
        $percentages = [
            1 => 0.5625,  // ~56%
            2 => 0.2708,  // ~27%
            3 => 0.1042,  // ~10%
            4 => 0.0417,  // ~4%
            5 => 0.0208   // ~2% (each for 5th/6th)
        ];
    } else {
        // 7 places (5th/6th split, 7th/8th split)
        $percentages = [
            1 => 0.5208,  // ~52%
            2 => 0.2708,  // ~27%
            3 => 0.1042,  // ~10%
            4 => 0.0521,  // ~5%
            5 => 0.0365,  // ~3.5% (each for 5th/6th)
            7 => 0.0156   // ~1.5% (each for 7th/8th)
        ];
    }
    
    // Calculate raw payouts
    $rawPayouts = [];
    foreach ($percentages as $place => $percentage) {
        $rawPayouts[$place] = $totalPot * $percentage;
    }
    
    // Round to nearest $5 and ensure minimum equals entry fee
    $finalPayouts = [];
    $totalPaid = 0;
    
    foreach ($rawPayouts as $place => $amount) {
        // Round to nearest $5
        $rounded = round($amount / 5) * 5;
        
        // Ensure minimum payout equals entry fee
        if ($rounded < $entryFee) {
            $rounded = $entryFee;
        }
        
        $finalPayouts[$place] = $rounded;
        $totalPaid += $rounded;
    }
    
    // Adjust first place to ensure 100% payout
    $difference = $totalPot - $totalPaid;
    $finalPayouts[1] += $difference;
    
    // Format payouts for display
    $formattedPayouts = [
        'Players' => $playerCount,
        '1st' => '$' . number_format($finalPayouts[1], 0),
        '2nd' => '$' . number_format($finalPayouts[2], 0),
        '3rd' => isset($finalPayouts[3]) ? '$' . number_format($finalPayouts[3], 0) : null,
        '4th' => isset($finalPayouts[4]) ? '$' . number_format($finalPayouts[4], 0) : null,
        '5th' => isset($finalPayouts[5]) ? '$' . number_format($finalPayouts[5], 0) : null,
        '7th' => isset($finalPayouts[7]) ? '$' . number_format($finalPayouts[7], 0) : null,
    ];
    
    return $formattedPayouts;
}

/**
 * Get payouts for a specific player count
 */
function getPayouts($playerCount, $entryFee = 15) {
    $payouts = calculatePayouts($playerCount, $entryFee);
    
    if (!$payouts) {
        return ['error' => 'Not enough players for payouts'];
    }
    
    return $payouts;
}

/**
 * Format payouts for HTML display
 */
function formatPayoutsHTML($playerCount, $entryFee = 15) {
    $payouts = getPayouts($playerCount, $entryFee);
    
    if (isset($payouts['error'])) {
        return '<div>Payouts TBD</div>';
    }
    
    $html = '';
    
    // Build payout display - only show places that have payouts
    if (isset($payouts['1st']) && $payouts['1st'] !== null) {
        $html .= '<div>1st: ' . $payouts['1st'] . '</div>';
    }
    
    if (isset($payouts['2nd']) && $payouts['2nd'] !== null) {
        $html .= '<div>2nd: ' . $payouts['2nd'] . '</div>';
    }
    
    if (isset($payouts['3rd']) && $payouts['3rd'] !== null) {
        $html .= '<div>3rd: ' . $payouts['3rd'] . '</div>';
    }
    
    if (isset($payouts['4th']) && $payouts['4th'] !== null) {
        $html .= '<div>4th: ' . $payouts['4th'] . '</div>';
    }
    
    if (isset($payouts['5th']) && $payouts['5th'] !== null) {
        $html .= '<div>5/6: ' . $payouts['5th'] . '</div>';
    }
    
    if (isset($payouts['7th']) && $payouts['7th'] !== null) {
        $html .= '<div>7/8: ' . $payouts['7th'] . '</div>';
    }
    
    return $html;
}

// If called directly, output JSON
if (php_sapi_name() === 'cli') {
    $playerCount = isset($argv[1]) ? (int)$argv[1] : 16;
    $entryFee = isset($argv[2]) ? (int)$argv[2] : 15;
    
    echo json_encode(getPayouts($playerCount, $entryFee), JSON_PRETTY_PRINT) . "\n";
}
?>
