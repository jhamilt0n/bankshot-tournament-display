<?php
/**
 * Tournament Payout Calculator - Based on Digital Pool Players $20 Entry Fee Template
 * 
 * Key Rules:
 * - Entry fees are always divisible by $5 (e.g., $15, $20, $25)
 * - Places 5/6 always tie (same amount)
 * - Places 7/8 always tie (same amount)
 * - Places 9-12 always tie (same amount)
 * - Last place paid >= entry fee (not exactly equal)
 * - Uses percentage-based distribution from $20 template
 * - Supports added money (house adds extra to prize pool)
 */

class TournamentPayoutCalculator {
    
    private $entryFee;
    private $numPlayers;
    private $totalPrizePool;
    private $addedMoney;
    
    public function __construct($entryFee, $numPlayers, $addedMoney = 0) {
        $this->entryFee = $entryFee;
        $this->numPlayers = $numPlayers;
        $this->addedMoney = $addedMoney;
        $this->totalPrizePool = ($entryFee * $numPlayers) + $addedMoney;
    }
    
    /**
     * Determine how many places should be paid based on number of players
     */
    private function getNumPlacesPaid() {
        if ($this->numPlayers < 8) return 0;
        if ($this->numPlayers <= 11) return 2;
        if ($this->numPlayers <= 15) return 3;
        if ($this->numPlayers <= 26) return 4;
        if ($this->numPlayers <= 34) return 6;
        return 8;
    }
    
    /**
     * Get percentage-based payout structure from $20 template
     * These percentages are derived from the Google Sheets template
     */
    private function getPayoutPercentages() {
        $numPlaces = $this->getNumPlacesPaid();
        $players = $this->numPlayers;
        
        // Percentages based on player count ranges (from $20 template)
        switch ($numPlaces) {
            case 2:
                // 8-11 players: ~65% / ~35%
                return [
                    1 => 65.0,
                    2 => 35.0
                ];
                
            case 3:
                // 12-15 players: ~57% / ~31% / ~12%
                return [
                    1 => 57.0,
                    2 => 31.0,
                    3 => 12.0
                ];
                
            case 4:
                // 16-26 players: ~52% / ~28% / ~13% / ~7%
                if ($players <= 20) {
                    return [
                        1 => 52.0,
                        2 => 28.0,
                        3 => 13.0,
                        4 => 7.0
                    ];
                } else {
                    return [
                        1 => 54.0,
                        2 => 27.0,
                        3 => 13.0,
                        4 => 6.0
                    ];
                }
                
            case 6:
                // 27-34 players: ~48% / ~28% / ~11% / ~7% / ~3% / ~3%
                return [
                    1 => 48.0,
                    2 => 28.0,
                    3 => 11.0,
                    4 => 7.0,
                    5 => 3.0,
                    6 => 3.0
                ];
                
            case 8:
                // 35+ players: ~46% / ~20% / ~11% / ~7% / ~5% / ~5% / ~3% / ~3%
                if ($players <= 40) {
                    return [
                        1 => 45.0,
                        2 => 20.0,
                        3 => 11.0,
                        4 => 7.0,
                        5 => 5.0,
                        6 => 5.0,
                        7 => 3.0,
                        8 => 3.0
                    ];
                } else {
                    return [
                        1 => 47.0,
                        2 => 20.0,
                        3 => 11.0,
                        4 => 7.0,
                        5 => 4.5,
                        6 => 4.5,
                        7 => 3.0,
                        8 => 3.0
                    ];
                }
                
            default:
                return [];
        }
    }
    
    /**
     * Calculate payouts using percentage-based distribution
     */
    public function calculatePayouts() {
        $numPlaces = $this->getNumPlacesPaid();
        
        if ($numPlaces == 0) {
            return ["error" => "Minimum 8 players required for payouts"];
        }
        
        $percentages = $this->getPayoutPercentages();
        $payouts = [];
        
        // Calculate raw payouts from percentages
        foreach ($percentages as $place => $percentage) {
            $amount = ($this->totalPrizePool * $percentage / 100);
            $payouts[$place] = $this->roundToNearestFive($amount);
        }
        
        // Ensure last place is AT LEAST the entry fee
        $lastPlace = max(array_keys($payouts));
        if ($payouts[$lastPlace] < $this->entryFee) {
            $payouts[$lastPlace] = $this->roundToNearestFive($this->entryFee);
        }
        
        // Apply tie rules
        $payouts = $this->applyTieRules($payouts);
        
        // Adjust to match total prize pool
        $payouts = $this->adjustToTotal($payouts);
        
        // Sort by place
        ksort($payouts);
        
        return $payouts;
    }
    
    /**
     * Apply tie rules: 5/6 same, 7/8 same, 9-12 same
     */
    private function applyTieRules($payouts) {
        // Handle 5/6 tie
        if (isset($payouts[5]) && isset($payouts[6])) {
            $average = ($payouts[5] + $payouts[6]) / 2;
            $average = $this->roundToNearestFive($average);
            
            // Ensure tied places >= entry fee
            if ($average < $this->entryFee) {
                $average = $this->roundToNearestFive($this->entryFee);
            }
            
            $payouts[5] = $average;
            $payouts[6] = $average;
        }
        
        // Handle 7/8 tie
        if (isset($payouts[7]) && isset($payouts[8])) {
            $average = ($payouts[7] + $payouts[8]) / 2;
            $average = $this->roundToNearestFive($average);
            
            // Ensure tied places >= entry fee
            if ($average < $this->entryFee) {
                $average = $this->roundToNearestFive($this->entryFee);
            }
            
            $payouts[7] = $average;
            $payouts[8] = $average;
        }
        
        // Handle 9-12 tie (if we ever extend)
        if (isset($payouts[9]) && isset($payouts[12])) {
            $average = ($payouts[9] + $payouts[10] + $payouts[11] + $payouts[12]) / 4;
            $average = $this->roundToNearestFive($average);
            
            // Ensure tied places >= entry fee
            if ($average < $this->entryFee) {
                $average = $this->roundToNearestFive($this->entryFee);
            }
            
            $payouts[9] = $average;
            $payouts[10] = $average;
            $payouts[11] = $average;
            $payouts[12] = $average;
        }
        
        return $payouts;
    }
    
    /**
     * Round amount based on entry fee
     * - If entry fee ends in 0, round to nearest 10
     * - If entry fee ends in 5, round to nearest 5
     */
    private function roundToNearestFive($amount) {
        // If entry fee ends in 0 (e.g., $20, $30), round to nearest 10
        if ($this->entryFee % 10 == 0) {
            return round($amount / 10) * 10;
        }
        // Otherwise (entry fee ends in 5, e.g., $15, $25), round to nearest 5
        return round($amount / 5) * 5;
    }
    
    /**
     * Adjust payouts to match total prize pool exactly
     * Adjusts first place to make up any difference
     */
    private function adjustToTotal($payouts) {
        $currentTotal = array_sum($payouts);
        $difference = $this->totalPrizePool - $currentTotal;
        
        if ($difference != 0) {
            // Adjust first place to make up the difference
            $adjustment = $this->roundToNearestFive($difference);
            $payouts[1] += $adjustment;
            
            // If we still have a small difference, add/subtract from first place
            $currentTotal = array_sum($payouts);
            $difference = $this->totalPrizePool - $currentTotal;
            
            if ($difference != 0) {
                $payouts[1] += $difference;
            }
        }
        
        return $payouts;
    }
    
    /**
     * Display payouts in a formatted way
     */
    public function displayPayouts() {
        $payouts = $this->calculatePayouts();
        
        if (isset($payouts['error'])) {
            return $payouts['error'];
        }
        
        $output = "Tournament Payout Structure\n";
        $output .= str_repeat("=", 50) . "\n";
        $output .= "Players: {$this->numPlayers}\n";
        $output .= "Entry Fee: $" . number_format($this->entryFee, 2) . "\n";
        if ($this->addedMoney > 0) {
            $output .= "Added Money: $" . number_format($this->addedMoney, 2) . "\n";
        }
        $output .= "Total Prize Pool: $" . number_format($this->totalPrizePool, 2) . "\n";
        $output .= str_repeat("-", 50) . "\n\n";
        
        $totalPaid = 0;
        foreach ($payouts as $place => $amount) {
            $percentage = ($amount / $this->totalPrizePool) * 100;
            $output .= sprintf("%-15s $%-10s (%5.2f%%)\n", 
                $this->getPlaceLabel($place), 
                number_format($amount, 2), 
                $percentage
            );
            $totalPaid += $amount;
        }
        
        $output .= str_repeat("-", 50) . "\n";
        $output .= sprintf("%-15s $%-10s\n", "TOTAL:", number_format($totalPaid, 2));
        $output .= sprintf("%-15s $%-10s\n", "Expected:", number_format($this->totalPrizePool, 2));
        $output .= sprintf("%-15s $%-10s\n", "Difference:", number_format($totalPaid - $this->totalPrizePool, 2));
        
        return $output;
    }
    
    /**
     * Get place label (handles ties)
     */
    private function getPlaceLabel($place) {
        $labels = [
            1 => "1st Place",
            2 => "2nd Place",
            3 => "3rd Place",
            4 => "4th Place",
            5 => "5th-6th (tie)",
            6 => "5th-6th (tie)",
            7 => "7th-8th (tie)",
            8 => "7th-8th (tie)",
            9 => "9th-12th (tie)",
            10 => "9th-12th (tie)",
            11 => "9th-12th (tie)",
            12 => "9th-12th (tie)"
        ];
        
        return $labels[$place] ?? "{$place}th Place";
    }
    
    /**
     * Get payouts as array
     */
    public function getPayoutsArray() {
        return $this->calculatePayouts();
    }
}
?>
