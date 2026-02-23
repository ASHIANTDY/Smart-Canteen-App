<?php
// Canteen Management System - Database Configuration

$host = 'localhost';
$dbname = 'canteen_db';
$username = 'root';
$password = '';

try {
    // PDO Connection
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
    // MySQLi Connection (for compatibility)
    $conn = new mysqli($host, $username, $password, $dbname);
    
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
    
} catch (Exception $e) {
    die("Database connection error: " . $e->getMessage());
}

// Helper Functions

function formatCurrency($amount) {
    return '₱' . number_format($amount, 2);
}

function getToday() {
    return date('Y-m-d');
}

function getCurrentMonth() {
    return date('Y-m');
}

// Get dashboard statistics
function getDashboardStats($pdo) {
    $stats = [];
    
    // Today's sales
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(total_amount), 0) as today_sales FROM sales WHERE sale_date = CURDATE()");
    $stmt->execute();
    $stats['today_sales'] = $stmt->fetch()['today_sales'];
    
    // This month's sales
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(total_amount), 0) as month_sales FROM sales WHERE MONTH(sale_date) = MONTH(CURDATE()) AND YEAR(sale_date) = YEAR(CURDATE())");
    $stmt->execute();
    $stats['month_sales'] = $stmt->fetch()['month_sales'];
    
    // Today's expenses
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) as today_expenses FROM expenses WHERE expense_date = CURDATE()");
    $stmt->execute();
    $stats['today_expenses'] = $stmt->fetch()['today_expenses'];
    
    // This month's expenses
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) as month_expenses FROM expenses WHERE MONTH(expense_date) = MONTH(CURDATE()) AND YEAR(expense_date) = YEAR(CURDATE())");
    $stmt->execute();
    $stats['month_expenses'] = $stmt->fetch()['month_expenses'];
    
    // Calculate profit/loss
    $stats['today_profit'] = $stats['today_sales'] - $stats['today_expenses'];
    $stats['month_profit'] = $stats['month_sales'] - $stats['month_expenses'];
    
    // Total menu items
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM menu_items WHERE is_active = 1");
    $stats['total_menu_items'] = $stmt->fetch()['total'];
    
    // Low stock items
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM ingredients WHERE stock_quantity <= reorder_level");
    $stats['low_stock'] = $stmt->fetch()['total'];
    
    // Total ingredients
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM ingredients");
    $stats['total_ingredients'] = $stmt->fetch()['total'];
    
    return $stats;
}

// Get sales data for charts
function getSalesChartData($pdo, $days = 7) {
    $data = [];
    $stmt = $pdo->prepare("
        SELECT sale_date, SUM(total_amount) as total 
        FROM sales 
        WHERE sale_date >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
        GROUP BY sale_date 
        ORDER BY sale_date
    ");
    $stmt->execute([$days]);
    return $stmt->fetchAll();
}

// Get top selling items
function getTopSellingItems($pdo, $limit = 5) {
    $stmt = $pdo->prepare("
        SELECT m.name, SUM(s.quantity_sold) as total_qty, SUM(s.total_amount) as total_sales
        FROM sales s
        JOIN menu_items m ON s.menu_item_id = m.id
        WHERE s.sale_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
        GROUP BY s.menu_item_id, m.name
        ORDER BY total_qty DESC
        LIMIT " . (int)$limit);
    $stmt->execute();
    return $stmt->fetchAll();
}

// Calculate menu item cost
function calculateMenuItemCost($pdo, $menuItemId) {
    $stmt = $pdo->prepare("
        SELECT SUM(mii.quantity_required * i.unit_cost) as total_cost
        FROM menu_item_ingredients mii
        JOIN ingredients i ON mii.ingredient_id = i.id
        WHERE mii.menu_item_id = ?
    ");
    $stmt->execute([$menuItemId]);
    $result = $stmt->fetch();
    return $result['total_cost'] ?? 0;
}

// Get menu items with costs
function getMenuItemsWithCosts($pdo) {
    $stmt = $pdo->query("SELECT * FROM menu_items WHERE is_active = 1 ORDER BY category, name");
    $items = $stmt->fetchAll();
    
    foreach ($items as &$item) {
        $item['cost_price'] = calculateMenuItemCost($pdo, $item['id']);
        $item['profit_margin'] = $item['selling_price'] - $item['cost_price'];
        $item['profit_percentage'] = $item['selling_price'] > 0 
            ? round(($item['profit_margin'] / $item['selling_price']) * 100, 2) 
            : 0;
    }
    
    return $items;
}

// Get inventory status
function getInventoryStatus($pdo) {
    $stmt = $pdo->query("
        SELECT *, 
            CASE 
                WHEN stock_quantity <= reorder_level THEN 'low'
                WHEN stock_quantity <= reorder_level * 1.5 THEN 'medium'
                ELSE 'good'
            END as stock_status
        FROM ingredients 
        ORDER BY name
    ");
    return $stmt->fetchAll();
}

// Get expense breakdown
function getExpenseBreakdown($pdo, $month = null) {
    if (!$month) $month = date('Y-m');
    
    $stmt = $pdo->prepare("
        SELECT expense_type, SUM(amount) as total
        FROM expenses
        WHERE DATE_FORMAT(expense_date, '%Y-%m') = ?
        GROUP BY expense_type
        ORDER BY total DESC
    ");
    $stmt->execute([$month]);
    return $stmt->fetchAll();
}

// API Response helper
function jsonResponse($data, $status = 200) {
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

// Session and Authentication Functions
session_start();

function isLoggedIn() {
    return isset($_SESSION['user_id']) && $_SESSION['user_id'] > 0;
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: canteen_login.php');
        exit;
    }
}

function requireRole($roles) {
    requireLogin();
    if (!is_array($roles)) $roles = [$roles];
    if (!in_array($_SESSION['user_role'], $roles)) {
        header('Location: canteen_dashboard.php?error=unauthorized');
        exit;
    }
}

function getCurrentUser($pdo) {
    if (!isLoggedIn()) return null;
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch();
}

function loginUser($pdo, $username, $password) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? AND is_active = 1");
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_username'] = $user['username'];
        $_SESSION['user_name'] = $user['full_name'];
        $_SESSION['user_role'] = $user['role'];
        
        // Update last login
        $stmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
        $stmt->execute([$user['id']]);
        
        return true;
    }
    return false;
}

function logoutUser() {
    session_destroy();
    header('Location: canteen_login.php');
    exit;
}
?>
