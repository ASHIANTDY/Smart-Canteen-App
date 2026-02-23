<?php
$pageTitle = 'Profit/Loss Dashboard';
require_once 'canteen_config.php';
requireLogin();
require_once 'canteen_header.php';

// Get dashboard statistics
$stats = getDashboardStats($pdo);
$salesData = getSalesChartData($pdo, 7);
$topItems = getTopSellingItems($pdo, 5);
$expenseBreakdown = getExpenseBreakdown($pdo);

// Prepare chart data
$labels = [];
$salesValues = [];
foreach ($salesData as $data) {
    $labels[] = date('M d', strtotime($data['sale_date']));
    $salesValues[] = $data['total'];
}
?>

<!-- Dashboard Content -->
<div class="space-y-6 animate-fade-in">
    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <!-- Today's Sales -->
        <div class="bg-white rounded-2xl p-6 shadow-lg card-hover border-l-4 border-blue-500">
            <div class="flex justify-between items-start">
                <div>
                    <p class="text-gray-500 text-sm">Today's Sales</p>
                    <h3 class="text-2xl font-bold text-gray-800 mt-1"><?php echo formatCurrency($stats['today_sales']); ?></h3>
                </div>
                <div class="w-12 h-12 bg-blue-100 rounded-xl flex items-center justify-center">
                    <i class="fas fa-peso-sign text-blue-600 text-xl"></i>
                </div>
            </div>
            <div class="mt-4 flex items-center text-sm">
                <span class="text-green-500 flex items-center gap-1">
                    <i class="fas fa-arrow-up"></i>
                    <span>Live</span>
                </span>
                <span class="text-gray-400 ml-2">Updated now</span>
            </div>
        </div>

        <!-- Today's Profit/Loss -->
        <div class="bg-white rounded-2xl p-6 shadow-lg card-hover border-l-4 border-<?php echo $stats['today_profit'] >= 0 ? 'green' : 'red'; ?>-500">
            <div class="flex justify-between items-start">
                <div>
                    <p class="text-gray-500 text-sm">Today's Profit/Loss</p>
                    <h3 class="text-2xl font-bold <?php echo $stats['today_profit'] >= 0 ? 'text-green-600' : 'text-red-600'; ?> mt-1">
                        <?php echo formatCurrency($stats['today_profit']); ?>
                    </h3>
                </div>
                <div class="w-12 h-12 bg-<?php echo $stats['today_profit'] >= 0 ? 'green' : 'red'; ?>-100 rounded-xl flex items-center justify-center">
                    <i class="fas fa-<?php echo $stats['today_profit'] >= 0 ? 'trending-up' : 'trending-down'; ?> text-<?php echo $stats['today_profit'] >= 0 ? 'green' : 'red'; ?>-600 text-xl"></i>
                </div>
            </div>
            <div class="mt-4 flex items-center text-sm">
                <span class="text-gray-500">
                    Sales: <?php echo formatCurrency($stats['today_sales']); ?> | 
                    Expenses: <?php echo formatCurrency($stats['today_expenses']); ?>
                </span>
            </div>
        </div>

        <!-- Monthly Sales -->
        <div class="bg-white rounded-2xl p-6 shadow-lg card-hover border-l-4 border-purple-500">
            <div class="flex justify-between items-start">
                <div>
                    <p class="text-gray-500 text-sm">This Month's Sales</p>
                    <h3 class="text-2xl font-bold text-gray-800 mt-1"><?php echo formatCurrency($stats['month_sales']); ?></h3>
                </div>
                <div class="w-12 h-12 bg-purple-100 rounded-xl flex items-center justify-center">
                    <i class="fas fa-calendar-alt text-purple-600 text-xl"></i>
                </div>
            </div>
            <div class="mt-4 flex items-center text-sm">
                <span class="text-purple-600 font-medium">
                    <?php echo date('F Y'); ?>
                </span>
            </div>
        </div>

        <!-- Monthly Profit -->
        <div class="bg-white rounded-2xl p-6 shadow-lg card-hover border-l-4 border-<?php echo $stats['month_profit'] >= 0 ? 'green' : 'red'; ?>-500">
            <div class="flex justify-between items-start">
                <div>
                    <p class="text-gray-500 text-sm">Monthly Profit/Loss</p>
                    <h3 class="text-2xl font-bold <?php echo $stats['month_profit'] >= 0 ? 'text-green-600' : 'text-red-600'; ?> mt-1">
                        <?php echo formatCurrency($stats['month_profit']); ?>
                    </h3>
                </div>
                <div class="w-12 h-12 bg-<?php echo $stats['month_profit'] >= 0 ? 'green' : 'red'; ?>-100 rounded-xl flex items-center justify-center">
                    <i class="fas fa-chart-pie text-<?php echo $stats['month_profit'] >= 0 ? 'green' : 'red'; ?>-600 text-xl"></i>
                </div>
            </div>
            <div class="mt-4 flex items-center text-sm">
                <span class="text-gray-500">
                    Margin: <?php echo $stats['month_sales'] > 0 ? round(($stats['month_profit'] / $stats['month_sales']) * 100, 1) : 0; ?>%
                </span>
            </div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Sales Trend Chart -->
        <div class="lg:col-span-2 bg-white rounded-2xl p-4 shadow-lg">
            <div class="flex justify-between items-center mb-3">
                <h3 class="text-base font-bold text-gray-800">Sales Trend (Last 7 Days)</h3>
                <button onclick="refreshData()" class="text-gray-400 hover:text-gray-600 transition-colors text-sm">
                    <i class="fas fa-sync-alt"></i>
                </button>
            </div>
            <div style="height: 200px;">
                <canvas id="salesChart"></canvas>
            </div>
        </div>

        <!-- Expense Breakdown -->
        <div class="bg-white rounded-2xl p-4 shadow-lg">
            <h3 class="text-base font-bold text-gray-800 mb-3">Expense Breakdown</h3>
            <div style="height: 150px;">
                <canvas id="expenseChart"></canvas>
            </div>
            <div class="mt-3 max-h-24 overflow-y-auto space-y-1">
                <?php foreach ($expenseBreakdown as $expense): ?>
                <div class="flex justify-between items-center text-xs py-1 border-b border-gray-100 last:border-0">
                    <span class="text-gray-600"><?php echo htmlspecialchars($expense['expense_type']); ?></span>
                    <span class="font-medium"><?php echo formatCurrency($expense['total']); ?></span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Quick Stats & Top Items -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Quick Stats -->
        <div class="bg-white rounded-2xl p-6 shadow-lg">
            <h3 class="text-lg font-bold text-gray-800 mb-4">Quick Overview</h3>
            <div class="grid grid-cols-2 gap-4">
                <div class="bg-blue-50 rounded-xl p-4">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-blue-500 rounded-lg flex items-center justify-center">
                            <i class="fas fa-utensils text-white"></i>
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-gray-800"><?php echo $stats['total_menu_items']; ?></p>
                            <p class="text-sm text-gray-500">Active Menu Items</p>
                        </div>
                    </div>
                </div>
                <div class="bg-orange-50 rounded-xl p-4">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-orange-500 rounded-lg flex items-center justify-center">
                            <i class="fas fa-boxes text-white"></i>
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-gray-800"><?php echo $stats['total_ingredients']; ?></p>
                            <p class="text-sm text-gray-500">Total Ingredients</p>
                        </div>
                    </div>
                </div>
                <div class="bg-red-50 rounded-xl p-4">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-red-500 rounded-lg flex items-center justify-center">
                            <i class="fas fa-exclamation-triangle text-white"></i>
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-gray-800"><?php echo $stats['low_stock']; ?></p>
                            <p class="text-sm text-gray-500">Low Stock Alerts</p>
                        </div>
                    </div>
                </div>
                <div class="bg-green-50 rounded-xl p-4">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-green-500 rounded-lg flex items-center justify-center">
                            <i class="fas fa-percentage text-white"></i>
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-gray-800">
                                <?php echo $stats['month_sales'] > 0 ? round(($stats['month_profit'] / $stats['month_sales']) * 100, 1) : 0; ?>%
                            </p>
                            <p class="text-sm text-gray-500">Profit Margin</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Top Selling Items -->
        <div class="bg-white rounded-2xl p-6 shadow-lg">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-bold text-gray-800">Top Selling Items (30 Days)</h3>
                <a href="canteen_menu.php" class="text-blue-600 text-sm hover:underline">View All</a>
            </div>
            <div class="space-y-3">
                <?php foreach ($topItems as $index => $item): ?>
                <div class="flex items-center gap-4 p-3 bg-gray-50 rounded-xl">
                    <div class="w-8 h-8 rounded-lg flex items-center justify-center font-bold text-sm
                        <?php echo $index === 0 ? 'bg-yellow-400 text-yellow-900' : ($index === 1 ? 'bg-gray-300 text-gray-700' : ($index === 2 ? 'bg-orange-300 text-orange-800' : 'bg-gray-200 text-gray-600')); ?>">
                        <?php echo $index + 1; ?>
                    </div>
                    <div class="flex-1">
                        <p class="font-medium text-gray-800"><?php echo htmlspecialchars($item['name']); ?></p>
                        <p class="text-sm text-gray-500"><?php echo $item['total_qty']; ?> sold</p>
                    </div>
                    <div class="text-right">
                        <p class="font-bold text-gray-800"><?php echo formatCurrency($item['total_sales']); ?></p>
                        <p class="text-xs text-green-600">Revenue</p>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Financial Summary Table -->
    <div class="bg-white rounded-2xl p-6 shadow-lg">
        <h3 class="text-lg font-bold text-gray-800 mb-4">Financial Summary</h3>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-sm font-medium text-gray-600">Period</th>
                        <th class="px-4 py-3 text-right text-sm font-medium text-gray-600">Sales</th>
                        <th class="px-4 py-3 text-right text-sm font-medium text-gray-600">Expenses</th>
                        <th class="px-4 py-3 text-right text-sm font-medium text-gray-600">Profit/Loss</th>
                        <th class="px-4 py-3 text-right text-sm font-medium text-gray-600">Margin</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    <tr>
                        <td class="px-4 py-3 text-sm font-medium text-gray-800">Today</td>
                        <td class="px-4 py-3 text-sm text-right text-green-600"><?php echo formatCurrency($stats['today_sales']); ?></td>
                        <td class="px-4 py-3 text-sm text-right text-red-600"><?php echo formatCurrency($stats['today_expenses']); ?></td>
                        <td class="px-4 py-3 text-sm text-right font-bold <?php echo $stats['today_profit'] >= 0 ? 'text-green-600' : 'text-red-600'; ?>">
                            <?php echo formatCurrency($stats['today_profit']); ?>
                        </td>
                        <td class="px-4 py-3 text-sm text-right">
                            <span class="px-2 py-1 rounded-full text-xs <?php echo $stats['today_profit'] >= 0 ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'; ?>">
                                <?php echo $stats['today_sales'] > 0 ? round(($stats['today_profit'] / $stats['today_sales']) * 100, 1) : 0; ?>%
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <td class="px-4 py-3 text-sm font-medium text-gray-800">This Month</td>
                        <td class="px-4 py-3 text-sm text-right text-green-600"><?php echo formatCurrency($stats['month_sales']); ?></td>
                        <td class="px-4 py-3 text-sm text-right text-red-600"><?php echo formatCurrency($stats['month_expenses']); ?></td>
                        <td class="px-4 py-3 text-sm text-right font-bold <?php echo $stats['month_profit'] >= 0 ? 'text-green-600' : 'text-red-600'; ?>">
                            <?php echo formatCurrency($stats['month_profit']); ?>
                        </td>
                        <td class="px-4 py-3 text-sm text-right">
                            <span class="px-2 py-1 rounded-full text-xs <?php echo $stats['month_profit'] >= 0 ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'; ?>">
                                <?php echo $stats['month_sales'] > 0 ? round(($stats['month_profit'] / $stats['month_sales']) * 100, 1) : 0; ?>%
                            </span>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    // Sales Trend Chart
    const salesCtx = document.getElementById('salesChart').getContext('2d');
    new Chart(salesCtx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode($labels); ?>,
            datasets: [{
                label: 'Sales (₱)',
                data: <?php echo json_encode($salesValues); ?>,
                borderColor: '#3b82f6',
                backgroundColor: 'rgba(59, 130, 246, 0.1)',
                borderWidth: 3,
                fill: true,
                tension: 0.4,
                pointBackgroundColor: '#3b82f6',
                pointBorderColor: '#fff',
                pointBorderWidth: 2,
                pointRadius: 5
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return '₱' + value.toLocaleString();
                        }
                    }
                }
            }
        }
    });

    // Expense Breakdown Chart
    const expenseCtx = document.getElementById('expenseChart').getContext('2d');
    new Chart(expenseCtx, {
        type: 'doughnut',
        data: {
            labels: <?php echo json_encode(array_column($expenseBreakdown, 'expense_type')); ?>,
            datasets: [{
                data: <?php echo json_encode(array_column($expenseBreakdown, 'total')); ?>,
                backgroundColor: [
                    '#ef4444',
                    '#f59e0b',
                    '#3b82f6',
                    '#10b981',
                    '#8b5cf6',
                    '#ec4899'
                ],
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        boxWidth: 12,
                        padding: 15
                    }
                }
            }
        }
    });

    function refreshData() {
        location.reload();
    }
</script>

<?php require_once 'canteen_footer.php'; ?>
