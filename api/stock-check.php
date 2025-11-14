<?php
session_start();
require_once '../config/config.php';
require_once '../config/Database.php';
require_once '../models/Product.php';

header('Content-Type: application/json');

if (!isset($_GET['product_id'])) {
    echo json_encode(['error' => 'Product ID required']);
    exit;
}

try {
    $database = new Database();
    $db = $database->getConnection();
    $productModel = new Product($db);
    
    $product_id = intval($_GET['product_id']);
    $product = $productModel->getProductById($product_id);
    
    if ($product) {
        echo json_encode([
            'available_stock' => $product['stock_quantity'],
            'product_name' => $product['name'],
            'product_id' => $product_id
        ]);
    } else {
        // Fallback to demo data if product not found in database
        $demo_products = [
            1 => ['stock_quantity' => 120, 'name' => 'Wireless Headphones'],
            2 => ['stock_quantity' => 85, 'name' => 'Smart Watch'],
            3 => ['stock_quantity' => 200, 'name' => 'Laptop Backpack'],
            4 => ['stock_quantity' => 150, 'name' => 'Bluetooth Speaker'],
            5 => ['stock_quantity' => 75, 'name' => 'Gaming Mouse'],
            6 => ['stock_quantity' => 120, 'name' => 'USB-C Hub'],
            7 => ['stock_quantity' => 200, 'name' => 'Cotton T-Shirt'],
            8 => ['stock_quantity' => 35, 'name' => 'Garden Tools Set'],
            9 => ['stock_quantity' => 80, 'name' => 'Bestseller Novel'],
            10 => ['stock_quantity' => 60, 'name' => 'Basketball']
        ];
        
        if (isset($demo_products[$product_id])) {
            echo json_encode([
                'available_stock' => $demo_products[$product_id]['stock_quantity'],
                'product_name' => $demo_products[$product_id]['name'],
                'product_id' => $product_id
            ]);
        } else {
            echo json_encode(['error' => 'Product not found']);
        }
    }
} catch (Exception $e) {
    error_log("Stock check error: " . $e->getMessage());
    echo json_encode(['error' => 'Database error']);
}
?>