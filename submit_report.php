<?php
require_once __DIR__ . '/Includes/csrf.php';
if (!isset($_SESSION['user_id'])) { header("Location: " . BASE_URL . "/login.php"); exit(); }
$csrf_token = htmlspecialchars(generate_csrf_token());
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <title>Submit Report | EcoWatch</title>
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
            <a href="<?php echo BASE_URL; ?>/dashboard.php" class="nav-link active block p-3 hover:bg-white/10 rounded-xl transition">Dashboard</a>
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
        <header class="flex justify-between items-center mb-8">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Submit New Report</h1>
                <p class="text-gray-500">Help your community by reporting environmental issues.</p>
            </div>
            <div class="flex items-center gap-4">
                <div class="w-10 h-10 bg-white rounded-full border shadow-sm flex items-center justify-center">🔔</div>
            </div>
        </header>

        <form id="submit-report-form" class="w-full max-w-3xl mx-auto bg-white p-6 md:p-8 rounded-3xl shadow-sm border border-gray-100 transition-all duration-300 space-y-6">
            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
            <input type="hidden" name="category" id="category-input" required>

            <div>
                <label class="block text-sm font-bold text-gray-700 mb-4">Select Issue Category</label>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <button type="button" onclick="selectCategory(this, 'flooding')" class="btn-cat btn-flood w-full p-4 rounded-2xl font-semibold border-2 text-left transition-all duration-300">Stagnant Water</button>
                    <button type="button" onclick="selectCategory(this, 'illegal_dumping')" class="btn-cat btn-dumping w-full p-4 rounded-2xl font-semibold border-2 text-left transition-all duration-300">Illegal Dumping</button>
                    <button type="button" onclick="selectCategory(this, 'clogged_drainage')" class="btn-cat btn-drainage w-full p-4 rounded-2xl font-semibold border-2 text-left transition-all duration-300">Drainage</button>
                    <button type="button" onclick="selectCategory(this, 'uncollected_garbage')" class="btn-cat btn-waste w-full p-4 rounded-2xl font-semibold border-2 text-left transition-all duration-300">Waste</button>
                    <button type="button" onclick="selectCategory(this, 'drug_concern')" class="btn-cat btn-drug w-full p-4 rounded-2xl font-semibold border-2 text-left transition-all duration-300">Drug Concern</button>
                </div>
                <p id="category-error" class="text-red-500 text-sm mt-2 hidden">Please select an issue category.</p>
            </div>

            <div>
                <label class="block text-sm font-bold text-gray-700 mb-2">Severity Level</label>
                <div class="grid grid-cols-3 gap-3">
                    <label class="border border-slate-200 rounded-xl p-3 flex items-center justify-center space-x-2 text-sm font-medium cursor-pointer hover:bg-slate-50">
                        <input type="radio" name="severity" value="low" checked class="text-brand-blue"> <span>Low</span>
                    </label>
                    <label class="border border-slate-200 rounded-xl p-3 flex items-center justify-center space-x-2 text-sm font-medium cursor-pointer hover:bg-slate-50">
                        <input type="radio" name="severity" value="high" class="text-brand-blue"> <span>High</span>
                    </label>
                    <label class="border border-slate-200 rounded-xl p-3 flex items-center justify-center space-x-2 text-sm font-medium cursor-pointer hover:bg-slate-50">
                        <input type="radio" name="severity" value="critical" class="text-red-600"> <span class="text-red-600">Critical</span>
                    </label>
                </div>
            </div>

            <div class="space-y-4">
                <select name="barangay" required class="w-full p-4 bg-gray-50 rounded-2xl border border-transparent focus:border-indigo-500 focus:bg-white focus:ring-4 focus:ring-indigo-500/10 outline-none transition-all duration-300">
                    <option value="">Select Barangay</option>
                    <option value="Airport">Airport</option>
                    <option value="Alegria">Alegria</option>
                    <option value="Alta Vista">Alta Vista</option>
                    <option value="Bagong">Bagong</option>
                    <option value="Bagong Buhay">Bagong Buhay</option>
                    <option value="Bantigue">Bantigue</option>
                    <option value="Barangay 1">Barangay 1</option>
                    <option value="Barangay 2">Barangay 2</option>
                    <option value="Barangay 3">Barangay 3</option>
                    <option value="Barangay 4">Barangay 4</option>
                    <option value="Barangay 5">Barangay 5</option>
                    <option value="Barangay 6">Barangay 6</option>
                    <option value="Barangay 7">Barangay 7</option>
                    <option value="Barangay 8">Barangay 8</option>
                    <option value="Barangay 9">Barangay 9</option>
                    <option value="Barangay 10">Barangay 10</option>
                    <option value="Barangay 11">Barangay 11</option>
                    <option value="Barangay 12">Barangay 12</option>
                    <option value="Barangay 13">Barangay 13</option>
                    <option value="Barangay 14">Barangay 14</option>
                    <option value="Barangay 15">Barangay 15</option>
                    <option value="Barangay 16">Barangay 16</option>
                    <option value="Barangay 17">Barangay 17</option>
                    <option value="Barangay 18">Barangay 18</option>
                    <option value="Barangay 19">Barangay 19</option>
                    <option value="Barangay 20">Barangay 20</option>
                    <option value="Barangay 21">Barangay 21</option>
                    <option value="Barangay 22">Barangay 22</option>
                    <option value="Barangay 23">Barangay 23</option>
                    <option value="Barangay 24">Barangay 24</option>
                    <option value="Barangay 25">Barangay 25</option>
                    <option value="Barangay 26">Barangay 26</option>
                    <option value="Barangay 27">Barangay 27</option>
                    <option value="Barangay 28">Barangay 28</option>
                    <option value="Barangay 29">Barangay 29</option>
                    <option value="Batuan">Batuan</option>
                    <option value="Bayog">Bayog</option>
                    <option value="Biliboy">Biliboy</option>
                    <option value="Borok">Borok</option>
                    <option value="Cabaon-an">Cabaon-an</option>
                    <option value="Cabintan">Cabintan</option>
                    <option value="Cabulihan">Cabulihan</option>
                    <option value="Cagbuhangin">Cagbuhangin</option>
                    <option value="Camp Downes">Camp Downes</option>
                    <option value="Can-adieng">Can-adieng</option>
                    <option value="Can-untog">Can-untog</option>
                    <option value="Catmon">Catmon</option>
                    <option value="Cogon Combado">Cogon Combado</option>
                    <option value="Concepcion">Concepcion</option>
                    <option value="Curva">Curva</option>
                    <option value="Danao">Danao</option>
                    <option value="Danhug">Danhug</option>
                    <option value="Dayhagan">Dayhagan</option>
                    <option value="Dolores">Dolores</option>
                    <option value="Domonar">Domonar</option>
                    <option value="Don Felipe Larrazabal">Don Felipe Larrazabal</option>
                    <option value="Don Potenciano Larrazabal">Don Potenciano Larrazabal</option>
                    <option value="Do&#241;a Feliza Z. Mejia">Do&#241;a Feliza Z. Mejia</option>
                    <option value="Donghol">Donghol</option>
                    <option value="Esperanza">Esperanza</option>
                    <option value="Gaas">Gaas</option>
                    <option value="Green Valley">Green Valley</option>
                    <option value="Guintigui-an">Guintigui-an</option>
                    <option value="Hibunawon">Hibunawon</option>
                    <option value="Hugpa">Hugpa</option>
                    <option value="Ipil">Ipil</option>
                    <option value="Juaton">Juaton</option>
                    <option value="Kadaohan">Kadaohan</option>
                    <option value="Labrador">Labrador</option>
                    <option value="Lao">Lao</option>
                    <option value="Leondoni">Leondoni</option>
                    <option value="Libertad">Libertad</option>
                    <option value="Liberty">Liberty</option>
                    <option value="Licuma">Licuma</option>
                    <option value="Liloan">Liloan</option>
                    <option value="Linao">Linao</option>
                    <option value="Luna">Luna</option>
                    <option value="Mabato">Mabato</option>
                    <option value="Mabini">Mabini</option>
                    <option value="Macabug">Macabug</option>
                    <option value="Magaswi">Magaswi</option>
                    <option value="Mahayag">Mahayag</option>
                    <option value="Mahayahay">Mahayahay</option>
                    <option value="Manlilinao">Manlilinao</option>
                    <option value="Margen">Margen</option>
                    <option value="Mas-in">Mas-in</option>
                    <option value="Matica-a">Matica-a</option>
                    <option value="Milagro">Milagro</option>
                    <option value="Monterico">Monterico</option>
                    <option value="Nasunogan">Nasunogan</option>
                    <option value="Naungan">Naungan</option>
                    <option value="Nueva Sociedad">Nueva Sociedad</option>
                    <option value="Nueva Vista">Nueva Vista</option>
                    <option value="Patag">Patag</option>
                    <option value="Punta">Punta</option>
                    <option value="Quezon, Jr.">Quezon, Jr.</option>
                    <option value="Rufina M. Tan">Rufina M. Tan</option>
                    <option value="Sabang Bao">Sabang Bao</option>
                    <option value="Salvacion">Salvacion</option>
                    <option value="San Antonio">San Antonio</option>
                    <option value="San Isidro">San Isidro</option>
                    <option value="San Jose">San Jose</option>
                    <option value="San Juan">San Juan</option>
                    <option value="San Pablo">San Pablo</option>
                    <option value="San Vicente">San Vicente</option>
                    <option value="Santo Ni&#241;o">Santo Ni&#241;o</option>
                    <option value="Sumangga">Sumangga</option>
                    <option value="Tambulilid">Tambulilid</option>
                    <option value="Tongonan">Tongonan</option>
                    <option value="Valencia">Valencia</option>
                </select>
                <input type="text" name="address" placeholder="Exact Location / Address" required class="w-full p-4 bg-gray-50 rounded-2xl border border-transparent focus:border-indigo-500 focus:bg-white focus:ring-4 focus:ring-indigo-500/10 outline-none transition-all duration-300" id="address-input">

                <!-- Optional Location Map -->
                <div class="bg-white border border-slate-200 rounded-2xl p-4">
                    <div class="flex items-center justify-between mb-3">
                        <label class="block text-sm font-bold text-gray-700">Exact Location (Optional)</label>
                        <span class="text-xs text-slate-500">Drag the pin to mark the exact location</span>
                    </div>
                    <div id="location-map" class="w-full h-64 rounded-xl border border-slate-200" style="z-index: 1;"></div>
                    <div class="mt-3 flex flex-wrap gap-4 text-sm text-slate-600">
                        <div>Latitude: <span id="lat-display" class="font-mono text-brand-blue">Not set</span></div>
                        <div>Longitude: <span id="lng-display" class="font-mono text-brand-blue">Not set</span></div>
                    </div>
                    <input type="hidden" name="latitude" id="latitude-input">
                    <input type="hidden" name="longitude" id="longitude-input">
                </div>

                <textarea name="description" rows="4" placeholder="Describe the issue..." class="w-full p-4 bg-gray-50 rounded-2xl border border-transparent focus:border-indigo-500 focus:bg-white focus:ring-4 focus:ring-indigo-500/10 outline-none transition-all duration-300"></textarea>

                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">Photo Evidence</label>
                    <input type="file" name="photo" accept="image/jpeg,image/png,image/webp" class="w-full p-3 bg-gray-50 rounded-2xl border border-gray-200 text-sm text-gray-600 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                </div>

                <button type="submit" id="submit-btn" class="w-full bg-[#3B49DF] text-white p-4 rounded-2xl font-bold hover:bg-[#2D39B5] shadow-lg hover:shadow-indigo-500/30 hover:shadow-2xl transition-all duration-300 transform hover:scale-[1.01]">
                    Submit Report
                </button>
            </div>
        </form>

        <div id="submit-success" class="hidden bg-white p-8 rounded-3xl shadow-sm border border-gray-100 text-center mt-6">
            <div class="mx-auto w-16 h-16 bg-emerald-100 text-emerald-600 rounded-full flex items-center justify-center mb-4">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
            </div>
            <h3 class="text-xl font-bold text-gray-900 mb-2">Report Submitted Successfully</h3>
            <p class="text-gray-500 text-sm mb-4">Save your tracking token to monitor progress:</p>
            <div class="bg-gray-50 border border-gray-200 rounded-xl p-4 inline-flex items-center space-x-2 mb-6">
                <span class="text-xs text-gray-400 font-semibold uppercase tracking-wider">Token</span>
                <span id="success-token" class="text-lg font-mono font-bold text-brand-blue">--</span>
            </div>
            <div class="flex space-x-4">
                <a href="<?php echo BASE_URL; ?>/my_reports.php" class="flex-1 bg-brand-blue text-white py-3 rounded-xl font-semibold hover:bg-blue-700 transition">View My Reports</a>
                <button onclick="resetSubmission()" class="flex-1 bg-white border border-gray-200 text-gray-700 py-3 rounded-xl font-semibold hover:bg-gray-50 transition">Submit Another</button>
            </div>
        </div>

        <div id="submit-error" class="hidden bg-white p-6 rounded-3xl shadow-sm border border-red-100 text-center mt-6">
            <h3 class="text-lg font-bold text-red-600 mb-2">Submission Failed</h3>
            <p id="submit-error-message" class="text-gray-600 text-sm mb-4"></p>
            <button onclick="resetSubmission()" class="bg-white border border-gray-200 text-gray-700 px-6 py-2 rounded-xl font-semibold hover:bg-gray-50 transition">Try Again</button>
        </div>

<style>
.selected { background: var(--primary) !important; color: white !important; border-color: var(--primary) !important; box-shadow: 0 10px 15px -3px rgba(59, 73, 223, 0.3); }
</style>
<style>
/* Base classes */
.btn-cat { background: white; transition: all 0.3s ease; }

/* Category Colors */
.btn-flood { border-color: #3B49DF; color: #3B49DF; }
.btn-dumping { border-color: #EF4444; color: #EF4444; }
.btn-drainage { border-color: #F59E0B; color: #F59E0B; }
.btn-waste { border-color: #10B981; color: #10B981; }
.btn-drug { border-color: #8B5CF6; color: #8B5CF6; }

/* The "Alive" Selected State:
   We create specific active classes so it doesn't turn generic white */
.btn-flood.selected { background: #3B49DF !important; color: white !important; }
.btn-dumping.selected { background: #EF4444 !important; color: white !important; }
.btn-drainage.selected { background: #F59E0B !important; color: white !important; }
.btn-waste.selected { background: #10B981 !important; color: white !important; }
.btn-drug.selected { background: #8B5CF6 !important; color: white !important; }
</style>

<script>
    function selectCategory(btn, cat) {
        document.getElementById('category-input').value = cat;
        document.querySelectorAll('.btn-cat').forEach(b => b.classList.remove('selected'));
        btn.classList.add('selected');
    }

    function resetSubmission() {
        document.getElementById('submit-report-form').reset();
        document.querySelectorAll('.btn-cat').forEach(b => b.classList.remove('selected'));
        document.getElementById('submit-success').classList.add('hidden');
        document.getElementById('submit-error').classList.add('hidden');
        document.getElementById('submit-report-form').classList.remove('hidden');
    }

    document.getElementById('submit-report-form').addEventListener('submit', async function(e) {
        e.preventDefault();

        const category = document.getElementById('category-input').value;
        if (!category) {
            document.getElementById('category-error').classList.remove('hidden');
            return;
        }
        document.getElementById('category-error').classList.add('hidden');

        const formData = new FormData(this);
        const submitBtn = document.getElementById('submit-btn');
        const originalText = submitBtn.innerText;
        submitBtn.disabled = true;
        submitBtn.innerText = 'Submitting...';

        try {
            const response = await fetch('<?php echo BASE_URL; ?>/api/submit_report.action.php', {
                method: 'POST',
                body: formData
            });
            const data = await response.json();

            if (data.success && data.tracking_token) {
                document.getElementById('submit-report-form').classList.add('hidden');
                document.getElementById('submit-success').classList.remove('hidden');
                document.getElementById('success-token').innerText = data.tracking_token;
            } else {
                document.getElementById('submit-report-form').classList.add('hidden');
                document.getElementById('submit-error').classList.remove('hidden');
                document.getElementById('submit-error-message').innerText = data.message || 'Submission failed. Please try again.';
            }
        } catch (error) {
            document.getElementById('submit-report-form').classList.add('hidden');
            document.getElementById('submit-error').classList.remove('hidden');
            document.getElementById('submit-error-message').innerText = 'Connection failed. Please try again.';
        } finally {
            submitBtn.disabled = false;
            submitBtn.innerText = originalText;
        }
    });

    // Mobile menu toggle
    function toggleMobileMenu() {
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('mobile-menu-overlay');
        const mobileBtn = document.getElementById('mobile-menu-btn');
        const closeBtn = document.getElementById('close-mobile-menu');
        
        if (sidebar.classList.contains('-translate-x-full')) {
            sidebar.classList.remove('-translate-x-full');
            overlay.classList.remove('hidden');
            mobileBtn.classList.add('hidden');
            closeBtn.classList.remove('hidden');
        } else {
            sidebar.classList.add('-translate-x-full');
            overlay.classList.remove('hidden');
            mobileBtn.classList.remove('hidden');
            closeBtn.classList.add('hidden');
        }
    }

    // Close mobile menu when clicking overlay
    document.getElementById('mobile-menu-overlay').addEventListener('click', toggleMobileMenu);
    document.getElementById('close-mobile-menu').addEventListener('click', toggleMobileMenu);

    // Initialize mobile menu on page load
    document.addEventListener('DOMContentLoaded', function() {
        lucide.createIcons();
        
        // Mobile menu button
        const mobileBtn = document.getElementById('mobile-menu-btn');
        if (mobileBtn) {
            mobileBtn.addEventListener('click', toggleMobileMenu);
        }
    });
</script>

    <!-- Location Map Initialization -->
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const mapContainer = document.getElementById('location-map');
            if (!mapContainer) return;

            const ORMOC_CENTER = [11.0064, 124.6072];
            const DEFAULT_ZOOM = 13;

            let map = L.map('location-map', {
                center: ORMOC_CENTER,
                zoom: 13,
                zoomControl: true,
                attributionControl: false
            });

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
            }).addTo(map);

            let marker = null;

            const latInput = document.getElementById('latitude-input');
            const lngInput = document.getElementById('longitude-input');
            const latDisplay = document.getElementById('lat-display');
            const lngDisplay = document.getElementById('lng-display');

            function updateMarker(lat, lng) {
                if (marker) {
                    marker.setLatLng([lat, lng]);
                } else {
                    marker = L.marker([lat, lng], {
                        draggable: true,
                        icon: L.divIcon({
                            className: 'custom-marker',
                            html: '<div style="background:#3B49DF;border:3px solid white;border-radius:50%;width:28px;height:28px;box-shadow:0 2px 8px rgba(0,0,0,0.3);"></div>',
                            iconSize: [28, 28],
                            iconAnchor: [14, 14]
                        })
                    }).addTo(map);
                }

                latInput.value = lat.toFixed(6);
                lngInput.value = lng.toFixed(6);
                document.getElementById('lat-display').textContent = lat.toFixed(6);
                document.getElementById('lng-display').textContent = lng.toFixed(6);
                map.panTo([lat, lng]);
            }

            // Click on map to place/drag marker
            map.on('click', function(e) {
                updateMarker(e.latlng.lat, e.latlng.lng);
            });

            // Also allow dragging the marker
            map.on('dragend', function() {
                if (marker) {
                    const pos = marker.getLatLng();
                    updateMarker(pos.lat, pos.lng);
                }
            });

            // Optional: geocode address when user types (basic)
            const addressInput = document.getElementById('address-input');
            if (addressInput) {
                let geocodeTimeout;
                addressInput.addEventListener('input', () => {
                    clearTimeout(geocodeTimeout);
                    geocodeTimeout = setTimeout(() => {
                        const query = addressInput.value.trim();
                        if (query.length > 5) {
                            // Use Nominatim for simple geocoding (free, no key needed)
                            fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(query + ', Ormoc City, Leyte, Philippines')}&limit=1`)
                                .then(res => res.json())
                                .then(data => {
                                    if (data.length > 0) {
                                        const lat = parseFloat(data[0].lat);
                                        const lng = parseFloat(data[0].lon);
                                        if (!isNaN(lat) && !isNaN(lng)) {
                                            updateMarker(lat, lng);
                                        }
                                    }
                                })
                                .catch(err => console.warn('Geocoding failed:', err));
                        }
                    }, 800);
                });
            }
        });

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

        // Close mobile menu when clicking overlay
        document.getElementById('mobile-menu-overlay').addEventListener('click', toggleMobileMenu);
        document.getElementById('close-mobile-menu').addEventListener('click', toggleMobileMenu);

        // Initialize mobile menu on page load
        document.addEventListener('DOMContentLoaded', function() {
            lucide.createIcons();
            
            // Mobile menu button
            const mobileBtn = document.getElementById('mobile-menu-btn');
            if (mobileBtn) {
                mobileBtn.addEventListener('click', toggleMobileMenu);
            }
        });
    </script>
</body>
</html>