<?php
/**
 * Generate QR Code for Tournament - Updated Version
 * Uses api.qrserver.com instead of deprecated Google Charts API
 * Links directly to bracket view without navigation
 */

// Load tournament data
$data_file = __DIR__ . '/tournament_data.json';

if (!file_exists($data_file)) {
    error_log("No tournament data file found");
    exit(1);
}

$tournament_data = json_decode(file_get_contents($data_file), true);

if (!$tournament_data || !isset($tournament_data['tournament_url'])) {
    error_log("No tournament URL in data");
    exit(1);
}

$tournament_url = $tournament_data['tournament_url'];

if (empty($tournament_url)) {
    error_log("Tournament URL is empty");
    exit(1);
}

// Append bracket view with no navigation to the URL
$tournament_url = rtrim($tournament_url, '/') . '/bracket?navigation=false';

// QR code output path
$qr_output = __DIR__ . '/tournament_qr.png';

// Use QR Server API (more reliable than Google Charts)
$qr_api_url = 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=' . urlencode($tournament_url);

// Download QR code
$qr_data = @file_get_contents($qr_api_url);

if ($qr_data === false) {
    // Fallback: Try using curl if file_get_contents fails
    if (function_exists('curl_init')) {
        $ch = curl_init($qr_api_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $qr_data = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($qr_data === false) {
            error_log("Failed to generate QR code using curl: " . $error);
            exit(1);
        }
    } else {
        error_log("Failed to generate QR code and curl is not available");
        exit(1);
    }
}

// Save QR code
$result = file_put_contents($qr_output, $qr_data);

if ($result === false) {
    error_log("Failed to save QR code to: " . $qr_output);
    exit(1);
}

echo "QR code generated successfully: " . $qr_output . "\n";
echo "URL: " . $tournament_url . "\n";
exit(0);
?>
