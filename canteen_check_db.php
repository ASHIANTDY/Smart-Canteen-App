<?php
require_once 'canteen_config.php';

echo "<!DOCTYPE html>
<html>
<head>
    <script src='https://cdn.tailwindcss.com'></script>
    <link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css'>
</head>
<body class='bg-gray-100 p-8'>
    <div class='max-w-4xl mx-auto'>
        <h1 class='text-2xl font-bold mb-6'>Database Check</h1>";

// Check all tables
echo "<div class='bg-white rounded-xl shadow p-6 mb-6'>";
echo "<h2 class='text-lg font-bold mb-4'>Sales Table Columns:</h2>";
echo "<ul class='space-y-2'>";

$columns = $pdo->query("SHOW COLUMNS FROM sales");
while ($col = $columns->fetch()) {
    echo "<li class='flex items-center gap-2'><i class='fas fa-columns text-blue-500'></i> " . $col['Field'] . " (" . $col['Type'] . ")</li>";
}
echo "</ul></div>";

// Check menu_items
echo "<div class='bg-white rounded-xl shadow p-6 mb-6'>";
echo "<h2 class='text-lg font-bold mb-4'>Menu Items Table Columns:</h2>";
echo "<ul class='space-y-2'>";
$columns = $pdo->query("SHOW COLUMNS FROM menu_items");
while ($col = $columns->fetch()) {
    echo "<li class='flex items-center gap-2'><i class='fas fa-columns text-green-500'></i> " . $col['Field'] . " (" . $col['Type'] . ")</li>";
}
echo "</ul></div>";

// Check ingredients
echo "<div class='bg-white rounded-xl shadow p-6 mb-6'>";
echo "<h2 class='text-lg font-bold mb-4'>Ingredients Table Columns:</h2>";
echo "<ul class='space-y-2'>";
$columns = $pdo->query("SHOW COLUMNS FROM ingredients");
while ($col = $columns->fetch()) {
    echo "<li class='flex items-center gap-2'><i class='fas fa-columns text-purple-500'></i> " . $col['Field'] . " (" . $col['Type'] . ")</li>";
}
echo "</ul></div>";

echo "<a href='canteen_fix_db.php' class='inline-block bg-blue-600 text-white px-6 py-3 rounded-xl font-semibold hover:bg-blue-700'>
    <i class='fas fa-wrench mr-2'></i>Run Fix Script
</a>";

echo "</div></body></html>";
?>
