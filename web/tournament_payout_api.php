<?php
/**
 * Tournament Payout Calculator API
 * 
 * Endpoint for calculating tournament payouts
 * Called by Google Apps Script to populate Google Sheets
 * 
 * Usage: GET /tournament_payout_api.php?entry_fee=20&player_count=16
 * 
 * Returns JSON:
 * {
 *   "success": true,
 *   "entry_fee": 20,
 *   "player_count": 16,
 *   "total_pot": 320,
 *   "payouts": {
 *     "1": 210.00,
 *     "2": 110.00,
 *     "3": 50.00,
 *     "4": 30.00
 *   },
 *   "formatted_payouts": {
 *     "1st": "$210.00",
 *     "2nd": "$110.00",
 *     "3rd": "$50.00",
 *     "4th": "$30.00"
 *   }
 * }
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle OPTIONS preflight request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once 'tournament_payout_calculator.php';

// Get parameters from GET or POST
$entry_fee = null;
$player_count = null;

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $entry_fee = isset($_GET['entry_fee']) ? floatval($_GET['entry_fee']) : null;
    $player_count = isset($_GET['player_count']) ? intval($_GET['player_count']) : null;
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $entry_fee = isset($input['entry_fee']) ? floatval($input['entry_fee']) : null;
    $player_count = isset($input['player_count']) ? intval($input['player_count']) : null;
}

// Validate inputs
if ($entry_fee === null || $player_count === null) {
    echo json_encode([
        'success' => false,
        'error' => 'Missing required parameters: entry_fee and player_count'
    ]);
    http_response_code(400);
    exit;
}

if ($entry_fee <= 0) {
    echo json_encode([
        'success' => false,
        'error' => 'Entry fee must be greater than 0'
    ]);
    http_response_code(400);
    exit;
}

if ($player_count < 8) {
    echo json_encode([
        'success' => false,
        'error' => 'Minimum 8 players required for payouts',
        'entry_fee' => $entry_fee,
        'player_count' => $player_count,
        'total_pot' => $entry_fee * $player_count
    ]);
    http_response_code(200); // Still 200 because it's a valid request, just not enough players
    exit;
}

if ($entry_fee % 5 != 0) {
    echo json_encode([
        'success' => false,
        'error' => 'Entry fee must be divisible by $5',
        'entry_fee' => $entry_fee
    ]);
    http_response_code(400);
    exit;
}

try {
    // Create calculator instance
    $calculator = new TournamentPayoutCalculator($entry_fee, $player_count);
    
    // Get payouts as array
    $payouts = $calculator->getPayoutsArray();
    
    // Create formatted payouts with place labels
    $formatted_payouts = [];
    foreach ($payouts as $place => $amount) {
        $place_label = getPlaceLabel($place);
        $formatted_payouts[$place_label] = '$' . number_format($amount, 2);
    }
    
    // Build response
    $response = [
        'success' => true,
        'entry_fee' => $entry_fee,
        'player_count' => $player_count,
        'total_pot' => $entry_fee * $player_count,
        'places_paid' => count(array_unique($payouts)), // Count unique amounts (tied places count as one)
        'payouts' => $payouts,
        'formatted_payouts' => $formatted_payouts,
        'timestamp' => date('Y-m-d H:i:s')
    ];
    
    echo json_encode($response, JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Error calculating payouts: ' . $e->getMessage()
    ]);
    http_response_code(500);
}

/**
 * Get place label (handles ties)
 */
function getPlaceLabel($place) {
    // Individual places
    if ($place == 1) return '1st';
    if ($place == 2) return '2nd';
    if ($place == 3) return '3rd';
    if ($place == 4) return '4th';
    
    // Tie groups
    if ($place == 5 || $place == 6) return '5th-6th';
    if ($place == 7 || $place == 8) return '7th-8th';
    if ($place >= 9 && $place <= 12) return '9th-12th';
    if ($place >= 13 && $place <= 16) return '13th-16th';
    if ($place >= 17 && $place <= 24) return '17th-24th';
    if ($place >= 25 && $place <= 32) return '25th-32nd';
    if ($place >= 33 && $place <= 48) return '33rd-48th';
    if ($place >= 49 && $place <= 64) return '49th-64th';
    if ($place >= 65 && $place <= 96) return '65th-96th';
    if ($place >= 97 && $place <= 128) return '97th-128th';
    if ($place >= 129 && $place <= 256) return '129th-256th';
    
    // Fallback for places beyond 256
    return $place . 'th';
}
?>
