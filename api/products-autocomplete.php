<?php
session_start();
require_once '../config/config.php';
require_once '../config/Database.php';
require_once '../models/Product.php';

header('Content-Type: application/json');

try {
    $database = new Database();
    $db = $database->getConnection();
    $productModel = new Product($db);
    
    // Try to get products from database
    $products_data = $productModel->getAllProductsWithCategory();
    $autocompleteData = [];
    
    foreach ($products_data as $product) {
        $autocompleteData[] = [
            'id' => $product['product_id'],
            'name' => $product['name'],
            'category' => $product['category_name'] ?? 'Uncategorized',
            'description' => $product['description'] ?? '',
            'price' => number_format($product['price'], 2)
        ];
    }
    
    echo json_encode($autocompleteData);
    
} catch (Exception $e) {
    // Fallback to demo data if database fails
    $demo_products = [
        [
            'id' => 1,
            'name' => 'Wireless Headphones',
            'category' => 'Electronics',
            'description' => 'High-quality wireless headphones with noise cancellation',
            'price' => '129.99'
        ],
        [
            'id' => 2,
            'name' => 'Smart Watch',
            'category' => 'Electronics', 
            'description' => 'Feature-rich smartwatch with health monitoring',
            'price' => '299.99'
        ],
        [
            'id' => 3,
            'name' => 'Laptop Backpack',
            'category' => 'Accessories',
            'description' => 'Durable laptop backpack with multiple compartments',
            'price' => '59.99'
        ],
        [
            'id' => 4,
            'name' => 'Bluetooth Speaker',
            'category' => 'Electronics',
            'description' => 'Portable Bluetooth speaker with crystal clear sound',
            'price' => '89.99'
        ],
        [
            'id' => 5, 
            'name' => 'Gaming Mouse',
            'category' => 'Electronics',
            'description' => 'High-precision gaming mouse with RGB lighting',
            'price' => '49.99'
        ],
        [
            'id' => 6,
            'name' => 'USB-C Hub',
            'category' => 'Electronics',
            'description' => 'Multi-port USB-C hub for expanding connectivity',
            'price' => '39.99'
        ],
        [
            'id' => 7,
            'name' => 'Cotton T-Shirt',
            'category' => 'Clothing',
            'description' => 'Comfortable cotton t-shirt in multiple colors',
            'price' => '19.99'
        ],
        [
            'id' => 8,
            'name' => 'Garden Tools Set',
            'category' => 'Home & Garden',
            'description' => 'Complete gardening tool set for home use',
            'price' => '39.99'
        ],
        [
            'id' => 9,
            'name' => 'Bestseller Novel',
            'category' => 'Books & Media',
            'description' => 'Award-winning fiction novel by popular author',
            'price' => '14.99'
        ],
        [
            'id' => 10,
            'name' => 'Basketball',
            'category' => 'Sports & Outdoors',
            'description' => 'Professional quality basketball for indoor and outdoor use',
            'price' => '29.99'
        ]
    ];
    
    echo json_encode($demo_products);
}
?>