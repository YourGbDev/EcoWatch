<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../Includes/csrf.php';
require_once __DIR__ . '/../db_connection.php';

if (!isset($_SESSION['user_id'])) {
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

if (!$data || empty($data['current_password']) || empty($data['new_password']) || empty($data['confirm_password'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'All password fields are required.']);
    exit;
}

if (!validate_csrf_token($data['csrf_token'] ?? '')) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid CSRF token.']);
    exit;
}

if ($data['new_password'] !== $data['confirm_password']) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'New passwords do not match.']);
    exit;
}

if (strlen($data['new_password']) < 8) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'New password must be at least 8 characters.']);
    exit;
}

try {
    $stmt = $pdo->prepare('SELECT password FROM users WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user || !password_verify($data['current_password'], $user['password'])) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Current password is incorrect.']);
        exit;
    }

    $newHash = password_hash($data['new_password'], PASSWORD_DEFAULT);
    $updateStmt = $pdo->prepare('UPDATE users SET password = :password WHERE id = :id');
    $updateStmt->execute([':password' => $newHash, ':id' => $_SESSION['user_id']]);

    echo json_encode(['success' => true, 'message' => 'Password changed successfully.']);

} catch (Exception $e) {
    error_log('Change password error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to change password.']);
}