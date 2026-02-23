<?php
require_once 'canteen_config.php';

header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'get_menu_ingredients':
        $menuId = $_GET['menu_id'] ?? 0;
        $stmt = $pdo->prepare("
            SELECT ingredient_id, quantity_required 
            FROM menu_item_ingredients 
            WHERE menu_item_id = ?
        ");
        $stmt->execute([$menuId]);
        jsonResponse(['ingredients' => $stmt->fetchAll()]);
        break;

    case 'get_stats':
        jsonResponse(getDashboardStats($pdo));
        break;

    case 'get_inventory':
        jsonResponse(getInventoryStatus($pdo));
        break;

    case 'get_sales_chart':
        $days = $_GET['days'] ?? 7;
        jsonResponse(getSalesChartData($pdo, $days));
        break;

    default:
        jsonResponse(['error' => 'Invalid action'], 400);
}
?>
