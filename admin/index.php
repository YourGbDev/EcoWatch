<?php
require_once __DIR__ . '/../Includes/csrf.php';
require_once __DIR__ . '/../db_connection.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: " . BASE_URL . "/login.php");
    exit;
}

$stats = [
    'total' => 0,
    'pending' => 0,
    'verified' => 0,
    'resolved' => 0,
    'critical' => 0
];
try {
    $stats['total'] = (int)$pdo->query("SELECT COUNT(*) FROM environmental_reports")->fetchColumn();
    $stats['pending'] = (int)$pdo->query("SELECT COUNT(*) FROM environmental_reports WHERE status = 'submitted'")->fetchColumn();
    $stats['verified'] = (int)$pdo->query("SELECT COUNT(*) FROM environmental_reports WHERE status = 'verified'")->fetchColumn();
    $stats['resolved'] = (int)$pdo->query("SELECT COUNT(*) FROM environmental_reports WHERE status = 'resolved'")->fetchColumn();
    $stats['critical'] = (int)$pdo->query("SELECT COUNT(*) FROM environmental_reports WHERE severity = 'critical' AND status NOT IN ('resolved','invalid')")->fetchColumn();
} catch (Exception $e) {
    error_log('Admin stats error: ' . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EcoWatch - Admin Control Panel</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: { brand: { blue: '#1E40AF', dark: '#0F172A', light: '#F8FAFC', green: '#10B981' } }
                }
            }
        }
    </script>
</head>
<body class="bg-brand-light text-brand-dark antialiased font-sans">

    <header class="bg-white border-b border-slate-200 h-20 flex items-center justify-between px-8 sticky top-0 z-30">
        <div class="flex items-center space-x-3">
            <div class="bg-brand-blue text-white p-2 rounded-xl"><i data-lucide="shield-alert" class="w-5 h-5"></i></div>
            <span class="font-bold text-lg tracking-tight">EcoWatch <span class="text-slate-400 font-normal">| Operations Terminal</span></span>
        </div>
        <div class="flex items-center space-x-4">
            <span class="text-xs bg-slate-200 text-slate-600 px-2.5 py-1 rounded-full ml-2">#1</span>
            <a href="<?php echo BASE_URL; ?>/api/logout.php" class="text-slate-500 hover:text-slate-700 text-sm font-medium flex items-center space-x-2 px-3 py-1.5 rounded-xl hover:bg-slate-50 transition-colors">
                <i data-lucide="log-out" class="w-4 h-4"></i>
                <span>Logout</span>
            </a>
        </div>
    </header>

    <!-- Admin navigation -->
    <nav class="bg-white border-b border-slate-200 sticky top-20 z-20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex space-x-2">
            <a href="<?php echo BASE_URL; ?>/admin/index.php" class="px-4 py-3 text-sm font-semibold rounded-t-xl border-b-2 border-[#3B49DF] text-[#3B49DF] transition">Incident Queue</a>
            <a href="<?php echo BASE_URL; ?>/admin/analytics.php" class="px-4 py-3 text-sm font-semibold rounded-t-xl border-b-2 border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300 transition">Analytics</a>
            <a href="<?php echo BASE_URL; ?>/admin/map.php" class="px-4 py-3 text-sm font-semibold rounded-t-xl border-b-2 border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300 transition">Map</a>
        </div>
    </nav>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10 space-y-8">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-3xl font-extrabold tracking-tight">Incident Queue Management</h1>
                <p class="text-slate-500 text-sm">Triage incoming community reports, update lifecycle statuses, and allocate resources.</p>
            </div>
            <button onclick="loadTickets()" class="bg-white border border-slate-200 text-slate-700 px-4 py-2.5 rounded-xl font-semibold shadow-sm hover:bg-slate-50 inline-flex items-center space-x-2 w-fit">
                <i data-lucide="refresh-cw" class="w-4 h-4"></i> <span>Refresh Queue</span>
            </button>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
            <div class="bg-white border border-slate-200 rounded-2xl p-5 shadow-sm">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Total Reports</p>
                        <p class="text-2xl font-bold text-brand-dark mt-1"><?php echo number_format($stats['total']); ?></p>
                    </div>
                    <div class="bg-slate-100 text-slate-600 p-2 rounded-xl"><i data-lucide="file-text" class="w-5 h-5"></i></div>
                </div>
            </div>
            <div class="bg-white border border-slate-200 rounded-2xl p-5 shadow-sm">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Pending</p>
                        <p class="text-2xl font-bold text-amber-600 mt-1"><?php echo number_format($stats['pending']); ?></p>
                    </div>
                    <div class="bg-amber-100 text-amber-600 p-2 rounded-xl"><i data-lucide="clock" class="w-5 h-5"></i></div>
                </div>
            </div>
            <div class="bg-white border border-slate-200 rounded-2xl p-5 shadow-sm">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Verified</p>
                        <p class="text-2xl font-bold text-blue-600 mt-1"><?php echo number_format($stats['verified']); ?></p>
                    </div>
                    <div class="bg-blue-100 text-blue-600 p-2 rounded-xl"><i data-lucide="check-circle" class="w-5 h-5"></i></div>
                </div>
            </div>
            <div class="bg-white border border-slate-200 rounded-2xl p-5 shadow-sm">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Resolved</p>
                        <p class="text-2xl font-bold text-emerald-600 mt-1"><?php echo number_format($stats['resolved']); ?></p>
                    </div>
                    <div class="bg-emerald-100 text-emerald-600 p-2 rounded-xl"><i data-lucide="shield-check" class="w-5 h-5"></i></div>
                </div>
            </div>
            <div class="bg-white border border-slate-200 rounded-2xl p-5 shadow-sm">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Critical Active</p>
                        <p class="text-2xl font-bold text-red-600 mt-1"><?php echo number_format($stats['critical']); ?></p>
                    </div>
                    <div class="bg-red-100 text-red-600 p-2 rounded-xl"><i data-lucide="alert-triangle" class="w-5 h-5"></i></div>
                </div>
            </div>
        </div>

        <div class="bg-white border border-slate-200 rounded-2xl shadow-sm p-6">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Search</label>
                    <input type="text" id="search-input" placeholder="Token, category, address..." class="w-full p-3 bg-gray-50 rounded-xl border border-gray-200 focus:outline-none focus:ring-2 focus:ring-brand-blue">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Status</label>
                    <select id="status-filter" class="w-full p-3 bg-gray-50 rounded-xl border border-gray-200 focus:outline-none focus:ring-2 focus:ring-brand-blue">
                        <option value="">All Statuses</option>
                        <option value="submitted">Submitted</option>
                        <option value="verified">Verified</option>
                        <option value="assigned">Assigned</option>
                        <option value="responding">Responding</option>
                        <option value="resolved">Resolved</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Barangay</label>
                    <select id="barangay-filter" class="w-full p-3 bg-gray-50 rounded-xl border border-gray-200 focus:outline-none focus:ring-2 focus:ring-brand-blue">
                        <option value="">All Barangays</option>
                    </select>
                </div>
                <div class="flex items-end">
                    <button onclick="applyFilters()" class="w-full bg-brand-blue text-white px-4 py-3 rounded-xl font-semibold hover:bg-blue-800 transition">Apply Filters</button>
                </div>
            </div>
        </div>

        <div class="bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-50 text-xs font-bold uppercase tracking-wider text-slate-400 border-b border-slate-200">
                            <th class="py-4 px-6">Token</th>
                            <th class="py-4 px-6">Category</th>
                            <th class="py-4 px-6">Severity</th>
                            <th class="py-4 px-6">Barangay</th>
                            <th class="py-4 px-6">Location</th>
                            <th class="py-4 px-6">Photo</th>
                            <th class="py-4 px-6">Status lifecycle</th>
                        </tr>
                    </thead>
                    <tbody id="tickets-table-body" class="divide-y divide-slate-100 text-sm">
                        <tr>
                            <td colspan="7" class="text-center py-10 text-slate-400">Loading master ticket entries...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div id="pagination-controls" class="flex items-center justify-between p-4 border-t border-gray-100">
                <button onclick="changePage(-1)" class="bg-white border border-gray-200 text-gray-700 px-4 py-2 rounded-xl font-semibold hover:bg-gray-50 transition">Previous</button>
                <span id="page-info" class="text-sm text-gray-600">Page 1 of 1</span>
                <button onclick="changePage(1)" class="bg-white border border-gray-200 text-gray-700 px-4 py-2 rounded-xl font-semibold hover:bg-gray-50 transition">Next</button>
            </div>
        </div>
    </main>

    <script>
        let currentPage = 1;
        let totalPages = 1;
        let csrfToken = '';

        async function loadCsrfToken() {
            try {
                const response = await fetch('<?php echo BASE_URL; ?>/api/csrf_token.php');
                const data = await response.json();
                csrfToken = data.csrf_token || '';
            } catch (error) {
                console.error('Failed to load CSRF token:', error);
            }
        }

        loadCsrfToken();

        lucide.createIcons();

        // Category label mapping for display
        const CATEGORY_LABELS = {
            flooding: 'Stagnant Water',
            illegal_dumping: 'Illegal Dumping',
            clogged_drainage: 'Clogged Drainage',
            uncollected_garbage: 'Uncollected Garbage',
            drug_concern: 'Drug Concern'
        };

        function escapeHtml(text) {
            if (text === null || text === undefined) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        function getCategoryLabel(cat) {
            return CATEGORY_LABELS[cat] || cat.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase());
        }

        function loadTickets(resetPage = true) {
            if (resetPage) currentPage = 1;
            
            const tableBody = document.getElementById('tickets-table-body');
            const search = document.getElementById('search-input').value;
            const statusFilter = document.getElementById('status-filter').value;
            const barangayFilter = document.getElementById('barangay-filter').value;
            
            const params = new URLSearchParams({
                search: search,
                status: statusFilter,
                barangay: barangayFilter,
                page: currentPage,
                limit: 10
            });

            fetch(`<?php echo BASE_URL; ?>/api/admin_fetch.php?${params.toString()}`)
            .then(res => res.json())
            .then(data => {
                if(!data.success) {
                    tableBody.innerHTML = `<tr><td colspan="7" class="text-center py-10 text-red-500 font-medium">${escapeHtml(data.message)}</td></tr>`;
                    return;
                }
                
                if(data.reports.length === 0) {
                    tableBody.innerHTML = `<tr><td colspan="7" class="text-center py-10 text-slate-400">The reporting queue is entirely clear! No active incidents found.</td></tr>`;
                    document.getElementById('pagination-controls').classList.add('hidden');
                    return;
                }

                totalPages = data.pagination.total_pages;
                document.getElementById('page-info').innerText = `Page ${data.pagination.page} of ${totalPages}`;
                document.getElementById('pagination-controls').classList.remove('hidden');

                const barangayOptions = data.filters.barangays.map(b => `<option value="${escapeHtml(b)}">${escapeHtml(b)}</option>`).join('');
                const barangaySelect = document.getElementById('barangay-filter');
                barangaySelect.innerHTML = '<option value="">All Barangays</option>' + barangayOptions;
                barangaySelect.value = barangayFilter;

                tableBody.innerHTML = data.reports.map(report => {
                    
                    let severityBadge = "bg-blue-50 text-blue-700";
                    if(report.severity === 'high') severityBadge = "bg-amber-50 text-amber-700 font-bold";
                    if(report.severity === 'critical') severityBadge = "bg-red-50 text-red-700 font-bold animate-pulse";

                    const photoCell = report.photo_path 
                        ? `<td class="py-4 px-6"><img src="${escapeHtml(report.photo_path)}" class="w-12 h-12 object-cover rounded-xl border border-slate-200 cursor-pointer hover:opacity-80" onclick="event.stopPropagation(); window.open('${escapeHtml(report.photo_path)}', '_blank')"></td>`
                        : '<td class="py-4 px-6 text-slate-400">-</td>';

                    return `
                        <tr class="hover:bg-slate-50/80 transition cursor-pointer" onclick="openAdminDetail(${report.id}, '${escapeHtml(report.tracking_token)}', '${escapeHtml(report.status)}')">
                            <td class="py-4 px-6 font-mono font-bold text-slate-900">#${escapeHtml(report.tracking_token)}</td>
                            <td class="py-4 px-6 font-semibold">${escapeHtml(getCategoryLabel(report.category))}</td>
                            <td class="py-4 px-6"><span class="px-2.5 py-1 rounded-md text-xs uppercase ${severityBadge}">${escapeHtml(report.severity)}</span></td>
                            <td class="py-4 px-6 text-slate-600">${escapeHtml(report.barangay)}</td>
                            <td class="py-4 px-6 text-slate-600 max-w-xs truncate">${escapeHtml(report.address)}</td>
                            ${photoCell}
                            <td class="py-4 px-6" onclick="event.stopPropagation()">
                                <select onchange="updateTicketStatus(${report.id}, this.value)" class="bg-slate-50 border border-slate-200 rounded-xl px-3 py-1.5 focus:outline-none focus:ring-2 focus:ring-brand-blue font-medium text-slate-700">
                                    <option value="submitted" ${report.status === 'submitted' ? 'selected' : ''}>Submitted</option>
                                    <option value="verified" ${report.status === 'verified' ? 'selected' : ''}>Verified</option>
                                    <option value="assigned" ${report.status === 'assigned' ? 'selected' : ''}>Assigned</option>
                                    <option value="responding" ${report.status === 'responding' ? 'selected' : ''}>Responding</option>
                                    <option value="resolved" ${report.status === 'resolved' ? 'selected' : ''}>Resolved</option>
                                </select>
                            </td>
                        </tr>
                    `;
                }).join('');
            })
            .catch(err => {
                console.error(err);
                tableBody.innerHTML = `<tr><td colspan="7" class="text-center py-10 text-red-500">Could not sync with operational terminal backend.</td></tr>`;
            });
        }

        function applyFilters() {
            currentPage = 1;
            loadTickets(true);
        }

        function changePage(direction) {
            const newPage = currentPage + direction;
            if (newPage >= 1 && newPage <= totalPages) {
                currentPage = newPage;
                loadTickets(false);
            }
        }

        function updateTicketStatus(id, currentStatus) {
            if (!csrfToken) {
                alert('Security token not ready. Please wait a moment and try again.');
                return;
            }
            fetch('<?php echo BASE_URL; ?>/api/admin_update.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: id, status: currentStatus, csrf_token: csrfToken })
            })
            .then(res => res.json())
            .then(data => {
                if(!data.success) alert(`Failed to update state: ${data.message}`);
            })
            .catch(err => console.error('Status updating fault:', err));
        }

        async function openAdminDetail(reportId, trackingToken, currentStatus) {
            const modal = document.getElementById('admin-detail-modal');
            const content = document.getElementById('admin-detail-content');
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
                const badgeClass = getAdminStatusBadge(report.status);

                let photoHtml = '';
                if (report.photo_path) {
                    photoHtml = `<div class="mt-4"><img src="${report.photo_path}" class="max-h-80 rounded-xl shadow-md cursor-pointer" onclick="window.open('${report.photo_path}', '_blank')" alt="Report photo"></div>`;
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

                const statusOptions = ['submitted', 'verified', 'assigned', 'responding', 'resolved'].map(s => 
                    `<option value="${s}" ${report.status === s ? 'selected' : ''}>${s.charAt(0).toUpperCase() + s.slice(1)}</option>`
                ).join('');

                content.innerHTML = `
                    <div class="flex flex-col md:flex-row md:items-start justify-between gap-4 mb-6">
                        <div>
                            <h3 class="text-xl font-bold text-gray-900">#${report.tracking_token}</h3>
                            <p class="text-sm text-gray-500">Submitted by ${escapeHtml(report.citizen_name || 'Unknown')} on ${new Date(report.created_at).toLocaleString('en-US')}</p>
                        </div>
                        <span class="px-3 py-1 rounded-full text-xs font-bold uppercase ${badgeClass}">${report.status.replace('_', ' ').toUpperCase()}</span>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm mb-6">
                        <div><span class="font-semibold text-gray-700">Category:</span> <p class="text-gray-600">${escapeHtml(getCategoryLabel(report.category))}</p></div>
                        <div><span class="font-semibold text-gray-700">Severity:</span> <p class="text-gray-600 capitalize">${report.severity}</p></div>
                        <div class="md:col-span-2"><span class="font-semibold text-gray-700">Barangay:</span> <p class="text-gray-600">${escapeHtml(report.barangay)}</p></div>
                        <div class="md:col-span-2"><span class="font-semibold text-gray-700">Address:</span> <p class="text-gray-600">${escapeHtml(report.address)}</p></div>
                        <div class="md:col-span-2"><span class="font-semibold text-gray-700">Description:</span> <p class="text-gray-600">${escapeHtml(report.description || 'None')}</p></div>
                    </div>
                    
                    ${photoHtml}
                    
                    <div class="mt-6 bg-slate-50 p-4 rounded-xl">
                        <h4 class="text-sm font-bold text-gray-700 mb-3 uppercase tracking-wider">Status Timeline</h4>
                        <div class="space-y-2">${timelineHtml}</div>
                    </div>

                    <div class="mt-6 pt-6 border-t border-gray-100">
                        <h4 class="text-sm font-bold text-gray-700 mb-3 uppercase tracking-wider">Update Status</h4>
                        <div class="flex flex-col sm:flex-row gap-3">
                            <select id="admin-status-select" class="bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-brand-blue font-medium text-slate-700">
                                ${statusOptions}
                            </select>
                            <input type="text" id="admin-notes-input" placeholder="Add notes (optional)" class="flex-1 bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-brand-blue text-sm">
                            <button onclick="adminUpdateFromModal(${report.id}, document.getElementById('admin-status-select').value, document.getElementById('admin-notes-input').value)" class="bg-brand-blue text-white px-4 py-2 rounded-xl font-semibold hover:bg-blue-800 transition shadow-sm">Update</button>
                        </div>
                    </div>
                `;

                lucide.createIcons();
            } catch (error) {
                console.error('Failed to load admin detail:', error);
                content.innerHTML = '<p class="text-red-500">Failed to load report details.</p>';
            }
        }

        function closeAdminDetailModal() {
            const modal = document.getElementById('admin-detail-modal');
            modal.classList.remove('modal-active');
            setTimeout(() => {
                modal.classList.add('invisible');
                document.body.classList.remove('overflow-hidden');
            }, 150);
            loadTickets();
        }

        function getAdminStatusBadge(status) {
            switch(status) {
                case 'submitted': return 'bg-amber-100 text-amber-700';
                case 'verified': return 'bg-blue-100 text-blue-700';
                case 'assigned': return 'bg-indigo-100 text-indigo-700';
                case 'responding': return 'bg-purple-100 text-purple-700';
                case 'resolved': return 'bg-emerald-100 text-emerald-700';
                default: return 'bg-gray-100 text-gray-700';
            }
        }

        function adminUpdateFromModal(reportId, newStatus, notes) {
            if (!csrfToken) {
                alert('Security token not ready. Please wait a moment and try again.');
                return;
            }
            const body = { id: reportId, status: newStatus, csrf_token: csrfToken };
            if (notes && notes.trim()) {
                body.notes = notes.trim();
            }
            fetch('<?php echo BASE_URL; ?>/api/admin_update.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(body)
            })
            .then(res => res.json())
            .then(data => {
                if(data.success) {
                    closeAdminDetailModal();
                } else {
                    alert(`Failed to update: ${data.message}`);
                }
            })
            .catch(err => console.error('Status updating fault:', err));
        }

        window.onload = loadTickets;
    </script>

    <!-- Admin Report Detail Modal -->
    <div id="admin-detail-modal" class="fixed inset-0 z-50 flex items-center justify-center invisible transition-all duration-300">
        <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" onclick="closeAdminDetailModal()"></div>
        <div class="bg-white rounded-2xl w-full max-w-3xl p-6 md:p-8 shadow-2xl relative z-10 mx-4 modal-animate max-h-[90vh] overflow-y-auto">
            <button onclick="closeAdminDetailModal()" class="absolute top-4 right-4 text-slate-400 hover:text-slate-600"><i data-lucide="x" class="w-6 h-6"></i></button>
            <div id="admin-detail-content">
                <p class="text-slate-500">Loading report details...</p>
            </div>
        </div>
    </div>
</body>
</html>