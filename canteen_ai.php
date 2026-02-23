<?php
$pageTitle = 'AI Assistant';
require_once 'canteen_config.php';
requireLogin();
require_once 'canteen_header.php';

// Get data for AI context
$stats = getDashboardStats($pdo);
$menuItems = getMenuItemsWithCosts($pdo);
$inventory = getInventoryStatus($pdo);
$topItems = getTopSellingItems($pdo, 5);
?>

<div class="space-y-6 animate-fade-in">
    <!-- AI Welcome Section -->
    <div class="bg-gradient-to-r from-purple-600 to-pink-600 rounded-2xl p-8 text-white shadow-xl">
        <div class="flex items-center gap-4 mb-4">
            <div class="w-16 h-16 bg-white/20 rounded-2xl flex items-center justify-center">
                <i class="fas fa-robot text-3xl"></i>
            </div>
            <div>
                <h2 class="text-2xl font-bold">Qwen AI Assistant</h2>
                <p class="text-purple-200">Your intelligent canteen management companion</p>
            </div>
        </div>
        <p class="text-lg text-purple-100 max-w-2xl">
            I'm here to help you make data-driven decisions for your canteen. Ask me about pricing strategies, 
            inventory optimization, sales forecasting, or any business insights you need.
        </p>
    </div>

    <!-- Quick Suggestions -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <button onclick="askAI('What are my top selling items and should I adjust their prices?')" 
            class="bg-white p-4 rounded-xl shadow-lg hover:shadow-xl transition-all text-left group">
            <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center mb-3 group-hover:bg-blue-200 transition-colors">
                <i class="fas fa-chart-line text-blue-600"></i>
            </div>
            <h4 class="font-bold text-gray-800">Price Analysis</h4>
            <p class="text-sm text-gray-500 mt-1">Get pricing recommendations</p>
        </button>

        <button onclick="askAI('Which ingredients are running low and what should I restock?')" 
            class="bg-white p-4 rounded-xl shadow-lg hover:shadow-xl transition-all text-left group">
            <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center mb-3 group-hover:bg-green-200 transition-colors">
                <i class="fas fa-boxes text-green-600"></i>
            </div>
            <h4 class="font-bold text-gray-800">Inventory Tips</h4>
            <p class="text-sm text-gray-500 mt-1">Optimize stock levels</p>
        </button>

        <button onclick="askAI('Analyze my profit margins and suggest improvements')" 
            class="bg-white p-4 rounded-xl shadow-lg hover:shadow-xl transition-all text-left group">
            <div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center mb-3 group-hover:bg-purple-200 transition-colors">
                <i class="fas fa-percentage text-purple-600"></i>
            </div>
            <h4 class="font-bold text-gray-800">Profit Analysis</h4>
            <p class="text-sm text-gray-500 mt-1">Improve profitability</p>
        </button>

        <button onclick="askAI('What menu items should I promote or discontinue?')" 
            class="bg-white p-4 rounded-xl shadow-lg hover:shadow-xl transition-all text-left group">
            <div class="w-10 h-10 bg-orange-100 rounded-lg flex items-center justify-center mb-3 group-hover:bg-orange-200 transition-colors">
                <i class="fas fa-utensils text-orange-600"></i>
            </div>
            <h4 class="font-bold text-gray-800">Menu Engineering</h4>
            <p class="text-sm text-gray-500 mt-1">Optimize your menu</p>
        </button>
    </div>

    <!-- AI Chat Interface -->
    <div class="bg-white rounded-2xl shadow-xl overflow-hidden">
        <div class="gradient-bg p-4 flex items-center gap-3">
            <i class="fas fa-robot text-white text-xl"></i>
            <span class="text-white font-bold">Chat with Qwen AI</span>
            <span class="ml-auto bg-green-400 text-xs px-2 py-1 rounded-full text-white pulse-dot">Online</span>
        </div>
        
        <div id="chatMessages" class="h-96 overflow-y-auto p-6 space-y-4 bg-gray-50">
            <!-- Welcome Message -->
            <div class="flex gap-3">
                <div class="w-10 h-10 bg-purple-100 rounded-full flex items-center justify-center flex-shrink-0">
                    <i class="fas fa-robot text-purple-600"></i>
                </div>
                <div class="bg-white p-4 rounded-2xl rounded-tl-none shadow-sm max-w-3xl">
                    <p class="text-gray-700">Hello! I'm Qwen, your AI canteen assistant. I have access to your current business data:</p>
                    <ul class="mt-2 space-y-1 text-sm text-gray-600">
                        <li>• Today's sales: <?php echo formatCurrency($stats['today_sales']); ?></li>
                        <li>• Today's profit: <?php echo formatCurrency($stats['today_profit']); ?></li>
                        <li>• Active menu items: <?php echo $stats['total_menu_items']; ?></li>
                        <li>• Low stock alerts: <?php echo $stats['low_stock']; ?></li>
                    </ul>
                    <p class="mt-3 text-gray-700">How can I help you optimize your canteen today?</p>
                </div>
            </div>
        </div>

        <div class="p-4 border-t bg-white">
            <form id="chatForm" class="flex gap-3">
                <input type="text" id="chatInput" placeholder="Ask me anything about your canteen..." 
                    class="flex-1 px-4 py-3 border rounded-xl focus:outline-none focus:ring-2 focus:ring-purple-500 text-base">
                <button type="submit" class="bg-gradient-to-r from-purple-600 to-pink-600 text-white px-6 py-3 rounded-xl font-medium hover:shadow-lg transition-all flex items-center gap-2">
                    <i class="fas fa-paper-plane"></i>
                    <span>Send</span>
                </button>
            </form>
        </div>
    </div>

    <!-- Data Context Panel -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Current Business Data -->
        <div class="bg-white rounded-2xl p-6 shadow-lg">
            <h3 class="text-lg font-bold text-gray-800 mb-4">
                <i class="fas fa-database text-blue-600 mr-2"></i>
                Current Business Data
            </h3>
            <div class="space-y-3 text-sm">
                <div class="flex justify-between p-3 bg-gray-50 rounded-lg">
                    <span class="text-gray-600">Today's Sales</span>
                    <span class="font-medium"><?php echo formatCurrency($stats['today_sales']); ?></span>
                </div>
                <div class="flex justify-between p-3 bg-gray-50 rounded-lg">
                    <span class="text-gray-600">Today's Expenses</span>
                    <span class="font-medium"><?php echo formatCurrency($stats['today_expenses']); ?></span>
                </div>
                <div class="flex justify-between p-3 bg-gray-50 rounded-lg">
                    <span class="text-gray-600">Today's Profit/Loss</span>
                    <span class="font-medium <?php echo $stats['today_profit'] >= 0 ? 'text-green-600' : 'text-red-600'; ?>">
                        <?php echo formatCurrency($stats['today_profit']); ?>
                    </span>
                </div>
                <div class="flex justify-between p-3 bg-gray-50 rounded-lg">
                    <span class="text-gray-600">Monthly Sales</span>
                    <span class="font-medium"><?php echo formatCurrency($stats['month_sales']); ?></span>
                </div>
                <div class="flex justify-between p-3 bg-gray-50 rounded-lg">
                    <span class="text-gray-600">Monthly Profit/Loss</span>
                    <span class="font-medium <?php echo $stats['month_profit'] >= 0 ? 'text-green-600' : 'text-red-600'; ?>">
                        <?php echo formatCurrency($stats['month_profit']); ?>
                    </span>
                </div>
            </div>
        </div>

        <!-- Menu Performance -->
        <div class="bg-white rounded-2xl p-6 shadow-lg">
            <h3 class="text-lg font-bold text-gray-800 mb-4">
                <i class="fas fa-chart-pie text-purple-600 mr-2"></i>
                Menu Performance
            </h3>
            <div class="space-y-3">
                <?php 
                $profitable = array_filter($menuItems, fn($i) => $i['profit_margin'] > 0);
                $unprofitable = array_filter($menuItems, fn($i) => $i['profit_margin'] <= 0);
                
                foreach (array_slice($menuItems, 0, 5) as $item): 
                    $marginClass = $item['profit_margin'] > 0 ? 'text-green-600' : 'text-red-600';
                ?>
                <div class="flex justify-between items-center p-3 bg-gray-50 rounded-lg">
                    <div>
                        <p class="font-medium text-gray-800"><?php echo htmlspecialchars($item['name']); ?></p>
                        <p class="text-xs text-gray-500">Cost: <?php echo formatCurrency($item['cost_price']); ?> | 
                            Price: <?php echo formatCurrency($item['selling_price']); ?></p>
                    </div>
                    <span class="font-bold <?php echo $marginClass; ?>">
                        <?php echo formatCurrency($item['profit_margin']); ?>
                    </span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Sample Questions -->
    <div class="bg-white rounded-2xl p-6 shadow-lg">
        <h3 class="text-lg font-bold text-gray-800 mb-4">
            <i class="fas fa-lightbulb text-yellow-500 mr-2"></i>
            Sample Questions You Can Ask
        </h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
            <button onclick="askAI(this.textContent)" class="text-left p-3 bg-gray-50 rounded-lg hover:bg-purple-50 hover:text-purple-700 transition-colors text-sm">
                "What is my profit margin percentage and is it healthy?"
            </button>
            <button onclick="askAI(this.textContent)" class="text-left p-3 bg-gray-50 rounded-lg hover:bg-purple-50 hover:text-purple-700 transition-colors text-sm">
                "Which menu items have the highest profit margins?"
            </button>
            <button onclick="askAI(this.textContent)" class="text-left p-3 bg-gray-50 rounded-lg hover:bg-purple-50 hover:text-purple-700 transition-colors text-sm">
                "Should I increase prices on any menu items?"
            </button>
            <button onclick="askAI(this.textContent)" class="text-left p-3 bg-gray-50 rounded-lg hover:bg-purple-50 hover:text-purple-700 transition-colors text-sm">
                "What ingredients are contributing most to my costs?"
            </button>
            <button onclick="askAI(this.textContent)" class="text-left p-3 bg-gray-50 rounded-lg hover:bg-purple-50 hover:text-purple-700 transition-colors text-sm">
                "How can I reduce food waste in my canteen?"
            </button>
            <button onclick="askAI(this.textContent)" class="text-left p-3 bg-gray-50 rounded-lg hover:bg-purple-50 hover:text-purple-700 transition-colors text-sm">
                "Create a promotional strategy for slow-moving items"
            </button>
        </div>
    </div>
</div>

<script>
    // Pre-fill chat input and submit
    function askAI(question) {
        const input = document.getElementById('chatInput');
        input.value = question;
        document.getElementById('chatForm').dispatchEvent(new Event('submit'));
    }

    // Chat form handler
    document.getElementById('chatForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        const input = document.getElementById('chatInput');
        const message = input.value.trim();
        if (!message) return;

        // Add user message
        addChatMessage(message, 'user');
        input.value = '';

        // Show typing indicator
        showTypingIndicator();

        try {
            const response = await fetch('canteen_ai_api.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ message: message })
            });
            const data = await response.json();
            
            removeTypingIndicator();
            addChatMessage(data.response, 'ai');
        } catch (error) {
            removeTypingIndicator();
            addChatMessage('Sorry, I encountered an error. Please try again.', 'ai');
        }
    });

    function addChatMessage(text, sender) {
        const container = document.getElementById('chatMessages');
        const div = document.createElement('div');
        div.className = 'flex gap-3 ' + (sender === 'user' ? 'justify-end' : '');
        
        if (sender === 'user') {
            div.innerHTML = `
                <div class="bg-gradient-to-r from-purple-600 to-pink-600 text-white p-4 rounded-2xl rounded-tr-none shadow-sm max-w-3xl">
                    <p>${escapeHtml(text)}</p>
                </div>
            `;
        } else {
            div.innerHTML = `
                <div class="w-10 h-10 bg-purple-100 rounded-full flex items-center justify-center flex-shrink-0">
                    <i class="fas fa-robot text-purple-600"></i>
                </div>
                <div class="bg-white p-4 rounded-2xl rounded-tl-none shadow-sm max-w-3xl">
                    <p class="text-gray-700">${formatResponse(text)}</p>
                </div>
            `;
        }
        
        container.appendChild(div);
        container.scrollTop = container.scrollHeight;
    }

    function showTypingIndicator() {
        const container = document.getElementById('chatMessages');
        const div = document.createElement('div');
        div.id = 'typingIndicator';
        div.className = 'flex gap-3';
        div.innerHTML = `
            <div class="w-10 h-10 bg-purple-100 rounded-full flex items-center justify-center flex-shrink-0">
                <i class="fas fa-robot text-purple-600"></i>
            </div>
            <div class="bg-white p-4 rounded-2xl rounded-tl-none shadow-sm">
                <div class="flex gap-2">
                    <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce"></div>
                    <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 0.1s"></div>
                    <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 0.2s"></div>
                </div>
            </div>
        `;
        container.appendChild(div);
        container.scrollTop = container.scrollHeight;
    }

    function removeTypingIndicator() {
        const indicator = document.getElementById('typingIndicator');
        if (indicator) indicator.remove();
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    function formatResponse(text) {
        // Convert markdown-like formatting to HTML
        return escapeHtml(text)
            .replace(/\n/g, '<br>')
            .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
            .replace(/\*(.*?)\*/g, '<em>$1</em>')
            .replace(/`(.*?)`/g, '<code class="bg-gray-100 px-1 rounded">$1</code>');
    }
</script>

<?php require_once 'canteen_footer.php'; ?>
