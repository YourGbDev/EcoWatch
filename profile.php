<?php
require_once __DIR__ . '/Includes/csrf.php';

if (!isset($_SESSION['user_id'])) { header("Location: " . BASE_URL . "/login.php"); exit(); }

require_once __DIR__ . '/db_connection.php';

$userId = $_SESSION['user_id'];
$message = '';
$error = '';

try {
    $stmt = $pdo->prepare('SELECT id, name, email, created_at FROM users WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log('Profile fetch error: ' . $e->getMessage());
    $user = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <script src="https://cdn.tailwindcss.com"></script>
    <title>Profile Settings | EcoWatch</title>
    <style>
        body { font-family: 'Inter', sans-serif; }
        .nav-link.active { background: rgba(255, 255, 255, 0.15); color: #FFFFFF; }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">
    <?php include __DIR__ . '/includes/sidebar.php'; ?>

    <main class="flex-1 ml-4 md:ml-64 p-4 sm:p-8">

    <main class="flex-1 ml-4 md:ml-64 p-4 sm:p-8">
        <h1 class="text-3xl font-bold mb-2">Profile Settings</h1>
        <p class="text-gray-500 mb-8">Manage your account information and security.</p>

        <?php if ($message): ?>
            <div class="bg-green-50 text-green-700 p-4 rounded-xl mb-6"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="bg-red-50 text-red-700 p-4 rounded-xl mb-6"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <div class="bg-white p-8 rounded-2xl shadow-sm border border-gray-100 mb-8">
            <h2 class="text-xl font-bold mb-6">Personal Information</h2>
            <form id="profile-form" onsubmit="updateProfile(event)" class="space-y-6">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(generate_csrf_token()); ?>">
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">Full Name</label>
                    <input type="text" name="name" value="<?php echo htmlspecialchars($user['name'] ?? ''); ?>" required class="w-full p-4 bg-gray-50 rounded-2xl border border-transparent focus:border-indigo-500 focus:bg-white focus:ring-4 focus:ring-indigo-500/10 outline-none transition-all duration-300">
                </div>
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">Email Address</label>
                    <input type="email" name="email" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" required class="w-full p-4 bg-gray-50 rounded-2xl border border-transparent focus:border-indigo-500 focus:bg-white focus:ring-4 focus:ring-indigo-500/10 outline-none transition-all duration-300">
                </div>
                <button type="submit" class="w-full bg-[#3B49DF] text-white p-4 rounded-2xl font-bold hover:bg-[#2D39B5] shadow-lg transition-all duration-300">Update Profile</button>
            </form>
        </div>

        <div class="bg-white p-8 rounded-2xl shadow-sm border border-gray-100">
            <h2 class="text-xl font-bold mb-6">Change Password</h2>
            <form id="password-form" onsubmit="changePassword(event)" class="space-y-6">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(generate_csrf_token()); ?>">
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">Current Password</label>
                    <input type="password" name="current_password" required class="w-full p-4 bg-gray-50 rounded-2xl border border-transparent focus:border-indigo-500 focus:bg-white focus:ring-4 focus:ring-indigo-500/10 outline-none transition-all duration-300">
                </div>
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">New Password</label>
                    <input type="password" name="new_password" required minlength="8" class="w-full p-4 bg-gray-50 rounded-2xl border border-transparent focus:border-indigo-500 focus:bg-white focus:ring-4 focus:ring-indigo-500/10 outline-none transition-all duration-300">
                    <p class="text-xs text-gray-500 mt-1">Minimum 8 characters.</p>
                </div>
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">Confirm New Password</label>
                    <input type="password" name="confirm_password" required minlength="8" class="w-full p-4 bg-gray-50 rounded-2xl border border-transparent focus:border-indigo-500 focus:bg-white focus:ring-4 focus:ring-indigo-500/10 outline-none transition-all duration-300">
                </div>
                <button type="submit" class="w-full bg-[#3B49DF] text-white p-4 rounded-2xl font-bold hover:bg-[#2D39B5] shadow-lg transition-all duration-300">Change Password</button>
            </form>
        </div>
    </main>

    <script>
        async function updateProfile(event) {
            event.preventDefault();
            const form = event.target;
            const formData = new FormData(form);
            const data = Object.fromEntries(formData.entries());

            const btn = form.querySelector('button[type="submit"]');
            btn.disabled = true;
            btn.innerText = 'Updating...';

            try {
                const response = await fetch('<?php echo BASE_URL; ?>/api/update_profile.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });
                const result = await response.json();
                alert(result.message || 'Profile updated successfully.');
                if (result.success) location.reload();
            } catch (error) {
                alert('Update failed. Please try again.');
            } finally {
                btn.disabled = false;
                btn.innerText = 'Update Profile';
            }
        }

        async function changePassword(event) {
            event.preventDefault();
            const form = event.target;
            const formData = new FormData(form);
            const data = Object.fromEntries(formData.entries());

            if (data.new_password !== data.confirm_password) {
                alert('New passwords do not match.');
                return;
            }

            const btn = form.querySelector('button[type="submit"]');
            btn.disabled = true;
            btn.innerText = 'Changing...';

            try {
                const response = await fetch('<?php echo BASE_URL; ?>/api/change_password.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });
                const result = await response.json();
                alert(result.message || 'Password changed successfully.');
                if (result.success) form.reset();
            } catch (error) {
                alert('Password change failed. Please try again.');
            } finally {
                btn.disabled = false;
                btn.innerText = 'Change Password';
            }
        }

        // Mobile menu is handled by sidebar.php include
        document.addEventListener('DOMContentLoaded', function() {
            lucide.createIcons();
        });
    </script>
</body>
</html>