<?php
require_once __DIR__ . '/../Includes/csrf.php';

$token = $_POST['csrf_token'] ?? $_GET['csrf_token'] ?? '';
if (!validate_csrf_token($token)) {
    echo "Invalid CSRF token.";
    exit;
}

session_destroy();
header("Location: ../login.php");
exit();
?>