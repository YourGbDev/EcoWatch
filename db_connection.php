<?php
// Database configuration
// SECURITY NOTE: These credentials are for local development only (XAMPP).
// For production deployment, use environment variables or a configuration file
// outside the web root to prevent accidental exposure of database credentials.

$host = 'localhost';
$db   = 'smart_flood_waste';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    // Log technical details server-side; return generic message to client
    error_log('Database connection failed: ' . $e->getMessage());
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}
?>