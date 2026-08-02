<?php
// api/track_report.php
header('Content-Type: application/json');
require_once __DIR__ . '/../Includes/csrf.php';
require_once __DIR__ . '/../db_connection.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

$token = isset($_GET['token']) ? trim($_GET['token']) : '';

if (empty($token)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing tracking token reference.']);
    exit;
}

try {
    $sql = "SELECT id, tracking_token, category, severity, barangay, address, description, status, created_at 
            FROM environmental_reports 
            WHERE tracking_token = :token 
            LIMIT 1";
            
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['token' => $token]);
    $report = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$report) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'Token identifier not found inside our active system directory.'
        ]);
        exit;
    }

    $historySql = "SELECT old_status, new_status, created_at 
                   FROM report_status_history 
                   WHERE report_id = :report_id 
                   ORDER BY created_at ASC";
    $historyStmt = $pdo->prepare($historySql);
    $historyStmt->execute([':report_id' => $report['id']]);
    $statusHistory = $historyStmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'report' => $report,
        'status_history' => $statusHistory
    ]);

} catch (Exception $e) {
    error_log('Track report error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'A system error occurred. Please try again later.'
    ]);
}