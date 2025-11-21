<?php
header('Content-Type: application/json');

$upload_dir = $_SERVER['DOCUMENT_ROOT'] . '/media/';

if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

if (!isset($_FILES['file'])) {
    echo json_encode(['success' => false, 'message' => 'No file uploaded']);
    exit;
}

$file = $_FILES['file'];

if ($file['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => 'Upload error: ' . $file['error']]);
    exit;
}

$file_name = basename($file['name']);
$file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

$allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'mp4', 'webm', 'mov', 'avi'];
if (!in_array($file_ext, $allowed_extensions)) {
    echo json_encode(['success' => false, 'message' => 'Invalid file type']);
    exit;
}

$unique_name = time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '', $file_name);
$target_path = $upload_dir . $unique_name;

if (move_uploaded_file($file['tmp_name'], $target_path)) {
    echo json_encode([
        'success' => true,
        'filename' => $unique_name,
        'path' => '/media/' . $unique_name
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to save file']);
}
?>
