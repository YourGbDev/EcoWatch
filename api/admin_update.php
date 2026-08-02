<?php
// api/admin_update.php
header('Content-Type: application/json');
require_once __DIR__ . '/../Includes/csrf.php';
require_once __DIR__ . '/../db_connection.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

$raw_input = file_get_contents('php://input');
$data = json_decode($raw_input, true);

if (!$data || empty($data['id']) || empty($data['status'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing data parameters.']);
    exit;
}

if (!validate_csrf_token($data['csrf_token'] ?? '')) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid CSRF token.']);
    exit;
}

$allowedStatuses = ['submitted', 'verified', 'assigned', 'responding', 'resolved'];
if (!in_array($data['status'], $allowedStatuses, true)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid status value.']);
    exit;
}

try {
    $pdo->beginTransaction();

    $fetchStmt = $pdo->prepare('SELECT status FROM environmental_reports WHERE id = :id FOR UPDATE');
    $fetchStmt->execute([':id' => $data['id']]);
    $currentStatus = $fetchStmt->fetchColumn();

    if ($currentStatus === false) {
        $pdo->rollBack();
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Report not found.']);
        exit;
    }

    $updateSql = "UPDATE environmental_reports SET status = :status, updated_at = CURRENT_TIMESTAMP WHERE id = :id";
    $updateStmt = $pdo->prepare($updateSql);
    $updateStmt->execute([
        'status' => $data['status'],
        'id' => $data['id']
    ]);

    $notes = isset($data['notes']) && is_string($data['notes']) ? trim($data['notes']) : null;

    $historySql = "INSERT INTO report_status_history (report_id, old_status, new_status, changed_by, notes) 
                   VALUES (:report_id, :old_status, :new_status, :changed_by, :notes)";
    $historyStmt = $pdo->prepare($historySql);
    $historyStmt->execute([
        ':report_id'  => $data['id'],
        ':old_status' => $currentStatus,
        ':new_status' => $data['status'],
        ':changed_by' => $_SESSION['user_id'],
        ':notes'      => $notes
    ]);

    $pdo->commit();

    echo json_encode([
        'success' => true, 
        'message' => 'Ticket status updated successfully.',
        'report' => [
            'id' => $data['id'],
            'status' => $data['status']
        ]
    ]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Admin update error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'A system error occurred. Please try again later.']);
}
