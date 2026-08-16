<?php
require_once __DIR__ . '/Includes/csrf.php';

if (!isset($_SESSION['user_id'])) { header("Location: " . BASE_URL . "/login.php"); exit(); }

require_once __DIR__ . '/db_connection.php';

$userId = $_SESSION['user_id'];

$stats = [
    'Total'     => ['count' => 0, 'color' => 'bg-indigo-50', 'text' => 'text-gray-900'],
    'Pending'   => ['count' => 0, 'color' => 'bg-amber-50', 'text' => 'text-gray-900'],
    'Responding' => ['count' => 0, 'color' => 'bg-blue-50', 'text' => 'text-gray-900'],
    'Resolved'  => ['count' => 0, 'color' => 'bg-green-50', 'text' => 'text-gray-900']
];

try {
    $totalStmt = $pdo->prepare('SELECT COUNT(*) FROM environmental_reports WHERE user_id = :user_id');
    $totalStmt->execute([':user_id' => $userId]);
    $stats['Total']['count'] = (int)$totalStmt->fetchColumn();

    $pendingStmt = $pdo->prepare("SELECT COUNT(*) FROM environmental_reports WHERE user_id = :user_id AND status = 'submitted'");
    $pendingStmt->execute([':user_id' => $userId]);
    $stats['Pending']['count'] = (int)$pendingStmt->fetchColumn();

    $respondingStmt = $pdo->prepare("SELECT COUNT(*) FROM environmental_reports WHERE user_id = :user_id AND status = 'responding'");
    $respondingStmt->execute([':user_id' => $userId]);
    $stats['Responding']['count'] = (int)$respondingStmt->fetchColumn();

    $resolvedStmt = $pdo->prepare("SELECT COUNT(*) FROM environmental_reports WHERE user_id = :user_id AND status = 'resolved'");
    $resolvedStmt->execute([':user_id' => $userId]);
    $stats['Resolved']['count'] = (int)$resolvedStmt->fetchColumn();

    $reportsStmt = $pdo->prepare('SELECT id, tracking_token, category, status, created_at, photo_path FROM environmental_reports WHERE user_id = :user_id ORDER BY created_at DESC LIMIT 10');
    $reportsStmt->execute([':user_id' => $userId]);
    $reports = $reportsStmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    $stats['Total']['count'] = 0;
    $reports = [];
    error_log('Dashboard query error: ' . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <title>EcoWatch | Dashboard</title>
    <style>
        body { font-family: 'Inter', sans-serif; }
        .nav-link.active { background: rgba(255, 255, 255, 0.15); color: #FFFFFF; }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">
    <!-- Mobile hamburger button (hidden on desktop) -->
    <button id="mobile-menu-btn" class="md:hidden fixed top-4 left-4 z-50 bg-[#3B49DF] text-white p-3 rounded-xl shadow-lg focus:outline-none focus:ring-2 focus:ring-[#3B49DF] transition-all">
        <i data-lucide="menu" class="w-5 h-5"></i>
    </button>
    
    <!-- Top bar for closing mobile menu -->
    <button id="close-mobile-menu" class="hidden md:hidden fixed top-4 right-4 z-50 bg-white text-[#3B49DF] p-3 rounded-xl shadow-lg focus:outline-none focus:ring-2 focus:ring-[#3B49DF] transition-all">
        <i data-lucide="x" class="w-5 h-5"></i>
    </button>

    <!-- Mobile overlay (appears when sidebar is open) -->
    <div id="mobile-menu-overlay" class="hidden md:hidden fixed inset-0 bg-black/50 backdrop-blur-sm z-40" style="display: none;"></div>

    <aside id="sidebar" class="w-64 bg-[#3B49DF] p-8 text-white fixed top-0 left-0 h-screen overflow-y-auto shadow-xl z-50 transform -translate-x-full transition-transform duration-300 ease-in-out md:translate-x-0">
        <div class="text-2xl font-bold mb-10 flex items-center gap-2">EcoWatch</div>
        <nav class="space-y-4">
            <a href="<?php echo BASE_URL; ?>/dashboard.php" class="nav-link active block p-3 rounded-xl transition">Dashboard</a>
            <a href="<?php echo BASE_URL; ?>/submit_report.php" class="nav-link block p-3 hover:bg-white/10 rounded-xl transition">Submit Report</a>
            <a href="<?php echo BASE_URL; ?>/my_reports.php" class="nav-link block p-3 hover:bg-white/10 rounded-xl transition">My Reports</a>
            <a href="<?php echo BASE_URL; ?>/profile.php" class="nav-link block p-3 hover:bg-white/10 rounded-xl transition">Profile Settings</a>
            <form action="<?php echo BASE_URL; ?>/api/logout.php" method="POST" class="mt-auto">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(generate_csrf_token()); ?>">
                <button type="submit" class="nav-link block w-full text-left p-3 rounded-xl transition hover:bg-white/10 text-red-300 hover:text-red-100">Logout</button>
            </form>
        </nav>
    </aside>
    
    <main class="flex-1 ml-4 md:ml-64 p-4 sm:p-8">
        <header class="flex justify-between items-center mb-8 md:items-start">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Welcome back, <?php echo htmlspecialchars($_SESSION['user_name']); ?>!</h1>
                <p class="text-gray-500"><?php echo date("l, F j, Y"); ?></p>
            </div>
            <div class="flex items-center gap-4">
                <div class="w-10 h-10 bg-white rounded-full border shadow-sm flex items-center justify-center">🔔</div>
            </div>
        </header>

        <section class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-8">
            <?php foreach($stats as $label => $info): ?>
                <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm hover:shadow-md transition-all">
                    <h3 class="text-sm text-gray-500 font-medium"><?php echo $label; ?></h3>
                    <p class="text-3xl font-bold mt-2 <?php echo $info['text']; ?>"><?php echo $info['count']; ?></p>
                </div>
            <?php endforeach; ?>
        </section>

        <section class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden mb-8">
            <div class="p-6 border-b border-gray-100 font-bold text-lg">Recent Reports</div>
            <table class="w-full text-left">
                <thead class="bg-gray-50 text-gray-600 text-sm">
                    <tr>
                        <th class="p-4">Report ID</th>
                        <th class="p-4">Category</th>
                        <th class="p-4">Date</th>
                        <th class="p-4">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($reports)): ?>
                        <tr>
                            <td colspan="4" class="p-12 text-center">
                                <div class="flex flex-col items-center">
                                    <svg class="w-12 h-12 text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                    <p class="text-gray-500 text-sm mb-4">No reports submitted yet.</p>
                                    <a href="<?php echo BASE_URL; ?>/submit_report.php" class="bg-[#3B49DF] text-white px-6 py-3 rounded-xl font-semibold hover:bg-[#2D39B5] transition shadow-lg inline-flex items-center space-x-2">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                                        <span>Submit Your First Report</span>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($reports as $report): ?>
                            <tr class="border-b border-gray-50 hover:bg-gray-50 cursor-pointer" onclick="openReportDetail(<?php echo (int)$report['id']; ?>)">
                                <td class="p-4 font-mono text-sm text-gray-700">#<?php echo htmlspecialchars($report['tracking_token']); ?></td>
                                <td class="p-4 text-sm capitalize text-gray-900 font-medium"><?php echo htmlspecialchars(str_replace('_', ' ', $report['category'])); ?></td>
                                <td class="p-4 text-sm text-gray-500"><?php echo date("M j, Y", strtotime($report['created_at'])); ?></td>
                                <td class="p-4">
                                    <?php
                                        $statusLabel = ucwords(str_replace('_', ' ', $report['status']));
                                        $badgeClass = 'bg-gray-100 text-gray-700';
                                        if ($report['status'] === 'submitted') $badgeClass = 'bg-amber-100 text-amber-700';
                                        if ($report['status'] === 'verified') $badgeClass = 'bg-blue-100 text-blue-700';
                                        if ($report['status'] === 'assigned') $badgeClass = 'bg-indigo-100 text-indigo-700';
                                        if ($report['status'] === 'responding') $badgeClass = 'bg-purple-100 text-purple-700';
                                        if ($report['status'] === 'resolved') $badgeClass = 'bg-green-100 text-green-700';
                                    ?>
                                    <span class="px-2.5 py-1 rounded-full text-xs font-semibold <?php echo $badgeClass; ?>"><?php echo $statusLabel; ?></span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </section>

        <div class="bg-[#1e1b4b] rounded-2xl p-8 text-white shadow-xl">
            <h3 class="font-bold text-xl mb-2">📢 Safety Reminder</h3>
            <p class="text-indigo-200">During heavy rains, please stay away from stagnant water areas and monitor our live updates for drainage blockages.</p>
        </div>
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
                const updatedAt = new Date(report.updated_at).toLocaleString('en-US');

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
                    
                    <div class="grid grid-cols-2 gap-4 text-sm">
                        <div><span class="font-semibold text-gray-700">Address:</span> <p class="text-gray-600">${report.address}</p></div>
                        <div><span class="font-semibold text-gray-700">Description:</span> <p class="text-gray-600">${report.description || 'None'}</p></div>
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

        // Mobile menu toggle functions
        function toggleMobileMenu() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('mobile-menu-overlay');
            const mobileBtn = document.getElementById('mobile-menu-btn');
            const closeBtn = document.getElementById('close-mobile-menu');
            
            if (sidebar && sidebar.classList.contains('-translate-x-full')) {
                sidebar.classList.remove('-translate-x-full');
                if (overlay) overlay.classList.remove('hidden');
                if (mobileBtn) mobileBtn.classList.add('hidden');
                if (closeBtn) closeBtn.classList.remove('hidden');
            } else {
                sidebar.classList.add('-translate-x-full');
                if (overlay) overlay.classList.remove('hidden');
                if (mobileBtn) mobileBtn.classList.remove('hidden');
                if (closeBtn) closeBtn.classList.add('hidden');
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            // Mobile menu button
            const mobileBtn = document.getElementById('mobile-menu-btn');
            const overlay = document.getElementById('mobile-menu-overlay');
            const closeBtn = document.getElementById('close-mobile-menu');
            
            if (mobileBtn) mobileBtn.addEventListener('click', toggleMobileMenu);
            if (overlay) overlay.addEventListener('click', toggleMobileMenu);
            if (closeBtn) closeBtn.addEventListener('click', toggleMobileMenu);
        });
    </script>
</body>
</html>