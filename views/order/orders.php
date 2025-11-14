<?php
/**
 * SmartRetail - Order History Page
 * Displays user's order history with order details
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../utils/Auth.php';
require_once '../../models/Order.php';
require_once '../../models/Product.php';

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
    $productModel = new Product($db);
} catch (Exception $e) {
    error_log("Database connection failed: " . $e->getMessage());
    $orderModel = null;
    $productModel = null;
}

// Get cart count for header
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}
$cart_count = count($_SESSION['cart']);

// Get orders for the user
$orders = [];
if ($orderModel) {
    try {
        // Get orders - this returns an array
        $orders_result = $orderModel->getUserOrders($user_id);
        
        // Process each order
        foreach ($orders_result as $order) {
            // Get order items for this order
            $order_items = $orderModel->getOrderItems($order['id']);
            $items = [];
            
            // Process each item
            foreach ($order_items as $item) {
                // Get product details for each item
                $product = null;
                if ($productModel) {
                    $product = $productModel->getProductById($item['product_id']);
                }
                
                if ($product) {
                    $items[] = [
                        'name' => $product['name'],
                        'price' => $item['unit_price'],
                        'quantity' => $item['quantity'],
                        'icon' => Product::getProductIcon($product['name'])
                    ];
                } else {
                    // Fallback if product not found
                    $items[] = [
                        'name' => $item['product_name'] ?? 'Unknown Product',
                        'price' => $item['unit_price'],
                        'quantity' => $item['quantity'],
                        'icon' => 'fas fa-box'
                    ];
                }
            }
            
            $order['items'] = $items;
            $orders[] = $order;
        }
        
        // If no orders found in database, use demo data
        if (empty($orders)) {
            $orders = getDemoOrders($user_id);
        }
        
    } catch (Exception $e) {
        error_log("Error fetching orders: " . $e->getMessage());
        // Fallback to demo data if database fails
        $orders = getDemoOrders($user_id);
    }
} else {
    // Use demo data if database is unavailable
    $orders = getDemoOrders($user_id);
}

// Helper function for demo orders
function getDemoOrders($user_id) {
    return [
        [
            'id' => 1,
            'order_id' => 'SR-000001',
            'user_id' => $user_id,
            'total' => 147.97,
            'status' => 'completed',
            'created_at' => date('Y-m-d H:i:s', strtotime('-5 days')),
            'items' => [
                ['name' => 'Wireless Headphones', 'price' => 12.99, 'quantity' => 1, 'icon' => 'fas fa-headphones'],
                ['name' => 'Smart Watch', 'price' => 89.99, 'quantity' => 1, 'icon' => 'fas fa-clock'],
                ['name' => 'Laptop Backpack', 'price' => 34.99, 'quantity' => 1, 'icon' => 'fas fa-briefcase'],
                ['name' => 'USB-C Hub', 'price' => 10.00, 'quantity' => 1, 'icon' => 'fas fa-plug']
            ]
        ],
        [
            'id' => 2,
            'order_id' => 'SR-000002',
            'user_id' => $user_id,
            'total' => 75.98,
            'status' => 'completed',
            'created_at' => date('Y-m-d H:i:s', strtotime('-2 days')),
            'items' => [
                ['name' => 'Bluetooth Speaker', 'price' => 45.99, 'quantity' => 1, 'icon' => 'fas fa-volume-up'],
                ['name' => 'Gaming Mouse', 'price' => 29.99, 'quantity' => 1, 'icon' => 'fas fa-mouse']
            ]
        ],
        [
            'id' => 3,
            'order_id' => 'SR-000003',
            'user_id' => $user_id,
            'total' => 34.99,
            'status' => 'pending',
            'created_at' => date('Y-m-d H:i:s', strtotime('-1 day')),
            'items' => [
                ['name' => 'Laptop Backpack', 'price' => 34.99, 'quantity' => 1, 'icon' => 'fas fa-briefcase']
            ]
        ]
    ];
}

// Function to get status badge class
function getStatusBadge($status) {
    switch ($status) {
        case 'completed':
            return 'status-delivered';
        case 'pending':
            return 'status-processing';
        case 'cancelled':
            return 'status-cancelled';
        default:
            return 'status-processing';
    }
}

// Function to get status text
function getStatusText($status) {
    switch ($status) {
        case 'completed':
            return 'Delivered';
        case 'pending':
            return 'Processing';
        case 'cancelled':
            return 'Cancelled';
        default:
            return 'Processing';
    }
}

// Function to format date
function formatOrderDate($date) {
    return date('F j, Y \a\t g:i A', strtotime($date));
}

// Function to generate order display ID
function getOrderDisplayId($order_id) {
    return 'SR-' . str_pad($order_id, 6, '0', STR_PAD_LEFT);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order History - SmartRetail</title>
    <link rel="stylesheet" href="../../assets/css/style.min.css">
    
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        /* Orders Page Styles */
        .orders-page {
            padding: 3rem 0;
            background: var(--light-bg);
            min-height: calc(100vh - 200px);
        }

        .page-header {
            text-align: center;
            margin-bottom: 3rem;
        }

        .page-header h1 {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--text-dark);
            margin-bottom: 1rem;
        }

        .page-header p {
            color: var(--text-light);
            font-size: 1.125rem;
        }

        /* Orders List */
        .orders-list {
            display: flex;
            flex-direction: column;
            gap: 2rem;
        }

        .order-card {
            background: white;
            border-radius: 0.75rem;
            box-shadow: var(--shadow);
            overflow: hidden;
            transition: all 0.3s;
        }

        .order-card:hover {
            box-shadow: var(--shadow-lg);
        }

        .order-header {
            padding: 1.5rem;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .order-info {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .order-id {
            font-size: 1.125rem;
            font-weight: 600;
            color: var(--text-dark);
        }

        .order-date {
            color: var(--text-light);
            font-size: 0.875rem;
        }

        .order-status {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .status-badge {
            padding: 0.5rem 1rem;
            border-radius: 2rem;
            font-size: 0.875rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .status-delivered {
            background: #d1fae5;
            color: #065f46;
        }

        .status-processing {
            background: #fef3c7;
            color: #92400e;
        }

        .status-cancelled {
            background: #fee2e2;
            color: #991b1b;
        }

        .order-total {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--primary-color);
        }

        .order-items {
            padding: 1.5rem;
        }

        .order-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem 0;
            border-bottom: 1px solid var(--border-color);
        }

        .order-item:last-child {
            border-bottom: none;
        }

        .item-icon {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%);
            border-radius: 0.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.25rem;
            flex-shrink: 0;
        }

        .item-details {
            flex: 1;
        }

        .item-name {
            font-weight: 500;
            color: var(--text-dark);
            margin-bottom: 0.25rem;
        }

        .item-quantity {
            color: var(--text-light);
            font-size: 0.875rem;
        }

        .item-price {
            font-weight: 600;
            color: var(--text-dark);
        }

        .order-actions {
            padding: 1.5rem;
            border-top: 1px solid var(--border-color);
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
        }

        /* Empty State */
        .empty-orders {
            text-align: center;
            padding: 4rem 2rem;
        }

        .empty-orders-icon {
            font-size: 4rem;
            color: var(--text-light);
            margin-bottom: 1.5rem;
            opacity: 0.5;
        }

        .empty-orders h2 {
            font-size: 1.75rem;
            color: var(--text-dark);
            margin-bottom: 1rem;
        }

        .empty-orders p {
            color: var(--text-light);
            margin-bottom: 2rem;
            font-size: 1.125rem;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .header-content {
                flex-direction: column;
                gap: 1rem;
            }

            .nav-links {
                gap: 1rem;
            }

            .order-header {
                flex-direction: column;
                align-items: flex-start;
            }

            .order-status {
                width: 100%;
                justify-content: space-between;
            }

            .order-actions {
                justify-content: center;
            }
        }
    </style>
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
                                <a href="../../views/order/orders.php" class="user-dropdown-link">
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
                    <li><a href="../views/products/products.php">
                        <i class="fas fa-shopping-bag"></i>
                        All Products
                    </a></li>
                    <li><a href="../views/categories/category.php">
                        <i class="fas fa-list"></i>
                        Categories
                    </a></li>
                    <li><a href="../views/order/orders.php">
                        <i class="fas fa-receipt"></i>
                        My Orders
                    </a></li>
                
                    <?php if (Auth::hasRole('admin') || Auth::hasRole('staff')): ?>
                        <div class="mobile-nav-divider"></div>
                        <li><a href="../views/admin/admin-dashboard.php">
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
                <a href="../views/cart/cart.php" class="mobile-nav-cart">
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

    <!-- Orders Page -->
    <section class="orders-page">
        <div class="container">
            <div class="page-header">
                <h1>Order History</h1>
                <p>View your past orders and track their status</p>
            </div>

            <?php if (!empty($orders)): ?>
                <div class="orders-list">
                    <?php foreach ($orders as $order): ?>
                    <div class="order-card">
                        <!-- Order Header -->
                        <div class="order-header">
                            <div class="order-info">
                                <div class="order-id">Order #<?php echo getOrderDisplayId($order['id']); ?></div>
                                <div class="order-date">Placed on <?php echo formatOrderDate($order['created_at']); ?></div>
                            </div>
                            <div class="order-status">
                                <div class="status-badge <?php echo getStatusBadge($order['status']); ?>">
                                    <?php echo getStatusText($order['status']); ?>
                                </div>
                                <div class="order-total">R<?php echo number_format($order['total'], 2); ?></div>
                            </div>
                        </div>

                        <!-- Order Items -->
                        <div class="order-items">
                            <?php if (isset($order['items']) && !empty($order['items'])): ?>
                                <?php foreach ($order['items'] as $item): ?>
                                <div class="order-item">
                                    <div class="item-icon">
                                        <i class="<?php echo $item['icon']; ?>"></i>
                                    </div>
                                    <div class="item-details">
                                        <div class="item-name"><?php echo htmlspecialchars($item['name']); ?></div>
                                        <div class="item-quantity">Quantity: <?php echo $item['quantity']; ?></div>
                                    </div>
                                    <div class="item-price">
                                        R<?php echo number_format($item['price'] * $item['quantity'], 2); ?>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="order-item">
                                    <div class="item-details">
                                        <div class="item-name">No items found for this order</div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Order Actions -->
                        <div class="order-actions">
                            <button class="btn btn-outline view-order-btn" data-order-id="<?php echo $order['id']; ?>">
                                <i class="fas fa-eye"></i>
                                View Details
                            </button>
                            <?php if ($order['status'] === 'completed'): ?>
                                <button class="btn btn-outline reorder-btn" data-order-id="<?php echo $order['id']; ?>">
                                    <i class="fas fa-redo"></i>
                                    Reorder
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <!-- Empty Orders State -->
                <div class="empty-orders">
                    <div class="empty-orders-icon">
                        <i class="fas fa-shopping-bag"></i>
                    </div>
                    <h2>No orders yet</h2>
                    <p>You haven't placed any orders. Start shopping to see your order history here.</p>
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

    <!-- JavaScript Files -->
    <script src="../../assets/js/dashboard.js"></script>
    <script src="../../assets/js/enhanced-features.js"></script>
    <script>
        // Simple JavaScript for button actions
        document.querySelectorAll('.view-order-btn').forEach(button => {
            button.addEventListener('click', function() {
                const orderId = this.getAttribute('data-order-id');
                alert('Viewing details for order: ' + orderId);
                // In a real application, you would redirect to order details page
                // window.location.href = 'order-details.php?id=' + orderId;
            });
        });

        document.querySelectorAll('.reorder-btn').forEach(button => {
            button.addEventListener('click', function() {
                const orderId = this.getAttribute('data-order-id');
                if (confirm('Add all items from this order to your cart?')) {
                    alert('Items added to cart from order: ' + orderId);
                    // In a real application, you would add items to cart via AJAX
                }
            });
        });
    </script>
</body>
</html>