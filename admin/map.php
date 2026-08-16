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
    <title>EcoWatch - Incident Map | Operations Terminal</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="https://unpkg.com/leaflet.heat@0.2.0/dist/leaflet-heat.js"></script>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: {
                            blue: '#3B49DF',
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
        .leaflet-container { font-family: inherit; }
        .custom-marker { background: transparent; border: none; }
        .severity-low { background: #10B981; border: 3px solid white; border-radius: 50%; width: 24px; height: 24px; box-shadow: 0 2px 6px rgba(0,0,0,0.3); }
        .severity-high { background: #F59E0B; border: 3px solid white; border-radius: 50%; width: 24px; height: 24px; box-shadow: 0 2px 6px rgba(0,0,0,0.3); }
        .severity-critical { background: #EF4444; border: 3px solid white; border-radius: 50%; width: 24px; height: 24px; box-shadow: 0 2px 6px rgba(0,0,0,0.3); animation: pulse 1.5s infinite; }
        @keyframes pulse { 0% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.6); } 70% { box-shadow: 0 0 0 10px rgba(239, 68, 68, 0); } 100% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0); } }
        .filter-btn { @apply px-4 py-2 text-sm font-semibold rounded-xl border-2 transition-colors; }
        .filter-btn.active { @apply bg-[#3B49DF] text-white border-[#3B49DF]; }
        .filter-btn:not(.active) { @apply text-slate-500 border-slate-200 hover:text-slate-700 hover:border-slate-300; }
        .leaflet-popup-content-wrapper { border-radius: 12px; box-shadow: 0 10px 25px rgba(0,0,0,0.15); }
        .leaflet-popup-content { margin: 8px 12px; line-height: 1.5; }
        .leaflet-popup-tip { box-shadow: 0 10px 25px rgba(0,0,0,0.15); }
    </style>
</head>
<body class="bg-brand-light text-brand-dark antialiased font-sans">

    <!-- Header (matches admin/index.php) -->
    <header class="bg-white border-b border-slate-200 h-20 flex items-center justify-between px-8 sticky top-0 z-30">
        <div class="flex items-center space-x-3">
            <div class="bg-[#3B49DF] text-white p-2 rounded-xl"><i data-lucide="shield-alert" class="w-5 h-5"></i></div>
            <span class="font-bold text-lg tracking-tight">EcoWatch <span class="text-slate-400 font-normal">| Operations Terminal</span></span>
        </div>
        <div class="flex items-center space-x-4">
            <span class="text-sm font-semibold bg-blue-50 text-[#3B49DF] px-3 py-1.5 rounded-lg">Muncipal Hub #1</span>
        </div>
    </header>

    <!-- Admin navigation -->
    <nav class="bg-white border-b border-slate-200 sticky top-20 z-20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex space-x-2">
            <a href="<?php echo BASE_URL; ?>/admin/index.php" class="px-4 py-3 text-sm font-semibold rounded-t-xl border-b-2 border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300 transition">Incident Queue</a>
            <a href="<?php echo BASE_URL; ?>/admin/analytics.php" class="px-4 py-3 text-sm font-semibold rounded-t-xl border-b-2 border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300 transition">Analytics</a>
            <a href="<?php echo BASE_URL; ?>/admin/map.php" class="px-4 py-3 text-sm font-semibold rounded-t-xl border-b-2 border-[#3B49DF] text-[#3B49DF] transition">Map</a>
        </div>
    </nav>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
            <div>
                <h1 class="text-3xl font-extrabold tracking-tight">Incident Map</h1>
                <p class="text-slate-500 text-sm">Geospatial view of all reported incidents with location data.</p>
            </div>
            <button onclick="loadMapData()" class="bg-white border border-slate-200 text-slate-700 px-4 py-2.5 rounded-xl font-semibold shadow-sm hover:bg-slate-50 inline-flex items-center space-x-2 w-fit">
                <i data-lucide="refresh-cw" class="w-4 h-4"></i> <span>Refresh</span>
            </button>
        </div>

        <!-- View Toggle & Severity Filter Buttons -->
        <div class="bg-white border border-slate-200 rounded-2xl p-4 mb-6 shadow-sm">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-4">
                <!-- View Toggle -->
                <div class="flex items-center gap-2">
                    <span class="text-sm font-semibold text-slate-700">View:</span>
                    <div class="flex bg-white border border-slate-200 rounded-xl p-1" role="radiogroup" aria-label="Map view">
                        <button id="view-markers" type="button" class="view-btn active px-4 py-2 text-sm font-semibold rounded-lg text-white bg-[#3B49DF] transition-colors" role="radio" aria-checked="true">
                            <i data-lucide="map-pin" class="w-4 h-4 inline-block mr-1"></i> Markers
                        </button>
                        <button id="view-heatmap" type="button" class="view-btn px-4 py-2 text-sm font-semibold rounded-lg text-slate-500 hover:text-slate-700 transition-colors" role="radio" aria-checked="false">
                            <i data-lucide="flame" class="w-4 h-4 inline-block mr-1"></i> Heatmap
                        </button>
                    </div>
                </div>

                <!-- Severity Filter Buttons -->
                <div id="severity-filters" class="flex flex-wrap gap-3">
                    <button class="filter-btn active" data-severity="all">All</button>
                    <button class="filter-btn" data-severity="low">Low</button>
                    <button class="filter-btn" data-severity="high">High</button>
                    <button class="filter-btn" data-severity="critical">Critical</button>
                </div>
            </div>
            <p class="text-sm text-slate-500" id="filter-count">Loading...</p>
        </div>

        <!-- Map Container -->
        <div class="bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden">
            <div id="map" class="w-full h-[600px] sm:h-[700px]"></div>
        </div>

        <!-- Legend (bottom-right corner overlay style) -->
        <div class="fixed bottom-6 right-6 sm:static sm:hidden lg:block z-50">
            <div class="bg-white border border-slate-200 rounded-2xl p-4 shadow-lg min-w-[200px]">
                <h4 class="text-sm font-semibold text-slate-700 mb-3">Severity Legend</h4>
                <div class="space-y-2">
                    <div class="flex items-center gap-2"><span class="severity-low w-5 h-5 flex-shrink-0"></span><span class="text-sm text-slate-600">Low</span></div>
                    <div class="flex items-center gap-2"><span class="severity-high w-5 h-5 flex-shrink-0"></span><span class="text-sm text-slate-600">High</span></div>
                    <div class="flex items-center gap-2"><span class="severity-critical w-5 h-5 flex-shrink-0"></span><span class="text-sm text-slate-600">Critical</span></div>
                </div>
            </div>
        </div>

        <!-- Legend for small screens -->
        <div class="hidden sm:block lg:hidden bg-white border border-slate-200 rounded-2xl p-4 mt-6 shadow-sm">
            <h4 class="text-sm font-semibold text-slate-700 mb-3">Severity Legend</h4>
            <div class="flex flex-wrap gap-4">
                <div class="flex items-center gap-2"><span class="severity-low w-5 h-5 flex-shrink-0"></span><span class="text-sm text-slate-600">Low</span></div>
                <div class="flex items-center gap-2"><span class="severity-high w-5 h-5 flex-shrink-0"></span><span class="text-sm text-slate-600">High</span></div>
                <div class="flex items-center gap-2"><span class="severity-critical w-5 h-5 flex-shrink-0"></span><span class="text-sm text-slate-600">Critical</span></div>
            </div>
        </div>
    </main>

    <script>
        lucide.createIcons();

        const API_URL = '<?php echo BASE_URL; ?>/api/admin_map.php';
        const ORMOC_CENTER = [11.0064, 124.6072];
        const DEFAULT_ZOOM = 13;

        let map = null;
        let markersLayer = null;
        let allReports = [];
        let currentFilter = 'all';
        let currentView = 'markers';

        // Marker icons by severity
        const severityIcons = {
            low: L.divIcon({
                className: 'custom-marker',
                html: '<div class="severity-low"></div>',
                iconSize: [24, 24],
                iconAnchor: [12, 12]
            }),
            high: L.divIcon({
                className: 'custom-marker',
                html: '<div class="severity-high"></div>',
                iconSize: [24, 24],
                iconAnchor: [12, 12]
            }),
            critical: L.divIcon({
                className: 'custom-marker',
                html: '<div class="severity-critical"></div>',
                iconSize: [24, 24],
                iconAnchor: [12, 12]
            })
        };

        const severityColors = {
            low: '#10B981',
            high: '#F59E0B',
            critical: '#EF4444'
        };

        function initMap() {
            map = L.map('map').setView(ORMOC_CENTER, DEFAULT_ZOOM);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
            }).addTo(map);

            markersLayer = L.layerGroup().addTo(map);
            heatmapLayer = null;
        }

        // Heatmap helper functions
        const severityWeight = { critical: 3, high: 2, low: 1 };

        function buildHeatmapData(reports) {
            return reports
                .filter(r => r.latitude !== null && r.longitude !== null)
                .map(r => [r.latitude, r.longitude, severityWeight[r.severity] || 1]);
        }

        function renderHeatmap(reports) {
            const heatData = buildHeatmapData(reports);

            if (heatmapLayer) {
                map.removeLayer(heatmapLayer);
            }

            heatmapLayer = L.heatLayer(heatData, {
                radius: 35,
                blur: 25,
                maxZoom: 17,
                gradient: {
                    0.2: '#10B981',   // green (low)
                    0.5: '#F59E0B',   // orange (high)
                    0.8: '#EF4444'    // red (critical)
                }
            }).addTo(map);

            document.getElementById('filter-count').textContent = `${heatData.length} incident(s) in heatmap`;
        }

        function severityLabel(severity) {
            const labels = { low: 'Low', high: 'High', critical: 'Critical' };
            return labels[severity] || severity;
        }

        function createPopupContent(report) {
            return `
                <div class="min-w-[250px]">
                    <div class="flex items-start justify-between gap-2 mb-2">
                        <span class="text-xs font-bold text-slate-900 font-mono">#${report.tracking_token}</span>
                        <span class="px-2 py-0.5 text-xs font-bold uppercase rounded-full bg-[${severityColors[report.severity]}] text-white">${severityLabel(report.severity)}</span>
                    </div>
                    <div class="space-y-1.5 text-sm text-slate-600">
                        <p><span class="font-semibold text-slate-900">${report.category.replace(/_/g, ' ')}</span></p>
                        <p><span class="font-semibold text-slate-900">Barangay:</span> ${report.barangay}</p>
                        <p><span class="font-semibold text-slate-900">Address:</span> ${report.address}</p>
                        <p><span class="font-semibold text-slate-900">Status:</span> <span class="capitalize">${report.status.replace(/_/g, ' ')}</span></p>
                        <p class="text-xs text-slate-500"><span class="font-semibold text-slate-900">Reported:</span> ${new Date(report.created_at).toLocaleString()}</p>
                    </div>
                </div>
            `;
        }

        function renderMarkers(reports) {
            markersLayer.clearLayers();

            reports.forEach(report => {
                if (report.latitude === null || report.longitude === null) return;

                const icon = severityIcons[report.severity] || severityIcons.low;
                const marker = L.marker([report.latitude, report.longitude], { icon });

                const popupContent = createPopupContent(report);
                marker.bindPopup(popupContent, {
                    maxWidth: 300,
                    className: 'custom-popup'
                });

                markersLayer.addLayer(marker);
            });

            // Update count
            document.getElementById('filter-count').textContent = `${reports.length} incident(s) shown`;
        }

        function applyFilter(filter) {
            currentFilter = filter;
            let filtered = allReports;

            if (filter !== 'all') {
                filtered = allReports.filter(r => r.severity === filter);
            }

            if (currentView === 'markers') {
                renderMarkers(filtered);
            } else {
                renderHeatmap(filtered);
            }

            // Update active button
            document.querySelectorAll('.filter-btn').forEach(btn => {
                if (btn.dataset.severity === filter) {
                    btn.classList.add('active');
                    btn.classList.remove('text-slate-500', 'border-slate-200');
                } else {
                    btn.classList.remove('active');
                    btn.classList.add('text-slate-500', 'border-slate-200');
                }
            });
        }

        async function loadMapData() {
            const refresh = document.querySelector('button[onclick="loadMapData()"] i');
            refresh?.classList.add('animate-spin');

            try {
                const res = await fetch(API_URL, { cache: 'no-store' });
                const data = await res.json();

                if (!data.success) throw new Error(data.message || 'Failed to load map data');

                allReports = data.reports;
                applyFilter(currentFilter);

                // Fit bounds to markers if any
                if (allReports.length > 0) {
                    const validReports = allReports.filter(r => r.latitude !== null && r.longitude !== null);
                    if (validReports.length > 0) {
                        const bounds = L.latLngBounds(validReports.map(r => [r.latitude, r.longitude]));
                        map.fitBounds(bounds, { padding: [50, 50] });
                    }
                }
            } catch (e) {
                console.error(e);
                document.getElementById('filter-count').textContent = 'Failed to load data';
            } finally {
                refresh?.classList.remove('animate-spin');
            }
        }

        function setView(view) {
            currentView = view;

            if (view === 'markers') {
                // Show markers, hide heatmap
                if (heatmapLayer) {
                    map.removeLayer(heatmapLayer);
                    heatmapLayer = null;
                }
                if (!map.hasLayer(markersLayer)) {
                    map.addLayer(markersLayer);
                }

                // Show severity filters
                document.getElementById('severity-filters').classList.remove('hidden');
                document.getElementById('severity-filters').classList.add('flex');

                // Update view toggle buttons
                document.getElementById('view-markers').classList.add('active', 'bg-[#3B49DF]', 'text-white');
                document.getElementById('view-markers').classList.remove('text-slate-500');
                document.getElementById('view-markers').setAttribute('aria-checked', 'true');
                document.getElementById('view-heatmap').classList.remove('active', 'bg-[#3B49DF]', 'text-white');
                document.getElementById('view-heatmap').classList.add('text-slate-500');
                document.getElementById('view-heatmap').setAttribute('aria-checked', 'false');

                // Re-apply current filter
                applyFilter(currentFilter);
            } else {
                // Show heatmap, hide markers
                markersLayer.clearLayers();
                if (map.hasLayer(markersLayer)) {
                    map.removeLayer(markersLayer);
                }

                // Hide severity filters
                document.getElementById('severity-filters').classList.add('hidden');
                document.getElementById('severity-filters').classList.remove('flex');

                // Update view toggle buttons
                document.getElementById('view-heatmap').classList.add('active', 'bg-[#3B49DF]', 'text-white');
                document.getElementById('view-heatmap').classList.remove('text-slate-500');
                document.getElementById('view-heatmap').setAttribute('aria-checked', 'true');
                document.getElementById('view-markers').classList.remove('active', 'bg-[#3B49DF]', 'text-white');
                document.getElementById('view-markers').classList.add('text-slate-500');
                document.getElementById('view-markers').setAttribute('aria-checked', 'false');

                // Render heatmap with all reports (no severity filtering in heatmap view)
                renderHeatmap(allReports);
            }
        }

        // Initialize on load
        document.addEventListener('DOMContentLoaded', () => {
            initMap();
            loadMapData();

            // Filter button handlers
            document.querySelectorAll('.filter-btn').forEach(btn => {
                btn.addEventListener('click', () => applyFilter(btn.dataset.severity));
            });

            // View toggle handlers
            document.getElementById('view-markers').addEventListener('click', () => setView('markers'));
            document.getElementById('view-heatmap').addEventListener('click', () => setView('heatmap'));
        });
    </script>
</body>
</html>