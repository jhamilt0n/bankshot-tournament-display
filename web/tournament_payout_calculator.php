<?php
/**
 * Tournament Payout Calculator - DYNAMIC CUTOFF (FIXED)
 * 
 * Rules enforced:
 * 1. Every payout >= entry fee
 * 2. Strict descending order: each place <= previous place
 * 3. First place is always highest
 * 4. Smart rounding to $5/$10/$20
 * 5. DYNAMIC CUTOFF: Stop when consecutive tie groups pay the same amount
 */

class TournamentPayoutCalculator {
    private $entryFee;
    private $numPlayers;
    private $addedMoney;
    private $totalPrizePool;
    private $baseEntryFee;
    private $multiplier;
    
    public function __construct($entryFee, $numPlayers, $addedMoney = 0) {
        $this->entryFee = $entryFee;
        $this->numPlayers = $numPlayers;
        $this->addedMoney = $addedMoney;
        $this->totalPrizePool = ($entryFee * $numPlayers) + $addedMoney;
        
        list($this->baseEntryFee, $this->multiplier) = $this->determineBaseAndMultiplier($entryFee);
    }
    
    private function determineBaseAndMultiplier($entryFee) {
        if ($entryFee % 10 == 5) {
            return [5, $entryFee / 5];
        }
        if ($entryFee % 10 == 0) {
            $tens = ($entryFee / 10) % 2;
            if ($tens == 1) {
                return [10, $entryFee / 10];
            } else {
                return [20, $entryFee / 20];
            }
        }
        return [$entryFee, 1];
    }
    
    private function roundAmount($amount) {
        $rounded = round($amount / $this->baseEntryFee) * $this->baseEntryFee;
        // Never round below entry fee
        if ($rounded < $this->entryFee) {
            $rounded = $this->entryFee;
        }
        return $rounded;
    }
    
    private function getMaxPlaceToPay() {
        if ($this->numPlayers < 8) return 0;
        if ($this->numPlayers <= 15) return 3;
        if ($this->numPlayers <= 19) return 4;
        if ($this->numPlayers <= 27) return 6;
        if ($this->numPlayers <= 35) return 8;
        if ($this->numPlayers <= 51) return 12;
        if ($this->numPlayers <= 67) return 16;
        if ($this->numPlayers <= 99) return 24;
        if ($this->numPlayers <= 131) return 32;
        if ($this->numPlayers <= 195) return 48;
        if ($this->numPlayers <= 259) return 64;
        if ($this->numPlayers <= 387) return 96;
        if ($this->numPlayers <= 515) return 128;
        return 256;
    }
    
    private function getTieGroups() {
        $maxPlace = $this->getMaxPlaceToPay();
        if ($maxPlace == 0) return [];
        
        $groups = [];
        $groups[] = ['start' => 1, 'end' => 1];
        
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
    
    public function calculatePayouts() {
        $groups = $this->getTieGroups();
        
        if (empty($groups)) {
            return ["error" => "Minimum 8 players required"];
        }
        
        $numGroups = count($groups);
        
        // Step 1: Calculate ideal amounts using exponential decay
        $weights = [];
        $totalWeight = 0;
        
        for ($i = 0; $i < $numGroups; $i++) {
            $decayRate = ($numGroups > 8) ? 0.55 : 0.6;
            $weight = pow($decayRate, $i);
            $weights[$i] = $weight;
            $totalWeight += $weight;
        }
        
        // Step 2: Allocate amounts to each group
        $payouts = [];
        
        foreach ($groups as $i => $group) {
            $percentage = ($weights[$i] / $totalWeight) * 100;
            $amount = ($this->totalPrizePool * $percentage / 100);
            $rounded = $this->roundAmount($amount);
            
            // Assign to all places in group
            for ($place = $group['start']; $place <= $group['end']; $place++) {
                $payouts[$place] = $rounded;
            }
        }
        
        // Step 3: ENFORCE RULES
        
        // Rule 1: Ensure minimum entry fee for ALL places
        foreach ($payouts as $place => $amount) {
            if ($amount < $this->entryFee) {
                $payouts[$place] = $this->entryFee;
            }
        }
        
        // Rule 2: Enforce strict descending order
        for ($i = 1; $i < $numGroups; $i++) {
            $currentGroup = $groups[$i];
            $previousGroup = $groups[$i - 1];
            
            $currentAmount = $payouts[$currentGroup['start']];
            $previousAmount = $payouts[$previousGroup['start']];
            
            // Current group must be less than previous group
            if ($currentAmount >= $previousAmount) {
                $newAmount = $previousAmount - $this->baseEntryFee;
                
                // But don't go below entry fee
                if ($newAmount < $this->entryFee) {
                    $newAmount = $this->entryFee;
                }
                
                // Update all places in current group
                for ($place = $currentGroup['start']; $place <= $currentGroup['end']; $place++) {
                    $payouts[$place] = $newAmount;
                }
            }
        }
        
        // Step 4: DYNAMIC CUTOFF - NOW check AFTER enforcement
        // Build array of what each group pays AFTER enforcement
        $groupPayouts = [];
        foreach ($groups as $i => $group) {
            $groupPayouts[$i] = $payouts[$group['start']];
        }
        
        // Find first group that pays the same as previous group
        $cutoffGroupIndex = count($groups) - 1; // Default: keep all
        
        for ($i = 1; $i < count($groupPayouts); $i++) {
            if ($groupPayouts[$i] >= $groupPayouts[$i - 1]) {
                // This group pays same or more than previous - CUT HERE
                $cutoffGroupIndex = $i - 1;
                break;
            }
        }
        
        // Remove all places beyond the cutoff group
        $cutoffPlace = $groups[$cutoffGroupIndex]['end'];
        $filteredPayouts = [];
        foreach ($payouts as $place => $amount) {
            if ($place <= $cutoffPlace) {
                $filteredPayouts[$place] = $amount;
            }
        }
        $payouts = $filteredPayouts;
        
        // Update groups list to match cutoff
        $groups = array_slice($groups, 0, $cutoffGroupIndex + 1);
        
        // Step 5: Calculate total allocated (after cutoff)
        $allocatedTotal = 0;
        foreach ($payouts as $amount) {
            $allocatedTotal += $amount;
        }
        
        // Step 6: Give remainder to first place
        $remainder = $this->totalPrizePool - $allocatedTotal;
        $payouts[1] += $remainder;
        $payouts[1] = $this->roundAmount($payouts[1]);
        
        // Step 7: Ensure first place is at least 20% higher than second
        if (isset($payouts[2])) {
            $minFirst = $this->roundAmount($payouts[2] * 1.2);
            if ($payouts[1] < $minFirst) {
                $payouts[1] = $minFirst;
            }
        }
        
        // Step 8: Final balance adjustment
        $newTotal = 0;
        foreach ($payouts as $amount) {
            $newTotal += $amount;
        }
        
        // If over budget, take from first place
        if ($newTotal > $this->totalPrizePool) {
            $overage = $newTotal - $this->totalPrizePool;
            $payouts[1] -= $overage;
            $payouts[1] = $this->roundAmount($payouts[1]);
            
            // Ensure first is still highest
            if (isset($payouts[2]) && $payouts[1] <= $payouts[2]) {
                $payouts[1] = $this->roundAmount($payouts[2] * 1.2);
            }
        }
        
        // Step 9: Sort by place number
        ksort($payouts);
        
        return $payouts;
    }
    
    public function getPayoutsArray() {
        return $this->calculatePayouts();
    }
    
    public function getFormattedPayouts() {
        $payouts = $this->calculatePayouts();
        $formatted = [];
        
        if (isset($payouts['error'])) {
            return $payouts;
        }
        
        foreach ($payouts as $place => $amount) {
            $label = $this->getPlaceLabel($place);
            if (!isset($formatted[$label])) {
                $formatted[$label] = $amount;
            }
        }
        
        return $formatted;
    }
    
    private function getPlaceLabel($place) {
        if ($place == 1) return '1st';
        if ($place == 2) return '2nd';
        if ($place == 3) return '3rd';
        if ($place == 4) return '4th';
        if ($place >= 5 && $place <= 6) return '5th-6th';
        if ($place >= 7 && $place <= 8) return '7th-8th';
        if ($place >= 9 && $place <= 12) return '9th-12th';
        if ($place >= 13 && $place <= 16) return '13th-16th';
        if ($place >= 17 && $place <= 24) return '17th-24th';
        if ($place >= 25 && $place <= 32) return '25th-32nd';
        if ($place >= 33 && $place <= 48) return '33rd-48th';
        if ($place >= 49 && $place <= 64) return '49th-64th';
        if ($place >= 65 && $place <= 96) return '65th-96th';
        if ($place >= 97 && $place <= 128) return '97th-128th';
        if ($place >= 129 && $place <= 256) return '129th-256th';
        return $place . 'th';
    }
}

// Test if run directly
if (php_sapi_name() == 'cli' && basename(__FILE__) == basename($_SERVER['PHP_SELF'])) {
    echo "Tournament Payout Calculator - Dynamic Cutoff Test (FIXED)\n";
    echo str_repeat('=', 70) . "\n\n";
    
    $tests = [
        [225, 100, 0],
        [214, 15, 0],
        [20, 20, 0],
        [64, 30, 0],
        [128, 20, 0]
    ];
    
    foreach ($tests as $test) {
        list($players, $fee, $added) = $test;
        
        $calc = new TournamentPayoutCalculator($fee, $players, $added);
        $payouts = $calc->getPayoutsArray();
        
        $total = ($fee * $players) + $added;
        $placesPaid = count($payouts);
        $percentPaid = ($placesPaid / $players) * 100;
        
        echo "$players players @ \$$fee" . ($added ? " + \$$added" : "") . ":\n";
        echo "  Places paid: $placesPaid (" . number_format($percentPaid, 1) . "%)\n";
        
        // Check for duplicate amounts in consecutive groups
        $groupAmounts = [];
        $duplicates = false;
        
        // Map places to groups and check
        $prevAmount = PHP_INT_MAX;
        foreach ($payouts as $place => $amount) {
            if ($amount == $prevAmount && $amount == $fee) {
                $duplicates = true;
                break;
            }
            $prevAmount = $amount;
        }
        
        if ($duplicates) {
            echo "  ❌ STILL HAS FAKE TIES!\n";
        } else {
            echo "  ✅ NO FAKE TIES\n";
        }
        
        echo "\n";
    }
}
?>
