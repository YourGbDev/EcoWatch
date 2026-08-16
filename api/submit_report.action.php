<?php
// Ensure NO HTML or spaces are sent before this point
header('Content-Type: application/json');

require_once '../Includes/csrf.php';
require_once '../db_connection.php';

function generateTrackingToken($pdo) {
    do {
        $token = 'EW-' . strtoupper(bin2hex(random_bytes(3)));
        $stmt = $pdo->prepare('SELECT id FROM environmental_reports WHERE tracking_token = :token LIMIT 1');
        $stmt->execute([':token' => $token]);
        $exists = $stmt->fetch();
    } while ($exists);
    return $token;
}

function handlePhotoUpload() {
    if (!isset($_FILES['photo']) || $_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
        return null;
    }

    $uploadDir = __DIR__ . '/../uploads/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $tmpPath = $_FILES['photo']['tmp_name'];
    $fileSize = $_FILES['photo']['size'];

    if ($fileSize > 5 * 1024 * 1024) {
        return ['error' => 'File size exceeds 5MB limit.'];
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->file($tmpPath);

    $allowedMimes = ['image/jpeg', 'image/png', 'image/webp'];
    if (!in_array($mimeType, $allowedMimes, true)) {
        return ['error' => 'Invalid file type. Only JPEG, PNG, and WebP are allowed.'];
    }

    if ($mimeType === 'image/jpeg') {
        $extension = 'jpg';
    } elseif ($mimeType === 'image/png') {
        $extension = 'png';
    } else {
        $extension = 'webp';
    }

    $filename = 'report_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $extension;
    $destination = $uploadDir . $filename;

    if (!move_uploaded_file($tmpPath, $destination)) {
        return ['error' => 'Failed to upload photo.'];
    }

    return 'uploads/' . $filename;
}

try {
    $data = [];
    $isJson = false;

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';

        if (strpos($contentType, 'application/json') !== false) {
            $jsonInput = file_get_contents('php://input');
            $decoded = json_decode($jsonInput, true);
            if ($decoded !== null && is_array($decoded)) {
                $data = $decoded;
                $isJson = true;
            }
        }

        if (!$isJson) {
            $data = $_POST;
        }
    }

    $required = ['category', 'severity', 'barangay', 'address'];
    $missing = [];
    foreach ($required as $field) {
        if (empty(trim($data[$field] ?? ''))) {
            $missing[] = $field;
        }
    }
    if ($missing) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Missing required fields: ' . implode(', ', $missing)]);
        exit;
    }

    $category   = strtolower(trim($data['category']));
    $severity   = strtolower(trim($data['severity']));
    $barangay    = trim($data['barangay'] ?? '');
    $address    = trim($data['address']);
    $description = isset($data['description']) ? trim($data['description']) : null;
    $latitude   = isset($data['latitude']) && $data['latitude'] !== '' ? (float)$data['latitude'] : null;
    $longitude  = isset($data['longitude']) && $data['longitude'] !== '' ? (float)$data['longitude'] : null;

    $allowedCategories = ['flooding', 'illegal_dumping', 'clogged_drainage', 'uncollected_garbage', 'drug_concern'];
    if (!in_array($category, $allowedCategories, true)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid category selected.']);
        exit;
    }

    $allowedSeverities = ['low', 'high', 'critical'];
    if (!in_array($severity, $allowedSeverities, true)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid severity selected.']);
        exit;
    }

    $photoResult = handlePhotoUpload();
    if (isset($photoResult['error'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => $photoResult['error']]);
        exit;
    }
    $photoPath = $photoResult;

    if (empty($_POST['csrf_token']) && empty($data['csrf_token'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Missing CSRF token.']);
        exit;
    }
    $csrfToken = $_POST['csrf_token'] ?? $data['csrf_token'] ?? '';
    if (!validate_csrf_token($csrfToken)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid CSRF token.']);
        exit;
    }

    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Unauthorized. Please log in.']);
        exit;
    }
    $userId = $_SESSION['user_id'];

    $trackingToken = generateTrackingToken($pdo);

    $pdo->beginTransaction();

    $sql = "INSERT INTO environmental_reports 
            (category, severity, barangay, address, description, status, tracking_token, user_id, photo_path, latitude, longitude) 
            VALUES (:category, :severity, :barangay, :address, :description, 'submitted', :tracking_token, :user_id, :photo_path, :latitude, :longitude)";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':category'       => $category,
        ':severity'       => $severity,
        ':barangay'       => $barangay,
        ':address'        => $address,
        ':description'    => $description,
        ':tracking_token' => $trackingToken,
        ':user_id'        => $userId,
        ':photo_path'     => $photoPath,
        ':latitude'       => $latitude,
        ':longitude'      => $longitude
    ]);

    $reportId = (int)$pdo->lastInsertId();

    try {
        $historySql = "INSERT INTO report_status_history (report_id, old_status, new_status, changed_by) 
                       VALUES (:report_id, NULL, 'submitted', :changed_by)";
        $historyStmt = $pdo->prepare($historySql);
        $historyStmt->execute([
            ':report_id'  => $reportId,
            ':changed_by' => $userId
        ]);
    } catch (Exception $historyError) {
        error_log('Status history insert failed: ' . $historyError->getMessage());
    }

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Report submitted successfully.',
        'tracking_token' => $trackingToken,
        'photo_path' => $photoPath
    ]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Submit report error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to submit report.']);
}