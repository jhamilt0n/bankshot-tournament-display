<?php
header('Content-Type: application/json');

// Get the JSON input
$json = file_get_contents('php://input');
$data = json_decode($json, true);

if (!isset($data['path']) || empty($data['path'])) {
    echo json_encode(['success' => false, 'message' => 'No file path provided']);
    exit;
}

$path = $data['path'];

// Security: ensure the path is within the media directory
$media_dir = $_SERVER['DOCUMENT_ROOT'] . '/media/';
$full_path = $_SERVER['DOCUMENT_ROOT'] . $path;

// Normalize paths and check if file is within media directory
$real_media_dir = realpath($media_dir);
$real_file_path = realpath($full_path);

// If file doesn't exist, realpath returns false
if ($real_file_path === false) {
    echo json_encode(['success' => false, 'message' => 'File not found: ' . $path]);
    exit;
}

// Check if file is within the allowed media directory
if (strpos($real_file_path, $real_media_dir) !== 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid file path - must be in media directory']);
    exit;
}

// Check if it's a file (not a directory)
if (!is_file($real_file_path)) {
    echo json_encode(['success' => false, 'message' => 'Not a valid file']);
    exit;
}

// Attempt to delete the file
if (unlink($real_file_path)) {
    echo json_encode([
        'success' => true, 
        'message' => 'File deleted successfully',
        'path' => $path
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to delete file - check permissions']);
}
?>
