<?php
$pageTitle = 'Sales & Inventory Tracker';
require_once 'canteen_config.php';
requireLogin();
require_once 'canteen_header.php';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_sale'])) {
        // Add new sale
        $menu_item_id = $_POST['menu_item_id'];
        $quantity = $_POST['quantity'];
        
        // Get menu item price
        $stmt = $pdo->prepare("SELECT selling_price FROM menu_items WHERE id = ?");
        $stmt->execute([$menu_item_id]);
        $item = $stmt->fetch();
        
        if ($item) {
            $unit_price = $item['selling_price'];
            $total_amount = $unit_price * $quantity;
            
            $stmt = $pdo->prepare("
                INSERT INTO sales (menu_item_id, quantity_sold, unit_price, total_amount, sale_date, sale_time) 
                VALUES (?, ?, ?, ?, CURDATE(), CURTIME())
            ");
            $stmt->execute([$menu_item_id, $quantity, $unit_price, $total_amount]);
            
            // Deduct from inventory
            $stmt = $pdo->prepare("
                SELECT ingredient_id, quantity_required 
                FROM menu_item_ingredients 
                WHERE menu_item_id = ?
            ");
            $stmt->execute([$menu_item_id]);
            $ingredients = $stmt->fetchAll();
            
            foreach ($ingredients as $ing) {
                $used_qty = $ing['quantity_required'] * $quantity;
                
                // Update stock
                $stmt = $pdo->prepare("
                    UPDATE ingredients 
                    SET stock_quantity = stock_quantity - ? 
                    WHERE id = ?
                ");
                $stmt->execute([$used_qty, $ing['ingredient_id']]);
                
                // Record transaction
                $stmt = $pdo->prepare("
                    INSERT INTO inventory_transactions 
                    (ingredient_id, transaction_type, quantity, transaction_date, notes) 
                    VALUES (?, 'usage', ?, CURDATE(), 'Sale transaction')
                ");
                $stmt->execute([$ing['ingredient_id'], $used_qty]);
            }
            
            $success = "Sale recorded successfully!";
        }
    } elseif (isset($_POST['add_expense'])) {
        // Add expense
        $stmt = $pdo->prepare("
            INSERT INTO expenses (expense_type, description, amount, expense_date) 
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([
            $_POST['expense_type'],
            $_POST['description'],
            $_POST['amount'],
            $_POST['expense_date']
        ]);
        $success = "Expense recorded successfully!";
    } elseif (isset($_POST['restock'])) {
        // Restock ingredient
        $stmt = $pdo->prepare("
            UPDATE ingredients 
            SET stock_quantity = stock_quantity + ? 
            WHERE id = ?
        ");
        $stmt->execute([$_POST['restock_qty'], $_POST['ingredient_id']]);
        
        // Record transaction
        $stmt = $pdo->prepare("
            INSERT INTO inventory_transactions 
            (ingredient_id, transaction_type, quantity, unit_cost, total_cost, transaction_date, notes) 
            VALUES (?, 'purchase', ?, ?, ?, ?, ?)
        ");
        $total_cost = $_POST['restock_qty'] * $_POST['unit_cost'];
        $stmt->execute([
            $_POST['ingredient_id'],
            $_POST['restock_qty'],
            $_POST['unit_cost'],
            $total_cost,
            $_POST['restock_date'],
            $_POST['notes']
        ]);
        $success = "Inventory restocked successfully!";
    }
}

// Get data
$menuItems = $pdo->query("SELECT * FROM menu_items WHERE is_active = 1 ORDER BY name")->fetchAll();
$inventory = getInventoryStatus($pdo);
$todaySales = $pdo->query("
    SELECT s.*, m.name as menu_name 
    FROM sales s 
    JOIN menu_items m ON s.menu_item_id = m.id 
    WHERE s.sale_date = CURDATE() 
    ORDER BY s.sale_time DESC
")->fetchAll();

// Calculate inventory value
$inventoryValue = 0;
foreach ($inventory as $item) {
    $inventoryValue += $item['stock_quantity'] * $item['unit_cost'];
}

// Get low stock count
$lowStockCount = count(array_filter($inventory, fn($i) => $i['stock_status'] === 'low'));
?>

<div class="space-y-6 animate-fade-in">
    <?php if (isset($success)): ?>
    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 rounded alert-auto-hide">
        <div class="flex items-center gap-2">
            <i class="fas fa-check-circle"></i>
            <span><?php echo $success; ?></span>
        </div>
    </div>
    <?php endif; ?>

    <!-- Quick Actions -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <button onclick="openModal('saleModal')" class="bg-blue-600 text-white p-4 rounded-xl flex items-center gap-3 hover:bg-blue-700 transition-colors shadow-lg">
            <i class="fas fa-plus-circle text-2xl"></i>
            <div class="text-left">
                <p class="font-bold">Record Sale</p>
                <p class="text-sm text-blue-200">Add new transaction</p>
            </div>
        </button>
        <button onclick="openModal('expenseModal')" class="bg-red-600 text-white p-4 rounded-xl flex items-center gap-3 hover:bg-red-700 transition-colors shadow-lg">
            <i class="fas fa-minus-circle text-2xl"></i>
            <div class="text-left">
                <p class="font-bold">Add Expense</p>
                <p class="text-sm text-red-200">Record spending</p>
            </div>
        </button>
        <button onclick="openModal('restockModal')" class="bg-green-600 text-white p-4 rounded-xl flex items-center gap-3 hover:bg-green-700 transition-colors shadow-lg">
            <i class="fas fa-boxes text-2xl"></i>
            <div class="text-left">
                <p class="font-bold">Restock Inventory</p>
                <p class="text-sm text-green-200">Add supplies</p>
            </div>
        </button>
    </div>

    <!-- Inventory Overview -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Inventory Stats -->
        <div class="bg-white rounded-2xl p-6 shadow-lg">
            <h3 class="text-lg font-bold text-gray-800 mb-4">Inventory Overview</h3>
            <div class="space-y-4">
                <div class="flex justify-between items-center p-4 bg-blue-50 rounded-xl">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-blue-500 rounded-lg flex items-center justify-center">
                            <i class="fas fa-warehouse text-white"></i>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Total Items</p>
                            <p class="text-xl font-bold text-gray-800"><?php echo count($inventory); ?></p>
                        </div>
                    </div>
                </div>
                <div class="flex justify-between items-center p-4 bg-green-50 rounded-xl">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-green-500 rounded-lg flex items-center justify-center">
                            <i class="fas fa-peso-sign text-white"></i>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Inventory Value</p>
                            <p class="text-xl font-bold text-gray-800"><?php echo formatCurrency($inventoryValue); ?></p>
                        </div>
                    </div>
                </div>
                <div class="flex justify-between items-center p-4 bg-red-50 rounded-xl">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-red-500 rounded-lg flex items-center justify-center">
                            <i class="fas fa-exclamation-triangle text-white"></i>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Low Stock Alerts</p>
                            <p class="text-xl font-bold text-gray-800"><?php echo $lowStockCount; ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Today's Sales Summary -->
        <div class="lg:col-span-2 bg-white rounded-2xl p-6 shadow-lg">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-bold text-gray-800">Today's Sales</h3>
                <span class="text-sm text-gray-500"><?php echo count($todaySales); ?> transactions</span>
            </div>
            <div class="overflow-x-auto max-h-64 overflow-y-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 sticky top-0">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-600">Item</th>
                            <th class="px-4 py-2 text-right text-xs font-medium text-gray-600">Qty</th>
                            <th class="px-4 py-2 text-right text-xs font-medium text-gray-600">Amount</th>
                            <th class="px-4 py-2 text-right text-xs font-medium text-gray-600">Time</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        <?php 
                        $todayTotal = 0;
                        foreach ($todaySales as $sale): 
                            $todayTotal += $sale['total_amount'];
                        ?>
                        <tr>
                            <td class="px-4 py-2 text-sm"><?php echo htmlspecialchars($sale['menu_name']); ?></td>
                            <td class="px-4 py-2 text-sm text-right"><?php echo $sale['quantity_sold']; ?></td>
                            <td class="px-4 py-2 text-sm text-right font-medium"><?php echo formatCurrency($sale['total_amount']); ?></td>
                            <td class="px-4 py-2 text-sm text-right text-gray-500"><?php echo date('h:i A', strtotime($sale['sale_time'])); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot class="bg-gray-50 sticky bottom-0">
                        <tr>
                            <td colspan="2" class="px-4 py-3 text-right font-bold">Today's Total:</td>
                            <td class="px-4 py-3 text-right font-bold text-green-600"><?php echo formatCurrency($todayTotal); ?></td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    <!-- Inventory Table -->
    <div class="bg-white rounded-2xl p-6 shadow-lg">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-bold text-gray-800">Inventory Status</h3>
            <div class="flex gap-2">
                <span class="flex items-center gap-1 text-xs">
                    <span class="w-3 h-3 bg-green-500 rounded-full"></span> Good
                </span>
                <span class="flex items-center gap-1 text-xs">
                    <span class="w-3 h-3 bg-yellow-500 rounded-full"></span> Medium
                </span>
                <span class="flex items-center gap-1 text-xs">
                    <span class="w-3 h-3 bg-red-500 rounded-full"></span> Low
                </span>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-sm font-medium text-gray-600">Ingredient</th>
                        <th class="px-4 py-3 text-left text-sm font-medium text-gray-600">Unit</th>
                        <th class="px-4 py-3 text-right text-sm font-medium text-gray-600">Stock</th>
                        <th class="px-4 py-3 text-right text-sm font-medium text-gray-600">Reorder Level</th>
                        <th class="px-4 py-3 text-right text-sm font-medium text-gray-600">Unit Cost</th>
                        <th class="px-4 py-3 text-center text-sm font-medium text-gray-600">Status</th>
                        <th class="px-4 py-3 text-center text-sm font-medium text-gray-600">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    <?php foreach ($inventory as $item): ?>
                    <tr>
                        <td class="px-4 py-3 text-sm font-medium text-gray-800"><?php echo htmlspecialchars($item['name']); ?></td>
                        <td class="px-4 py-3 text-sm text-gray-600"><?php echo $item['unit']; ?></td>
                        <td class="px-4 py-3 text-sm text-right"><?php echo number_format($item['stock_quantity'], 2); ?></td>
                        <td class="px-4 py-3 text-sm text-right text-gray-500"><?php echo number_format($item['reorder_level'], 2); ?></td>
                        <td class="px-4 py-3 text-sm text-right"><?php echo formatCurrency($item['unit_cost']); ?></td>
                        <td class="px-4 py-3 text-center">
                            <span class="px-3 py-1 rounded-full text-xs font-medium
                                <?php echo $item['stock_status'] === 'good' ? 'bg-green-100 text-green-700' : 
                                    ($item['stock_status'] === 'medium' ? 'bg-yellow-100 text-yellow-700' : 'bg-red-100 text-red-700'); ?>">
                                <?php echo ucfirst($item['stock_status']); ?>
                            </span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <button onclick="quickRestock(<?php echo $item['id']; ?>, '<?php echo htmlspecialchars($item['name']); ?>', <?php echo $item['unit_cost']; ?>)" 
                                class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                                <i class="fas fa-plus-circle"></i> Restock
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Sale Modal -->
<div id="saleModal" class="fixed inset-0 bg-black/50 z-50 hidden flex items-center justify-center">
    <div class="bg-white rounded-2xl w-full max-w-md p-6 m-4">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-xl font-bold text-gray-800">Record Sale</h3>
            <button onclick="closeModal('saleModal')" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        <form method="POST" class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Menu Item</label>
                <select name="menu_item_id" required class="w-full px-4 py-2 border rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">Select item...</option>
                    <?php foreach ($menuItems as $item): ?>
                    <option value="<?php echo $item['id']; ?>"><?php echo htmlspecialchars($item['name']); ?> - <?php echo formatCurrency($item['selling_price']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Quantity</label>
                <input type="number" name="quantity" min="1" value="1" required 
                    class="w-full px-4 py-2 border rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <button type="submit" name="add_sale" class="w-full bg-blue-600 text-white py-3 rounded-xl font-medium hover:bg-blue-700 transition-colors">
                Record Sale
            </button>
        </form>
    </div>
</div>

<!-- Expense Modal -->
<div id="expenseModal" class="fixed inset-0 bg-black/50 z-50 hidden flex items-center justify-center">
    <div class="bg-white rounded-2xl w-full max-w-md p-6 m-4">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-xl font-bold text-gray-800">Add Expense</h3>
            <button onclick="closeModal('expenseModal')" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        <form method="POST" class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Expense Type</label>
                <select name="expense_type" required class="w-full px-4 py-2 border rounded-xl focus:outline-none focus:ring-2 focus:ring-red-500">
                    <option value="Ingredients">Ingredients</option>
                    <option value="Utilities">Utilities</option>
                    <option value="Supplies">Supplies</option>
                    <option value="Maintenance">Maintenance</option>
                    <option value="Rent">Rent</option>
                    <option value="Other">Other</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                <input type="text" name="description" required 
                    class="w-full px-4 py-2 border rounded-xl focus:outline-none focus:ring-2 focus:ring-red-500"
                    placeholder="What was this expense for?">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Amount</label>
                <input type="number" name="amount" min="0" step="0.01" required 
                    class="w-full px-4 py-2 border rounded-xl focus:outline-none focus:ring-2 focus:ring-red-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Date</label>
                <input type="date" name="expense_date" value="<?php echo date('Y-m-d'); ?>" required 
                    class="w-full px-4 py-2 border rounded-xl focus:outline-none focus:ring-2 focus:ring-red-500">
            </div>
            <button type="submit" name="add_expense" class="w-full bg-red-600 text-white py-3 rounded-xl font-medium hover:bg-red-700 transition-colors">
                Add Expense
            </button>
        </form>
    </div>
</div>

<!-- Restock Modal -->
<div id="restockModal" class="fixed inset-0 bg-black/50 z-50 hidden flex items-center justify-center">
    <div class="bg-white rounded-2xl w-full max-w-md p-6 m-4">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-xl font-bold text-gray-800">Restock Inventory</h3>
            <button onclick="closeModal('restockModal')" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        <form method="POST" class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Ingredient</label>
                <select name="ingredient_id" id="restock_ingredient" required 
                    class="w-full px-4 py-2 border rounded-xl focus:outline-none focus:ring-2 focus:ring-green-500"
                    onchange="updateUnitCost()">
                    <option value="">Select ingredient...</option>
                    <?php foreach ($inventory as $item): ?>
                    <option value="<?php echo $item['id']; ?>" data-cost="<?php echo $item['unit_cost']; ?>">
                        <?php echo htmlspecialchars($item['name']); ?> (Current: <?php echo $item['stock_quantity']; ?> <?php echo $item['unit']; ?>)
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Quantity to Add</label>
                <input type="number" name="restock_qty" min="0.01" step="0.01" required 
                    class="w-full px-4 py-2 border rounded-xl focus:outline-none focus:ring-2 focus:ring-green-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Unit Cost</label>
                <input type="number" name="unit_cost" id="restock_unit_cost" min="0" step="0.01" required 
                    class="w-full px-4 py-2 border rounded-xl focus:outline-none focus:ring-2 focus:ring-green-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Date</label>
                <input type="date" name="restock_date" value="<?php echo date('Y-m-d'); ?>" required 
                    class="w-full px-4 py-2 border rounded-xl focus:outline-none focus:ring-2 focus:ring-green-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                <input type="text" name="notes" 
                    class="w-full px-4 py-2 border rounded-xl focus:outline-none focus:ring-2 focus:ring-green-500"
                    placeholder="Optional notes...">
            </div>
            <button type="submit" name="restock" class="w-full bg-green-600 text-white py-3 rounded-xl font-medium hover:bg-green-700 transition-colors">
                Restock
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

    function updateUnitCost() {
        const select = document.getElementById('restock_ingredient');
        const option = select.options[select.selectedIndex];
        const cost = option.getAttribute('data-cost');
        if (cost) {
            document.getElementById('restock_unit_cost').value = cost;
        }
    }

    function quickRestock(id, name, cost) {
        openModal('restockModal');
        const select = document.getElementById('restock_ingredient');
        select.value = id;
        document.getElementById('restock_unit_cost').value = cost;
    }

    // Close modal when clicking outside
    window.onclick = function(event) {
        if (event.target.classList.contains('fixed')) {
            event.target.classList.add('hidden');
        }
    }
</script>

<?php require_once 'canteen_footer.php'; ?>
