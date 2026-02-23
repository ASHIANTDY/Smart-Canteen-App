<?php
require_once 'canteen_config.php';

header('Content-Type: application/json');

// Get JSON input
$json = file_get_contents('php://input');
$data = json_decode($json, true);
$userMessage = $data['message'] ?? '';

if (empty($userMessage)) {
    jsonResponse(['error' => 'No message provided'], 400);
}

// Get current business data for context
$stats = getDashboardStats($pdo);
$menuItems = getMenuItemsWithCosts($pdo);
$inventory = getInventoryStatus($pdo);
$topItems = getTopSellingItems($pdo, 5);

// Build context for AI
$context = buildBusinessContext($stats, $menuItems, $inventory, $topItems);

// Call Qwen AI API
$aiResponse = callQwenAI($userMessage, $context);

jsonResponse(['response' => $aiResponse]);

function buildBusinessContext($stats, $menuItems, $inventory, $topItems) {
    $lowStockItems = array_filter($inventory, fn($i) => $i['stock_status'] === 'low');
    $profitableItems = array_filter($menuItems, fn($i) => $i['profit_margin'] > 0);
    $unprofitableItems = array_filter($menuItems, fn($i) => $i['profit_margin'] <= 0);
    
    $context = "CANTEEN BUSINESS DATA:\n\n";
    
    // Financial Summary
    $context .= "FINANCIAL SUMMARY:\n";
    $context .= "- Today's Sales: ₱" . number_format($stats['today_sales'], 2) . "\n";
    $context .= "- Today's Expenses: ₱" . number_format($stats['today_expenses'], 2) . "\n";
    $context .= "- Today's Profit/Loss: ₱" . number_format($stats['today_profit'], 2) . "\n";
    $context .= "- Monthly Sales: ₱" . number_format($stats['month_sales'], 2) . "\n";
    $context .= "- Monthly Expenses: ₱" . number_format($stats['month_expenses'], 2) . "\n";
    $context .= "- Monthly Profit/Loss: ₱" . number_format($stats['month_profit'], 2) . "\n";
    $context .= "- Total Menu Items: " . $stats['total_menu_items'] . "\n";
    $context .= "- Total Ingredients: " . $stats['total_ingredients'] . "\n\n";
    
    // Top Selling Items
    $context .= "TOP SELLING ITEMS (Last 30 Days):\n";
    foreach ($topItems as $item) {
        $context .= "- " . $item['name'] . ": " . $item['total_qty'] . " sold (₱" . number_format($item['total_sales'], 2) . ")\n";
    }
    $context .= "\n";
    
    // Menu Items with Margins
    $context .= "MENU ITEMS WITH PROFIT MARGINS:\n";
    foreach (array_slice($menuItems, 0, 10) as $item) {
        $margin = $item['profit_margin'];
        $marginPct = $item['selling_price'] > 0 ? round(($margin / $item['selling_price']) * 100, 1) : 0;
        $context .= "- " . $item['name'] . ": Cost ₱" . number_format($item['cost_price'], 2) . 
                   ", Price ₱" . number_format($item['selling_price'], 2) . 
                   ", Margin ₱" . number_format($margin, 2) . " (" . $marginPct . "%)\n";
    }
    $context .= "\n";
    
    // Low Stock Alerts
    if (count($lowStockItems) > 0) {
        $context .= "LOW STOCK ALERTS:\n";
        foreach ($lowStockItems as $item) {
            $context .= "- " . $item['name'] . ": " . $item['stock_quantity'] . " " . $item['unit'] . 
                       " (Reorder at: " . $item['reorder_level'] . ")\n";
        }
        $context .= "\n";
    }
    
    return $context;
}

function callQwenAI($userMessage, $context) {
    // Qwen API Configuration
    // Note: You'll need to set your actual API key
    $apiKey = $_ENV['QWEN_API_KEY'] ?? 'YOUR_QWEN_API_KEY_HERE';
    $apiUrl = 'https://dashscope.aliyuncs.com/api/v1/services/aigc/text-generation/generation';
    
    // If no API key is configured, use simulated responses
    if ($apiKey === 'YOUR_QWEN_API_KEY_HERE') {
        return generateSimulatedResponse($userMessage, $context);
    }
    
    $prompt = "You are an expert canteen business consultant AI assistant. Use the following business data to provide specific, actionable advice.\n\n";
    $prompt .= $context;
    $prompt .= "\nUSER QUESTION: " . $userMessage . "\n\n";
    $prompt .= "Provide a helpful, specific response based on the actual business data above. Be concise but thorough.";
    
    $payload = [
        'model' => 'qwen-turbo',
        'input' => [
            'prompt' => $prompt
        ],
        'parameters' => [
            'result_format' => 'message',
            'max_tokens' => 1500,
            'temperature' => 0.7
        ]
    ];
    
    $ch = curl_init($apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $apiKey
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if ($httpCode === 200) {
        $data = json_decode($response, true);
        return $data['output']['text'] ?? $data['output']['choices'][0]['message']['content'] ?? 'No response from AI';
    }
    
    // Fallback to simulated response
    return generateSimulatedResponse($userMessage, $context);
}

function generateSimulatedResponse($userMessage, $context) {
    $message = strtolower($userMessage);
    
    // Extract data from context for personalized responses
    preg_match('/Today\'s Profit\/Loss: ₱([0-9,.]+)/', $context, $todayProfitMatch);
    $todayProfit = isset($todayProfitMatch[1]) ? (float)str_replace(',', '', $todayProfitMatch[1]) : 0;
    
    preg_match('/Monthly Profit\/Loss: ₱([0-9,.]+)/', $context, $monthProfitMatch);
    $monthProfit = isset($monthProfitMatch[1]) ? (float)str_replace(',', '', $monthProfitMatch[1]) : 0;
    
    // Price Analysis
    if (strpos($message, 'price') !== false || strpos($message, 'pricing') !== false) {
        return "**Pricing Analysis:**\n\nBased on your menu data, here are my recommendations:\n\n" .
               "1. **High-margin items to promote:** Focus on items with 40%+ profit margins. These are your stars.\n" .
               "2. **Underpriced items:** Consider reviewing items where cost is more than 60% of selling price.\n" .
               "3. **Bundle deals:** Create meal combos with high-margin sides and drinks to increase average ticket size.\n\n" .
               "**Action:** Review your top 5 selling items and ensure they're optimally priced. A 10% price increase on popular items can significantly boost profits with minimal volume loss.";
    }
    
    // Inventory/Restock
    if (strpos($message, 'inventory') !== false || strpos($message, 'stock') !== false || strpos($message, 'restock') !== false) {
        return "**Inventory Recommendations:**\n\n" .
               "Based on your current stock levels:\n\n" .
               "1. **Immediate Action:** Restock items marked as 'Low' status immediately to avoid stockouts.\n" .
               "2. **Par Level System:** Maintain 2x reorder level as safety stock for high-demand ingredients.\n" .
               "3. **First In, First Out:** Implement FIFO rotation to reduce spoilage and waste.\n" .
               "4. **Weekly Counts:** Conduct inventory counts every Monday morning for accurate tracking.\n\n" .
               "**Pro Tip:** Track usage rates and adjust reorder levels seasonally. Busy periods may require 30% more safety stock.";
    }
    
    // Profit Analysis
    if (strpos($message, 'profit') !== false || strpos($message, 'margin') !== false || strpos($message, 'loss') !== false) {
        $profitStatus = $todayProfit >= 0 ? 'profitable' : 'operating at a loss';
        return "**Profit Analysis:**\n\n" .
               "Today's performance: You are **" . $profitStatus . "** with ₱" . number_format(abs($todayProfit), 2) . "\n\n" .
               "**Key Insights:**\n" .
               "1. **Target Margin:** Aim for 60-70% gross margin on food items for healthy profitability.\n" .
               "2. **Cost Control:** Monitor your top 3 expenses daily. Small reductions compound significantly.\n" .
               "3. **Menu Mix:** Ensure 60% of your menu items are high-margin (40%+ profit).\n\n" .
               "**Monthly Outlook:** " . ($monthProfit >= 0 ? 
                   "You're on track with ₱" . number_format($monthProfit, 2) . " monthly profit." : 
                   "Monthly loss of ₱" . number_format(abs($monthProfit), 2) . " - review pricing and costs urgently.") . "\n\n" .
               "**Recommendation:** " . ($todayProfit < 0 ? 
                   "Focus on increasing sales volume or reducing portion costs immediately." : 
                   "Maintain current strategies while looking for optimization opportunities.");
    }
    
    // Menu Engineering
    if (strpos($message, 'menu') !== false || strpos($message, 'item') !== false || strpos($message, 'promote') !== false || strpos($message, 'discontinue') !== false) {
        return "**Menu Engineering Analysis:**\n\n" .
               "Using the Menu Engineering Matrix (Stars, Plow Horses, Puzzles, Dogs):\n\n" .
               "**STARS** (High Profit + High Popularity):\n- These are your signature items. Promote them heavily.\n- Consider slight price increases (5-10%)\n- Train staff to upsell these items\n\n" .
               "**PLOW HORSES** (Low Profit + High Popularity):\n- High volume but low margin - optimize recipes or raise prices\n- Use as loss leaders to drive traffic\n- Bundle with high-margin items\n\n" .
               "**PUZZLES** (High Profit + Low Popularity):\n- Rename or reposition these items\n- Feature them on menu boards/specials\n- Have staff recommend them\n\n" .
               "**DOGS** (Low Profit + Low Popularity):\n- Consider removing from menu\n- Or reinvent with better ingredients/presentation\n\n" .
               "**Action:** Analyze your sales data weekly and adjust menu placement accordingly.";
    }
    
    // Sales/Forecasting
    if (strpos($message, 'sales') !== false || strpos($message, 'forecast') !== false || strpos($message, 'predict') !== false) {
        return "**Sales Analysis & Forecasting:**\n\n" .
               "Based on your recent sales patterns:\n\n" .
               "1. **Peak Hours:** Identify your busiest times and ensure adequate staffing.\n" .
               "2. **Trending Items:** Your top sellers should drive your inventory purchasing.\n" .
               "3. **Day-of-Week Patterns:** Track which days are strongest and plan promotions for slower days.\n\n" .
               "**Forecasting Tips:**\n" .
               "- Use 4-week moving averages for demand prediction\n" .
               "- Factor in local events, weather, and holidays\n" .
               "- Maintain 15% buffer stock for unexpected demand\n\n" .
               "**Growth Strategy:** Focus on increasing frequency of visits from existing customers through loyalty programs.";
    }
    
    // Food Waste
    if (strpos($message, 'waste') !== false || strpos($message, 'spoilage') !== false) {
        return "**Food Waste Reduction Strategy:**\n\n" .
               "1. **FIFO System:** First In, First Out - always use oldest stock first\n" .
               "2. **Prep Schedules:** Prepare ingredients in batches based on predicted demand\n" .
               "3. **Portion Control:** Standardize serving sizes to reduce over-portioning\n" .
               "4. **Daily Specials:** Use near-expiry ingredients in daily specials\n" .
               "5. **Inventory Rotation:** Check expiration dates weekly and plan usage accordingly\n\n" .
               "**Target:** Keep food waste under 5% of total food costs. Track waste daily by category.\n\n" .
               "**Quick Win:** Implement a 'Chef\'s Special' using ingredients that need to be used up.";
    }
    
    // General/Default Response
    return "**Canteen Business Insights:**\n\n" .
           "Based on your current data:\n\n" .
           "**Financial Health:**\n" .
           "- Today: " . ($todayProfit >= 0 ? "Profit of ₱" : "Loss of ₱") . number_format(abs($todayProfit), 2) . "\n" .
           "- Monthly: " . ($monthProfit >= 0 ? "Profit of ₱" : "Loss of ₱") . number_format(abs($monthProfit), 2) . "\n\n" .
           "**Quick Recommendations:**\n" .
           "1. Monitor your profit margins daily - aim for 60%+ on food items\n" .
           "2. Keep inventory turnover high - fresh ingredients, less waste\n" .
           "3. Promote high-margin items actively through staff recommendations\n" .
           "4. Review low-performing items monthly - improve or remove\n\n" .
           "**Ask me specifically about:**\n" .
           "- Pricing strategies for specific items\n" .
           "- Inventory management and restocking\n" .
           "- Menu engineering and optimization\n" .
           "- Cost reduction strategies\n\n" .
           "What would you like to dive deeper into?";
}
?>
