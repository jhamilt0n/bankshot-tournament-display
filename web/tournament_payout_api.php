<?php
/**
 * Tournament Payout Calculator API
 * 
 * Endpoint for calculating tournament payouts
 * Called by Google Apps Script to populate Google Sheets
 * 
 * Usage: GET /tournament_payout_api.php?entry_fee=20&player_count=16
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
    http_response_code(200);
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
    
    // Get payouts as array (with numeric keys)
    $payoutsArray = $calculator->getPayoutsArray();
    
    // Convert to Google Sheets friendly format with named keys
    $payouts = [];
    $formatted_payouts = [];
    
    foreach ($payoutsArray as $place => $amount) {
        $key = getNamedKey($place);
        
        // Only add if we have a named key (skips duplicate tie places)
        if ($key) {
            $payouts[$key] = $amount;
            $formatted_payouts[$key] = '$' . number_format($amount, 2);
        }
    }
    
    // Build response
    $response = [
        'success' => true,
        'entry_fee' => $entry_fee,
        'player_count' => $player_count,
        'total_pot' => $entry_fee * $player_count,
        'places_paid' => count($payouts),
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
 * Convert numeric place to named key for Google Sheets
 * Returns null for duplicate tie places (6, 8, 10-12)
 */
function getNamedKey($place) {
    $keys = [
        1 => 'first',
        2 => 'second',
        3 => 'third',
        4 => 'fourth',
        5 => 'fifth_sixth',    // 5 and 6 tie
        6 => null,              // Skip - duplicate of 5
        7 => 'seventh_eighth',  // 7 and 8 tie
        8 => null,              // Skip - duplicate of 7
        9 => 'ninth_twelfth',   // 9-12 tie
        10 => null,             // Skip - duplicate of 9
        11 => null,             // Skip - duplicate of 9
        12 => null              // Skip - duplicate of 9
    ];
    
    return $keys[$place] ?? null;
}
?>
