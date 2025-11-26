<?php
/**
 * Get Raspberry Pi IP Address
 * 
 * Returns the current local IP address of the Raspberry Pi
 * Used by Google Sheets to auto-discover the API endpoint
 * 
 * Usage: GET /get_ip.php
 * 
 * Returns JSON:
 * {
 *   "success": true,
 *   "ip": "192.168.1.100",
 *   "hostname": "bankshot-display",
 *   "api_url": "http://192.168.1.100/tournament_payout_api.php"
 * }
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

// Get local IP address
function getLocalIP() {
    // Method 1: Use hostname -I command (most reliable on Pi)
    $ip = trim(shell_exec("hostname -I | awk '{print $1}'"));
    
    if (!empty($ip) && filter_var($ip, FILTER_VALIDATE_IP)) {
        return $ip;
    }
    
    // Method 2: Socket connection method
    $sock = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
    socket_connect($sock, "8.8.8.8", 53);
    socket_getsockname($sock, $ip);
    socket_close($sock);
    
    if (!empty($ip) && filter_var($ip, FILTER_VALIDATE_IP)) {
        return $ip;
    }
    
    // Method 3: Read from network interfaces
    $output = shell_exec("ip addr show | grep 'inet ' | grep -v '127.0.0.1' | awk '{print $2}' | cut -d/ -f1 | head -n1");
    $ip = trim($output);
    
    if (!empty($ip) && filter_var($ip, FILTER_VALIDATE_IP)) {
        return $ip;
    }
    
    return null;
}

// Get hostname
function getHostname() {
    return trim(shell_exec("hostname"));
}

try {
    $ip = getLocalIP();
    $hostname = getHostname();
    
    if ($ip) {
        echo json_encode([
            'success' => true,
            'ip' => $ip,
            'hostname' => $hostname,
            'api_url' => "http://{$ip}/tournament_payout_api.php",
            'timestamp' => date('Y-m-d H:i:s')
        ], JSON_PRETTY_PRINT);
    } else {
        echo json_encode([
            'success' => false,
            'error' => 'Could not determine IP address',
            'hostname' => $hostname
        ]);
        http_response_code(500);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Error: ' . $e->getMessage()
    ]);
    http_response_code(500);
}
?>
