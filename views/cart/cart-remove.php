<?php
session_start();
require_once '../../config/config.php';

header('Content-Type: application/json');

// Simple removal logic
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['item_id'])) {
    $item_id = $_POST['item_id'];
    
    // Remove item
    if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
        $_SESSION['cart'] = array_filter($_SESSION['cart'], function($item) use ($item_id) {
            return isset($item['id']) && $item['id'] != $item_id;
        });
        $_SESSION['cart'] = array_values($_SESSION['cart']); // Re-index
    }
    
    // Calculate total
    $total = 0;
    $count = 0;
    if (isset($_SESSION['cart'])) {
        foreach ($_SESSION['cart'] as $item) {
            if (isset($item['price']) && isset($item['quantity'])) {
                $total += $item['price'] * $item['quantity'];
            }
        }
        $count = count($_SESSION['cart']);
    }
    
    echo json_encode([
        'success' => true,
        'cart_total' => number_format($total, 2),
        'cart_count' => $count
    ]);
} else {
    echo json_encode([
        'success' => false,
        'error' => 'Invalid request'
    ]);
}
exit;
?>