<?php
/**
 * Citizen Dashboard Sidebar Component
 * 
 * This file contains the shared sidebar navigation for authenticated user pages.
 * It is included on: dashboard.php, submit_report.php, my_reports.php, profile.php
 * 
 * Mobile behavior:
 * - Sidebar starts hidden off-canvas on mobile
 * - Hamburger button (md:hidden) opens drawer as overlay
 * - Tap outside or X button closes drawer
 */
?>

<!-- Mobile hamburger button (visible on mobile, hidden on desktop) -->
<button id="mobile-menu-btn" class="md:hidden fixed top-4 left-4 z-50 bg-[#3B49DF] text-white p-3 rounded-xl shadow-lg focus:outline-none focus:ring-2 focus:ring-[#3B49DF] transition-all">
    <i data-lucide="menu" class="w-5 h-5"></i>
</button>

<!-- Top bar for closing mobile menu (visible on mobile, hidden on desktop) -->
<button id="close-mobile-menu" class="hidden md:hidden fixed top-4 right-4 z-50 bg-white text-[#3B49DF] p-3 rounded-xl shadow-lg focus:outline-none focus:ring-2 focus:ring-[#3B49DF] transition-all">
    <i data-lucide="x" class="w-5 h-5"></i>
</button>

<!-- Mobile overlay (appears when sidebar is open) -->
<div id="mobile-menu-overlay" class="hidden md:hidden fixed inset-0 bg-black/50 backdrop-blur-sm z-40" style="display: none;"></div>

<aside id="sidebar" class="w-64 bg-[#3B49DF] p-8 text-white fixed top-0 left-0 h-screen overflow-y-auto shadow-xl z-50 transform -translate-x-full transition-transform duration-300 ease-in-out md:translate-x-0">
    <div class="text-2xl font-bold mb-10 flex items-center gap-2">EcoWatch</div>
    <nav class="space-y-4">
        <a href="<?php echo BASE_URL; ?>/dashboard.php" class="nav-link block p-3 hover:bg-white/10 rounded-xl transition">Dashboard</a>
        <a href="<?php echo BASE_URL; ?>/submit_report.php" class="nav-link block p-3 hover:bg-white/10 rounded-xl transition">Submit Report</a>
        <a href="<?php echo BASE_URL; ?>/my_reports.php" class="nav-link block p-3 hover:bg-white/10 rounded-xl transition">My Reports</a>
        <a href="<?php echo BASE_URL; ?>/profile.php" class="nav-link block p-3 hover:bg-white/10 rounded-xl transition">Profile Settings</a>
        <form action="<?php echo BASE_URL; ?>/api/logout.php" method="POST" class="mt-auto">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(generate_csrf_token()); ?>">
            <button type="submit" class="nav-link block w-full text-left p-3 rounded-xl transition hover:bg-white/10 text-red-300 hover:text-red-100">Logout</button>
        </form>
    </nav>
</aside>

<!-- Mobile Menu Toggle Script -->
<script>
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

    // Attach mobile menu handlers on page load
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize Lucide icons (must be called to render data-lucide icons)
        lucide.createIcons();
        
        const mobileBtn = document.getElementById('mobile-menu-btn');
        const overlay = document.getElementById('mobile-menu-overlay');
        const closeBtn = document.getElementById('close-mobile-menu');
        
        if (mobileBtn) mobileBtn.addEventListener('click', toggleMobileMenu);
        if (overlay) overlay.addEventListener('click', toggleMobileMenu);
        if (closeBtn) closeBtn.addEventListener('click', toggleMobileMenu);
    });
</script>