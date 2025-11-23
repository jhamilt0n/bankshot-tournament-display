<?php
header('Content-Type: application/json');

$media_dir = $_SERVER['DOCUMENT_ROOT'] . '/media/';
$config_file = $media_dir . 'media_config.json';

if (file_exists($config_file)) {
    echo file_get_contents($config_file);
} else {
    echo json_encode([]);
}
?>
