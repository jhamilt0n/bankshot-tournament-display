<?php
/**
 * Payout Calculator
 * Calculates tournament payouts based on player count and entry fee
 * Used when Digital Pool Sheet doesn't have payout information yet
 */

function calculatePayouts($player_count, $entry_fee) {
    // Remove $ sign if present and convert to float
    if (is_string($entry_fee)) {
        $entry_fee = (float) str_replace('$', '', $entry_fee);
    }
    
    $total_pot = $player_count * $entry_fee;
    $payouts = [];
    
    if ($player_count < 4) {
        return $payouts; // Not enough players
    }
    
    // Payout percentages based on player count
    if ($player_count >= 4 && $player_count <= 7) {
        // Small tournament: 1st, 2nd
        $payouts[] = ['place' => '1st', 'amount' => '$' . number_format($total_pot * 0.60, 2)];
        $payouts[] = ['place' => '2nd', 'amount' => '$' . number_format($total_pot * 0.40, 2)];
    } 
    elseif ($player_count >= 8 && $player_count <= 15) {
        // Medium tournament: 1st, 2nd, 3rd
        $payouts[] = ['place' => '1st', 'amount' => '$' . number_format($total_pot * 0.50, 2)];
        $payouts[] = ['place' => '2nd', 'amount' => '$' . number_format($total_pot * 0.30, 2)];
        $payouts[] = ['place' => '3rd', 'amount' => '$' . number_format($total_pot * 0.20, 2)];
    } 
    elseif ($player_count >= 16 && $player_count <= 24) {
        // Large tournament: 1st through 4th
        $payouts[] = ['place' => '1st', 'amount' => '$' . number_format($total_pot * 0.40, 2)];
        $payouts[] = ['place' => '2nd', 'amount' => '$' . number_format($total_pot * 0.30, 2)];
        $payouts[] = ['place' => '3rd', 'amount' => '$' . number_format($total_pot * 0.20, 2)];
        $payouts[] = ['place' => '4th', 'amount' => '$' . number_format($total_pot * 0.10, 2)];
    } 
    else { // 25+ players
        // Very large tournament: 1st through 6th
        $payouts[] = ['place' => '1st', 'amount' => '$' . number_format($total_pot * 0.35, 2)];
        $payouts[] = ['place' => '2nd', 'amount' => '$' . number_format($total_pot * 0.25, 2)];
        $payouts[] = ['place' => '3rd', 'amount' => '$' . number_format($total_pot * 0.15, 2)];
        $payouts[] = ['place' => '4th', 'amount' => '$' . number_format($total_pot * 0.10, 2)];
        $payouts[] = ['place' => '5th', 'amount' => '$' . number_format($total_pot * 0.075, 2)];
        $payouts[] = ['place' => '6th', 'amount' => '$' . number_format($total_pot * 0.075, 2)];
    }
    
    return $payouts;
}

function formatPayoutsHTML($payouts) {
    if (empty($payouts)) {
        return '<div>Calculating...</div>';
    }
    
    $html = '';
    foreach ($payouts as $payout) {
        $place = $payout['place'] ?? '';
        $amount = $payout['amount'] ?? '';
        $class = ($place === '1st') ? 'first-place' : '';
        $html .= "<div class='$class'>$place: $amount</div>\n";
    }
    
    return $html;
}
?>
