<?php
require_once __DIR__ . '/../Includes/csrf.php';
require_once __DIR__ . '/../db_connection.php';

// Admin gate — mirrors admin/index.php.
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: " . BASE_URL . "/login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EcoWatch - Analytics | Operations Terminal</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: {
                            blue: '#3B49DF',   /* user-facing dark blue accent */
                            dark: '#0F172A',
                            light: '#F8FAFC',
                            green: '#10B981'
                        }
                    }
                }
            }
        }
    </script>
    <style>
        [x-cloak] { display: none !important; }
        canvas { -webkit-tap-highlight-color: transparent; }
    </style>
</head>
<body class="bg-brand-light text-brand-dark antialiased font-sans">

    <header class="bg-white border-b border-slate-200 h-20 flex items-center justify-between px-8 sticky top-0 z-30">
        <div class="flex items-center space-x-3">
            <div class="bg-[#3B49DF] text-white p-2 rounded-xl"><i data-lucide="shield-alert" class="w-5 h-5"></i></div>
            <span class="font-bold text-lg tracking-tight">EcoWatch <span class="text-slate-400 font-normal">| Operations Terminal</span></span>
        </div>
        <div class="flex items-center space-x-4">
            <span class="text-sm font-semibold bg-blue-50 text-[#3B49DF] px-3 py-1.5 rounded-lg">Muncipal Hub #1</span>
            <a href="<?php echo BASE_URL; ?>/api/logout.php" class="text-slate-500 hover:text-slate-700 text-sm font-medium flex items-center space-x-2 px-3 py-1.5 rounded-xl hover:bg-slate-50 transition-colors">
                <i data-lucide="log-out" class="w-4 h-4"></i>
                <span>Logout</span>
            </a>
        </div>
    </header>

    <!-- Admin navigation -->
    <nav class="bg-white border-b border-slate-200 sticky top-20 z-20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex space-x-2">
            <a href="<?php echo BASE_URL; ?>/admin/index.php" class="px-4 py-3 text-sm font-semibold rounded-t-xl border-b-2 border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300 transition">Incident Queue</a>
            <a href="<?php echo BASE_URL; ?>/admin/analytics.php" class="px-4 py-3 text-sm font-semibold rounded-t-xl border-b-2 border-[#3B49DF] text-[#3B49DF] transition">Analytics</a>
            <a href="<?php echo BASE_URL; ?>/admin/map.php" class="px-4 py-3 text-sm font-semibold rounded-t-xl border-b-2 border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300 transition">Map</a>
        </div>
    </nav>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-3xl font-extrabold tracking-tight">Incident Analytics</h1>
                <p class="text-slate-500 text-sm">Volume trends, category breakdown, resolution metrics, and barangay hotspots.</p>
            </div>
            <button onclick="loadAnalytics()" class="bg-white border border-slate-200 text-slate-700 px-4 py-2.5 rounded-xl font-semibold shadow-sm hover:bg-slate-50 inline-flex items-center space-x-2 w-fit">
                <i data-lucide="refresh-cw" class="w-4 h-4"></i> <span>Refresh</span>
            </button>
        </div>

        <!-- Summary cards -->
        <section class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Total Reports</p>
                        <p id="stat-total-reports" class="text-3xl font-bold text-brand-dark mt-1">—</p>
                    </div>
                    <div class="bg-slate-100 text-slate-600 p-2 rounded-xl"><i data-lucide="file-text" class="w-5 h-5"></i></div>
                </div>
            </div>
            <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Resolution Rate</p>
                        <p id="stat-resolution-rate" class="text-3xl font-bold text-emerald-600 mt-1">—</p>
                        <p class="text-sm text-slate-500 mt-1"><span id="stat-resolved">—</span> of <span id="stat-total-denom">—</span> resolved</p>
                    </div>
                    <div class="bg-emerald-100 text-emerald-600 p-2 rounded-xl"><i data-lucide="check-check" class="w-5 h-5"></i></div>
                </div>
            </div>
            <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Most Active Barangay</p>
                        <p id="stat-active-barangay" class="text-2xl font-bold text-[#3B49DF] mt-1">—</p>
                        <p class="text-sm text-slate-500 mt-1"><span id="stat-active-count">—</span> incidents</p>
                    </div>
                    <div class="bg-indigo-100 text-indigo-600 p-2 rounded-xl"><i data-lucide="map-pin" class="w-5 h-5"></i></div>
                </div>
            </div>
        </section>

        <!-- Row 1: two charts -->
        <section class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm">
                <h3 class="text-lg font-semibold text-slate-700 mb-4">Reports by Category</h3>
                <div class="relative h-72">
                    <canvas id="chart-category" class="w-full h-full"></canvas>
                    <div id="empty-category" class="absolute inset-0 flex items-center justify-center text-sm text-slate-400 hidden">No category data available.</div>
                </div>
            </div>
            <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm">
                <h3 class="text-lg font-semibold text-slate-700 mb-4">Reports by Status</h3>
                <div class="relative h-72">
                    <canvas id="chart-status" class="w-full h-full"></canvas>
                    <div id="empty-status" class="absolute inset-0 flex items-center justify-center text-sm text-slate-400 hidden">No status data available.</div>
                </div>
            </div>
        </section>

        <!-- Row 2: two charts -->
        <section class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm">
                <h3 class="text-lg font-semibold text-slate-700 mb-4">Top Barangays</h3>
                <div class="relative h-72">
                    <canvas id="chart-barangay" class="w-full h-full"></canvas>
                    <div id="empty-barangay" class="absolute inset-0 flex items-center justify-center text-sm text-slate-400 hidden">No barangay data available.</div>
                </div>
            </div>
            <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm">
                <h3 class="text-lg font-semibold text-slate-700 mb-4">Reports Over Time (Last 30 Days)</h3>
                <div class="relative h-72">
                    <canvas id="chart-trend" class="w-full h-full"></canvas>
                    <div id="empty-trend" class="absolute inset-0 flex items-center justify-center text-sm text-slate-400 hidden">No timeline data available.</div>
                </div>
            </div>
        </section>
    </main>

    <script>
        lucide.createIcons();

        const API_URL = '<?php echo BASE_URL; ?>/api/admin_analytics.php';

        /* Display labels & palette — keyed by the actual DB column values. */
        const CATEGORY_LABELS = {
            flooding: 'Stagnant Water',
            illegal_dumping: 'Illegal Dumping',
            clogged_drainage: 'Drainage',
            uncollected_garbage: 'Waste',
            drug_concern: 'Drug Concern'
        };
        const CATEGORY_COLORS = {
            flooding: '#3B49DF',
            illegal_dumping: '#EF4444',
            clogged_drainage: '#F59E0B',
            uncollected_garbage: '#10B981',
            drug_concern: '#8B5CF6'
        };
        const STATUS_ORDER = ['submitted', 'verified', 'assigned', 'responding', 'resolved'];
        const STATUS_LABELS = {
            submitted: 'Submitted', verified: 'Verified', assigned: 'Assigned',
            responding: 'Responding', resolved: 'Resolved'
        };
        const STATUS_COLORS = {
            submitted: '#F59E0B', verified: '#3B49DF', assigned: '#6366F1',
            responding: '#8B5CF6', resolved: '#10B981'
        };

        let charts = {};

        function humanize(value, map) {
            if (map[value] !== undefined) return map[value];
            return value.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase());
        }
        function palette(value, map) {
            return map[value] || '#94A3B8';
        }
        function destroyCharts() {
            Object.values(charts).forEach(c => { try { c.destroy(); } catch (e) {} });
            charts = {};
        }
        function showEmpty(id) { document.getElementById('empty-' + id)?.classList.remove('hidden'); }
        function hideEmpty(id) { document.getElementById('empty-' + id)?.classList.add('hidden'); }

        async function loadAnalytics() {
            const refresh = document.querySelector('button[onclick="loadAnalytics()"] i');
            refresh?.classList.add('animate-spin');
            try {
                const res = await fetch(API_URL, { cache: 'no-store' });
                const data = await res.json();
                if (!data.success) throw new Error(data.message || 'Failed to load analytics');

                destroyCharts();
                renderSummary(data.summary);
                renderCategoryChart(data.reports_by_category);
                renderStatusChart(data.reports_by_status);
                renderBarangayChart(data.reports_by_barangay);
                renderTrendChart(data.reports_over_time);
            } catch (e) {
                console.error(e);
                ['category', 'status', 'barangay', 'trend'].forEach(showEmpty);
            } finally {
                refresh?.classList.remove('animate-spin');
            }
        }

        function renderSummary(s) {
            document.getElementById('stat-total-reports').textContent = s.total_reports.toLocaleString();
            document.getElementById('stat-resolved').textContent = s.resolved.toLocaleString();
            document.getElementById('stat-total-denom').textContent = s.total_reports.toLocaleString();
            document.getElementById('stat-resolution-rate').textContent = s.resolution_rate + '%';
            document.getElementById('stat-active-barangay').textContent = s.most_active_barangay?.name || '—';
            document.getElementById('stat-active-count').textContent = (s.most_active_barangay?.count || 0).toLocaleString();
        }

        function renderCategoryChart(rows) {
            hideEmpty('category');
            const ctx = document.getElementById('chart-category').getContext('2d');
            if (!rows.length) { showEmpty('category'); return; }
            const labels = rows.map(r => humanize(r.category, CATEGORY_LABELS));
            const data = rows.map(r => r.count);
            const bg = rows.map(r => palette(r.category, CATEGORY_COLORS));
            charts.category = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels,
                    datasets: [{ label: 'Reports', data, backgroundColor: bg, borderRadius: 6, borderWidth: 0 }]
                },
                options: {
                    indexAxis: 'h',
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        x: { grid: { display: false }, beginAtZero: true, ticks: { color: '#6474a5', stepSize: 1, precision: 0, callback: function(value) { if (Number.isInteger(value)) return value; } } },
                        y: { grid: { display: false }, ticks: { color: '#6474a5' } }
                    }
                }
            });
        }

        function renderStatusChart(rows) {
            hideEmpty('status');
            const ctx = document.getElementById('chart-status').getContext('2d');
            if (!rows.length) { showEmpty('status'); return; }
            const ordered = rows.slice().sort((a, b) => {
                const ai = STATUS_ORDER.indexOf(a.status), bi = STATUS_ORDER.indexOf(b.status);
                return (ai === -1 ? 99 : ai) - (bi === -1 ? 99 : bi);
            });
            const labels = ordered.map(r => humanize(r.status, STATUS_LABELS));
            const data = ordered.map(r => r.count);
            const bg = ordered.map(r => palette(r.status, STATUS_COLORS));
            charts.status = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels,
                    datasets: [{ data, backgroundColor: bg, borderWidth: 0, hoverOffset: 6 }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { position: 'bottom', labels: { color: '#475569', padding: 16, usePointStyle: true, boxWidth: 12 } } },
                    cutout: '60%'
                }
            });
        }

        function renderBarangayChart(rows) {
            hideEmpty('barangay');
            const ctx = document.getElementById('chart-barangay').getContext('2d');
            if (!rows.length) { showEmpty('barangay'); return; }
            const labels = rows.map(r => r.barangay);
            const data = rows.map(r => r.count);
            charts.barangay = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels,
                    datasets: [{ label: 'Reports', data, backgroundColor: '#3B49DF', borderRadius: 6, borderWidth: 0 }]
                },
                options: {
                    indexAxis: 'h',
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        x: { grid: { display: false }, beginAtZero: true, ticks: { color: '#6474a5', stepSize: 1, precision: 0, callback: function(value) { if (Number.isInteger(value)) return value; } } },
                        y: { grid: { display: false }, ticks: { color: '#6474a5' } }
                    }
                }
            });
        }

        function renderTrendChart(rows) {
            hideEmpty('trend');
            const ctx = document.getElementById('chart-trend').getContext('2d');
            if (!rows.length) { showEmpty('trend'); return; }
            const labels = rows.map(r => {
                const d = new Date(r.date + 'T00:00:00');
                return (d.getMonth() + 1) + '/' + d.getDate();
            });
            const data = rows.map(r => r.count);
            charts.trend = new Chart(ctx, {
                type: 'line',
                data: {
                    labels,
                    datasets: [{
                        label: 'Reports',
                        data,
                        borderColor: '#3B49DF',
                        backgroundColor: 'rgba(59,73,223,0.12)',
                        tension: 0.3,
                        fill: true,
                        pointRadius: 3,
                        pointHoverRadius: 5,
                        borderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        x: { grid: { display: false }, ticks: { color: '#6474a5', maxRotation: 45, minRotation: 45 } },
                        y: { grid: { display: false }, ticks: { color: '#6474a5', stepSize: 1, precision: 0 }, beginAtZero: true }
                    }
                }
            });
        }

        loadAnalytics();
    </script>
</body>
</html>
