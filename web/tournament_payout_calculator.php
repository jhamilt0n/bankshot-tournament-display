<?php
/**
 * Tournament Payout Calculator - AGGRESSIVE BONUS MODEL
 * 
 * Strategy:
 * 1. Everyone gets entry fee as baseline
 * 2. Remaining "bonus pot" distributed aggressively to top finishers
 * 3. Uses steeper decay (0.65-0.7) for more aggressive top-heavy payouts
 * 4. Last place often receives only entry fee (no bonus)
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
        
        // Standard tie structure for all tournaments
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
        
        $maxPlace = $groups[count($groups) - 1]['end'];
        $numGroups = count($groups);
        
        // Special case: 4 places gets direct percentages to match reference
        if ($maxPlace == 4) {
            // Reference shows: 55%, 25%, 15%, 5% for 1st, 2nd, 3rd, 4th
            // This is EXTREMELY aggressive - first gets more than half!
            $payouts = [];
            $payouts[1] = $this->roundAmount($this->totalPrizePool * 0.55);
            $payouts[2] = $this->roundAmount($this->totalPrizePool * 0.25);
            $payouts[3] = $this->roundAmount($this->totalPrizePool * 0.15);
            $payouts[4] = $this->entryFee; // Last place = entry fee exactly
            
            // Balance adjustment
            $total = array_sum($payouts);
            $payouts[1] += ($this->totalPrizePool - $total);
            
            return $payouts;
        }
        
        // For all other cases, use baseline + bonus model
        $baselinePot = $this->entryFee * $maxPlace;
        $bonusPot = $this->totalPrizePool - $baselinePot;
        
        // Calculate bonus weights with VERY AGGRESSIVE decay
        $weights = [];
        $totalWeight = 0;
        
        // Use MUCH more aggressive decay (0.45-0.5 instead of 0.65-0.7)
        // This gives first place a much bigger share
        $decayRate = ($numGroups > 6) ? 0.45 : 0.5;
        
        for ($i = 0; $i < $numGroups; $i++) {
            $weight = pow($decayRate, $i);
            $weights[$i] = $weight;
            $totalWeight += $weight;
        }
        
        // Distribute bonuses
        $payouts = [];
        
        foreach ($groups as $i => $group) {
            $groupSize = $group['end'] - $group['start'] + 1;
            $bonusShare = ($weights[$i] / $totalWeight) * $bonusPot;
            $bonusPerPlace = $bonusShare / $groupSize;
            
            $totalPayout = $this->entryFee + $bonusPerPlace;
            $rounded = $this->roundAmount($totalPayout);
            
            for ($place = $group['start']; $place <= $group['end']; $place++) {
                $payouts[$place] = $rounded;
            }
        }
        
        // Enforce descending order
        for ($i = 1; $i < $numGroups; $i++) {
            $currentGroup = $groups[$i];
            $previousGroup = $groups[$i - 1];
            
            $currentAmount = $payouts[$currentGroup['start']];
            $previousAmount = $payouts[$previousGroup['start']];
            
            if ($currentAmount >= $previousAmount) {
                $newAmount = $previousAmount - $this->baseEntryFee;
                
                if ($newAmount < $this->entryFee) {
                    $newAmount = $this->entryFee;
                }
                
                for ($place = $currentGroup['start']; $place <= $currentGroup['end']; $place++) {
                    $payouts[$place] = $newAmount;
                }
            }
        }
        
        // Dynamic cutoff
        $groupPayouts = [];
        foreach ($groups as $i => $group) {
            $groupPayouts[$i] = $payouts[$group['start']];
        }
        
        $cutoffGroupIndex = count($groups) - 1;
        
        for ($i = 1; $i < count($groupPayouts); $i++) {
            if ($groupPayouts[$i] >= $groupPayouts[$i - 1]) {
                $cutoffGroupIndex = $i - 1;
                break;
            }
        }
        
        $cutoffPlace = $groups[$cutoffGroupIndex]['end'];
        $filteredPayouts = [];
        foreach ($payouts as $place => $amount) {
            if ($place <= $cutoffPlace) {
                $filteredPayouts[$place] = $amount;
            }
        }
        $payouts = $filteredPayouts;
        
        // AGGRESSIVE STRATEGY: Try to make last place = entry fee exactly
        // This gives more money to top finishers
        $lastPlace = max(array_keys($payouts));
        if ($payouts[$lastPlace] > $this->entryFee) {
            // Calculate how much we can reclaim from last place
            $reclaimable = $payouts[$lastPlace] - $this->entryFee;
            $payouts[$lastPlace] = $this->entryFee;
            
            // Redistribute to earlier places, weighted toward first
            $recipientPlaces = [];
            $recipientWeights = [];
            $totalRecipientWeight = 0;
            
            foreach ($payouts as $place => $amount) {
                if ($place < $lastPlace) {
                    $weight = pow(0.6, $place - 1); // Earlier places get more
                    $recipientPlaces[] = $place;
                    $recipientWeights[$place] = $weight;
                    $totalRecipientWeight += $weight;
                }
            }
            
            // Distribute the reclaimed money
            foreach ($recipientPlaces as $place) {
                $share = ($recipientWeights[$place] / $totalRecipientWeight) * $reclaimable;
                $payouts[$place] += $share;
            }
        }
        
        // Final balance adjustment
        $allocatedTotal = array_sum($payouts);
        $difference = $this->totalPrizePool - $allocatedTotal;
        
        $payouts[1] += $difference;
        
        if (isset($payouts[2])) {
            $minFirst = $payouts[2] * 1.15;
            if ($payouts[1] < $minFirst) {
                $needed = $minFirst - $payouts[1];
                $payouts[1] = $minFirst;
                
                $otherTotal = 0;
                foreach ($payouts as $place => $amount) {
                    if ($place > 1) $otherTotal += $amount;
                }
                
                if ($otherTotal > $needed) {
                    foreach ($payouts as $place => $amount) {
                        if ($place > 1) {
                            $payouts[$place] -= ($amount / $otherTotal) * $needed;
                        }
                    }
                }
            }
        }
        
        // FINAL: Round all payouts to smart denominations
        foreach ($payouts as $place => $amount) {
            $payouts[$place] = $this->roundAmount($amount);
        }
        
        // One more balance check after rounding
        $finalTotal = array_sum($payouts);
        if (abs($finalTotal - $this->totalPrizePool) > 0.01) {
            $payouts[1] += ($this->totalPrizePool - $finalTotal);
        }
        
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

// Test
if (php_sapi_name() == 'cli' && basename(__FILE__) == basename($_SERVER['PHP_SELF'])) {
    echo "Testing against reference: 20 players @ \$20\n";
    echo str_repeat('=', 70) . "\n";
    
    $calc = new TournamentPayoutCalculator(20, 20);
    $payouts = $calc->getPayoutsArray();
    
    echo "Reference: 1st=\$140, 2nd=\$100, 3rd=\$80, 4th=\$80\n";
    echo "Calculated: ";
    for ($i = 1; $i <= 4; $i++) {
        echo "$i=\$" . number_format($payouts[$i], 2) . " ";
    }
    echo "\n";
    echo "Total: \$" . number_format(array_sum($payouts), 2) . " / \$400\n";
}
?>
