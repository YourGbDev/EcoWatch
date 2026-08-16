<?php
require_once __DIR__ . '/Includes/csrf.php';
if (!isset($_SESSION['user_id'])) { header("Location: " . BASE_URL . "/login.php"); exit(); }
$csrf_token = htmlspecialchars(generate_csrf_token());
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <title>Submit Report | EcoWatch</title>
    <style>
        body { font-family: 'Inter', sans-serif; }
        .nav-link.active { background: rgba(255, 255, 255, 0.15); color: #FFFFFF; }
    </style>
</head>
<body class="bg-gray-50 flex min-h-screen">

    <aside class="w-64 bg-[#3B49DF] p-8 text-white">
        <div class="text-2xl font-bold mb-10 flex items-center gap-2">EcoWatch</div>
        <nav class="space-y-4">
            <a href="<?php echo BASE_URL; ?>/dashboard.php" class="nav-link block p-3 hover:bg-white/10 rounded-xl transition">Dashboard</a>
            <a href="<?php echo BASE_URL; ?>/submit_report.php" class="nav-link active block p-3 hover:bg-white/10 rounded-xl transition">Submit Report</a>
            <a href="<?php echo BASE_URL; ?>/my_reports.php" class="nav-link block p-3 hover:bg-white/10 rounded-xl transition">My Reports</a>
            <a href="<?php echo BASE_URL; ?>/profile.php" class="nav-link block p-3 hover:bg-white/10 rounded-xl transition">Profile Settings</a>
            <form action="<?php echo BASE_URL; ?>/api/logout.php" method="POST" class="mt-auto">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(generate_csrf_token()); ?>">
                <button type="submit" class="nav-link block w-full text-left p-3 rounded-xl transition hover:bg-white/10 text-red-300 hover:text-red-100">Logout</button>
            </form>
        </nav>
    </aside>

    <main class="flex-1 p-8">
        <header class="flex justify-between items-center mb-8">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Submit New Report</h1>
                <p class="text-gray-500">Help your community by reporting environmental issues.</p>
            </div>
            <div class="flex items-center gap-4">
                <div class="w-10 h-10 bg-white rounded-full border shadow-sm flex items-center justify-center">🔔</div>
            </div>
        </header>

        <form id="submit-report-form" class="bg-white p-8 rounded-3xl shadow-sm border border-gray-100 transition-all duration-300 space-y-6">
            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
            <input type="hidden" name="category" id="category-input" required>

            <div>
                <label class="block text-sm font-bold text-gray-700 mb-4">Select Issue Category</label>
                <div class="grid grid-cols-2 gap-4">
                    <button type="button" onclick="selectCategory(this, 'flooding')" class="btn-cat btn-flood p-4 rounded-2xl font-semibold border-2 transition-all duration-300 transform hover:scale-[1.02] active:scale-[0.98]">Stagnant Water</button>
                    <button type="button" onclick="selectCategory(this, 'illegal_dumping')" class="btn-cat btn-dumping p-4 rounded-2xl font-semibold border-2 transition-all duration-300 transform hover:scale-[1.02] active:scale-[0.98]">Illegal Dumping</button>
                    <button type="button" onclick="selectCategory(this, 'clogged_drainage')" class="btn-cat btn-drainage p-4 rounded-2xl font-semibold border-2 transition-all duration-300 transform hover:scale-[1.02] active:scale-[0.98]">Drainage</button>
                    <button type="button" onclick="selectCategory(this, 'uncollected_garbage')" class="btn-cat btn-waste p-4 rounded-2xl font-semibold border-2 transition-all duration-300 transform hover:scale-[1.02] active:scale-[0.98]">Waste</button>
                    <button type="button" onclick="selectCategory(this, 'drug_concern')" class="btn-cat btn-drug p-4 rounded-2xl font-semibold border-2 transition-all duration-300 transform hover:scale-[1.02] active:scale-[0.98]">Drug Concern</button>
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
                    <option value="Barangay 1">Barangay 1</option>
                    <option value="Barangay 2">Barangay 2</option>
                    <option value="Barangay 3">Barangay 3</option>
                    <option value="Barangay 4">Barangay 4</option>
                    <option value="Barangay 5">Barangay 5</option>
                </select>
                <input type="text" name="address" placeholder="Exact Location / Address" required class="w-full p-4 bg-gray-50 rounded-2xl border border-transparent focus:border-indigo-500 focus:bg-white focus:ring-4 focus:ring-indigo-500/10 outline-none transition-all duration-300">
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
</script>
</main>
</body>
</html>