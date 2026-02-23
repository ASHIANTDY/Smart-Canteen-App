<?php
$pageTitle = 'My Profile';
require_once 'canteen_config.php';
requireLogin();

$currentUser = getCurrentUser($pdo);
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_profile'])) {
        $fullName = $_POST['full_name'] ?? '';
        $email = $_POST['email'] ?? '';
        
        $stmt = $pdo->prepare("UPDATE users SET full_name = ?, email = ? WHERE id = ?");
        if ($stmt->execute([$fullName, $email, $_SESSION['user_id']])) {
            $_SESSION['user_name'] = $fullName;
            $success = 'Profile updated successfully!';
            $currentUser = getCurrentUser($pdo);
        } else {
            $error = 'Failed to update profile';
        }
    } elseif (isset($_POST['change_password'])) {
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        
        if (!password_verify($currentPassword, $currentUser['password'])) {
            $error = 'Current password is incorrect';
        } elseif ($newPassword !== $confirmPassword) {
            $error = 'New passwords do not match';
        } elseif (strlen($newPassword) < 6) {
            $error = 'Password must be at least 6 characters';
        } else {
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            if ($stmt->execute([$hashedPassword, $_SESSION['user_id']])) {
                $success = 'Password changed successfully!';
            } else {
                $error = 'Failed to change password';
            }
        }
    }
}

require_once 'canteen_header.php';
?>

<div class="max-w-2xl mx-auto animate-fade-in">
    <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
        <!-- Profile Header -->
        <div class="gradient-bg p-8 text-center">
            <div class="w-24 h-24 bg-white/20 rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-user text-5xl text-white"></i>
            </div>
            <h2 class="text-2xl font-bold text-white"><?php echo htmlspecialchars($currentUser['full_name']); ?></h2>
            <p class="text-purple-200 capitalize"><?php echo $currentUser['role']; ?></p>
            <span class="inline-block mt-2 px-3 py-1 bg-white/20 rounded-full text-sm text-white">
                <?php echo htmlspecialchars($currentUser['username']); ?>
            </span>
        </div>

        <div class="p-8">
            <?php if ($success): ?>
            <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 rounded mb-6">
                <div class="flex items-center gap-2">
                    <i class="fas fa-check-circle"></i>
                    <span><?php echo $success; ?></span>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($error): ?>
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded mb-6">
                <div class="flex items-center gap-2">
                    <i class="fas fa-exclamation-circle"></i>
                    <span><?php echo $error; ?></span>
                </div>
            </div>
            <?php endif; ?>

            <!-- Profile Info -->
            <div class="mb-8">
                <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center gap-2">
                    <i class="fas fa-user-circle text-purple-600"></i>
                    Profile Information
                </h3>
                <form method="POST" class="space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Full Name</label>
                            <input type="text" name="full_name" value="<?php echo htmlspecialchars($currentUser['full_name']); ?>" required
                                class="w-full px-4 py-2 border rounded-xl focus:outline-none focus:ring-2 focus:ring-purple-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                            <input type="email" name="email" value="<?php echo htmlspecialchars($currentUser['email'] ?? ''); ?>"
                                class="w-full px-4 py-2 border rounded-xl focus:outline-none focus:ring-2 focus:ring-purple-500">
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Username</label>
                            <input type="text" value="<?php echo htmlspecialchars($currentUser['username']); ?>" disabled
                                class="w-full px-4 py-2 border rounded-xl bg-gray-100 text-gray-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Role</label>
                            <input type="text" value="<?php echo ucfirst($currentUser['role']); ?>" disabled
                                class="w-full px-4 py-2 border rounded-xl bg-gray-100 text-gray-500 capitalize">
                        </div>
                    </div>
                    <button type="submit" name="update_profile" 
                        class="bg-blue-600 text-white px-6 py-2 rounded-xl hover:bg-blue-700 transition-colors">
                        <i class="fas fa-save mr-2"></i>Update Profile
                    </button>
                </form>
            </div>

            <hr class="my-8">

            <!-- Change Password -->
            <div>
                <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center gap-2">
                    <i class="fas fa-lock text-purple-600"></i>
                    Change Password
                </h3>
                <form method="POST" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Current Password</label>
                        <input type="password" name="current_password" required
                            class="w-full px-4 py-2 border rounded-xl focus:outline-none focus:ring-2 focus:ring-purple-500">
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">New Password</label>
                            <input type="password" name="new_password" required minlength="6"
                                class="w-full px-4 py-2 border rounded-xl focus:outline-none focus:ring-2 focus:ring-purple-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Confirm New Password</label>
                            <input type="password" name="confirm_password" required
                                class="w-full px-4 py-2 border rounded-xl focus:outline-none focus:ring-2 focus:ring-purple-500">
                        </div>
                    </div>
                    <button type="submit" name="change_password" 
                        class="bg-purple-600 text-white px-6 py-2 rounded-xl hover:bg-purple-700 transition-colors">
                        <i class="fas fa-key mr-2"></i>Change Password
                    </button>
                </form>
            </div>

            <!-- Account Info -->
            <div class="mt-8 p-4 bg-gray-50 rounded-xl">
                <h4 class="text-sm font-medium text-gray-700 mb-2">Account Information</h4>
                <div class="text-sm text-gray-500 space-y-1">
                    <p>Member since: <?php echo date('F j, Y', strtotime($currentUser['created_at'])); ?></p>
                    <p>Last login: <?php echo $currentUser['last_login'] ? date('F j, Y g:i A', strtotime($currentUser['last_login'])) : 'Never'; ?></p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'canteen_footer.php'; ?>
