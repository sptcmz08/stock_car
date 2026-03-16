<?php
/**
 * Vehicle Images API
 * POST   - Upload images for a vehicle
 * DELETE - Delete an image
 */
require_once __DIR__ . '/../config.php';

header('Content-Type: application/json; charset=utf-8');

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'POST':
        uploadVehicleImages();
        break;
    case 'DELETE':
        deleteVehicleImage();
        break;
    default:
        jsonResponse(['error' => 'Method not allowed'], 405);
}

function uploadVehicleImages() {
    $db = getDB();
    
    $vehicleId = (int)($_POST['vehicle_id'] ?? 0);
    if ($vehicleId <= 0) {
        jsonResponse(['error' => 'Invalid vehicle ID'], 400);
    }
    
    // Check vehicle exists
    $check = $db->prepare("SELECT id FROM vehicles WHERE id = ?");
    $check->execute([$vehicleId]);
    if (!$check->fetch()) {
        jsonResponse(['error' => 'Vehicle not found'], 404);
    }
    
    if (!isset($_FILES['images'])) {
        jsonResponse(['error' => 'No images uploaded'], 400);
    }
    
    // Get current max sort_order
    $maxSort = $db->prepare("SELECT COALESCE(MAX(sort_order), -1) FROM vehicle_images WHERE vehicle_id = ?");
    $maxSort->execute([$vehicleId]);
    $sortOrder = (int)$maxSort->fetchColumn() + 1;
    
    $uploaded = [];
    $files = $_FILES['images'];
    $fileCount = is_array($files['name']) ? count($files['name']) : 1;
    
    for ($i = 0; $i < $fileCount; $i++) {
        $name = is_array($files['name']) ? $files['name'][$i] : $files['name'];
        $tmpName = is_array($files['tmp_name']) ? $files['tmp_name'][$i] : $files['tmp_name'];
        $size = is_array($files['size']) ? $files['size'][$i] : $files['size'];
        $type = is_array($files['type']) ? $files['type'][$i] : $files['type'];
        $error = is_array($files['error']) ? $files['error'][$i] : $files['error'];
        
        if ($error !== UPLOAD_ERR_OK) continue;
        if ($size > MAX_FILE_SIZE) continue;
        if (!in_array($type, ALLOWED_TYPES)) continue;
        
        $ext = pathinfo($name, PATHINFO_EXTENSION);
        $filename = 'vehicle_' . $vehicleId . '_' . time() . '_' . ($sortOrder + $i) . '.' . $ext;
        $destination = UPLOAD_DIR . $filename;
        
        if (move_uploaded_file($tmpName, $destination)) {
            $stmt = $db->prepare("INSERT INTO vehicle_images (vehicle_id, filename, sort_order) VALUES (?, ?, ?)");
            $stmt->execute([$vehicleId, $filename, $sortOrder + $i]);
            $uploaded[] = [
                'id' => $db->lastInsertId(),
                'filename' => $filename,
                'url' => BASE_URL . '/uploads/' . $filename
            ];
        }
    }
    
    if (count($uploaded) === 0) {
        jsonResponse(['error' => 'ไม่สามารถอัพโหลดรูปภาพได้ (ไฟล์อาจไม่ใช่รูปภาพ หรือขนาดเกิน 10MB)'], 400);
    }

    jsonResponse([
        'success' => true,
        'message' => 'อัพโหลดรูปภาพเรียบร้อย ' . count($uploaded) . ' รูป',
        'images' => $uploaded
    ]);
}

function deleteVehicleImage() {
    $db = getDB();
    $input = getJsonInput();
    
    $id = (int)($input['id'] ?? $_GET['id'] ?? 0);
    if ($id <= 0) {
        jsonResponse(['error' => 'Invalid image ID'], 400);
    }
    
    // Get image info
    $stmt = $db->prepare("SELECT * FROM vehicle_images WHERE id = ?");
    $stmt->execute([$id]);
    $image = $stmt->fetch();
    
    if (!$image) {
        jsonResponse(['error' => 'Image not found'], 404);
    }
    
    // Delete file from disk
    $filePath = UPLOAD_DIR . $image['filename'];
    if (file_exists($filePath)) {
        unlink($filePath);
    }
    
    // Delete from database
    $deleteStmt = $db->prepare("DELETE FROM vehicle_images WHERE id = ?");
    $deleteStmt->execute([$id]);
    
    jsonResponse([
        'success' => true,
        'message' => 'ลบรูปภาพเรียบร้อยแล้ว'
    ]);
}
