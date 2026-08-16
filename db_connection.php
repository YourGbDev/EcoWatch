<?php
/**
 * Database Connection Configuration
 * 
 * Supports environment variables for production deployments.
 * For local development, falls back to default XAMPP settings.
 * 
 * Environment Variables (optional):
 * - DB_HOST: Database host (default: localhost)
 * - DB_NAME: Database name (default: smart_flood_waste)
 * - DB_USER: Database user (default: root)
 * - DB_PASS: Database password (default: empty string for XAMPP)
 * - DB_CHARSET: Database charset (default: utf8mb4)
 */

// Load from environment or use development defaults
$host = getenv('DB_HOST') ?: 'localhost';
$db   = getenv('DB_NAME') ?: 'smart_flood_waste';
$user = getenv('DB_USER') ?: 'root';
$pass = getenv('DB_PASS') ?: '';
$charset = getenv('DB_CHARSET') ?: 'utf8mb4';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=$charset", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Log technical details server-side; return generic message to client
    error_log('Database connection failed: ' . $e->getMessage());
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}
?>