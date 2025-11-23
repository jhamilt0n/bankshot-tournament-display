<?php
// QR Code Setup Page - Easy TV Configuration
$hostname = trim(shell_exec('hostname'));
$url = "http://{$hostname}.local/tv.html";
$ip = $_SERVER['SERVER_ADDR'];
if ($ip === '::1' || $ip === '127.0.0.1') {
    $ip = trim(shell_exec("hostname -I | awk '{print $1}'"));
}
$fallback_url = "http://{$ip}/tv.html";

require_once __DIR__ . '/vendor/autoload.php';
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;

$options = new QROptions([
    'version'    => 5,
    'outputType' => QRCode::OUTPUT_MARKUP_SVG,
    'eccLevel'   => QRCode::ECC_L,
    'scale'      => 10,
]);

$qrcode = new QRCode($options);
$qrSvg = $qrcode->render($url);
$qrSvgFallback = $qrcode->render($fallback_url);
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TV Setup - Scan QR Code</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            text-align: center;
            background: linear-gradient(135deg, #1e7e34 0%, #0d4d1f 100%);
            color: white;
            min-height: 100vh;
        }
        .container {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 15px;
            padding: 30px;
            backdrop-filter: blur(10px);
        }
        .qr-box {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
            margin: 30px 0;
        }
        .qr-box svg {
            max-width: 100%;
            height: auto;
        }
        .url {
            background: #333;
            color: #0f0;
            padding: 15px;
            border-radius: 5px;
            font-family: monospace;
            font-size: 18px;
            margin: 20px 0;
            word-break: break-all;
        }
        h1 {
            font-size: 2.5em;
            margin-bottom: 20px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }
        h2 {
            font-size: 1.8em;
            margin-top: 40px;
            margin-bottom: 15px;
        }
        .badge {
            background: #10b981;
            padding: 5px 15px;
            border-radius: 15px;
            font-size: 14px;
            font-weight: bold;
            display: inline-block;
            margin-bottom: 10px;
        }
        @media (max-width: 600px) {
            body { padding: 10px; }
            h1 { font-size: 1.8em; }
            .url { font-size: 14px; }
        }
    </style>
    <meta http-equiv="refresh" content="60">
</head>
<body>
    <div class="container">
        <h1>📺 TV Setup</h1>
        <div class="badge">✅ RECOMMENDED</div>
        <p style="font-size: 1.2em; margin-bottom: 20px;">
            Scan this QR code with your Smart TV's browser:
        </p>
        <div class="qr-box"><?php echo $qrSvg; ?></div>
        <p style="font-size: 1.1em;">Or manually enter:</p>
        <div class="url"><?php echo htmlspecialchars($url); ?></div>
        
        <div style="margin-top: 40px; padding-top: 40px; border-top: 2px solid rgba(255,255,255,0.2);">
            <h2>🔧 Alternative Method</h2>
            <p>If your TV doesn't support <code>.local</code> addresses:</p>
            <div class="qr-box"><?php echo $qrSvgFallback; ?></div>
            <div class="url"><?php echo htmlspecialchars($fallback_url); ?></div>
        </div>
    </div>
</body>
</html>
