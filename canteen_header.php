<?php
// Common header for all canteen pages
require_once 'canteen_config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle ?? 'Canteen Management'; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#3b82f6',
                        secondary: '#8b5cf6',
                        success: '#10b981',
                        danger: '#ef4444',
                        warning: '#f59e0b',
                    }
                }
            }
        }
    </script>
    <style>
        .glass-effect {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
        }
        .gradient-bg {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .card-hover {
            transition: all 0.3s ease;
        }
        .card-hover:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
        }
        .sidebar-link {
            transition: all 0.3s ease;
        }
        .sidebar-link:hover, .sidebar-link.active {
            background: linear-gradient(135deg, #3b82f6 0%, #8b5cf6 100%);
            color: white;
        }
        .animate-fade-in {
            animation: fadeIn 0.5s ease-in;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .pulse-dot {
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">
    <!-- Sidebar -->
    <div class="fixed left-0 top-0 h-full w-64 gradient-bg text-white z-50 shadow-2xl">
        <div class="p-6">
            <div class="flex items-center gap-3 mb-8">
                <div class="w-12 h-12 bg-white rounded-xl flex items-center justify-center">
                    <i class="fas fa-utensils text-2xl text-purple-600"></i>
                </div>
                <div>
                    <h1 class="text-xl font-bold">SmartCanteen</h1>
                    <p class="text-xs text-purple-200">AI-Powered</p>
                </div>
            </div>
            
            <nav class="space-y-2">
                <a href="canteen_dashboard.php" class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-xl <?php echo basename($_SERVER['PHP_SELF']) == 'canteen_dashboard.php' ? 'active' : ''; ?>">
                    <i class="fas fa-chart-line w-5"></i>
                    <span>Dashboard</span>
                </a>
                <a href="canteen_sales.php" class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-xl <?php echo basename($_SERVER['PHP_SELF']) == 'canteen_sales.php' ? 'active' : ''; ?>">
                    <i class="fas fa-cash-register w-5"></i>
                    <span>Sales & Inventory</span>
                </a>
                <a href="canteen_menu.php" class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-xl <?php echo basename($_SERVER['PHP_SELF']) == 'canteen_menu.php' ? 'active' : ''; ?>">
                    <i class="fas fa-book-open w-5"></i>
                    <span>Menu Builder</span>
                </a>
                <a href="canteen_ai.php" class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-xl <?php echo basename($_SERVER['PHP_SELF']) == 'canteen_ai.php' ? 'active' : ''; ?>">
                    <i class="fas fa-robot w-5"></i>
                    <span>AI Assistant</span>
                    <span class="ml-auto bg-green-400 text-xs px-2 py-1 rounded-full pulse-dot">AI</span>
                </a>
                <?php if (in_array($_SESSION['user_role'] ?? '', ['admin', 'manager'])): ?>
                <a href="canteen_users.php" class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-xl <?php echo basename($_SERVER['PHP_SELF']) == 'canteen_users.php' ? 'active' : ''; ?>">
                    <i class="fas fa-users w-5"></i>
                    <span>User Management</span>
                </a>
                <?php endif; ?>
            </nav>
        </div>
        
        <div class="absolute bottom-0 left-0 right-0 p-6">
            <div class="bg-white/10 rounded-xl p-4 cursor-pointer hover:bg-white/20 transition-colors" onclick="toggleAdminMenu()">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-white/20 rounded-full flex items-center justify-center">
                        <i class="fas fa-user"></i>
                    </div>
                    <div class="flex-1">
                        <p class="font-medium">Admin</p>
                        <p class="text-xs text-purple-200">Canteen Manager</p>
                    </div>
                    <i class="fas fa-chevron-up text-purple-200"></i>
                </div>
                <!-- Admin Dropdown -->
                <div id="adminMenu" class="hidden mt-3 pt-3 border-t border-white/20 space-y-2">
                    <a href="canteen_profile.php" class="flex items-center gap-2 text-sm text-purple-100 hover:text-white py-1">
                        <i class="fas fa-user w-4"></i> My Profile
                    </a>
                    <a href="canteen_dashboard.php" class="flex items-center gap-2 text-sm text-purple-100 hover:text-white py-1">
                        <i class="fas fa-cog w-4"></i> Settings
                    </a>
                    <a href="canteen_login.php?logout=1" class="flex items-center gap-2 text-sm text-purple-100 hover:text-white py-1">
                        <i class="fas fa-sign-out-alt w-4"></i> Logout
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="ml-64 min-h-screen">
        <!-- Top Bar -->
        <header class="glass-effect sticky top-0 z-40 px-8 py-4 shadow-sm">
            <div class="flex justify-between items-center">
                <div>
                    <h2 class="text-2xl font-bold text-gray-800"><?php echo $pageTitle ?? 'Dashboard'; ?></h2>
                    <p class="text-gray-500 text-sm"><?php echo date('l, F j, Y'); ?></p>
                </div>
                <div class="flex items-center gap-4">
                    <button onclick="toggleAIChat()" class="bg-gradient-to-r from-purple-500 to-pink-500 text-white px-4 py-2 rounded-xl flex items-center gap-2 hover:shadow-lg transition-all">
                        <i class="fas fa-robot"></i>
                        <span>Ask AI</span>
                    </button>
                    
                    <!-- Notification Bell -->
                    <div class="relative" id="notificationDropdown">
                        <button onclick="toggleNotifications()" class="w-10 h-10 bg-gray-100 rounded-full flex items-center justify-center hover:bg-gray-200 transition-colors relative">
                            <i class="fas fa-bell text-gray-600"></i>
                            <span class="absolute -top-1 -right-1 w-5 h-5 bg-red-500 text-white text-xs rounded-full flex items-center justify-center">3</span>
                        </button>
                        <!-- Notification Dropdown -->
                        <div id="notificationMenu" class="hidden absolute right-0 mt-2 w-80 bg-white rounded-xl shadow-2xl z-50 border">
                            <div class="p-4 border-b">
                                <h4 class="font-bold text-gray-800">Notifications</h4>
                            </div>
                            <div class="max-h-64 overflow-y-auto">
                                <a href="canteen_sales.php" class="block p-4 hover:bg-gray-50 border-b">
                                    <div class="flex items-start gap-3">
                                        <div class="w-8 h-8 bg-red-100 rounded-full flex items-center justify-center flex-shrink-0">
                                            <i class="fas fa-exclamation-triangle text-red-500 text-sm"></i>
                                        </div>
                                        <div>
                                            <p class="text-sm font-medium text-gray-800">Low Stock Alert</p>
                                            <p class="text-xs text-gray-500">Some ingredients are running low</p>
                                        </div>
                                    </div>
                                </a>
                                <a href="canteen_dashboard.php" class="block p-4 hover:bg-gray-50 border-b">
                                    <div class="flex items-start gap-3">
                                        <div class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center flex-shrink-0">
                                            <i class="fas fa-chart-line text-green-500 text-sm"></i>
                                        </div>
                                        <div>
                                            <p class="text-sm font-medium text-gray-800">Daily Report Ready</p>
                                            <p class="text-xs text-gray-500">View today's sales summary</p>
                                        </div>
                                    </div>
                                </a>
                                <a href="canteen_menu.php" class="block p-4 hover:bg-gray-50">
                                    <div class="flex items-start gap-3">
                                        <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center flex-shrink-0">
                                            <i class="fas fa-lightbulb text-blue-500 text-sm"></i>
                                        </div>
                                        <div>
                                            <p class="text-sm font-medium text-gray-800">AI Suggestion</p>
                                            <p class="text-xs text-gray-500">Check menu pricing recommendations</p>
                                        </div>
                                    </div>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <!-- Page Content -->
        <main class="p-8">
