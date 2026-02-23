<?php
// Database Fix Script - Add missing columns
require_once 'canteen_config.php';

$results = [];

try {
    // Create expenses table if not exists
    $pdo->exec("CREATE TABLE IF NOT EXISTS expenses (
        id INT AUTO_INCREMENT PRIMARY KEY,
        expense_type VARCHAR(50) NOT NULL,
        description TEXT,
        amount DECIMAL(10,2) NOT NULL,
        expense_date DATE NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    $results[] = ['status' => 'exists', 'message' => "Expenses table ready", 'icon' => 'check', 'color' => 'green'];

    // Create inventory_transactions table if not exists
    $pdo->exec("CREATE TABLE IF NOT EXISTS inventory_transactions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        ingredient_id INT NOT NULL,
        transaction_type ENUM('purchase', 'usage', 'adjustment', 'waste') NOT NULL,
        quantity DECIMAL(10,2) NOT NULL,
        unit_cost DECIMAL(10,2),
        total_cost DECIMAL(10,2),
        transaction_date DATE NOT NULL,
        notes TEXT,
        FOREIGN KEY (ingredient_id) REFERENCES ingredients(id)
    )");
    $results[] = ['status' => 'exists', 'message' => "Inventory transactions table ready", 'icon' => 'check', 'color' => 'green'];

    // Create menu_item_ingredients table if not exists
    $pdo->exec("CREATE TABLE IF NOT EXISTS menu_item_ingredients (
        id INT AUTO_INCREMENT PRIMARY KEY,
        menu_item_id INT NOT NULL,
        ingredient_id INT NOT NULL,
        quantity_required DECIMAL(10,2) NOT NULL,
        FOREIGN KEY (menu_item_id) REFERENCES menu_items(id) ON DELETE CASCADE,
        FOREIGN KEY (ingredient_id) REFERENCES ingredients(id) ON DELETE CASCADE
    )");
    $results[] = ['status' => 'exists', 'message' => "Menu item ingredients table ready", 'icon' => 'check', 'color' => 'green'];

    // Create users table if not exists
    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        full_name VARCHAR(100) NOT NULL,
        email VARCHAR(100),
        role ENUM('admin', 'manager', 'cashier') DEFAULT 'cashier',
        is_active BOOLEAN DEFAULT TRUE,
        last_login TIMESTAMP NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    $results[] = ['status' => 'exists', 'message' => "Users table ready", 'icon' => 'check', 'color' => 'green'];

    // Insert default admin user if no users exist
    $userCount = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    if ($userCount == 0) {
        $defaultPassword = password_hash('admin123', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (username, password, full_name, email, role) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute(['admin', $defaultPassword, 'System Administrator', 'admin@canteen.com', 'admin']);
        $results[] = ['status' => 'added', 'message' => "Default admin user created (admin/admin123)", 'icon' => 'plus', 'color' => 'blue'];
    }
    // Check and add is_active column to menu_items
    $result = $pdo->query("SHOW COLUMNS FROM menu_items LIKE 'is_active'");
    if ($result->rowCount() == 0) {
        $pdo->exec("ALTER TABLE menu_items ADD COLUMN is_active BOOLEAN DEFAULT TRUE");
        $results[] = ['status' => 'added', 'message' => "Added 'is_active' column to menu_items", 'icon' => 'plus', 'color' => 'blue'];
    } else {
        $results[] = ['status' => 'exists', 'message' => "'is_active' column already exists in menu_items", 'icon' => 'check', 'color' => 'green'];
    }

    // Check and add total_amount column to sales
    $result = $pdo->query("SHOW COLUMNS FROM sales LIKE 'total_amount'");
    if ($result->rowCount() == 0) {
        $pdo->exec("ALTER TABLE sales ADD COLUMN total_amount DECIMAL(10,2) NOT NULL DEFAULT 0");
        $results[] = ['status' => 'added', 'message' => "Added 'total_amount' column to sales", 'icon' => 'plus', 'color' => 'blue'];
        
        $checkColumns = $pdo->query("SHOW COLUMNS FROM sales WHERE Field IN ('quantity_sold', 'unit_price')");
        $columns = $checkColumns->fetchAll(PDO::FETCH_COLUMN);
        if (in_array('quantity_sold', $columns) && in_array('unit_price', $columns)) {
            $pdo->exec("UPDATE sales SET total_amount = COALESCE(quantity_sold, 0) * COALESCE(unit_price, 0)");
            $results[] = ['status' => 'updated', 'message' => "Updated existing sales records with calculated total_amount", 'icon' => 'sync', 'color' => 'purple'];
        } else {
            $results[] = ['status' => 'skipped', 'message' => "Skipped updating sales records: required columns not found", 'icon' => 'exclamation', 'color' => 'yellow'];
        }
    } else {
        $results[] = ['status' => 'exists', 'message' => "'total_amount' column already exists in sales", 'icon' => 'check', 'color' => 'green'];
    }

    // Check and add category column to menu_items
    $result = $pdo->query("SHOW COLUMNS FROM menu_items LIKE 'category'");
    if ($result->rowCount() == 0) {
        $pdo->exec("ALTER TABLE menu_items ADD COLUMN category VARCHAR(50) DEFAULT 'Main'");
        $results[] = ['status' => 'added', 'message' => "Added 'category' column to menu_items", 'icon' => 'plus', 'color' => 'blue'];
    } else {
        $results[] = ['status' => 'exists', 'message' => "'category' column already exists in menu_items", 'icon' => 'check', 'color' => 'green'];
    }

    // Check and add description column to menu_items
    $result = $pdo->query("SHOW COLUMNS FROM menu_items LIKE 'description'");
    if ($result->rowCount() == 0) {
        $pdo->exec("ALTER TABLE menu_items ADD COLUMN description TEXT");
        $results[] = ['status' => 'added', 'message' => "Added 'description' column to menu_items", 'icon' => 'plus', 'color' => 'blue'];
    } else {
        $results[] = ['status' => 'exists', 'message' => "'description' column already exists in menu_items", 'icon' => 'check', 'color' => 'green'];
    }

    // Check and add reorder_level column to ingredients
    $result = $pdo->query("SHOW COLUMNS FROM ingredients LIKE 'reorder_level'");
    if ($result->rowCount() == 0) {
        $pdo->exec("ALTER TABLE ingredients ADD COLUMN reorder_level DECIMAL(10,2) DEFAULT 10");
        $results[] = ['status' => 'added', 'message' => "Added 'reorder_level' column to ingredients", 'icon' => 'plus', 'color' => 'blue'];
    } else {
        $results[] = ['status' => 'exists', 'message' => "'reorder_level' column already exists in ingredients", 'icon' => 'check', 'color' => 'green'];
    }

    // Check and add stock_quantity column to ingredients
    $result = $pdo->query("SHOW COLUMNS FROM ingredients LIKE 'stock_quantity'");
    if ($result->rowCount() == 0) {
        $pdo->exec("ALTER TABLE ingredients ADD COLUMN stock_quantity DECIMAL(10,2) DEFAULT 0");
        $results[] = ['status' => 'added', 'message' => "Added 'stock_quantity' column to ingredients", 'icon' => 'plus', 'color' => 'blue'];
    } else {
        $results[] = ['status' => 'exists', 'message' => "'stock_quantity' column already exists in ingredients", 'icon' => 'check', 'color' => 'green'];
    }

    // Check and add unit_cost column to ingredients
    $result = $pdo->query("SHOW COLUMNS FROM ingredients LIKE 'unit_cost'");
    if ($result->rowCount() == 0) {
        $pdo->exec("ALTER TABLE ingredients ADD COLUMN unit_cost DECIMAL(10,2) NOT NULL DEFAULT 0");
        $results[] = ['status' => 'added', 'message' => "Added 'unit_cost' column to ingredients", 'icon' => 'plus', 'color' => 'blue'];
    } else {
        $results[] = ['status' => 'exists', 'message' => "'unit_cost' column already exists in ingredients", 'icon' => 'check', 'color' => 'green'];
    }

    // Check and add sale_date column to sales
    $result = $pdo->query("SHOW COLUMNS FROM sales LIKE 'sale_date'");
    if ($result->rowCount() == 0) {
        $pdo->exec("ALTER TABLE sales ADD COLUMN sale_date DATE NOT NULL DEFAULT (CURDATE())");
        $results[] = ['status' => 'added', 'message' => "Added 'sale_date' column to sales", 'icon' => 'plus', 'color' => 'blue'];
    } else {
        $results[] = ['status' => 'exists', 'message' => "'sale_date' column already exists in sales", 'icon' => 'check', 'color' => 'green'];
    }

    // Check and add sale_time column to sales
    $result = $pdo->query("SHOW COLUMNS FROM sales LIKE 'sale_time'");
    if ($result->rowCount() == 0) {
        $pdo->exec("ALTER TABLE sales ADD COLUMN sale_time TIME NOT NULL DEFAULT (CURTIME())");
        $results[] = ['status' => 'added', 'message' => "Added 'sale_time' column to sales", 'icon' => 'plus', 'color' => 'blue'];
    } else {
        $results[] = ['status' => 'exists', 'message' => "'sale_time' column already exists in sales", 'icon' => 'check', 'color' => 'green'];
    }

    // Check and add quantity_sold column to sales
    $result = $pdo->query("SHOW COLUMNS FROM sales LIKE 'quantity_sold'");
    if ($result->rowCount() == 0) {
        $pdo->exec("ALTER TABLE sales ADD COLUMN quantity_sold INT NOT NULL DEFAULT 1");
        $results[] = ['status' => 'added', 'message' => "Added 'quantity_sold' column to sales", 'icon' => 'plus', 'color' => 'blue'];
    } else {
        $results[] = ['status' => 'exists', 'message' => "'quantity_sold' column already exists in sales", 'icon' => 'check', 'color' => 'green'];
    }

    // Check and add unit_price column to sales
    $result = $pdo->query("SHOW COLUMNS FROM sales LIKE 'unit_price'");
    if ($result->rowCount() == 0) {
        $pdo->exec("ALTER TABLE sales ADD COLUMN unit_price DECIMAL(10,2) NOT NULL DEFAULT 0");
        $results[] = ['status' => 'added', 'message' => "Added 'unit_price' column to sales", 'icon' => 'plus', 'color' => 'blue'];
    } else {
        $results[] = ['status' => 'exists', 'message' => "'unit_price' column already exists in sales", 'icon' => 'check', 'color' => 'green'];
    }

    // Check and add menu_item_id column to sales
    $result = $pdo->query("SHOW COLUMNS FROM sales LIKE 'menu_item_id'");
    if ($result->rowCount() == 0) {
        $pdo->exec("ALTER TABLE sales ADD COLUMN menu_item_id INT NOT NULL DEFAULT 1");
        $results[] = ['status' => 'added', 'message' => "Added 'menu_item_id' column to sales", 'icon' => 'plus', 'color' => 'blue'];
    } else {
        $results[] = ['status' => 'exists', 'message' => "'menu_item_id' column already exists in sales", 'icon' => 'check', 'color' => 'green'];
    }

} catch (PDOException $e) {
    $error = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Fix - SmartCanteen</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gradient-to-br from-purple-600 via-blue-600 to-teal-500 min-h-screen flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl shadow-2xl w-full max-w-2xl overflow-hidden">
        <!-- Header -->
        <div class="bg-gradient-to-r from-purple-600 to-pink-600 p-8 text-center">
            <div class="w-20 h-20 bg-white/20 rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-database text-4xl text-white"></i>
            </div>
            <h1 class="text-3xl font-bold text-white">Database Fix Script</h1>
            <p class="text-purple-200 mt-2">Checking and repairing database schema</p>
        </div>
        
        <!-- Content -->
        <div class="p-8">
            <?php if (isset($error)): ?>
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded-xl mb-6">
                <div class="flex items-center gap-2">
                    <i class="fas fa-exclamation-circle text-xl"></i>
                    <span class="font-semibold"><?php echo $error; ?></span>
                </div>
            </div>
            <?php else: ?>
            <div class="space-y-3">
                <?php foreach ($results as $item): ?>
                <div class="flex items-center gap-3 p-4 rounded-xl
                    <?php echo $item['color'] === 'blue' ? 'bg-blue-50 border border-blue-200' : 
                        ($item['color'] === 'green' ? 'bg-green-50 border border-green-200' : 
                        ($item['color'] === 'purple' ? 'bg-purple-50 border border-purple-200' : 
                        'bg-yellow-50 border border-yellow-200')); ?>">
                    <div class="w-10 h-10 rounded-full flex items-center justify-center
                        <?php echo $item['color'] === 'blue' ? 'bg-blue-500' : 
                            ($item['color'] === 'green' ? 'bg-green-500' : 
                            ($item['color'] === 'purple' ? 'bg-purple-500' : 
                            'bg-yellow-500')); ?>">
                        <i class="fas fa-<?php echo $item['icon']; ?> text-white"></i>
                    </div>
                    <span class="text-gray-700 font-medium"><?php echo $item['message']; ?></span>
                </div>
                <?php endforeach; ?>
            </div>
            
            <div class="mt-8 text-center">
                <div class="inline-flex items-center gap-2 bg-green-100 text-green-700 px-6 py-3 rounded-full mb-6">
                    <i class="fas fa-check-circle text-xl"></i>
                    <span class="font-semibold">Database fix completed successfully!</span>
                </div>
                <br>
                <a href="canteen_dashboard.php" class="inline-flex items-center gap-2 bg-gradient-to-r from-purple-600 to-pink-600 text-white px-8 py-4 rounded-xl font-semibold text-lg hover:shadow-lg hover:scale-105 transition-all">
                    <span>Go to Dashboard</span>
                    <i class="fas fa-arrow-right"></i>
                </a>
            </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
