<?php
/**
 * Vehicle API
 * GET    - List/Search vehicles
 * POST   - Create vehicle
 * PUT    - Update vehicle
 * DELETE - Delete vehicle
 */
require_once __DIR__ . '/../config.php';

session_start();
if (!isset($_SESSION['user_id'])) {
    jsonResponse(['error' => 'Unauthorized'], 401);
    exit();
}

header('Content-Type: application/json; charset=utf-8');

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        getVehicles();
        break;
    case 'POST':
        createVehicle();
        break;
    case 'PUT':
        updateVehicle();
        break;
    case 'DELETE':
        deleteVehicle();
        break;
    default:
        jsonResponse(['error' => 'Method not allowed'], 405);
}

function getVehicles() {
    $db = getDB();
    
    $where = [];
    $params = [];
    
    // Search filters
    if (!empty($_GET['search'])) {
        $search = '%' . $_GET['search'] . '%';
        $where[] = "(v.brand LIKE ? OR v.model LIKE ? OR v.vin LIKE ? OR v.license_plate LIKE ?)";
        $params = array_merge($params, [$search, $search, $search, $search]);
    }
    
    if (!empty($_GET['brand'])) {
        $where[] = "v.brand = ?";
        $params[] = $_GET['brand'];
    }
    
    if (!empty($_GET['model'])) {
        $where[] = "v.model = ?";
        $params[] = $_GET['model'];
    }
    
    if (!empty($_GET['year'])) {
        $where[] = "v.year = ?";
        $params[] = (int)$_GET['year'];
    }
    
    if (!empty($_GET['year_min'])) {
        $where[] = "v.year >= ?";
        $params[] = (int)$_GET['year_min'];
    }
    
    if (!empty($_GET['year_max'])) {
        $where[] = "v.year <= ?";
        $params[] = (int)$_GET['year_max'];
    }
    
    if (!empty($_GET['status'])) {
        $where[] = "v.status = ?";
        $params[] = $_GET['status'];
    }
    
    if (!empty($_GET['branch_id'])) {
        $where[] = "v.branch_id = ?";
        $params[] = (int)$_GET['branch_id'];
    }
    
    if (!empty($_GET['price_min'])) {
        $where[] = "v.selling_price >= ?";
        $params[] = (float)$_GET['price_min'];
    }
    
    if (!empty($_GET['price_max'])) {
        $where[] = "v.selling_price <= ?";
        $params[] = (float)$_GET['price_max'];
    }
    
    // Single vehicle by ID
    if (!empty($_GET['id'])) {
        $where[] = "v.id = ?";
        $params[] = (int)$_GET['id'];
    }
    
    $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
    
    $sql = "SELECT v.*, b.name as branch_name, b.color as branch_color
            FROM vehicles v
            LEFT JOIN branches b ON v.branch_id = b.id
            $whereClause
            ORDER BY v.created_at DESC";
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $vehicles = $stmt->fetchAll();
    
    // Attach images to each vehicle
    foreach ($vehicles as &$vehicle) {
        $imgStmt = $db->prepare("SELECT * FROM vehicle_images WHERE vehicle_id = ? ORDER BY sort_order ASC");
        $imgStmt->execute([$vehicle['id']]);
        $vehicle['images'] = $imgStmt->fetchAll();
    }
    
    // Get distinct brands for filter dropdown
    $brandsStmt = $db->query("SELECT DISTINCT brand FROM vehicles ORDER BY brand ASC");
    $brands = $brandsStmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Get distinct years
    $yearsStmt = $db->query("SELECT DISTINCT year FROM vehicles ORDER BY year DESC");
    $years = $yearsStmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Get distinct models
    $modelsStmt = $db->query("SELECT DISTINCT model FROM vehicles ORDER BY model ASC");
    $models = $modelsStmt->fetchAll(PDO::FETCH_COLUMN);
    
    jsonResponse([
        'success' => true,
        'vehicles' => $vehicles,
        'brands' => $brands,
        'years' => $years,
        'models' => $models,
        'total' => count($vehicles)
    ]);
}

function createVehicle() {
    $db = getDB();
    
    $brand = sanitize($_POST['brand'] ?? '');
    $model = sanitize($_POST['model'] ?? '');
    $year = (int)($_POST['year'] ?? date('Y'));
    $color = sanitize($_POST['color'] ?? '');
    $vin = sanitize($_POST['vin'] ?? '');
    $license_plate = sanitize($_POST['license_plate'] ?? '');
    $mileage = (int)($_POST['mileage'] ?? 0);
    $cost_price = (float)($_POST['cost_price'] ?? 0);
    $selling_price = (float)($_POST['selling_price'] ?? 0);
    $branch_id = !empty($_POST['branch_id']) ? (int)$_POST['branch_id'] : null;
    $status = $_POST['status'] ?? 'available';
    $validStatuses = ['available', 'reserved', 'sold', 'maintenance'];
    if (!in_array($status, $validStatuses)) $status = 'available';
    $notes = sanitize($_POST['notes'] ?? '');
    
    if (empty($brand) || empty($model)) {
        jsonResponse(['error' => 'กรุณากรอกยี่ห้อและรุ่น'], 400);
    }
    
    $stmt = $db->prepare("INSERT INTO vehicles (brand, model, year, color, vin, license_plate, mileage, cost_price, selling_price, branch_id, status, notes) 
                          VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$brand, $model, $year, $color, $vin, $license_plate, $mileage, $cost_price, $selling_price, $branch_id, $status, $notes]);
    
    $vehicleId = $db->lastInsertId();
    
    // Handle image uploads
    $uploadedImages = uploadImages($vehicleId);
    
    jsonResponse([
        'success' => true,
        'message' => 'เพิ่มรถเรียบร้อยแล้ว',
        'vehicle_id' => $vehicleId,
        'images_uploaded' => count($uploadedImages)
    ], 201);
}

function updateVehicle() {
    $db = getDB();
    $input = getJsonInput();
    
    $id = (int)($input['id'] ?? 0);
    if ($id <= 0) {
        jsonResponse(['error' => 'Invalid vehicle ID'], 400);
    }
    
    $fields = [];
    $params = [];
    
    $allowedFields = ['brand', 'model', 'year', 'color', 'vin', 'license_plate', 'mileage', 'cost_price', 'selling_price', 'branch_id', 'status', 'notes'];
    $textFields = ['brand', 'model', 'color', 'vin', 'license_plate', 'notes'];
    $validStatuses = ['available', 'reserved', 'sold', 'maintenance'];

    foreach ($allowedFields as $field) {
        if (isset($input[$field])) {
            if ($field === 'branch_id') {
                $fields[] = "$field = ?";
                $params[] = $input[$field] === '' ? null : (int)$input[$field];
            } elseif ($field === 'status') {
                $fields[] = "$field = ?";
                $params[] = in_array($input[$field], $validStatuses) ? $input[$field] : 'available';
            } elseif (in_array($field, $textFields)) {
                $fields[] = "$field = ?";
                $params[] = sanitize($input[$field]);
            } else {
                $fields[] = "$field = ?";
                $params[] = $input[$field];
            }
        }
    }
    
    if (empty($fields)) {
        jsonResponse(['error' => 'No fields to update'], 400);
    }
    
    $params[] = $id;
    $sql = "UPDATE vehicles SET " . implode(', ', $fields) . " WHERE id = ?";
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    
    jsonResponse([
        'success' => true,
        'message' => 'อัพเดทข้อมูลรถเรียบร้อยแล้ว'
    ]);
}

function deleteVehicle() {
    $db = getDB();
    $input = getJsonInput();
    
    $id = (int)($input['id'] ?? $_GET['id'] ?? 0);
    if ($id <= 0) {
        jsonResponse(['error' => 'Invalid vehicle ID'], 400);
    }
    
    // Delete associated images from disk
    $imgStmt = $db->prepare("SELECT filename FROM vehicle_images WHERE vehicle_id = ?");
    $imgStmt->execute([$id]);
    $images = $imgStmt->fetchAll();
    
    foreach ($images as $img) {
        $filePath = UPLOAD_DIR . $img['filename'];
        if (file_exists($filePath)) {
            unlink($filePath);
        }
    }
    
    // Delete vehicle (CASCADE deletes images from DB)
    $stmt = $db->prepare("DELETE FROM vehicles WHERE id = ?");
    $stmt->execute([$id]);
    
    jsonResponse([
        'success' => true,
        'message' => 'ลบรถเรียบร้อยแล้ว'
    ]);
}

function uploadImages($vehicleId) {
    $db = getDB();
    $uploaded = [];
    
    if (!isset($_FILES['images'])) return $uploaded;
    
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
        $filename = 'vehicle_' . $vehicleId . '_' . time() . '_' . $i . '.' . $ext;
        $destination = UPLOAD_DIR . $filename;
        
        if (move_uploaded_file($tmpName, $destination)) {
            $stmt = $db->prepare("INSERT INTO vehicle_images (vehicle_id, filename, sort_order) VALUES (?, ?, ?)");
            $stmt->execute([$vehicleId, $filename, $i]);
            $uploaded[] = $filename;
        }
    }
    
    return $uploaded;
}
