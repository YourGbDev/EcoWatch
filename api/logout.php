<?php
require_once __DIR__ . '/../Includes/csrf.php';

// Allow GET requests for logout (CSRF exempt - just destroys session)
// For POST requests, validate CSRF token as an extra security measure
$token = $_POST['csrf_token'] ?? $_GET['csrf_token'] ?? '';

// Skip CSRF validation for GET requests (link-based logout)
// POST requests should still include valid CSRF token for extra security
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !validate_csrf_token($token)) {
    http_response_code(403);
    echo "Invalid CSRF token.";
    exit;
}

// Destroy session
session_destroy();
header("Location: " . BASE_URL . "/login.php");
exit();
?>