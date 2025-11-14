<?php
/**
 * SmartRetail - Shopping Cart
 * Modern, responsive cart page with order summary
 */

session_start();

require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../utils/Auth.php';
require_once '../../models/Order.php';
require_once '../../models/User.php';

// Require authentication
Auth::requireAuth();

// Get user information
$user_id = Auth::getUserId();
$username = $_SESSION['username'];
$full_name = $_SESSION['full_name'];
$email = $_SESSION['email'];

// Initialize database connection
try {
    $database = new Database();
    $db = $database->getConnection();
    $orderModel = new Order($db);
    $userModel = new User($db);
} catch (Exception $e) {
    error_log("Database connection failed: " . $e->getMessage());
}

// Initialize cart in session if not exists
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Get cart items from session
$cart_items = $_SESSION['cart'];
$cart_total = 0;
$cart_count = count($cart_items);

// Map product names to icons
$product_icons = [
    'Wireless Headphones' => 'fas fa-headphones',
    'Smart Watch' => 'fas fa-clock',
    'Laptop Backpack' => 'fas fa-briefcase',
    'Bluetooth Speaker' => 'fas fa-volume-up',
    'Gaming Mouse' => 'fas fa-mouse',
    'USB-C Hub' => 'fas fa-plug',
    'Product' => 'fas fa-box' // Default icon
];

// Calculate total and ensure we have proper data structure
foreach ($cart_items as &$item) {
    if (!isset($item['quantity'])) {
        $item['quantity'] = 1;
    }
    if (isset($item['price'])) {
        $cart_total += $item['price'] * $item['quantity'];
    }
    
    // Ensure all required fields exist for display
    $item['id'] = $item['id'] ?? uniqid();
    $item['name'] = $item['name'] ?? 'Product';
    $item['price'] = $item['price'] ?? 0;
    $item['icon'] = $product_icons[$item['name']] ?? $product_icons['Product'];
    $item['stock_quantity'] = $item['stock_quantity'] ?? 10;
}

// Handle cart actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    error_log("=== POST REQUEST RECEIVED ===");
    error_log("POST data: " . print_r($_POST, true));
    
    if (isset($_POST['update_quantity']) && isset($_POST['item_id']) && isset($_POST['action'])) {
        $item_id = $_POST['item_id'];
        $action = $_POST['action'];
        
        error_log("Updating quantity for item: $item_id, action: $action");
        
        // Handle increase/decrease actions - USE CONSISTENT 'id' FIELD
        foreach ($_SESSION['cart'] as &$item) {
            if ($item['id'] == $item_id) { // Only check 'id' field
                if ($action === 'increase') {
                    $item['quantity'] = min($item['quantity'] + 1, $item['stock_quantity'] ?? 10);
                    error_log("Increased quantity to: " . $item['quantity']);
                } elseif ($action === 'decrease') {
                    $item['quantity'] = max($item['quantity'] - 1, 1);
                    error_log("Decreased quantity to: " . $item['quantity']);
                }
                break;
            }
        }
        
        $_SESSION['cart'] = array_values($_SESSION['cart']);
        header('Location: cart.php?updated=1');
        exit;
        
    } elseif (isset($_POST['remove_item']) && isset($_POST['item_id'])) {
        session_start();
        $item_id = $_POST['item_id'];

        error_log("=== REMOVING ITEM ===");
        error_log("Item ID to remove: $item_id");
        error_log("Cart before removal: " . print_r($_SESSION['cart'], true));

        // Remove item - USE CONSISTENT 'id' FIELD
        $_SESSION['cart'] = array_values(array_filter($_SESSION['cart'], function ($item) use ($item_id) {
            return $item['id'] == $item_id; // Only check 'id' field
        }));

        error_log("Cart after removal: " . print_r($_SESSION['cart'], true));

        header('Location: ' . $_SERVER['PHP_SELF'] . '?removed=1');
        exit;  
    }
}

// Handle checkout
if (isset($_GET['checkout']) && !empty($cart_items)) {
    // Debug: Check what we're passing to checkout
    error_log("=== CHECKOUT INITIATED ===");
    error_log("Cart items count: " . count($cart_items));
    error_log("Cart total: R" . $cart_total);
    error_log("Session ID: " . session_id());
    
    // Simply redirect to checkout page - cart data is in session
    header('Location: checkout.php');
    exit;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart - SmartRetail</title>
    <link rel="stylesheet" href="../../assets/css/style.min.css">
    
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="container">
            <div class="header-content">
                <!-- Mobile Navigation Toggle -->
                <button class="mobile-nav-toggle" id="mobileNavToggle">
                    <i class="fas fa-bars"></i>
                    <span class="logo">
                        <i class="fas fa-store"></i>
                        SmartRetail
                    </span>
                </button>
            
                <!-- Desktop Logo (hidden on mobile) -->
                <a href="/" class="logo desktop-logo">
                    <i class="fas fa-store"></i>
                    SmartRetail
                </a>
                
                <!-- Navigation -->
                <nav>
                    <ul class="nav-links">
                        <li><a href="../products/products.php">Products</a></li>
                        <li><a href="../categories/category.php">Categories</a></li>
                        <li><a href="../../dashboard.php">Dashboard</a></li>
                    </ul>
                </nav>
                
                <!-- Header Actions -->
                <div class="header-actions">    
                    <!-- Shopping Cart -->
                    <a href="../cart/cart.php" class="cart-icon" title="Shopping Cart">
                        <i class="fas fa-shopping-cart"></i>
                        <?php if ($cart_count > 0): ?>
                            <span class="cart-count"><?php echo $cart_count; ?></span>
                        <?php endif; ?>
                    </a>
                    
                    <!-- User Dropdown Menu -->
                    <div class="user-menu">
                        <button class="btn btn-outline" id="userMenuButton" style="display: flex; align-items: center; gap: 0.5rem;">
                            <i class="fas fa-user"></i>
                            <?php echo htmlspecialchars($username); ?>
                            <i class="fas fa-chevron-down" style="font-size: 0.75rem;"></i>
                        </button>
                        
                        <div class="user-dropdown" id="userDropdown">
                            <div class="user-dropdown-header">
                                <div class="username"><?php echo htmlspecialchars($full_name); ?></div>
                                <div class="email"><?php echo htmlspecialchars($email); ?></div>
                                <div class="role"><?php echo ucfirst($_SESSION['role'] ?? 'User'); ?> Account</div>
                            </div>
                            
                            <div class="user-dropdown-links">
                                <a href="../order/orders.php" class="user-dropdown-link">
                                    <i class="fas fa-shopping-bag"></i>
                                    My Orders
                                </a>
                                
                                <?php if (Auth::hasRole('admin') || Auth::hasRole('staff')): ?>
                                    <div class="user-dropdown-divider"></div>
                                    <a href="views/admin/admin-dashboard.php" class="user-dropdown-link">
                                        <i class="fas fa-cog"></i>
                                        Admin Panel
                                    </a>
                                <?php endif; ?>
                                
                                <div class="user-dropdown-divider"></div>
                                <a href="../../logout.php" class="user-dropdown-link logout">
                                    <i class="fas fa-sign-out-alt"></i>
                                    Logout
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- Mobile Navigation -->
    <div class="mobile-nav" id="mobileNav">
        <div class="mobile-nav-sidebar">
            <!-- Single Line Header -->
            <div class="mobile-nav-header">
                <div class="mobile-nav-brand">
                    <i class="fas fa-store"></i>
                    <span>SmartRetail</span>
                </div>
                <button class="mobile-nav-close" id="mobileNavClose">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <!-- User Info -->
            <div class="mobile-nav-user">
                <div class="mobile-nav-user-avatar">
                    <?php echo strtoupper(substr($full_name, 0, 1)); ?>
                </div>
                <div class="mobile-nav-user-info">
                    <div class="username"><?php echo htmlspecialchars($full_name); ?></div>
                    <div class="email"><?php echo htmlspecialchars($email); ?></div>
                    <div class="role"><?php echo ucfirst($_SESSION['role'] ?? 'User'); ?></div>
                </div>
            </div>

            <!-- Navigation Links -->
            <div class="mobile-nav-content">
                <ul class="mobile-nav-links">
                    <li><a href="dashboard.php" class="active">
                        <i class="fas fa-home"></i>
                        Dashboard
                    </a></li>
                    <li><a href="../products/products.php">
                        <i class="fas fa-shopping-bag"></i>
                        All Products
                    </a></li>
                    <li><a href="../categories/category.php">
                        <i class="fas fa-list"></i>
                        Categories
                    </a></li>
                    <li><a href="../order/orders.php">
                        <i class="fas fa-receipt"></i>
                        My Orders
                    </a></li>
                
                    <?php if (Auth::hasRole('admin') || Auth::hasRole('staff')): ?>
                        <div class="mobile-nav-divider"></div>
                        <li><a href="views/admin/admin-dashboard.php">
                            <i class="fas fa-cog"></i>
                            Admin Panel
                        </a></li>
                    <?php endif; ?>
                
                    <div class="mobile-nav-divider"></div>
                    <li><a href="../../logout.php" class="logout-link">
                        <i class="fas fa-sign-out-alt"></i>
                        Logout
                    </a></li>
                </ul>
            </div>

            <!-- Cart Summary -->
            <div class="mobile-nav-footer">
                <a href="views/cart/cart.php" class="mobile-nav-cart">
                    <div class="mobile-nav-cart-info">
                        <div class="mobile-nav-cart-label">Shopping Cart</div>
                    <div class="mobile-nav-cart-count">
                            <?php echo $cart_count; ?> item<?php echo $cart_count !== 1 ? 's' : ''; ?>
                        </div>
                    </div>
                    <div class="mobile-nav-cart-icon">
                        <i class="fas fa-shopping-cart"></i>
                        <?php if ($cart_count > 0): ?>
                            <span class="mobile-nav-cart-badge"><?php echo $cart_count; ?></span>
                        <?php endif; ?>
                    </div>
                </a>
            </div>
        </div>
    </div>

    <!-- Cart Page -->
    <section class="cart-page">
        <div class="container">
            <div class="page-header">
                <h1>Shopping Cart</h1>
                <p>Review your items and proceed to checkout</p>
            </div>

            <?php if (!empty($cart_items)): ?>
                <div class="cart-content">
                    <!-- Cart Items -->
                    <div class="cart-items">
                            <?php foreach ($cart_items as $item): ?>
                            <div class="cart-item" data-item-id="<?php echo $item['id']; ?>">
                                <div class="item-icon">
                                    <i class="<?php echo $item['icon']; ?>"></i>
                                </div>
                                <div class="item-details">
                                    <h3 class="item-name"><?php echo htmlspecialchars($item['name']); ?></h3>
                                    <div class="item-price">R<?php echo number_format($item['price'], 2); ?></div>
                                    <div class="item-stock">
                                        <i class="fas fa-check-circle"></i>
                                        <?php echo $item['stock_quantity']; ?> in stock
                                    </div>
                                <div class="item-actions">
                                <!-- Quantity Update Form -->
                                <form method="POST" class="quantity-selector">
                                    <input type="hidden" name="update_quantity" value="1">
                                    <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                                    <button type="submit" name="action" value="decrease" class="quantity-btn">
                                        <i class="fas fa-minus"></i>
                                    </button>
                                    <span class="quantity-display" style="padding: 0.5rem; min-width: 60px; text-align: center; display: inline-block; border: 1px solid var(--border-color); border-radius: 0.375rem;">
                                        <?php echo $item['quantity']; ?>
                                    </span>
                                    <button type="submit" name="action" value="increase" class="quantity-btn">
                                        <i class="fas fa-plus"></i>
                                    </button>
                                </form>

                                <!-- Remove Form -->
                                <form class="remove-form" onsubmit="removeFromCart(event, '<?php echo $item['id']; ?>')">
                                    <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                                    <button type="submit" class="remove-btn">
                                        <i class="fas fa-trash"></i>
                                        <span>Remove</span>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
    
                    <!-- Continue Shopping -->
                    <div class="continue-shopping">
                        <a href="../../dashboard.php" class="btn btn-outline">
                            <i class="fas fa-arrow-left"></i>
                                Continue Shopping
                            </a>
                        </div>
                    </div>    

                    <!-- Order Summary -->
                    <div class="order-summary">
                        <h2 class="summary-header">Order Summary</h2>
                        
                        <div class="summary-row">
                            <span class="summary-label">Subtotal:</span>
                            <span class="summary-value">R<?php echo number_format($cart_total, 2); ?></span>
                        </div>
                        
                        <div class="summary-row">
                            <span class="summary-label">Shipping:</span>
                            <span class="summary-value">Free</span>
                        </div>
                        
                        <div class="summary-row">
                            <span class="summary-label">Total:</span>
                            <span class="summary-value summary-total">R<?php echo number_format($cart_total, 2); ?></span>
                        </div>

                        <div class="shipping-note">
                            <i class="fas fa-shipping-fast"></i>
                            Free shipping on all orders
                        </div>

                        <a href="?checkout=1" class="btn btn-primary checkout-btn" onclick="return confirm('Proceed to checkout?')">
                            <i class="fas fa-lock"></i>
                            Proceed to Checkout
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <!-- Empty Cart -->
                <div class="empty-cart">
                    <div class="empty-cart-icon">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                    <h2>Your cart is empty</h2>
                    <p>Looks like you haven't added any items to your cart yet.</p>
                    <a href="../products/products.php" class="btn btn-primary">
                        <i class="fas fa-shopping-bag"></i>
                        Start Shopping
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <!-- About Section -->
                <div class="footer-section">
                    <h3>About SmartRetail</h3>
                    <p>Your trusted destination for quality products and exceptional service.</p>
                </div>
            </div>

            <div class="footer-bottom">
                <p>&copy; <?php echo date('Y'); ?> SmartRetail System. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <!-- JavaScript -->
    <script src="../../assets/js/cart.js"></script>
    <script src="../../assets/js/enhanced-features.js"></script>
    <script src="../../assets/js/enhanced-features.js"></script>
    <script src="../../assets/js/dynamic-cart.js"></script>
</body>
</html>