<?php
require_once 'canteen_config.php';

// Handle logout
if (isset($_GET['logout'])) {
    logoutUser();
}

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: canteen_dashboard.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if (loginUser($pdo, $username, $password)) {
        header('Location: canteen_dashboard.php');
        exit;
    } else {
        $error = 'Invalid username or password';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - SmartCanteen</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .gradient-bg {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .glass-effect {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
        }
    </style>
</head>
<body class="gradient-bg min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-md">
        <!-- Logo Section -->
        <div class="text-center mb-8">
            <div class="w-20 h-20 bg-white rounded-2xl flex items-center justify-center mx-auto mb-4 shadow-lg">
                <i class="fas fa-utensils text-4xl text-purple-600"></i>
            </div>
            <h1 class="text-3xl font-bold text-white">SmartCanteen</h1>
            <p class="text-purple-200 mt-2">AI-Powered Management System</p>
        </div>

        <!-- Login Form -->
        <div class="glass-effect rounded-3xl shadow-2xl p-8">
            <h2 class="text-2xl font-bold text-gray-800 text-center mb-6">Welcome Back</h2>
            
            <?php if ($error): ?>
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded mb-6">
                <div class="flex items-center gap-2">
                    <i class="fas fa-exclamation-circle"></i>
                    <span><?php echo htmlspecialchars($error); ?></span>
                </div>
            </div>
            <?php endif; ?>

            <form method="POST" class="space-y-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Username</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-user text-gray-400"></i>
                        </div>
                        <input type="text" name="username" required 
                            class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                            placeholder="Enter your username">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Password</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-lock text-gray-400"></i>
                        </div>
                        <input type="password" name="password" required 
                            class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                            placeholder="Enter your password">
                    </div>
                </div>

                <button type="submit" 
                    class="w-full bg-gradient-to-r from-purple-600 to-pink-600 text-white py-3 rounded-xl font-semibold text-lg hover:shadow-lg hover:scale-[1.02] transition-all">
                    <i class="fas fa-sign-in-alt mr-2"></i>Sign In
                </button>
            </form>

            <div class="mt-6 text-center text-sm text-gray-500">
                <p>Default login: <strong>admin</strong> / <strong>admin123</strong></p>
            </div>
        </div>

        <!-- Footer -->
        <p class="text-center text-purple-200 text-sm mt-8">
            &copy; <?php echo date('Y'); ?> SmartCanteen. All rights reserved.
        </p>
    </div>
</body>
</html>
