<?php
/**
 * Download Album API - ZIP all vehicle images
 * Supports both logged-in users (by ID) and public share (by token)
 */
require_once __DIR__ . '/../config.php';

$vehicleId = intval($_GET['id'] ?? 0);
$token = $_GET['token'] ?? '';

$db = getDB();

if ($token) {
    // Public access via share token
    $stmt = $db->prepare("SELECT id, brand, model FROM vehicles WHERE id = ? AND share_token = ?");
    $stmt->execute([$vehicleId, $token]);
    $vehicle = $stmt->fetch();
} else {
    // Authenticated access
    session_start();
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        echo 'Unauthorized';
        exit;
    }
    $stmt = $db->prepare("SELECT id, brand, model FROM vehicles WHERE id = ?");
    $stmt->execute([$vehicleId]);
    $vehicle = $stmt->fetch();
}

if (!$vehicle) {
    http_response_code(404);
    echo 'Vehicle not found';
    exit;
}

// Get images
$stmt = $db->prepare("SELECT filename FROM vehicle_images WHERE vehicle_id = ? ORDER BY sort_order");
$stmt->execute([$vehicle['id']]);
$images = $stmt->fetchAll();

if (empty($images)) {
    http_response_code(404);
    echo 'No images found';
    exit;
}

// Create ZIP
$zipName = $vehicle['brand'] . '_' . $vehicle['model'] . '_album.zip';
$tmpFile = tempnam(sys_get_temp_dir(), 'album_');

$zip = new ZipArchive();
if ($zip->open($tmpFile, ZipArchive::OVERWRITE) !== true) {
    http_response_code(500);
    echo 'Cannot create ZIP';
    exit;
}

$uploadDir = __DIR__ . '/../uploads/';
foreach ($images as $img) {
    $filePath = $uploadDir . $img['filename'];
    if (file_exists($filePath)) {
        $zip->addFile($filePath, $img['filename']);
    }
}
$zip->close();

// Stream download
header('Content-Type: application/zip');
header('Content-Disposition: attachment; filename="' . $zipName . '"');
header('Content-Length: ' . filesize($tmpFile));
readfile($tmpFile);
unlink($tmpFile);
exit;
