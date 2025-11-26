<?php
/**
 * Tournament Payout Calculator
 * Based on Digital Pool Players payout structure
 * 
 * Key Rules:
 * - Entry fees are always divisible by $5 (e.g., $15, $20, $25)
 * - Places 5/6 always tie (same amount)
 * - Places 7/8 always tie (same amount)
 * - Places 9-12 always tie (same amount)
 * - Last place paid must be >= entry fee
 * - All payouts must be divisible by 5
 */

class TournamentPayoutCalculator {
    
    private $entryFee;
    private $numPlayers;
    private $totalPrizePool;
    
    public function __construct($entryFee, $numPlayers) {
        $this->entryFee = $entryFee;
        $this->numPlayers = $numPlayers;
        $this->totalPrizePool = $entryFee * $numPlayers;
    }
    
    /**
     * Determine how many places should be paid based on number of players
     */
    private function getNumPlacesPaid() {
        if ($this->numPlayers < 8) return 0;
        if ($this->numPlayers <= 9) return 2;
        if ($this->numPlayers <= 11) return 2;
        if ($this->numPlayers <= 15) return 3;
        if ($this->numPlayers <= 23) return 4;
        if ($this->numPlayers <= 26) return 4;
        if ($this->numPlayers <= 31) return 6;
        if ($this->numPlayers <= 34) return 6;
        return 8;
    }
    
    /**
     * Calculate payouts using percentages derived from the spreadsheet
     */
    public function calculatePayouts() {
        $numPlaces = $this->getNumPlacesPaid();
        
        if ($numPlaces == 0) {
            return ["error" => "Minimum 8 players required for payouts"];
        }
        
        $payouts = [];
        
        // Calculate base percentages based on number of places
        switch ($numPlaces) {
            case 2:
                $percentages = $this->calculate2Places();
                break;
            case 3:
                $percentages = $this->calculate3Places();
                break;
            case 4:
                $percentages = $this->calculate4Places();
                break;
            case 6:
                $percentages = $this->calculate6Places();
                break;
            case 8:
                $percentages = $this->calculate8Places();
                break;
            default:
                return ["error" => "Invalid number of places"];
        }
        
        // Convert percentages to dollar amounts
        foreach ($percentages as $place => $percentage) {
            $amount = ($this->totalPrizePool * $percentage / 100);
            $payouts[$place] = $this->roundToNearestFive($amount);
        }
        
        // Adjust to ensure last place >= entry fee
        $lastPlace = max(array_keys($payouts));
        if ($payouts[$lastPlace] < $this->entryFee) {
            $payouts[$lastPlace] = $this->roundToNearestFive($this->entryFee);
        }
        
        // Handle ties for places 5/6, 7/8, and 9-12
        $payouts = $this->applyTieRules($payouts);
        
        // Final adjustment to match total prize pool
        $payouts = $this->adjustToTotal($payouts);
        
        return $payouts;
    }
    
    /**
     * Calculate percentages for 2 places paid
     */
    private function calculate2Places() {
        // Based on pattern: ~65-70% for 1st, remainder for 2nd
        $first = 65;
        $second = 35;
        
        // Adjust based on player count to keep last place >= entry fee
        $secondAmount = ($this->totalPrizePool * $second / 100);
        if ($secondAmount < $this->entryFee) {
            $secondMin = $this->roundToNearestFive($this->entryFee);
            $second = ($secondMin / $this->totalPrizePool) * 100;
            $first = 100 - $second;
        }
        
        return [1 => $first, 2 => $second];
    }
    
    /**
     * Calculate percentages for 3 places paid
     */
    private function calculate3Places() {
        // Based on pattern: ~55-60% for 1st, ~30% for 2nd, ~10-15% for 3rd
        $first = 57;
        $second = 30;
        $third = 13;
        
        // Ensure 3rd place >= entry fee
        $thirdAmount = ($this->totalPrizePool * $third / 100);
        if ($thirdAmount < $this->entryFee) {
            $thirdMin = $this->roundToNearestFive($this->entryFee);
            $third = ($thirdMin / $this->totalPrizePool) * 100;
            $remaining = 100 - $third;
            $first = $remaining * 0.65;
            $second = $remaining * 0.35;
        }
        
        return [1 => $first, 2 => $second, 3 => $third];
    }
    
    /**
     * Calculate percentages for 4 places paid
     */
    private function calculate4Places() {
        // Based on pattern: ~50-55% for 1st, ~27% for 2nd, ~12% for 3rd, ~6% for 4th
        $first = 52;
        $second = 28;
        $third = 13;
        $fourth = 7;
        
        // Ensure 4th place >= entry fee
        $fourthAmount = ($this->totalPrizePool * $fourth / 100);
        if ($fourthAmount < $this->entryFee) {
            $fourthMin = $this->roundToNearestFive($this->entryFee);
            $fourth = ($fourthMin / $this->totalPrizePool) * 100;
            $remaining = 100 - $fourth;
            $first = $remaining * 0.52;
            $second = $remaining * 0.28;
            $third = $remaining * 0.20;
        }
        
        return [1 => $first, 2 => $second, 3 => $third, 4 => $fourth];
    }
    
    /**
     * Calculate percentages for 6 places paid (5th and 6th tie)
     */
    private function calculate6Places() {
        // Based on pattern: ~47% for 1st, ~28% for 2nd, ~11% for 3rd, ~7% for 4th, ~3.5% each for 5/6
        $first = 47;
        $second = 28;
        $third = 11;
        $fourth = 7;
        $fifthSixth = 3.5;
        
        // Ensure 5/6 place >= entry fee
        $fifthSixthAmount = ($this->totalPrizePool * $fifthSixth / 100);
        if ($fifthSixthAmount < $this->entryFee) {
            $fifthSixthMin = $this->roundToNearestFive($this->entryFee);
            $fifthSixth = ($fifthSixthMin / $this->totalPrizePool) * 100;
            $remaining = 100 - ($fifthSixth * 2);
            $first = $remaining * 0.50;
            $second = $remaining * 0.28;
            $third = $remaining * 0.12;
            $fourth = $remaining * 0.10;
        }
        
        return [1 => $first, 2 => $second, 3 => $third, 4 => $fourth, 5 => $fifthSixth, 6 => $fifthSixth];
    }
    
    /**
     * Calculate percentages for 8 places paid (5/6 tie, 7/8 tie)
     */
    private function calculate8Places() {
        // Based on pattern: ~46% for 1st, ~20% for 2nd, ~12% for 3rd, ~7% for 4th, ~5% each for 5/6, ~2.5% each for 7/8
        $first = 46;
        $second = 20;
        $third = 12;
        $fourth = 7;
        $fifthSixth = 5;
        $seventhEighth = 2.5;
        
        // Ensure 7/8 place >= entry fee
        $seventhEighthAmount = ($this->totalPrizePool * $seventhEighth / 100);
        if ($seventhEighthAmount < $this->entryFee) {
            $seventhEighthMin = $this->roundToNearestFive($this->entryFee);
            $seventhEighth = ($seventhEighthMin / $this->totalPrizePool) * 100;
            $remaining = 100 - ($seventhEighth * 2);
            $first = $remaining * 0.48;
            $second = $remaining * 0.21;
            $third = $remaining * 0.13;
            $fourth = $remaining * 0.08;
            $fifthSixth = $remaining * 0.05;
        }
        
        return [
            1 => $first,
            2 => $second,
            3 => $third,
            4 => $fourth,
            5 => $fifthSixth,
            6 => $fifthSixth,
            7 => $seventhEighth,
            8 => $seventhEighth
        ];
    }
    
    /**
     * Apply tie rules: 5/6 same, 7/8 same, 9-12 same
     */
    private function applyTieRules($payouts) {
        // Handle 5/6 tie
        if (isset($payouts[5]) && isset($payouts[6])) {
            $average = ($payouts[5] + $payouts[6]) / 2;
            $average = $this->roundToNearestFive($average);
            $payouts[5] = $average;
            $payouts[6] = $average;
        }
        
        // Handle 7/8 tie
        if (isset($payouts[7]) && isset($payouts[8])) {
            $average = ($payouts[7] + $payouts[8]) / 2;
            $average = $this->roundToNearestFive($average);
            $payouts[7] = $average;
            $payouts[8] = $average;
        }
        
        // Handle 9-12 tie (if we ever extend to 12 places)
        if (isset($payouts[9]) && isset($payouts[12])) {
            $average = ($payouts[9] + $payouts[10] + $payouts[11] + $payouts[12]) / 4;
            $average = $this->roundToNearestFive($average);
            $payouts[9] = $average;
            $payouts[10] = $average;
            $payouts[11] = $average;
            $payouts[12] = $average;
        }
        
        return $payouts;
    }
    
    /**
     * Round amount to nearest 5
     */
    private function roundToNearestFive($amount) {
        return round($amount / 5) * 5;
    }
    
    /**
     * Adjust payouts to match total prize pool exactly
     */
    private function adjustToTotal($payouts) {
        $currentTotal = array_sum($payouts);
        $difference = $this->totalPrizePool - $currentTotal;
        
        if ($difference != 0) {
            // Adjust first place to make up the difference
            // Make sure it stays divisible by 5
            $adjustment = $this->roundToNearestFive($difference);
            $payouts[1] += $adjustment;
            
            // If we still have a small difference, distribute among top places
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

// ============================================================================
// USAGE EXAMPLES
// ============================================================================

?>
