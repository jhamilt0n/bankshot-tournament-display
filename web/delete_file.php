<?php
header('Content-Type: application/json');

$json = file_get_contents('php://input');
$data = json_decode($json, true);

if (!isset($data['path']) || empty($data['path'])) {
    echo json_encode(['success' => false, 'message' => 'No file path provided']);
    exit;
}

$path = $data['path'];
$media_dir = $_SERVER['DOCUMENT_ROOT'] . '/media/';
$full_path = $_SERVER['DOCUMENT_ROOT'] . $path;

$real_media_dir = realpath($media_dir);
$real_file_path = realpath($full_path);

if ($real_file_path === false) {
    echo json_encode(['success' => false, 'message' => 'File not found: ' . $path]);
    exit;
}

if (strpos($real_file_path, $real_media_dir) !== 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid file path - must be in media directory']);
    exit;
}

if (!is_file($real_file_path)) {
    echo json_encode(['success' => false, 'message' => 'Not a valid file']);
    exit;
}

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
