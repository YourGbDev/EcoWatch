<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../Includes/csrf.php';
require_once __DIR__ . '/../db_connection.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $csrf_token = $_POST['csrf_token'] ?? '';

    if (!validate_csrf_token($csrf_token)) {
        echo json_encode(['success' => false, 'message' => 'Invalid CSRF token.']);
        exit;
    }

    if (empty($email) || empty($password)) {
        echo json_encode(['success' => false, 'message' => 'Email and password are required.']);
        exit;
    }

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email");
    $stmt->execute(['email' => $email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['user_name'] = $user['name'];

        $redirect = ($user['role'] === 'admin') ? BASE_URL . '/admin/index.php' : BASE_URL . '/dashboard.php';
        echo json_encode(['success' => true, 'message' => 'Login successful.', 'redirect' => $redirect]);
        exit;
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid credentials.']);
        exit;
    }
}
?>