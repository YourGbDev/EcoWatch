<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../Includes/csrf.php';
require_once __DIR__ . '/../db_connection.php';

if ($_SERVER["REQUEST_METHOD"] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

$email = trim($_POST['email'] ?? '');
$csrf_token = $_POST['csrf_token'] ?? '';

// Validate CSRF token
if (!validate_csrf_token($csrf_token)) {
    echo json_encode(['success' => false, 'message' => 'Invalid security token. Please refresh and try again.']);
    exit;
}

// Validate email is not empty
if (empty($email)) {
    echo json_encode(['success' => false, 'message' => 'Please enter your email address.']);
    exit;
}

// Validate email format
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Please enter a valid email address.']);
    exit;
}

// Check if email exists (but don't reveal this to user for security)
$stmt = $pdo->prepare("SELECT id, name, email FROM users WHERE email = :email LIMIT 1");
$stmt->execute(['email' => $email]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Always return success to prevent email enumeration
// In a real implementation, you would send an email with a reset link
if ($user) {
    // Generate a temporary reset token (in production, send via email)
    $resetToken = bin2hex(random_bytes(16));
    
    // Store the reset token (in production, set expiration)
    $stmt = $pdo->prepare("UPDATE users SET reset_token = :token WHERE email = :email");
    $stmt->execute(['token' => $resetToken, 'email' => $email]);
    
    // In production, send email here
    // For this demo, we'll just return success
}

echo json_encode([
    'success' => true,
    'message' => 'If an account exists with this email, a password reset link has been sent.'
]);
?>