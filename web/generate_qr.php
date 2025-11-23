<?php
require_once __DIR__ . '/vendor/autoload.php';

use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;

$tournament_data_file = '/var/www/html/tournament_data.json';
$qr_output_file = '/var/www/html/qr_code.png';

if (!file_exists($tournament_data_file)) {
    error_log("Tournament data file not found");
    exit(1);
}

$data = json_decode(file_get_contents($tournament_data_file), true);

if (!$data || !isset($data['tournament_url']) || empty($data['tournament_url'])) {
    error_log("No tournament URL found in data");
    exit(1);
}

$tournament_url = rtrim($data['tournament_url'], '/') . '/bracket?navigation=false';

try {
    $options = new QROptions([
        'version'      => 7,
        'outputType'   => QRCode::OUTPUT_IMAGE_PNG,
        'eccLevel'     => QRCode::ECC_L,
        'scale'        => 10,
        'imageBase64'  => false,
        'cachefile'    => null,
    ]);
    
    $qrcode = new QRCode($options);
    file_put_contents($qr_output_file, $qrcode->render($tournament_url));
    
    echo "QR code generated successfully for: $tournament_url\n";
} catch (Exception $e) {
    error_log("Error generating QR code: " . $e->getMessage());
    exit(1);
}
?>
