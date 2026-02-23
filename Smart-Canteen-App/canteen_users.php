<?php
$pageTitle = 'User Management';
require_once 'canteen_config.php';
requireLogin();
requireRole(['admin', 'manager']);

$success = '';
$error = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_user'])) {
        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';
        $fullName = $_POST['full_name'] ?? '';
        $email = $_POST['email'] ?? '';
        $role = $_POST['role'] ?? 'cashier';
        
        // Check if username exists
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->fetchColumn() > 0) {
            $error = 'Username already exists';
        } elseif (strlen($password) < 6) {
            $error = 'Password must be at least 6 characters';
        } else {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (username, password, full_name, email, role) VALUES (?, ?, ?, ?, ?)");
            if ($stmt->execute([$username, $hashedPassword, $fullName, $email, $role])) {
                $success = 'User created successfully!';
            } else {
                $error = 'Failed to create user';
            }
        }
    } elseif (isset($_POST['update_user'])) {
        $userId = $_POST['user_id'] ?? 0;
        $fullName = $_POST['full_name'] ?? '';
        $email = $_POST['email'] ?? '';
        $role = $_POST['role'] ?? 'cashier';
        $isActive = isset($_POST['is_active']) ? 1 : 0;
        
        // Prevent changing own role
        if ($userId == $_SESSION['user_id'] && $role != $_SESSION['user_role']) {
            $error = 'You cannot change your own role';
        } else {
            $stmt = $pdo->prepare("UPDATE users SET full_name = ?, email = ?, role = ?, is_active = ? WHERE id = ?");
            if ($stmt->execute([$fullName, $email, $role, $isActive, $userId])) {
                $success = 'User updated successfully!';
            } else {
                $error = 'Failed to update user';
            }
        }
    } elseif (isset($_POST['reset_password'])) {
        $userId = $_POST['user_id'] ?? 0;
        $newPassword = $_POST['new_password'] ?? '';
        
        if (strlen($newPassword) < 6) {
            $error = 'Password must be at least 6 characters';
        } else {
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            if ($stmt->execute([$hashedPassword, $userId])) {
                $success = 'Password reset successfully!';
            } else {
                $error = 'Failed to reset password';
            }
        }
    } elseif (isset($_POST['delete_user'])) {
        $userId = $_POST['user_id'] ?? 0;
        
        // Prevent deleting self
        if ($userId == $_SESSION['user_id']) {
            $error = 'You cannot delete your own account';
        } else {
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            if ($stmt->execute([$userId])) {
                $success = 'User deleted successfully!';
            } else {
                $error = 'Failed to delete user';
            }
        }
    }
}

// Get all users
$users = $pdo->query("SELECT * FROM users ORDER BY created_at DESC")->fetchAll();

require_once 'canteen_header.php';
?>

<div class="space-y-6 animate-fade-in">
    <!-- Header -->
    <div class="flex justify-between items-center">
        <div>
            <h3 class="text-lg font-bold text-gray-800">User Management</h3>
            <p class="text-gray-500 text-sm">Manage system users and their roles</p>
        </div>
        <button onclick="openModal('addUserModal')" class="bg-blue-600 text-white px-4 py-2 rounded-xl flex items-center gap-2 hover:bg-blue-700 transition-colors">
            <i class="fas fa-plus"></i>
            <span>Add User</span>
        </button>
    </div>

    <?php if ($success): ?>
    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 rounded">
        <div class="flex items-center gap-2">
            <i class="fas fa-check-circle"></i>
            <span><?php echo $success; ?></span>
        </div>
    </div>
    <?php endif; ?>

    <?php if ($error): ?>
    <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded">
        <div class="flex items-center gap-2">
            <i class="fas fa-exclamation-circle"></i>
            <span><?php echo $error; ?></span>
        </div>
    </div>
    <?php endif; ?>

    <!-- Users Table -->
    <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-4 text-left text-sm font-medium text-gray-600">User</th>
                        <th class="px-6 py-4 text-left text-sm font-medium text-gray-600">Role</th>
                        <th class="px-6 py-4 text-left text-sm font-medium text-gray-600">Status</th>
                        <th class="px-6 py-4 text-left text-sm font-medium text-gray-600">Last Login</th>
                        <th class="px-6 py-4 text-center text-sm font-medium text-gray-600">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    <?php foreach ($users as $user): ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 bg-purple-100 rounded-full flex items-center justify-center">
                                    <i class="fas fa-user text-purple-600"></i>
                                </div>
                                <div>
                                    <p class="font-medium text-gray-800"><?php echo htmlspecialchars($user['full_name']); ?></p>
                                    <p class="text-sm text-gray-500"><?php echo htmlspecialchars($user['username']); ?></p>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <span class="px-3 py-1 rounded-full text-xs font-medium
                                <?php echo $user['role'] === 'admin' ? 'bg-red-100 text-red-700' : 
                                    ($user['role'] === 'manager' ? 'bg-blue-100 text-blue-700' : 'bg-green-100 text-green-700'); ?>">
                                <?php echo ucfirst($user['role']); ?>
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            <span class="px-3 py-1 rounded-full text-xs font-medium
                                <?php echo $user['is_active'] ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-700'; ?>">
                                <?php echo $user['is_active'] ? 'Active' : 'Inactive'; ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-600">
                            <?php echo $user['last_login'] ? date('M j, Y g:i A', strtotime($user['last_login'])) : 'Never'; ?>
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex justify-center gap-2">
                                <button onclick="editUser(<?php echo htmlspecialchars(json_encode($user)); ?>)" 
                                    class="w-8 h-8 bg-blue-100 text-blue-600 rounded-lg flex items-center justify-center hover:bg-blue-200 transition-colors"
                                    title="Edit">
                                    <i class="fas fa-edit text-sm"></i>
                                </button>
                                <button onclick="resetPassword(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['username']); ?>')" 
                                    class="w-8 h-8 bg-yellow-100 text-yellow-600 rounded-lg flex items-center justify-center hover:bg-yellow-200 transition-colors"
                                    title="Reset Password">
                                    <i class="fas fa-key text-sm"></i>
                                </button>
                                <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                <form method="POST" class="inline" onsubmit="return confirm('Delete this user?')">
                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                    <button type="submit" name="delete_user" 
                                        class="w-8 h-8 bg-red-100 text-red-600 rounded-lg flex items-center justify-center hover:bg-red-200 transition-colors"
                                        title="Delete">
                                        <i class="fas fa-trash text-sm"></i>
                                    </button>
                                </form>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Role Legend -->
    <div class="bg-white rounded-xl p-4 shadow">
        <h4 class="text-sm font-medium text-gray-700 mb-3">Role Permissions</h4>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
            <div class="flex items-center gap-2">
                <span class="px-2 py-1 bg-red-100 text-red-700 rounded text-xs font-medium">Admin</span>
                <span class="text-gray-600">Full access to all features</span>
            </div>
            <div class="flex items-center gap-2">
                <span class="px-2 py-1 bg-blue-100 text-blue-700 rounded text-xs font-medium">Manager</span>
                <span class="text-gray-600">Can manage sales, inventory, menu</span>
            </div>
            <div class="flex items-center gap-2">
                <span class="px-2 py-1 bg-green-100 text-green-700 rounded text-xs font-medium">Cashier</span>
                <span class="text-gray-600">Can record sales only</span>
            </div>
        </div>
    </div>
</div>

<!-- Add User Modal -->
<div id="addUserModal" class="fixed inset-0 bg-black/50 z-50 hidden flex items-center justify-center">
    <div class="bg-white rounded-2xl w-full max-w-md p-6 m-4">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-xl font-bold text-gray-800">Add New User</h3>
            <button onclick="closeModal('addUserModal')" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        <form method="POST" class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Full Name</label>
                <input type="text" name="full_name" required 
                    class="w-full px-4 py-2 border rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Username</label>
                <input type="text" name="username" required 
                    class="w-full px-4 py-2 border rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                <input type="email" name="email" 
                    class="w-full px-4 py-2 border rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                <input type="password" name="password" required minlength="6"
                    class="w-full px-4 py-2 border rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500">
                <p class="text-xs text-gray-500 mt-1">Minimum 6 characters</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Role</label>
                <select name="role" required 
                    class="w-full px-4 py-2 border rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="cashier">Cashier</option>
                    <option value="manager">Manager</option>
                    <?php if ($_SESSION['user_role'] === 'admin'): ?>
                    <option value="admin">Admin</option>
                    <?php endif; ?>
                </select>
            </div>
            <button type="submit" name="add_user" class="w-full bg-blue-600 text-white py-3 rounded-xl font-medium hover:bg-blue-700 transition-colors">
                Create User
            </button>
        </form>
    </div>
</div>

<!-- Edit User Modal -->
<div id="editUserModal" class="fixed inset-0 bg-black/50 z-50 hidden flex items-center justify-center">
    <div class="bg-white rounded-2xl w-full max-w-md p-6 m-4">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-xl font-bold text-gray-800">Edit User</h3>
            <button onclick="closeModal('editUserModal')" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        <form method="POST" class="space-y-4">
            <input type="hidden" name="user_id" id="editUserId">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Full Name</label>
                <input type="text" name="full_name" id="editFullName" required 
                    class="w-full px-4 py-2 border rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                <input type="email" name="email" id="editEmail" 
                    class="w-full px-4 py-2 border rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Role</label>
                <select name="role" id="editRole" required 
                    class="w-full px-4 py-2 border rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="cashier">Cashier</option>
                    <option value="manager">Manager</option>
                    <?php if ($_SESSION['user_role'] === 'admin'): ?>
                    <option value="admin">Admin</option>
                    <?php endif; ?>
                </select>
            </div>
            <div class="flex items-center gap-2">
                <input type="checkbox" name="is_active" id="editIsActive" class="w-4 h-4 rounded">
                <label class="text-sm text-gray-700">Active</label>
            </div>
            <button type="submit" name="update_user" class="w-full bg-blue-600 text-white py-3 rounded-xl font-medium hover:bg-blue-700 transition-colors">
                Update User
            </button>
        </form>
    </div>
</div>

<!-- Reset Password Modal -->
<div id="resetPasswordModal" class="fixed inset-0 bg-black/50 z-50 hidden flex items-center justify-center">
    <div class="bg-white rounded-2xl w-full max-w-md p-6 m-4">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-xl font-bold text-gray-800">Reset Password</h3>
            <button onclick="closeModal('resetPasswordModal')" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        <form method="POST" class="space-y-4">
            <input type="hidden" name="user_id" id="resetUserId">
            <p class="text-sm text-gray-600">Resetting password for: <strong id="resetUsername"></strong></p>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">New Password</label>
                <input type="password" name="new_password" required minlength="6"
                    class="w-full px-4 py-2 border rounded-xl focus:outline-none focus:ring-2 focus:ring-yellow-500">
                <p class="text-xs text-gray-500 mt-1">Minimum 6 characters</p>
            </div>
            <button type="submit" name="reset_password" class="w-full bg-yellow-600 text-white py-3 rounded-xl font-medium hover:bg-yellow-700 transition-colors">
                Reset Password
            </button>
        </form>
    </div>
</div>

<script>
    function openModal(modalId) {
        document.getElementById(modalId).classList.remove('hidden');
    }

    function closeModal(modalId) {
        document.getElementById(modalId).classList.add('hidden');
    }

    function editUser(user) {
        document.getElementById('editUserId').value = user.id;
        document.getElementById('editFullName').value = user.full_name;
        document.getElementById('editEmail').value = user.email || '';
        document.getElementById('editRole').value = user.role;
        document.getElementById('editIsActive').checked = user.is_active == 1;
        openModal('editUserModal');
    }

    function resetPassword(userId, username) {
        document.getElementById('resetUserId').value = userId;
        document.getElementById('resetUsername').textContent = username;
        openModal('resetPasswordModal');
    }

    window.onclick = function(event) {
        if (event.target.classList.contains('fixed')) {
            event.target.classList.add('hidden');
        }
    }
</script>

<?php require_once 'canteen_footer.php'; ?>
