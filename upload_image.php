<?php
/**
 * Image upload handler for TinyMCE
 */

// Include initialization file
require_once __DIR__ . '/includes/init.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header('HTTP/1.1 401 Unauthorized');
    echo json_encode(['error' => 'You must be logged in to upload images.']);
    exit;
}

// Verify CSRF token
if (!Security::verifyCSRFToken($_POST['csrf_token'] ?? '')) {
    header('HTTP/1.1 403 Forbidden');
    echo json_encode(['error' => 'Invalid request.']);
    exit;
}

// Check if file was uploaded
if (empty($_FILES['file']['name'])) {
    header('HTTP/1.1 400 Bad Request');
    echo json_encode(['error' => 'No file uploaded.']);
    exit;
}

// Validate file
$allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
$maxSize = 2 * 1024 * 1024; // 2MB

if (!in_array($_FILES['file']['type'], $allowedTypes)) {
    header('HTTP/1.1 400 Bad Request');
    echo json_encode(['error' => 'Invalid image format. Allowed formats: JPG, PNG, GIF, WEBP.']);
    exit;
}

if ($_FILES['file']['size'] > $maxSize) {
    header('HTTP/1.1 400 Bad Request');
    echo json_encode(['error' => 'Image size exceeds the maximum allowed (2MB).']);
    exit;
}

// Create uploads/editor directory if it doesn't exist
$uploadDir = __DIR__ . '/uploads/editor';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

// Generate unique filename
$filename = uniqid() . '_' . $_FILES['file']['name'];
$uploadPath = $uploadDir . '/' . $filename;

// Upload file
if (move_uploaded_file($_FILES['file']['tmp_name'], $uploadPath)) {
    // Return success response
    echo json_encode([
        'location' => BASE_URL . '/uploads/editor/' . $filename
    ]);
} else {
    header('HTTP/1.1 500 Internal Server Error');
    echo json_encode(['error' => 'Failed to upload image.']);
}