<?php
// api/admin_analytics.php
// Aggregates incident analytics from the environmental_reports table for the
// admin analytics dashboard. Returns a single JSON payload consumed by
// admin/analytics.php.
header('Content-Type: application/json');

require_once __DIR__ . '/../Includes/csrf.php';
require_once __DIR__ . '/../db_connection.php';

// Authorization: admin gate, same pattern as api/admin_fetch.php.
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

try {
    // ------------------------------------------------------------------
    // Summary cards
    // ------------------------------------------------------------------
    $totalReports = (int)$pdo->query("SELECT COUNT(*) FROM environmental_reports")->fetchColumn();
    $resolvedCount = (int)$pdo->query("SELECT COUNT(*) FROM environmental_reports WHERE status = 'resolved'")->fetchColumn();
    $resolutionRate = $totalReports > 0 ? round(($resolvedCount / $totalReports) * 100, 1) : 0.0;

    $activeBar = $pdo->query(
        "SELECT barangay, COUNT(*) AS c FROM environmental_reports
         WHERE barangay IS NOT NULL AND barangay != ''
         GROUP BY barangay ORDER BY c DESC, barangay ASC LIMIT 1"
    )->fetch(PDO::FETCH_ASSOC);

    $summary = [
        'total_reports' => $totalReports,
        'resolved'      => $resolvedCount,
        'resolution_rate' => $resolutionRate,
        'most_active_barangay' => $activeBar
            ? ['name' => $activeBar['barangay'], 'count' => (int)$activeBar['c']]
            : ['name' => 'N/A', 'count' => 0],
    ];

    // Reports by category (every category value actually present in the DB —
    // the chart adapts as categories are added).
    $categoryRows = $pdo->query(
        "SELECT category, COUNT(*) AS c FROM environmental_reports
         WHERE category IS NOT NULL AND category != ''
         GROUP BY category ORDER BY c DESC, category ASC"
    )->fetchAll(PDO::FETCH_ASSOC);

    // Reports by status — normalized to the standard incident lifecycle order.
    $STATUS_ORDER = ['submitted', 'verified', 'assigned', 'responding', 'resolved'];
    $statusMap = [];
    foreach ($pdo->query(
        "SELECT status, COUNT(*) AS c FROM environmental_reports
         WHERE status IS NOT NULL GROUP BY status ORDER BY c DESC"
    )->fetchAll(PDO::FETCH_ASSOC) as $r) {
        $statusMap[$r['status']] = (int)$r['c'];
    }
    $reportsByStatus = [];
    foreach ($STATUS_ORDER as $s) {
        $reportsByStatus[] = ['status' => $s, 'count' => $statusMap[$s] ?? 0];
    }
    // Append any statuses outside the known lifecycle (future-proofing).
    foreach ($statusMap as $s => $c) {
        if (!in_array($s, $STATUS_ORDER, true)) {
            $reportsByStatus[] = ['status' => $s, 'count' => $c];
        }
    }

    // Top 10 barangays by report volume.
    $barangayRows = $pdo->query(
        "SELECT barangay, COUNT(*) AS c FROM environmental_reports
         WHERE barangay IS NOT NULL AND barangay != ''
         GROUP BY barangay ORDER BY c DESC, barangay ASC LIMIT 10"
    )->fetchAll(PDO::FETCH_ASSOC);

    // Reports over time — last 30 days, grouped by day, zero-filled so the
    // line chart has a continuous axis.
    $daySeries = [];
    for ($i = 29; $i >= 0; $i--) {
        $d = date('Y-m-d', strtotime("-$i days"));
        $daySeries[$d] = 0;
    }
    $trendRows = $pdo->query(
        "SELECT DATE(created_at) AS day, COUNT(*) AS c FROM environmental_reports
         WHERE created_at >= (CURDATE() - INTERVAL 29 DAY)
         GROUP BY DATE(created_at)"
    )->fetchAll(PDO::FETCH_ASSOC);
    foreach ($trendRows as $r) {
        $day = $r['day'] instanceof DateTimeInterface ? $r['day']->format('Y-m-d') : $r['day'];
        if (array_key_exists($day, $daySeries)) {
            $daySeries[$day] = (int)$r['c'];
        }
    }
    $reportsOverTime = [];
    foreach ($daySeries as $day => $count) {
        $reportsOverTime[] = ['date' => $day, 'count' => $count];
    }

    echo json_encode([
        'success'            => true,
        'summary'            => $summary,
        'reports_by_category' => $categoryRows
            ? array_map(fn($r) => ['category' => $r['category'], 'count' => (int)$r['c']], $categoryRows)
            : [],
        'reports_by_status'  => $reportsByStatus,
        'reports_by_barangay' => $barangayRows
            ? array_map(fn($r) => ['barangay' => $r['barangay'], 'count' => (int)$r['c']], $barangayRows)
            : [],
        'reports_over_time'  => $reportsOverTime,
    ]);

} catch (Exception $e) {
    error_log('Admin analytics error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'A system error occurred. Please try again later.']);
}
