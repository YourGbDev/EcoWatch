<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../Includes/csrf.php';
echo json_encode(['csrf_token' => generate_csrf_token()]);
