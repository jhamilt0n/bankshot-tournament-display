<?php
/**
 * Tournament Payout Calculator - EXACT RULES
 * 
 * Player count to places paid:
 * < 4: not enough
 * 4-7: pay 1
 * 8-11: pay 2
 * 12-15: pay 3
 * 16-23: pay 4
 * 24-31: pay 6
 * 32-47: pay 8
 * 48-63: pay 12
 * 64-95: pay 16
 * 96-127: pay 24
 * 128-191: pay 32
 * 192-255: pay 48
 * 256: pay 64
 * 
 * Tie structure: 1-4 individual, 5-6 tie, 7-8 tie, 9-12 tie, 13-16 tie,
 *                17-24 tie, 25-32 tie, 33-48 tie, 49-64 tie
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
        // Entry fee with 5 in ones place: divisible by 5
        if ($entryFee % 10 == 5) {
            return [5, $entryFee / 5];
        }
        // Entry fee with 0 in ones place
        if ($entryFee % 10 == 0) {
            $tens = ($entryFee / 10) % 2;
            // Odd tens digit: divisible by 10
            if ($tens == 1) {
                return [10, $entryFee / 10];
            } else {
                // Even tens digit: divisible by 20
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
        if ($this->numPlayers < 4) return 0;
        if ($this->numPlayers >= 4 && $this->numPlayers < 8) return 1;
        if ($this->numPlayers >= 8 && $this->numPlayers < 12) return 2;
        if ($this->numPlayers >= 12 && $this->numPlayers < 16) return 3;
        if ($this->numPlayers >= 16 && $this->numPlayers < 24) return 4;
        if ($this->numPlayers >= 24 && $this->numPlayers < 32) return 6;
        if ($this->numPlayers >= 32 && $this->numPlayers < 48) return 8;
        if ($this->numPlayers >= 48 && $this->numPlayers < 64) return 12;
        if ($this->numPlayers >= 64 && $this->numPlayers < 96) return 16;
        if ($this->numPlayers >= 96 && $this->numPlayers < 128) return 24;
        if ($this->numPlayers >= 128 && $this->numPlayers < 192) return 32;
        if ($this->numPlayers >= 192 && $this->numPlayers < 256) return 48;
        if ($this->numPlayers == 256) return 64;
        return 0;
    }
    
    private function getTieGroups() {
        $maxPlace = $this->getMaxPlaceToPay();
        if ($maxPlace == 0) return [];
        
        $groups = [];
        
        // Always include places up to maxPlace following tie structure
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
        
        return $groups;
    }
    
    public function calculatePayouts() {
        $groups = $this->getTieGroups();
        
        if (empty($groups)) {
            return ["error" => "Minimum 4 players required"];
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
        
        // Rule 2: Enforce strict descending order within groups
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
        
        // Step 4: DYNAMIC CUTOFF - Check if multiple groups pay the same
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
        
        // Step 8: Final balance - EXACT match (no rounding on final adjustment)
        $finalTotal = 0;
        foreach ($payouts as $amount) {
            $finalTotal += $amount;
        }
        
        $finalDiff = $this->totalPrizePool - $finalTotal;
        
        if (abs($finalDiff) > 0.01) {
            // Adjust first place to match EXACTLY
            $payouts[1] += $finalDiff;
            
            // Ensure first place doesn't drop too low
            if (isset($payouts[2])) {
                $minFirst = $payouts[2] * 1.15;
                if ($payouts[1] < $minFirst) {
                    $needed = $minFirst - $payouts[1];
                    $payouts[1] = $minFirst;
                    
                    // Take from other places proportionally
                    $otherTotal = 0;
                    foreach ($payouts as $place => $amount) {
                        if ($place > 1) $otherTotal += $amount;
                    }
                    
                    if ($otherTotal > $needed) {
                        foreach ($payouts as $place => $amount) {
                            if ($place > 1) {
                                $reduction = ($amount / $otherTotal) * $needed;
                                $payouts[$place] -= $reduction;
                            }
                        }
                    }
                }
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
        return $place . 'th';
    }
}

// Test if run directly
if (php_sapi_name() == 'cli' && basename(__FILE__) == basename($_SERVER['PHP_SELF'])) {
    echo "Tournament Payout Calculator - Exact Rules Test\n";
    echo str_repeat('=', 70) . "\n\n";
    
    $tests = [
        [6, 20, 0],
        [10, 25, 0],
        [14, 30, 0],
        [20, 20, 0],
        [28, 25, 0],
        [40, 30, 0],
        [60, 20, 100],
        [80, 30, 0],
        [120, 25, 0],
        [200, 20, 0],
        [256, 25, 0]
    ];
    
    foreach ($tests as $test) {
        list($players, $fee, $added) = $test;
        
        $calc = new TournamentPayoutCalculator($fee, $players, $added);
        $payouts = $calc->getPayoutsArray();
        
        if (isset($payouts['error'])) {
            echo "$players @ \$$fee: " . $payouts['error'] . "\n";
            continue;
        }
        
        $total = ($fee * $players) + $added;
        $payoutSum = array_sum($payouts);
        
        echo "$players @ \$$fee" . ($added ? " + \$$added" : "") . ":\n";
        echo "  Places paid: " . count($payouts) . "\n";
        echo "  Total: \$" . number_format($payoutSum, 2) . " / \$" . number_format($total, 2);
        
        if (abs($total - $payoutSum) < 0.01) {
            echo " ✅\n";
        } else {
            echo " ❌ (off by \$" . number_format(abs($total - $payoutSum), 2) . ")\n";
        }
        
        echo "\n";
    }
}
?>
