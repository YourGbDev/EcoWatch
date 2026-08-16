<?php
require_once __DIR__ . '/Includes/csrf.php';

if (!isset($_SESSION['user_id'])) { header("Location: " . BASE_URL . "/login.php"); exit(); }

require_once __DIR__ . '/db_connection.php';

$userId = $_SESSION['user_id'];

try {
    $reportsStmt = $pdo->prepare('SELECT id, tracking_token, category, severity, status, created_at, photo_path, address, description, barangay FROM environmental_reports WHERE user_id = :user_id ORDER BY created_at DESC');
    $reportsStmt->execute([':user_id' => $userId]);
    $reports = $reportsStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log('My Reports query error: ' . $e->getMessage());
    $reports = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <title>My Reports | EcoWatch</title>
    <style>
        body { font-family: 'Inter', sans-serif; }
        .nav-link.active { background: rgba(255, 255, 255, 0.15); color: #FFFFFF; }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">
    <?php include __DIR__ . '/includes/sidebar.php'; ?>

    <main class="flex-1 ml-4 md:ml-64 p-4 sm:p-8">
        <header class="flex justify-between items-center mb-8">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">My Reports</h1>
                <p class="text-gray-500">Track and manage all your submitted environmental incident reports.</p>
            </div>
            <div class="flex items-center gap-4">
                <div class="w-10 h-10 bg-white rounded-full border shadow-sm flex items-center justify-center">🔔</div>
            </div>
        </header>

        <?php if (empty($reports)): ?>
            <div class="bg-white p-12 rounded-2xl text-center border border-gray-100">
                <p class="text-gray-400 mb-4">You haven't submitted any reports yet.</p>
                <a href="<?php echo BASE_URL; ?>/submit_report.php" class="bg-[#3B49DF] text-white px-6 py-2 rounded-xl font-semibold hover:bg-[#2D39B5] transition shadow-lg">Submit Your First Report</a>
            </div>
        <?php else: ?>
            <div class="space-y-6">
                <?php foreach ($reports as $report): ?>
                    <?php
                        $statusLabel = ucwords(str_replace('_', ' ', $report['status']));
                        $badgeClass = 'bg-gray-100 text-gray-700';
                        if ($report['status'] === 'verified') $badgeClass = 'bg-blue-100 text-blue-700';
                        if ($report['status'] === 'assigned') $badgeClass = 'bg-indigo-100 text-indigo-700';
                        if ($report['status'] === 'responding') $badgeClass = 'bg-purple-100 text-purple-700';
                        if ($report['status'] === 'resolved') $badgeClass = 'bg-green-100 text-green-700';
                    ?>
                    <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm hover:shadow-md transition-all">
                        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                            <div class="flex items-start gap-4">
                                <?php if ($report['photo_path']): ?>
                                    <img src="<?php echo htmlspecialchars($report['photo_path']); ?>" class="w-16 h-16 object-cover rounded-xl border border-gray-200" alt="Report photo">
                                <?php else: ?>
                                    <div class="w-16 h-16 bg-gray-100 rounded-xl flex items-center justify-center text-gray-400">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="3" rx="2" ry="2"/><circle cx="9" cy="9" r="2"/><path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"/></svg>
                                    </div>
                                <?php endif; ?>
                                <div>
                                    <h3 class="font-bold text-gray-900">#<?php echo htmlspecialchars($report['tracking_token']); ?></h3>
                                    <p class="text-sm text-gray-500 capitalize"><?php echo htmlspecialchars(str_replace('_', ' ', $report['category'])); ?> • <?php echo htmlspecialchars($report['severity']); ?></p>
                                    <p class="text-xs text-gray-400 mt-1"><?php echo date("M j, Y g:i A", strtotime($report['created_at'])); ?></p>
                                </div>
                            </div>
                            <div class="flex items-center gap-3">
                                <span class="px-3 py-1 rounded-full text-xs font-semibold <?php echo $badgeClass; ?>"><?php echo $statusLabel; ?></span>
                                <button onclick="openReportDetail(<?php echo (int)$report['id']; ?>)" class="bg-white border border-gray-200 text-gray-700 px-4 py-2 rounded-xl font-semibold hover:bg-gray-50 transition text-sm">View Details</button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>

    <!-- Report Detail Modal -->
    <div id="report-detail-modal" class="fixed inset-0 z-50 flex items-center justify-center invisible transition-all duration-300">
        <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" onclick="closeDetailModal()"></div>
        <div class="bg-white rounded-2xl w-full max-w-2xl p-6 md:p-8 shadow-2xl relative z-10 mx-4 modal-animate max-h-[90vh] overflow-y-auto">
            <button onclick="closeDetailModal()" class="absolute top-4 right-4 text-slate-400 hover:text-slate-600"><i data-lucide="x" class="w-6 h-6"></i></button>
            <div id="detail-content" class="space-y-4">
                <p class="text-slate-500">Loading report details...</p>
            </div>
        </div>
    </div>

    <script>
        async function openReportDetail(reportId) {
            const modal = document.getElementById('report-detail-modal');
            const content = document.getElementById('detail-content');
            modal.classList.remove('invisible');
            modal.classList.add('modal-active', 'flex');
            document.body.classList.add('overflow-hidden');
            content.innerHTML = '<p class="text-slate-500">Loading report details...</p>';

            try {
                const response = await fetch(`<?php echo BASE_URL; ?>/api/get_report_detail.php?id=${reportId}`);
                const data = await response.json();

                if (!data.success) {
                    content.innerHTML = `<p class="text-red-500">${data.message}</p>`;
                    return;
                }

                const report = data.report;
                const history = data.status_history || [];
                const statusLabel = report.status.replace('_', ' ').toUpperCase();
                const categoryLabel = report.category.replace('_', ' ').toUpperCase();
                const createdAt = new Date(report.created_at).toLocaleString('en-US');

                let photoHtml = '';
                if (report.photo_path && fileExists(report.photo_path)) {
                    photoHtml = `<div class="mt-4"><img src="${report.photo_path}" class="max-h-64 rounded-xl shadow-md" alt="Report photo"></div>`;
                }

                let timelineHtml = history.map((entry, index) => {
                    const date = new Date(entry.created_at).toLocaleString('en-US');
                    const dotColor = entry.new_status === 'resolved' ? 'bg-emerald-500' : 
                                    entry.new_status === 'responding' ? 'bg-purple-500' :
                                    entry.new_status === 'assigned' ? 'bg-indigo-500' :
                                    entry.new_status === 'verified' ? 'bg-blue-500' : 'bg-amber-500';
                    const isLast = index === history.length - 1;
                    const notesHtml = entry.notes ? `<p class="text-xs text-slate-500 mt-0.5">${escapeHtml(entry.notes)}</p>` : '';
                    return `
                        <div class="flex items-start space-x-3">
                            <div class="flex flex-col items-center">
                                <div class="w-3 h-3 rounded-full ${dotColor} ${isLast ? 'ring-4 ring-blue-100' : ''}"></div>
                                ${!isLast ? '<div class="w-0.5 h-6 bg-slate-200 mt-1"></div>' : ''}
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-slate-800">${entry.new_status.replace('_', ' ').toUpperCase()}</p>
                                <p class="text-xs text-slate-500">${date}</p>
                                ${notesHtml}
                            </div>
                        </div>
                    `;
                }).join('');

                content.innerHTML = `
                    <div class="flex justify-between items-start">
                        <div>
                            <h3 class="text-xl font-bold text-gray-900">#${report.tracking_token}</h3>
                            <p class="text-sm text-gray-500">${categoryLabel} • Submitted ${createdAt}</p>
                        </div>
                        <span class="px-3 py-1 rounded-full text-xs font-bold uppercase bg-amber-100 text-amber-700">${statusLabel}</span>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                        <div><span class="font-semibold text-gray-700">Category:</span> <p class="text-gray-600 capitalize">${report.category.replace('_', ' ')}</p></div>
                        <div><span class="font-semibold text-gray-700">Severity:</span> <p class="text-gray-600 capitalize">${report.severity}</p></div>
                        <div class="md:col-span-2"><span class="font-semibold text-gray-700">Address:</span> <p class="text-gray-600">${report.address}</p></div>
                        <div class="md:col-span-2"><span class="font-semibold text-gray-700">Description:</span> <p class="text-gray-600">${report.description || 'None'}</p></div>
                    </div>
                    
                    ${photoHtml}
                    
                    <div class="mt-6">
                        <h4 class="text-sm font-bold text-gray-700 mb-3 uppercase tracking-wider">Status Timeline</h4>
                        <div class="space-y-2">${timelineHtml}</div>
                    </div>
                `;

                lucide.createIcons();
            } catch (error) {
                console.error('Failed to load report detail:', error);
                content.innerHTML = '<p class="text-red-500">Failed to load report details.</p>';
            }
        }

        function closeDetailModal() {
            const modal = document.getElementById('report-detail-modal');
            modal.classList.remove('modal-active');
            setTimeout(() => {
                modal.classList.add('invisible');
                document.body.classList.remove('overflow-hidden');
            }, 150);
        }

        function fileExists(path) {
            return path && path.length > 0;
        }

        // Mobile menu is handled by sidebar.php include - initialize icons here
        document.addEventListener('DOMContentLoaded', function() {
            lucide.createIcons();
        });
    </script>
</body>
</html>