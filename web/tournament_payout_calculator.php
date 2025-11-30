<?php
/**
 * Tournament Payout Calculator - GUARANTEED First Place Highest
 * 
 * Key principle: Calculate places 2-N first, then give remainder to first place
 * This ensures first place always gets the highest payout
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
        
        // Determine smart rounding base
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
        return round($amount / $this->baseEntryFee) * $this->baseEntryFee;
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
        
        // NEW APPROACH: Calculate places 2-N FIRST, reserve for first place
        
        // Step 1: Calculate ideal weights for places 2-N only
        $weights = [];
        $totalWeight = 0;
        
        for ($i = 1; $i < $numGroups; $i++) {  // Start at index 1 (skip first place)
            $decayRate = ($numGroups > 8) ? 0.55 : 0.6;
            $weight = pow($decayRate, $i);
            $weights[$i] = $weight;
            $totalWeight += $weight;
        }
        
        // Step 2: Reserve minimum 25% for first place, allocate rest to places 2-N
        $reserveForFirst = $this->totalPrizePool * 0.25;
        $availableForOthers = $this->totalPrizePool - $reserveForFirst;
        
        // Step 3: Allocate to places 2-N
        $payouts = [];
        $allocatedToOthers = 0;
        
        for ($i = 1; $i < $numGroups; $i++) {
            $group = $groups[$i];
            $percentage = ($weights[$i] / $totalWeight) * 100;
            $amount = ($availableForOthers * $percentage / 100);
            $rounded = $this->roundAmount($amount);
            
            // Ensure minimum
            if ($rounded < $this->baseEntryFee) {
                $rounded = $this->baseEntryFee;
            }
            
            // Last group must be at least entry fee
            if ($i == $numGroups - 1 && $rounded < $this->entryFee) {
                $rounded = $this->roundAmount($this->entryFee);
            }
            
            // Assign to all places in group
            for ($place = $group['start']; $place <= $group['end']; $place++) {
                $payouts[$place] = $rounded;
            }
            
            // Track total allocated
            $groupCount = $group['end'] - $group['start'] + 1;
            $allocatedToOthers += ($rounded * $groupCount);
        }
        
        // Step 4: First place gets ALL the remainder
        $firstPlaceAmount = $this->totalPrizePool - $allocatedToOthers;
        $payouts[1] = $this->roundAmount($firstPlaceAmount);
        
        // Step 5: CRITICAL - Ensure first place is at least 20% higher than second
        if (isset($payouts[2])) {
            $minimumFirst = $this->roundAmount($payouts[2] * 1.2);
            
            if ($payouts[1] < $minimumFirst) {
                // Increase first place
                $payouts[1] = $minimumFirst;
                
                // Recalculate - we need to reduce others proportionally
                $newTotal = $payouts[1] + $allocatedToOthers;
                
                if ($newTotal > $this->totalPrizePool) {
                    $excess = $newTotal - $this->totalPrizePool;
                    $reductionFactor = ($allocatedToOthers - $excess) / $allocatedToOthers;
                    
                    // Reduce all other places proportionally
                    for ($i = 1; $i < $numGroups; $i++) {
                        $group = $groups[$i];
                        $oldAmount = $payouts[$group['start']];
                        $newAmount = $this->roundAmount($oldAmount * $reductionFactor);
                        
                        // Don't go below minimums
                        if ($i == $numGroups - 1 && $newAmount < $this->entryFee) {
                            $newAmount = $this->roundAmount($this->entryFee);
                        } else if ($newAmount < $this->baseEntryFee) {
                            $newAmount = $this->baseEntryFee;
                        }
                        
                        // Update all in group
                        for ($place = $group['start']; $place <= $group['end']; $place++) {
                            $payouts[$place] = $newAmount;
                        }
                    }
                    
                    // Recalculate first place with new totals
                    $newAllocatedToOthers = 0;
                    for ($i = 1; $i < $numGroups; $i++) {
                        $group = $groups[$i];
                        $groupCount = $group['end'] - $group['start'] + 1;
                        $newAllocatedToOthers += ($payouts[$group['start']] * $groupCount);
                    }
                    
                    $payouts[1] = $this->roundAmount($this->totalPrizePool - $newAllocatedToOthers);
                }
            }
        }
        
        // Step 6: Final safety check - ensure first place is highest
        $maxOther = 0;
        foreach ($payouts as $place => $amount) {
            if ($place > 1 && $amount > $maxOther) {
                $maxOther = $amount;
            }
        }
        
        if ($payouts[1] <= $maxOther) {
            // Force first place to be 20% higher than max
            $payouts[1] = $this->roundAmount($maxOther * 1.2);
        }
        
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
    echo "Tournament Payout Calculator - Test\n";
    echo str_repeat('=', 70) . "\n\n";
    
    echo "TEST 1: 214 players @ \$15\n";
    echo str_repeat('-', 70) . "\n";
    $calc = new TournamentPayoutCalculator(15, 214);
    $payouts = $calc->getPayoutsArray();
    echo "1st: \$" . number_format($payouts[1], 2) . "\n";
    echo "2nd: \$" . number_format($payouts[2], 2) . "\n";
    echo "3rd: \$" . number_format($payouts[3], 2) . "\n";
    echo "1st > 2nd? " . ($payouts[1] > $payouts[2] ? "YES ✓" : "NO ✗") . "\n\n";
    
    echo "TEST 2: 20 players @ \$20\n";
    echo str_repeat('-', 70) . "\n";
    $calc2 = new TournamentPayoutCalculator(20, 20);
    $payouts2 = $calc2->getPayoutsArray();
    echo "1st: \$" . number_format($payouts2[1], 2) . "\n";
    echo "2nd: \$" . number_format($payouts2[2], 2) . "\n";
    echo "1st > 2nd? " . ($payouts2[1] > $payouts2[2] ? "YES ✓" : "NO ✗") . "\n";
}
?>
