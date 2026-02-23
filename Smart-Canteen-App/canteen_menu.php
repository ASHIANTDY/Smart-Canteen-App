<?php
$pageTitle = 'Menu Builder & Cost Calculator';
require_once 'canteen_config.php';
requireLogin();
require_once 'canteen_header.php';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_menu_item'])) {
        // Add new menu item
        $stmt = $pdo->prepare("
            INSERT INTO menu_items (name, description, selling_price, category) 
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([
            $_POST['name'],
            $_POST['description'],
            $_POST['selling_price'],
            $_POST['category']
        ]);
        $menuItemId = $pdo->lastInsertId();
        
        // Add ingredients
        if (!empty($_POST['ingredients'])) {
            $stmt = $pdo->prepare("
                INSERT INTO menu_item_ingredients (menu_item_id, ingredient_id, quantity_required) 
                VALUES (?, ?, ?)
            ");
            foreach ($_POST['ingredients'] as $index => $ingredientId) {
                if (!empty($ingredientId) && !empty($_POST['quantities'][$index])) {
                    $stmt->execute([$menuItemId, $ingredientId, $_POST['quantities'][$index]]);
                }
            }
        }
        $success = "Menu item added successfully!";
    } elseif (isset($_POST['update_menu_item'])) {
        // Update menu item
        $stmt = $pdo->prepare("
            UPDATE menu_items 
            SET name = ?, description = ?, selling_price = ?, category = ? 
            WHERE id = ?
        ");
        $stmt->execute([
            $_POST['name'],
            $_POST['description'],
            $_POST['selling_price'],
            $_POST['category'],
            $_POST['menu_item_id']
        ]);
        
        // Delete old ingredients
        $stmt = $pdo->prepare("DELETE FROM menu_item_ingredients WHERE menu_item_id = ?");
        $stmt->execute([$_POST['menu_item_id']]);
        
        // Add new ingredients
        if (!empty($_POST['ingredients'])) {
            $stmt = $pdo->prepare("
                INSERT INTO menu_item_ingredients (menu_item_id, ingredient_id, quantity_required) 
                VALUES (?, ?, ?)
            ");
            foreach ($_POST['ingredients'] as $index => $ingredientId) {
                if (!empty($ingredientId) && !empty($_POST['quantities'][$index])) {
                    $stmt->execute([$_POST['menu_item_id'], $ingredientId, $_POST['quantities'][$index]]);
                }
            }
        }
        $success = "Menu item updated successfully!";
    } elseif (isset($_POST['delete_menu_item'])) {
        $stmt = $pdo->prepare("DELETE FROM menu_items WHERE id = ?");
        $stmt->execute([$_POST['menu_item_id']]);
        $success = "Menu item deleted successfully!";
    }
}

// Get all data
$menuItems = getMenuItemsWithCosts($pdo);
$ingredients = $pdo->query("SELECT * FROM ingredients ORDER BY name")->fetchAll();
$categories = $pdo->query("SELECT DISTINCT category FROM menu_items ORDER BY category")->fetchAll(PDO::FETCH_COLUMN);

// Calculate overall statistics
$totalMenuItems = count($menuItems);
$avgProfitMargin = 0;
$profitableItems = 0;
$totalRevenue = 0;
$totalCost = 0;

foreach ($menuItems as $item) {
    $totalRevenue += $item['selling_price'];
    $totalCost += $item['cost_price'];
    if ($item['profit_margin'] > 0) $profitableItems++;
}

$avgProfitMargin = $totalRevenue > 0 ? round((($totalRevenue - $totalCost) / $totalRevenue) * 100, 2) : 0;
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

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-white rounded-xl p-4 shadow-lg">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                    <i class="fas fa-utensils text-blue-600"></i>
                </div>
                <div>
                    <p class="text-2xl font-bold text-gray-800"><?php echo $totalMenuItems; ?></p>
                    <p class="text-sm text-gray-500">Menu Items</p>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-xl p-4 shadow-lg">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center">
                    <i class="fas fa-chart-line text-green-600"></i>
                </div>
                <div>
                    <p class="text-2xl font-bold text-gray-800"><?php echo $avgProfitMargin; ?>%</p>
                    <p class="text-sm text-gray-500">Avg. Profit Margin</p>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-xl p-4 shadow-lg">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center">
                    <i class="fas fa-check-circle text-purple-600"></i>
                </div>
                <div>
                    <p class="text-2xl font-bold text-gray-800"><?php echo $profitableItems; ?></p>
                    <p class="text-sm text-gray-500">Profitable Items</p>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-xl p-4 shadow-lg">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-orange-100 rounded-lg flex items-center justify-center">
                    <i class="fas fa-calculator text-orange-600"></i>
                </div>
                <div>
                    <p class="text-2xl font-bold text-gray-800"><?php echo formatCurrency($totalCost / max($totalMenuItems, 1)); ?></p>
                    <p class="text-sm text-gray-500">Avg. Cost per Item</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Add New Menu Item Button -->
    <div class="flex justify-between items-center">
        <h3 class="text-lg font-bold text-gray-800">Menu Items</h3>
        <button onclick="openMenuModal()" class="bg-blue-600 text-white px-6 py-3 rounded-xl flex items-center gap-2 hover:bg-blue-700 transition-colors shadow-lg">
            <i class="fas fa-plus"></i>
            <span>Add Menu Item</span>
        </button>
    </div>

    <!-- Menu Items Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-6">
        <?php foreach ($menuItems as $item): 
            $profitColor = $item['profit_margin'] > 0 ? 'green' : 'red';
            $marginPercent = $item['selling_price'] > 0 ? round(($item['profit_margin'] / $item['selling_price']) * 100, 1) : 0;
        ?>
        <div class="bg-white rounded-2xl p-6 shadow-lg card-hover border-t-4 border-<?php echo $profitColor; ?>-500">
            <div class="flex justify-between items-start mb-4">
                <div>
                    <span class="text-xs font-medium text-gray-500 uppercase tracking-wide"><?php echo $item['category']; ?></span>
                    <h4 class="text-lg font-bold text-gray-800 mt-1"><?php echo htmlspecialchars($item['name']); ?></h4>
                </div>
                <div class="flex gap-2">
                    <button onclick="editMenuItem(<?php echo htmlspecialchars(json_encode($item)); ?>)" 
                        class="w-8 h-8 bg-blue-100 text-blue-600 rounded-lg flex items-center justify-center hover:bg-blue-200 transition-colors">
                        <i class="fas fa-edit text-sm"></i>
                    </button>
                    <form method="POST" class="inline" onsubmit="return confirm('Delete this menu item?')">
                        <input type="hidden" name="menu_item_id" value="<?php echo $item['id']; ?>">
                        <button type="submit" name="delete_menu_item" 
                            class="w-8 h-8 bg-red-100 text-red-600 rounded-lg flex items-center justify-center hover:bg-red-200 transition-colors">
                            <i class="fas fa-trash text-sm"></i>
                        </button>
                    </form>
                </div>
            </div>
            
            <p class="text-gray-600 text-sm mb-4 line-clamp-2"><?php echo htmlspecialchars($item['description']); ?></p>
            
            <!-- Cost Breakdown -->
            <div class="bg-gray-50 rounded-xl p-4 mb-4">
                <div class="flex justify-between items-center mb-2">
                    <span class="text-sm text-gray-600">Cost Price:</span>
                    <span class="font-medium text-gray-800"><?php echo formatCurrency($item['cost_price']); ?></span>
                </div>
                <div class="flex justify-between items-center mb-2">
                    <span class="text-sm text-gray-600">Selling Price:</span>
                    <span class="font-medium text-gray-800"><?php echo formatCurrency($item['selling_price']); ?></span>
                </div>
                <div class="border-t pt-2 mt-2">
                    <div class="flex justify-between items-center">
                        <span class="text-sm font-medium text-gray-700">Profit Margin:</span>
                        <span class="font-bold text-<?php echo $profitColor; ?>-600"><?php echo formatCurrency($item['profit_margin']); ?></span>
                    </div>
                    <div class="mt-2">
                        <div class="flex justify-between text-xs mb-1">
                            <span class="text-gray-500">Margin %</span>
                            <span class="font-medium text-<?php echo $profitColor; ?>-600"><?php echo $marginPercent; ?>%</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div class="bg-<?php echo $profitColor; ?>-500 h-2 rounded-full transition-all" 
                                style="width: <?php echo min(abs($marginPercent), 100); ?>"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Ingredients List -->
            <div>
                <p class="text-xs font-medium text-gray-500 uppercase mb-2">Ingredients</p>
                <?php
                $stmt = $pdo->prepare("
                    SELECT i.name, i.unit, mii.quantity_required, i.unit_cost
                    FROM menu_item_ingredients mii
                    JOIN ingredients i ON mii.ingredient_id = i.id
                    WHERE mii.menu_item_id = ?
                ");
                $stmt->execute([$item['id']]);
                $itemIngredients = $stmt->fetchAll();
                ?>
                <div class="space-y-1">
                    <?php foreach ($itemIngredients as $ing): ?>
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600"><?php echo htmlspecialchars($ing['name']); ?></span>
                        <span class="text-gray-500"><?php echo $ing['quantity_required']; ?> <?php echo $ing['unit']; ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Menu Item Modal -->
<div id="menuModal" class="fixed inset-0 bg-black/50 z-50 hidden flex items-center justify-center overflow-y-auto">
    <div class="bg-white rounded-2xl w-full max-w-2xl m-4 my-8">
        <div class="p-6 border-b">
            <div class="flex justify-between items-center">
                <h3 class="text-xl font-bold text-gray-800" id="modalTitle">Add Menu Item</h3>
                <button onclick="closeMenuModal()" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
        </div>
        <form method="POST" class="p-6 space-y-4">
            <input type="hidden" name="menu_item_id" id="menuItemId">
            
            <div class="grid grid-cols-2 gap-4">
                <div class="col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Item Name</label>
                    <input type="text" name="name" id="menuName" required 
                        class="w-full px-4 py-2 border rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500"
                        placeholder="e.g., Chicken Adobo">
                </div>
                <div class="col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                    <textarea name="description" id="menuDescription" rows="2"
                        class="w-full px-4 py-2 border rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500"
                        placeholder="Brief description of the dish..."></textarea>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Category</label>
                    <select name="category" id="menuCategory" required 
                        class="w-full px-4 py-2 border rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="Meals">Meals</option>
                        <option value="Noodles">Noodles</option>
                        <option value="Pasta">Pasta</option>
                        <option value="Snacks">Snacks</option>
                        <option value="Sides">Sides</option>
                        <option value="Beverages">Beverages</option>
                        <option value="Desserts">Desserts</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Selling Price (₱)</label>
                    <input type="number" name="selling_price" id="menuPrice" min="0" step="0.01" required 
                        class="w-full px-4 py-2 border rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500"
                        placeholder="0.00">
                </div>
            </div>

            <!-- Ingredients Section -->
            <div class="border-t pt-4">
                <div class="flex justify-between items-center mb-4">
                    <label class="block text-sm font-medium text-gray-700">Ingredients</label>
                    <button type="button" onclick="addIngredientRow()" 
                        class="text-blue-600 text-sm font-medium hover:text-blue-800 flex items-center gap-1">
                        <i class="fas fa-plus"></i> Add Ingredient
                    </button>
                </div>
                <div id="ingredientsList" class="space-y-2">
                    <!-- Ingredient rows will be added here -->
                </div>
                <div class="mt-4 p-4 bg-blue-50 rounded-xl">
                    <div class="flex justify-between items-center">
                        <span class="text-sm font-medium text-gray-700">Estimated Cost:</span>
                        <span class="text-xl font-bold text-blue-600" id="estimatedCost">₱0.00</span>
                    </div>
                    <div class="flex justify-between items-center mt-2">
                        <span class="text-sm font-medium text-gray-700">Projected Profit:</span>
                        <span class="text-xl font-bold text-green-600" id="projectedProfit">₱0.00</span>
                    </div>
                </div>
            </div>

            <div class="flex gap-3 pt-4">
                <button type="button" onclick="closeMenuModal()" 
                    class="flex-1 px-4 py-3 border border-gray-300 rounded-xl font-medium hover:bg-gray-50 transition-colors">
                    Cancel
                </button>
                <button type="submit" name="add_menu_item" id="addBtn"
                    class="flex-1 bg-blue-600 text-white px-4 py-3 rounded-xl font-medium hover:bg-blue-700 transition-colors">
                    Add Menu Item
                </button>
                <button type="submit" name="update_menu_item" id="updateBtn"
                    class="flex-1 bg-green-600 text-white px-4 py-3 rounded-xl font-medium hover:bg-green-700 transition-colors hidden">
                    Update Menu Item
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    const ingredients = <?php echo json_encode($ingredients); ?>;
    let ingredientRows = 0;

    function openMenuModal() {
        document.getElementById('menuModal').classList.remove('hidden');
        document.getElementById('modalTitle').textContent = 'Add Menu Item';
        document.getElementById('addBtn').classList.remove('hidden');
        document.getElementById('updateBtn').classList.add('hidden');
        document.getElementById('menuItemId').value = '';
        document.getElementById('menuName').value = '';
        document.getElementById('menuDescription').value = '';
        document.getElementById('menuPrice').value = '';
        document.getElementById('menuCategory').value = 'Meals';
        document.getElementById('ingredientsList').innerHTML = '';
        addIngredientRow();
        calculateCost();
    }

    function closeMenuModal() {
        document.getElementById('menuModal').classList.add('hidden');
    }

    function addIngredientRow(selectedId = '', quantity = '') {
        const container = document.getElementById('ingredientsList');
        const row = document.createElement('div');
        row.className = 'flex gap-2 items-center ingredient-row';
        row.innerHTML = `
            <select name="ingredients[]" class="flex-1 px-4 py-2 border rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500" onchange="calculateCost()">
                <option value="">Select ingredient...</option>
                ${ingredients.map(ing => `<option value="${ing.id}" data-cost="${ing.unit_cost}" ${ing.id == selectedId ? 'selected' : ''}>${ing.name} (₱${ing.unit_cost}/${ing.unit})</option>`).join('')}
            </select>
            <input type="number" name="quantities[]" value="${quantity}" min="0.01" step="0.01" placeholder="Qty" 
                class="w-24 px-4 py-2 border rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500" oninput="calculateCost()">
            <button type="button" onclick="this.parentElement.remove(); calculateCost();" 
                class="w-10 h-10 bg-red-100 text-red-600 rounded-xl flex items-center justify-center hover:bg-red-200 transition-colors">
                <i class="fas fa-times"></i>
            </button>
        `;
        container.appendChild(row);
        ingredientRows++;
    }

    function calculateCost() {
        let totalCost = 0;
        const rows = document.querySelectorAll('.ingredient-row');
        
        rows.forEach(row => {
            const select = row.querySelector('select');
            const qtyInput = row.querySelector('input[type="number"]');
            
            if (select.value && qtyInput.value) {
                const option = select.options[select.selectedIndex];
                const unitCost = parseFloat(option.getAttribute('data-cost')) || 0;
                const quantity = parseFloat(qtyInput.value) || 0;
                totalCost += unitCost * quantity;
            }
        });

        const sellingPrice = parseFloat(document.getElementById('menuPrice').value) || 0;
        const profit = sellingPrice - totalCost;

        document.getElementById('estimatedCost').textContent = '₱' + totalCost.toFixed(2);
        document.getElementById('projectedProfit').textContent = '₱' + profit.toFixed(2);
        document.getElementById('projectedProfit').className = 'text-xl font-bold ' + (profit >= 0 ? 'text-green-600' : 'text-red-600');
    }

    function editMenuItem(item) {
        document.getElementById('menuModal').classList.remove('hidden');
        document.getElementById('modalTitle').textContent = 'Edit Menu Item';
        document.getElementById('addBtn').classList.add('hidden');
        document.getElementById('updateBtn').classList.remove('hidden');
        
        document.getElementById('menuItemId').value = item.id;
        document.getElementById('menuName').value = item.name;
        document.getElementById('menuDescription').value = item.description;
        document.getElementById('menuPrice').value = item.selling_price;
        document.getElementById('menuCategory').value = item.category;
        
        // Load ingredients
        document.getElementById('ingredientsList').innerHTML = '';
        
        // Fetch ingredients for this menu item
        fetch('canteen_api.php?action=get_menu_ingredients&menu_id=' + item.id)
            .then(response => response.json())
            .then(data => {
                if (data.ingredients && data.ingredients.length > 0) {
                    data.ingredients.forEach(ing => {
                        addIngredientRow(ing.ingredient_id, ing.quantity_required);
                    });
                } else {
                    addIngredientRow();
                }
                calculateCost();
            });
    }

    // Event listeners
    document.getElementById('menuPrice')?.addEventListener('input', calculateCost);

    // Close modal when clicking outside
    window.onclick = function(event) {
        const modal = document.getElementById('menuModal');
        if (event.target === modal) {
            closeMenuModal();
        }
    }

    // Initialize
    addIngredientRow();
</script>

<?php require_once 'canteen_footer.php'; ?>
