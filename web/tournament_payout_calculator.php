<?php
/**
 * Tournament Payout Calculator
 * 
 * Supports up to 256 players/teams with tournament bracket tie structure
 * Uses smart rounding based on entry fee to avoid making change
 */

class TournamentPayoutCalculator {
    
    private $entryFee;
    private $numPlayers;
    private $totalPrizePool;
    private $addedMoney;
    private $baseEntryFee;
    
    public function __construct($entryFee, $numPlayers, $addedMoney = 0) {
        $this->entryFee = $entryFee;
        $this->numPlayers = $numPlayers;
        $this->addedMoney = $addedMoney;
        $this->totalPrizePool = ($entryFee * $numPlayers) + $addedMoney;
        
        // Determine base rounding amount
        if ($entryFee % 10 == 5) {
            $this->baseEntryFee = 5;
        } elseif ($entryFee % 10 == 0) {
            $tens = ($entryFee / 10) % 2;
            $this->baseEntryFee = ($tens == 1) ? 10 : 20;
        } else {
            $this->baseEntryFee = 5;
        }
    }
    
    private function roundAmount($amount) {
        return round($amount / $this->baseEntryFee) * $this->baseEntryFee;
    }
    
    /**
     * Get tie groups structure
     * Returns array of [start, end, count]
     */
    private function getTieGroups() {
        $groups = [];
        $maxPlace = $this->getMaxPlaceToPay();
        
        if ($maxPlace >= 1) $groups[] = ['start' => 1, 'end' => 1];
        if ($maxPlace >= 2) $groups[] = ['start' => 2, 'end' => 2];
        if ($maxPlace >= 3) $groups[] = ['start' => 3, 'end' => 3];
        if ($maxPlace >= 4) $groups[] = ['start' => 4, 'end' => 4];
        if ($maxPlace >= 6) $groups[] = ['start' => 5, 'end' => 6];
        if ($maxPlace >= 8) $groups[] = ['start' => 7, 'end' => 8];
        if ($maxPlace >= 12) $groups[] = ['start' => 9, 'end' => 12];
        if ($maxPlace >= 16) $groups[] = ['start' => 13, 'end' => 16];
        if ($maxPlace >= 24) $groups[] = ['start' => 17, 'end' => 24];
        if ($maxPlace >= 32) $groups[] = ['start' => 25, 'end' => 32];
        if ($maxPlace >= 48) $groups[] = ['start' => 33, 'end' => 48];
        if ($maxPlace >= 64) $groups[] = ['start' => 49, 'end' => 64];
        if ($maxPlace >= 96) $groups[] = ['start' => 65, 'end' => 96];
        if ($maxPlace >= 128) $groups[] = ['start' => 97, 'end' => 128];
        if ($maxPlace >= 256) $groups[] = ['start' => 129, 'end' => 256];
        
        return $groups;
    }
    
    /**
     * Determine maximum place to pay (approximately 25% of field)
     * Uses bracket tie points: 3, 4, 6, 8, 12, 16, 24, 32, 48, 64, 96, 128, 256
     */
    private function getMaxPlaceToPay() {
        if ($this->numPlayers < 8) return 0;
        
        // Target: Pay ~25% of field using bracket tie points
        // These brackets give clean payouts at tournament tie points
        
        if ($this->numPlayers <= 15) return 3;   // 8-15 players → pay 3 (20-37%)
        if ($this->numPlayers <= 19) return 4;   // 16-19 players → pay 4 (21-25%)
        if ($this->numPlayers <= 27) return 6;   // 20-27 players → pay 6 (22-30%)
        if ($this->numPlayers <= 35) return 8;   // 28-35 players → pay 8 (23-29%)
        if ($this->numPlayers <= 51) return 12;  // 36-51 players → pay 12 (24-33%)
        if ($this->numPlayers <= 67) return 16;  // 52-67 players → pay 16 (24-31%)
        if ($this->numPlayers <= 99) return 24;  // 68-99 players → pay 24 (24-35%)
        if ($this->numPlayers <= 131) return 32; // 100-131 players → pay 32 (24-32%)
        if ($this->numPlayers <= 195) return 48; // 132-195 players → pay 48 (25-36%)
        if ($this->numPlayers <= 259) return 64; // 196-259 players → pay 64 (25-33%)
        
        // For very large fields (rare)
        if ($this->numPlayers <= 387) return 96;   // 260-387 players → pay 96 (25-37%)
        if ($this->numPlayers <= 515) return 128;  // 388-515 players → pay 128 (25-33%)
        return 256;  // 516+ players → pay 256 (pay top bracket)
    }
    
    public function calculatePayouts() {
        $groups = $this->getTieGroups();
        
        if (empty($groups)) {
            return ["error" => "Minimum 8 players required"];
        }
        
        $numGroups = count($groups);
        
        // Calculate ideal percentages using exponential decay
        $weights = [];
        $totalWeight = 0;
        for ($i = 0; $i < $numGroups; $i++) {
            $weight = pow(0.6, $i);
            $weights[$i] = $weight;
            $totalWeight += $weight;
        }
        
        // Convert to amounts, round, and assign to groups
        // But track ACTUAL total that will be paid out
        $payouts = [];
        $runningTotal = 0;
        
        foreach ($groups as $i => $group) {
            $percentage = ($weights[$i] / $totalWeight) * 100;
            $amount = ($this->totalPrizePool * $percentage / 100);
            $rounded = $this->roundAmount($amount);
            
            // Ensure minimum payout
            if ($rounded < $this->baseEntryFee) {
                $rounded = $this->baseEntryFee;
            }
            
            // Last group must be at least entry fee
            if ($i == $numGroups - 1 && $rounded < $this->entryFee) {
                $rounded = $this->roundAmount($this->entryFee);
            }
            
            // Calculate how much this group will cost (amount × number of tied places)
            $groupCount = $group['end'] - $group['start'] + 1;
            $groupCost = $rounded * $groupCount;
            
            // Assign to all places in group
            for ($place = $group['start']; $place <= $group['end']; $place++) {
                $payouts[$place] = $rounded;
            }
            
            $runningTotal += $groupCost;
        }
        
        // Now adjust first place to match total EXACTLY
        // The difference is what we need to add/subtract from first place
        $diff = $this->totalPrizePool - $runningTotal;
        
        if ($diff != 0 && isset($payouts[1])) {
            // Add the ACTUAL difference (not rounded)
            $payouts[1] += $diff;
            
            // Then round the result
            $payouts[1] = $this->roundAmount($payouts[1]);
            
            // Ensure first place is still reasonable
            if ($payouts[1] < $this->entryFee) {
                $payouts[1] = $this->roundAmount($this->entryFee * 2);
            }
        }
        
        return $payouts;
    }
    
    public function getPayoutsArray() {
        return $this->calculatePayouts();
    }
    
    public function displayPayouts() {
        $payouts = $this->calculatePayouts();
        
        if (isset($payouts['error'])) {
            return $payouts['error'];
        }
        
        $groups = $this->getTieGroups();
        
        $output = "Tournament Payout Structure\n";
        $output .= str_repeat("=", 50) . "\n";
        $output .= "Players: {$this->numPlayers}\n";
        $output .= "Entry Fee: $" . number_format($this->entryFee, 2) . "\n";
        
        if ($this->addedMoney > 0) {
            $output .= "Entry Pool: $" . number_format($this->entryFee * $this->numPlayers, 2) . "\n";
            $output .= "Added Money: $" . number_format($this->addedMoney, 2) . "\n";
        }
        
        $output .= "Total Prize Pool: $" . number_format($this->totalPrizePool, 2) . "\n";
        $output .= str_repeat("-", 50) . "\n\n";
        
        $total = 0;
        foreach ($groups as $group) {
            $amount = $payouts[$group['start']];
            $count = $group['end'] - $group['start'] + 1;
            $percentage = ($amount * $count / $this->totalPrizePool) * 100;
            
            if ($group['start'] == $group['end']) {
                $label = $this->formatPlace($group['start']);
            } else {
                $label = $this->formatPlace($group['start']) . "-" . $this->formatPlace($group['end']) . " (tie)";
            }
            
            $output .= sprintf("%-25s \$%-11s (%5.2f%%)\n", 
                $label, 
                number_format($amount, 2), 
                $percentage
            );
            
            $total += $amount * $count;
        }
        
        $output .= str_repeat("-", 50) . "\n";
        $output .= sprintf("%-25s \$%-11s\n", "TOTAL:", number_format($total, 2));
        $output .= sprintf("%-25s \$%-11s\n", "Expected:", number_format($this->totalPrizePool, 2));
        $output .= sprintf("%-25s \$%-11s\n", "Difference:", number_format($this->totalPrizePool - $total, 2));
        
        return $output;
    }
    
    private function formatPlace($place) {
        $suffix = 'th';
        if ($place % 100 < 11 || $place % 100 > 13) {
            switch ($place % 10) {
                case 1: $suffix = 'st'; break;
                case 2: $suffix = 'nd'; break;
                case 3: $suffix = 'rd'; break;
            }
        }
        return $place . $suffix . " Place";
    }
}

// Testing
if (php_sapi_name() === 'cli') {
    echo "Tournament Payout Calculator - Test Examples\n";
    echo str_repeat("=", 70) . "\n\n";
    
    $tests = [
        [20, 12, 0, "12 players @ \$20"],
        [20, 17, 0, "17 players @ \$20 (Friday night scenario)"],
        [25, 32, 100, "32 players @ \$25 + \$100 added"],
        [30, 64, 0, "64 players @ \$30"],
        [20, 128, 0, "128 players @ \$20"],
        [15, 256, 0, "256 players @ \$15"]
    ];
    
    foreach ($tests as $i => $test) {
        list($entry, $players, $added, $desc) = $test;
        echo "EXAMPLE " . ($i + 1) . ": $desc\n";
        echo str_repeat("=", 70) . "\n";
        $calc = new TournamentPayoutCalculator($entry, $players, $added);
        echo $calc->displayPayouts() . "\n\n";
    }
}
?>
