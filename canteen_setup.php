<?php
require_once 'canteen_config.php';

$results = [];

try {
    // Drop existing tables (if any)
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
    $pdo->exec("DROP TABLE IF EXISTS inventory_transactions");
    $pdo->exec("DROP TABLE IF EXISTS expenses");
    $pdo->exec("DROP TABLE IF EXISTS sales");
    $pdo->exec("DROP TABLE IF EXISTS menu_item_ingredients");
    $pdo->exec("DROP TABLE IF EXISTS menu_items");
    $pdo->exec("DROP TABLE IF EXISTS ingredients");
    $pdo->exec("DROP TABLE IF EXISTS users");
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    $results[] = ['status' => 'success', 'message' => 'Cleared existing tables', 'icon' => 'trash', 'color' => 'gray'];

    // Create users table
    $pdo->exec("CREATE TABLE users (
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
    $results[] = ['status' => 'success', 'message' => 'Created users table', 'icon' => 'check', 'color' => 'green'];

    // Create ingredients table
    $pdo->exec("CREATE TABLE ingredients (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        unit VARCHAR(20) NOT NULL,
        stock_quantity DECIMAL(10,2) DEFAULT 0,
        unit_cost DECIMAL(10,2) NOT NULL,
        reorder_level DECIMAL(10,2) DEFAULT 10,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");
    $results[] = ['status' => 'success', 'message' => 'Created ingredients table', 'icon' => 'check', 'color' => 'green'];

    // Create menu_items table
    $pdo->exec("CREATE TABLE menu_items (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        description TEXT,
        selling_price DECIMAL(10,2) NOT NULL,
        category VARCHAR(50) DEFAULT 'Main',
        is_active BOOLEAN DEFAULT TRUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    $results[] = ['status' => 'success', 'message' => 'Created menu_items table', 'icon' => 'check', 'color' => 'green'];

    // Create menu_item_ingredients table
    $pdo->exec("CREATE TABLE menu_item_ingredients (
        id INT AUTO_INCREMENT PRIMARY KEY,
        menu_item_id INT NOT NULL,
        ingredient_id INT NOT NULL,
        quantity_required DECIMAL(10,2) NOT NULL,
        FOREIGN KEY (menu_item_id) REFERENCES menu_items(id) ON DELETE CASCADE,
        FOREIGN KEY (ingredient_id) REFERENCES ingredients(id) ON DELETE CASCADE
    )");
    $results[] = ['status' => 'success', 'message' => 'Created menu_item_ingredients table', 'icon' => 'check', 'color' => 'green'];

    // Create sales table
    $pdo->exec("CREATE TABLE sales (
        id INT AUTO_INCREMENT PRIMARY KEY,
        menu_item_id INT NOT NULL,
        quantity_sold INT NOT NULL,
        unit_price DECIMAL(10,2) NOT NULL,
        total_amount DECIMAL(10,2) NOT NULL,
        sale_date DATE NOT NULL,
        sale_time TIME NOT NULL,
        FOREIGN KEY (menu_item_id) REFERENCES menu_items(id)
    )");
    $results[] = ['status' => 'success', 'message' => 'Created sales table', 'icon' => 'check', 'color' => 'green'];

    // Create expenses table
    $pdo->exec("CREATE TABLE expenses (
        id INT AUTO_INCREMENT PRIMARY KEY,
        expense_type VARCHAR(50) NOT NULL,
        description TEXT,
        amount DECIMAL(10,2) NOT NULL,
        expense_date DATE NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    $results[] = ['status' => 'success', 'message' => 'Created expenses table', 'icon' => 'check', 'color' => 'green'];

    // Create inventory_transactions table
    $pdo->exec("CREATE TABLE inventory_transactions (
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
    $results[] = ['status' => 'success', 'message' => 'Created inventory_transactions table', 'icon' => 'check', 'color' => 'green'];

    // Insert default admin user
    $defaultPassword = password_hash('admin123', PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO users (username, password, full_name, email, role) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute(['admin', $defaultPassword, 'System Administrator', 'admin@canteen.com', 'admin']);
    $results[] = ['status' => 'success', 'message' => 'Created default admin user (admin/admin123)', 'icon' => 'user', 'color' => 'blue'];

    // Insert sample ingredients
    $pdo->exec("INSERT INTO ingredients (name, unit, stock_quantity, unit_cost, reorder_level) VALUES
    ('Rice', 'kg', 50, 45.00, 20),
    ('Chicken Breast', 'kg', 30, 180.00, 10),
    ('Pork Belly', 'kg', 25, 220.00, 8),
    ('Cooking Oil', 'liter', 20, 85.00, 5),
    ('Soy Sauce', 'liter', 10, 45.00, 3),
    ('Vinegar', 'liter', 8, 35.00, 3),
    ('Garlic', 'kg', 5, 120.00, 2),
    ('Onion', 'kg', 8, 80.00, 2),
    ('Tomato', 'kg', 10, 60.00, 3),
    ('Egg', 'piece', 100, 8.00, 30),
    ('Flour', 'kg', 15, 55.00, 5),
    ('Sugar', 'kg', 10, 65.00, 3),
    ('Salt', 'kg', 5, 25.00, 2),
    ('Pepper', 'kg', 2, 450.00, 0.5),
    ('Cabbage', 'kg', 12, 40.00, 5),
    ('Carrot', 'kg', 8, 50.00, 3),
    ('Potato', 'kg', 15, 55.00, 5),
    ('Bell Pepper', 'kg', 6, 120.00, 2),
    ('Cheese', 'kg', 5, 350.00, 2),
    ('Ground Beef', 'kg', 10, 280.00, 3)");
    $results[] = ['status' => 'success', 'message' => 'Inserted sample ingredients', 'icon' => 'box', 'color' => 'purple'];

    // Insert sample menu items
    $pdo->exec("INSERT INTO menu_items (name, description, selling_price, category) VALUES
    ('Adobo Rice Meal', 'Classic Filipino adobo with steamed rice', 85.00, 'Meals'),
    ('Fried Chicken Rice', 'Crispy fried chicken with rice and gravy', 95.00, 'Meals'),
    ('Pork Sinigang', 'Sour pork soup with vegetables', 90.00, 'Meals'),
    ('Beef Steak Rice', 'Beef steak with onions and rice', 110.00, 'Meals'),
    ('Vegetable Stir-fry', 'Mixed vegetables with rice', 70.00, 'Meals'),
    ('Omelette Rice', 'Fluffy omelette with fried rice', 65.00, 'Meals'),
    ('Pancit Canton', 'Stir-fried noodles with vegetables', 75.00, 'Noodles'),
    ('Spaghetti', 'Filipino-style sweet spaghetti', 80.00, 'Pasta'),
    ('Hamburger', 'Beef patty with bun and veggies', 60.00, 'Snacks'),
    ('Cheese Sandwich', 'Grilled cheese sandwich', 45.00, 'Snacks'),
    ('Fried Rice', 'Garlic fried rice', 40.00, 'Sides'),
    ('Soft Drink', 'Assorted soft drinks', 25.00, 'Beverages'),
    ('Iced Tea', 'Homemade iced tea', 30.00, 'Beverages'),
    ('Bottled Water', 'Mineral water', 20.00, 'Beverages')");
    $results[] = ['status' => 'success', 'message' => 'Inserted sample menu items', 'icon' => 'utensils', 'color' => 'orange'];

    // Insert sample recipes
    $pdo->exec("INSERT INTO menu_item_ingredients (menu_item_id, ingredient_id, quantity_required) VALUES
    (1, 2, 0.15), (1, 1, 0.2), (1, 4, 0.02), (1, 5, 0.03), (1, 6, 0.02), (1, 7, 0.01), (1, 8, 0.02),
    (2, 2, 0.18), (2, 1, 0.2), (2, 4, 0.05), (2, 11, 0.03), (2, 13, 0.005), (2, 14, 0.002)");
    $results[] = ['status' => 'success', 'message' => 'Inserted sample recipes', 'icon' => 'book', 'color' => 'yellow'];

    // Insert sample sales
    $pdo->exec("INSERT INTO sales (menu_item_id, quantity_sold, unit_price, total_amount, sale_date, sale_time) VALUES
    (1, 20, 85.00, 1700.00, CURDATE(), '11:30:00'),
    (2, 25, 95.00, 2375.00, CURDATE(), '12:00:00'),
    (3, 15, 90.00, 1350.00, CURDATE(), '12:30:00'),
    (4, 10, 110.00, 1100.00, CURDATE(), '12:45:00')");
    $results[] = ['status' => 'success', 'message' => 'Inserted sample sales', 'icon' => 'cash-register', 'color' => 'green'];

    // Insert sample expenses
    $pdo->exec("INSERT INTO expenses (expense_type, description, amount, expense_date) VALUES
    ('Ingredients', 'Weekly vegetable delivery', 3500.00, DATE_SUB(CURDATE(), INTERVAL 5 DAY)),
    ('Ingredients', 'Meat and poultry purchase', 8500.00, DATE_SUB(CURDATE(), INTERVAL 4 DAY)),
    ('Utilities', 'Electricity bill', 4500.00, DATE_SUB(CURDATE(), INTERVAL 3 DAY))");
    $results[] = ['status' => 'success', 'message' => 'Inserted sample expenses', 'icon' => 'receipt', 'color' => 'red'];

} catch (PDOException $e) {
    $error = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Setup - SmartCanteen</title>
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
            <h1 class="text-3xl font-bold text-white">Database Setup</h1>
            <p class="text-purple-200 mt-2">Creating tables and sample data</p>
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
                <div class="flex items-center gap-3 p-4 rounded-xl bg-<?php echo $item['color']; ?>-50 border border-<?php echo $item['color']; ?>-200">
                    <div class="w-10 h-10 bg-<?php echo $item['color']; ?>-500 rounded-full flex items-center justify-center">
                        <i class="fas fa-<?php echo $item['icon']; ?> text-white"></i>
                    </div>
                    <span class="text-gray-700 font-medium"><?php echo $item['message']; ?></span>
                </div>
                <?php endforeach; ?>
            </div>
            
            <div class="mt-8 text-center">
                <div class="inline-flex items-center gap-2 bg-green-100 text-green-700 px-6 py-3 rounded-full mb-6">
                    <i class="fas fa-check-circle text-xl"></i>
                    <span class="font-semibold">Setup completed successfully!</span>
                </div>
                <br>
                <a href="canteen_login.php" class="inline-flex items-center gap-2 bg-gradient-to-r from-purple-600 to-pink-600 text-white px-8 py-4 rounded-xl font-semibold text-lg hover:shadow-lg hover:scale-105 transition-all">
                    <span>Go to Login</span>
                    <i class="fas fa-arrow-right"></i>
                </a>
                <p class="text-gray-500 text-sm mt-4">Default login: <strong>admin</strong> / <strong>admin123</strong></p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
