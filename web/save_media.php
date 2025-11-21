<?php
header('Content-Type: application/json');

$media_dir = $_SERVER['DOCUMENT_ROOT'] . '/media/';

if (!file_exists($media_dir)) {
    mkdir($media_dir, 0755, true);
}

$json = file_get_contents('php://input');
$mediaItems = json_decode($json, true);

if ($mediaItems !== null) {
    $config_file = $media_dir . 'media_config.json';
    $result = file_put_contents($config_file, json_encode($mediaItems, JSON_PRETTY_PRINT));
    
    if ($result !== false) {
        $timestamp_file = $media_dir . 'last_update.txt';
        file_put_contents($timestamp_file, time());
        
        echo json_encode([
            'success' => true,
            'message' => 'Media saved successfully',
            'timestamp' => time()
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to write config file']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid data']);
}
?>
