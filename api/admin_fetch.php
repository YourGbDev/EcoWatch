<?php
// api/admin_fetch.php
header('Content-Type: application/json');
require_once __DIR__ . '/../Includes/csrf.php';
require_once __DIR__ . '/../db_connection.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$search = trim($_GET['search'] ?? '');
$statusFilter = trim($_GET['status'] ?? '');
$barangayFilter = trim($_GET['barangay'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = max(1, min(50, (int)($_GET['limit'] ?? 10)));
$offset = ($page - 1) * $limit;

try {
    $where = [];
    $params = [];

    if ($search !== '') {
        $where[] = '(tracking_token LIKE :search OR category LIKE :search OR address LIKE :search OR description LIKE :search)';
        $params[':search'] = '%' . $search . '%';
    }

    if ($statusFilter !== '') {
        $where[] = 'status = :status';
        $params[':status'] = $statusFilter;
    }

    if ($barangayFilter !== '') {
        $where[] = 'barangay = :barangay';
        $params[':barangay'] = $barangayFilter;
    }

    $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

    $countSql = "SELECT COUNT(*) FROM environmental_reports $whereSql";
    $countStmt = $pdo->prepare($countSql);
    $countStmt->execute($params);
    $totalRecords = (int)$countStmt->fetchColumn();
    $totalPages = max(1, (int)ceil($totalRecords / $limit));

    $sql = "SELECT id, tracking_token, category, severity, barangay, address, description, status, created_at 
            FROM environmental_reports 
            $whereSql
            ORDER BY case when severity = 'critical' then 1 when severity = 'high' then 2 else 3 end, created_at DESC
            LIMIT :limit OFFSET :offset";

    $stmt = $pdo->prepare($sql);
    foreach ($params as $key => $val) {
        $stmt->bindValue($key, $val);
    }
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $reports = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $barangaySql = "SELECT DISTINCT barangay FROM environmental_reports WHERE barangay IS NOT NULL AND barangay != '' ORDER BY barangay ASC";
    $barangayStmt = $pdo->query($barangaySql);
    $barangays = $barangayStmt->fetchAll(PDO::FETCH_COLUMN);

    echo json_encode([
        'success' => true,
        'reports' => $reports,
        'pagination' => [
            'page' => $page,
            'limit' => $limit,
            'total_records' => $totalRecords,
            'total_pages' => $totalPages
        ],
        'filters' => [
            'barangays' => array_values($barangays)
        ]
    ]);

} catch (Exception $e) {
    error_log('Admin fetch error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'A system error occurred. Please try again later.']);
}
