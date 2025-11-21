<?php
/**
 * Tournament Payout Calculator
 */

function getPayouts($playerCount, $entryFee = 15) {
    if ($playerCount < 8) {
        return ['error' => 'Minimum 8 players required'];
    }
    
    $totalPool = $playerCount * $entryFee;
    $payouts = [];
    
    if ($playerCount >= 8 && $playerCount <= 15) {
        $payouts['1st'] = '$' . number_format($totalPool * 0.625, 0);
        $payouts['2nd'] = '$' . number_format($totalPool * 0.375, 0);
    } elseif ($playerCount >= 16 && $playerCount <= 25) {
        $payouts['1st'] = '$' . number_format($totalPool * 0.50, 0);
        $payouts['2nd'] = '$' . number_format($totalPool * 0.3125, 0);
        $payouts['3rd'] = '$' . number_format($totalPool * 0.125, 0);
        $payouts['4th'] = '$' . number_format($totalPool * 0.0625, 0);
    } elseif ($playerCount >= 26 && $playerCount <= 34) {
        $payouts['1st'] = '$' . number_format($totalPool * 0.425, 0);
        $payouts['2nd'] = '$' . number_format($totalPool * 0.285, 0);
        $payouts['3rd'] = '$' . number_format($totalPool * 0.105, 0);
        $payouts['4th'] = '$' . number_format($totalPool * 0.095, 0);
        $payouts['5th'] = '$' . number_format($totalPool * 0.09, 0);
    } else {
        $payouts['1st'] = '$' . number_format($totalPool * 0.38, 0);
        $payouts['2nd'] = '$' . number_format($totalPool * 0.255, 0);
        $payouts['3rd'] = '$' . number_format($totalPool * 0.095, 0);
        $payouts['4th'] = '$' . number_format($totalPool * 0.09, 0);
        $payouts['5th'] = '$' . number_format($totalPool * 0.09, 0);
        $payouts['7th'] = '$' . number_format($totalPool * 0.09, 0);
    }
    
    return $payouts;
}

function formatPayoutsHTML($playerCount, $entryFee = 15) {
    $payouts = getPayouts($playerCount, $entryFee);
    
    if (isset($payouts['error'])) {
        return '<div>Payouts TBD</div>';
    }
    
    $html = '';
    
    if (isset($payouts['1st'])) $html .= '<div>1st: ' . $payouts['1st'] . '</div>';
    if (isset($payouts['2nd'])) $html .= '<div>2nd: ' . $payouts['2nd'] . '</div>';
    if (isset($payouts['3rd'])) $html .= '<div>3rd: ' . $payouts['3rd'] . '</div>';
    if (isset($payouts['4th'])) $html .= '<div>4th: ' . $payouts['4th'] . '</div>';
    if (isset($payouts['5th'])) $html .= '<div>5/6: ' . $payouts['5th'] . '</div>';
    if (isset($payouts['7th'])) $html .= '<div>7/8: ' . $payouts['7th'] . '</div>';
    
    return $html;
}
?>
