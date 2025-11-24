<?php
/**
 * Load Media Configuration
 * Returns all media items from the configuration file
 */

header('Content-Type: application/json');

// Check multiple possible locations for media_config.json
$possible_locations = [
    __DIR__ . '/media_config.json',           // Root of web directory
    __DIR__ . '/media/media_config.json',     // Inside media folder
];

$config_file = null;
foreach ($possible_locations as $location) {
    if (file_exists($location)) {
        $config_file = $location;
        break;
    }
}

// If no config file exists anywhere, return empty array
if (!$config_file) {
    echo json_encode([]);
    exit;
}

// Read and return the configuration
$config = file_get_contents($config_file);
$media_items = json_decode($config, true);

// Ensure it's an array
if (!is_array($media_items)) {
    echo json_encode([]);
    exit;
}

// Return the media items
echo json_encode($media_items);
?>
