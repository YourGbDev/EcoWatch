<?php
// api/admin_map.php
// Returns all reports with location data for the interactive map.
header('Content-Type: application/json');

require_once __DIR__ . '/../Includes/csrf.php';
require_once __DIR__ . '/../db_connection.php';

// Admin gate - same pattern as other admin APIs.
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

try {
    $sql = "SELECT id, tracking_token, category, severity, barangay, address, status, created_at, latitude, longitude
            FROM environmental_reports
            WHERE latitude IS NOT NULL AND longitude IS NOT NULL
            ORDER BY created_at DESC";

    $stmt = $pdo->query($sql);
    $reports = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Ensure numeric types for lat/lng
    foreach ($reports as &$report) {
        $report['latitude'] = $report['latitude'] !== null ? (float)$report['latitude'] : null;
        $report['longitude'] = $report['longitude'] !== null ? (float)$report['longitude'] : null;
    }

    echo json_encode([
        'success' => true,
        'reports' => $reports
    ]);

} catch (Exception $e) {
    error_log('Admin map error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'A system error occurred. Please try again later.']);
}