<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../Includes/csrf.php';
require_once __DIR__ . '/../db_connection.php';

try {
    $report = null;
    $statusHistory = [];

    if (isset($_GET['tracking_token']) && !empty($_GET['tracking_token'])) {
        $token = trim($_GET['tracking_token']);
        $stmt = $pdo->prepare('SELECT id, tracking_token, category, severity, barangay, address, description, status, photo_path, created_at, updated_at 
                               FROM environmental_reports 
                               WHERE tracking_token = :token 
                               LIMIT 1');
        $stmt->execute([':token' => $token]);
        $report = $stmt->fetch(PDO::FETCH_ASSOC);
    } elseif (isset($_GET['id']) && !empty($_GET['id'])) {
        $reportId = (int)$_GET['id'];

        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit;
        }

        $stmt = $pdo->prepare('SELECT id, user_id, tracking_token, category, severity, barangay, address, description, status, photo_path, created_at, updated_at 
                               FROM environmental_reports 
                               WHERE id = :id 
                               LIMIT 1');
        $stmt->execute([':id' => $reportId]);
        $report = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($report && ($_SESSION['role'] ?? '') !== 'admin') {
            if ((int)$report['user_id'] !== (int)$_SESSION['user_id']) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Forbidden']);
                exit;
            }
        }

        if ($report) {
            $userStmt = $pdo->prepare('SELECT name FROM users WHERE id = :id LIMIT 1');
            $userStmt->execute([':id' => $report['user_id']]);
            $user = $userStmt->fetch(PDO::FETCH_ASSOC);
            $report['citizen_name'] = $user ? $user['name'] : 'Unknown';
        }
    }

    if (!$report) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Report not found.']);
        exit;
    }

    $historyStmt = $pdo->prepare('SELECT old_status, new_status, changed_by, notes, created_at 
                                  FROM report_status_history 
                                  WHERE report_id = :report_id 
                                  ORDER BY created_at ASC');
    $historyStmt->execute([':report_id' => $report['id']]);
    $statusHistory = $historyStmt->fetchAll(PDO::FETCH_ASSOC);

    // Normalize photo_path to an absolute, document-root-relative URL so that
    // uploaded report photos render correctly from any page depth (e.g. when
    // viewed from the admin panel at /admin/). Stored values are relative
    // ("uploads/<file>"); absolute (http*) or root-absolute (/...) values
    // are left untouched.
    if (!empty($report['photo_path'])) {
        $photoValue = $report['photo_path'];
        if (strpos($photoValue, 'http') !== 0 && strpos($photoValue, '/') !== 0) {
            $photoValue = BASE_URL . '/' . ltrim($photoValue, '/');
        }
        $report['photo_path'] = $photoValue;
    }

    echo json_encode([
        'success' => true,
        'report' => $report,
        'status_history' => $statusHistory
    ]);

} catch (Exception $e) {
    error_log('Get report detail error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'A system error occurred. Please try again later.']);
}