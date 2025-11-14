<?php
/**
 * SmartRetail - Admin Dashboard
 * Admin panel for managing products, stock, and customer orders
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../../config/config.php';
require_once '../../config/Database.php';
require_once '../../utils/Auth.php';
require_once '../../models/Order.php';
require_once '../../models/Product.php';
require_once '../../models/User.php';
require_once '../../models/Category.php';

// Require admin authentication
Auth::requireAuth();
if (!Auth::hasRole('admin')) {
    header('Location: ../../dashboard.php');
    exit;
}

// Get admin information
$user_id = Auth::getUserId();
$username = $_SESSION['username'];
$full_name = $_SESSION['full_name'];
$email = $_SESSION['email'];

// Initialize cart if not exists (for header cart count)
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Get cart items from session and calculate proper cart count
$cart_items = $_SESSION['cart'];
$cart_count = 0;
foreach ($cart_items as $item) {
    $cart_count += $item['quantity'] ?? 1;
}

// Initialize database connection
try {
    $database = new Database();
    $db = $database->getConnection();
    $orderModel = new Order($db);
    $productModel = new Product($db);
    $userModel = new User($db);
    $categoryModel = new Category($db);
} catch (Exception $e) {
    error_log("Database connection failed: " . $e->getMessage());
    die("Database connection failed. Please try again later.");
}

// Get dashboard statistics
try {
    $stats = [
        'total_products' => count($productModel->getAllProductsWithCategory()),
        'total_orders' => count($orderModel->getAllOrdersWithUsers()),
        'total_customers' => $userModel->getUsersCount(),
        'total_revenue' => $orderModel->getTotalRevenue(),
        'pending_orders' => $orderModel->getOrdersByStatus('pending')
    ];
} catch (Exception $e) {
    error_log("Error getting stats: " . $e->getMessage());
    $stats = [
        'total_products' => 0,
        'total_orders' => 0,
        'total_customers' => 0,
        'total_revenue' => 0,
        'pending_orders' => []
    ];
}

// Get recent orders
try {
    $recent_orders = $orderModel->getRecentOrders(10);
} catch (Exception $e) {
    error_log("Error getting recent orders: " . $e->getMessage());
    $recent_orders = [];
}

// Handle product actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_product'])) {
        // Add new product
        $product_data = [
            'name' => $_POST['name'],
            'description' => $_POST['description'],
            'price' => $_POST['price'],
            'category_id' => $_POST['category_id'],
            'stock_quantity' => $_POST['stock_quantity'],
            'image' => $_POST['image'] ?? ''
        ];
        
        if ($productModel->create($product_data)) {
            $_SESSION['success'] = "Product added successfully!";
        } else {
            $_SESSION['error'] = "Failed to add product.";
        }
        header('Location: admin-dashboard.php');
        exit;
        
    } elseif (isset($_POST['update_stock'])) {
        // Update product stock
        $product_id = $_POST['product_id'];
        $new_stock = $_POST['stock_quantity'];
        
        if ($productModel->updateStock($product_id, $new_stock)) {
            $_SESSION['success'] = "Stock updated successfully!";
        } else {
            $_SESSION['error'] = "Failed to update stock.";
        }
        header('Location: admin-dashboard.php');
        exit;
        
    } elseif (isset($_POST['update_order_status'])) {
        // Update order status
        $order_id = $_POST['order_id'];
        $new_status = $_POST['status'];
        
        if ($orderModel->updateOrderStatus($order_id, $new_status)) {
            $_SESSION['success'] = "Order status updated successfully!";
        } else {
            $_SESSION['error'] = "Failed to update order status.";
        }
        header('Location: admin-dashboard.php');
        exit;
    }
}

// Get all products and categories for forms
try {
    $products = $productModel->getAllProductsWithCategory();
    $categories = $categoryModel->getAllCategories();
    $all_orders = $orderModel->getAllOrdersWithUsers();
    
    // Debug: Check what data we're getting
    error_log("Products count: " . (is_array($products) ? count($products) : '0'));
    error_log("Categories count: " . (is_array($categories) ? count($categories) : '0'));
    error_log("Orders count: " . (is_array($all_orders) ? count($all_orders) : '0'));
    
} catch (Exception $e) {
    error_log("Error fetching data: " . $e->getMessage());
    $products = [];
    $categories = [];
    $all_orders = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - SmartRetail</title>
    <link rel="stylesheet" href="../../assets/css/style.min.css">
    
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        /* Additional admin-specific styles */
        .admin-page {
            padding: 2rem 0;
            min-height: calc(100vh - 200px);
            background: var(--light-bg);
        }

        .admin-welcome {
            background: white;
            border-radius: 0.75rem;
            box-shadow: var(--shadow);
            padding: 1.5rem;
            margin-bottom: 2rem;
        }

        .admin-welcome h2 {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: var(--text-dark);
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            border-radius: 0.75rem;
            box-shadow: var(--shadow);
            padding: 1.25rem;
            text-align: center;
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            font-size: 1.5rem;
        }

        .stat-icon.primary {
            background: #dbeafe;
            color: var(--primary-color);
        }

        .stat-icon.success {
            background: #d1fae5;
            color: var(--success-color);
        }

        .stat-icon.warning {
            background: #fef3c7;
            color: var(--warning-color);
        }

        .stat-icon.danger {
            background: #fee2e2;
            color: var(--danger-color);
        }

        .stat-value {
            font-size: 1.75rem;
            font-weight: 700;
            color: var(--text-dark);
            margin-bottom: 0.5rem;
        }

        .stat-label {
            color: var(--text-light);
            font-size: 0.875rem;
        }

        .tabs {
            background: white;
            border-radius: 0.75rem;
            box-shadow: var(--shadow);
            overflow: hidden;
            margin-bottom: 2rem;
        }

        .tab-headers {
            display: flex;
            border-bottom: 1px solid var(--border-color);
            background: var(--light-bg);
            overflow-x: auto;
        }

        .tab-header {
            padding: 1rem 1.5rem;
            cursor: pointer;
            border-bottom: 2px solid transparent;
            transition: all 0.3s;
            font-weight: 500;
            white-space: nowrap;
        }

        .tab-header.active {
            border-bottom-color: var(--primary-color);
            color: var(--primary-color);
            background: white;
        }

        .tab-content {
            padding: 0;
        }

        .tab-pane {
            display: none;
            padding: 1.25rem;
        }

        .tab-pane.active {
            display: block;
        }

        .table-container {
            background: white;
            border-radius: 0.75rem;
            box-shadow: var(--shadow);
            overflow: hidden;
            margin-bottom: 2rem;
        }

        .table-header {
            padding: 1.25rem;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
        }

        .table th,
        .table td {
            padding: 0.75rem;
            text-align: left;
            border-bottom: 1px solid var(--border-color);
        }

        .table th {
            background: var(--light-bg);
            font-weight: 600;
            color: var(--text-dark);
            font-size: 0.875rem;
        }

        .table tr:hover {
            background: #f8fafc;
        }

        .status-badge {
            padding: 0.25rem 0.5rem;
            border-radius: 2rem;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-pending {
            background: #fef3c7;
            color: #92400e;
        }

        .status-completed {
            background: #d1fae5;
            color: #065f46;
        }

        .status-cancelled {
            background: #fee2e2;
            color: #991b1b;
        }

        .stock-low {
            color: var(--danger-color);
            font-weight: 600;
        }

        .stock-ok {
            color: var(--success-color);
            font-weight: 600;
        }

        .form-container {
            background: white;
            border-radius: 0.75rem;
            box-shadow: var(--shadow);
            padding: 1.5rem;
            margin-bottom: 2rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-label {
            display: block;
            font-weight: 500;
            margin-bottom: 0.5rem;
            color: var(--text-dark);
        }

        .form-control {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid var(--border-color);
            border-radius: 0.5rem;
            font-size: 1rem;
            transition: all 0.3s;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        .notification {
            padding: 1rem;
            border-radius: 0.5rem;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .notification.success {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }

        .notification.error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }

        .table-responsive {
            overflow-x: auto;
        }

        .admin-subtitle {
            font-size: 0.8rem;
            color: var(--text-light);
            margin-top: 0.25rem;
            font-weight: 500;
        }

        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .tab-headers {
                flex-direction: column;
            }
            
            .tab-header {
                text-align: center;
                border-bottom: none;
                border-left: 3px solid transparent;
            }
            
            .tab-header.active {
                border-left-color: var(--primary-color);
                border-bottom-color: transparent;
            }
            
            .table {
                min-width: 700px;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="container">
            <div class="header-content">
                <!-- Logo -->
                <span class="logo">
                    <i class="fas fa-store"></i>
                    SmartRetail
                </span>
                
                <!-- Navigation -->
                <nav>
                    <ul class="nav-links">
                        <li><a href="manage-products.php">Products</a></li>
                        <li><a href="manage-orders.php">Orders</a></li>
                    </ul>
                </nav>
                
                <!-- Header Actions -->
                <div class="header-actions">
                    <!-- User Info -->
                    <div class="user-info" style="display: flex; align-items: center; gap: 0.5rem;">
                        <i class="fas fa-user-shield" style="color: var(--primary-color);"></i>
                        <span style="font-weight: 500;"><?php echo htmlspecialchars($username); ?> (Admin)</span>
                    </div>
                    
                    <a href="../../logout.php" class="btn btn-outline">
                        <i class="fas fa-sign-out-alt"></i>
                        Logout
                    </a>
                </div>
            </div>
        </div>
    </header>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container">
            <div class="hero-content">
                <h1>Welcome back, <?php echo htmlspecialchars($full_name); ?>! 👋</h1>
                <p>Manage your store, track orders, and update inventory from this admin dashboard.</p>
            </div>
        </div>
    </section>

    <!-- Admin Dashboard -->
    <section class="hero-container">
        <div class="container">
            <!-- Notifications -->
            <?php if (isset($_SESSION['success'])): ?>
                <div class="notification success">
                    <i class="fas fa-check-circle"></i>
                    <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="notification error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                </div>
            <?php endif; ?>

            <!-- Stats Grid -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon primary">
                        <i class="fas fa-box"></i>
                    </div>
                    <div class="stat-value"><?php echo $stats['total_products']; ?></div>
                    <div class="stat-label">Total Products</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon success">
                        <i class="fas fa-shopping-bag"></i>
                    </div>
                    <div class="stat-value"><?php echo $stats['total_orders']; ?></div>
                    <div class="stat-label">Total Orders</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon warning">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-value"><?php echo $stats['total_customers']; ?></div>
                    <div class="stat-label">Total Customers</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon danger">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <div class="stat-value">R<?php echo number_format($stats['total_revenue'], 2); ?></div>
                    <div class="stat-label">Total Revenue</div>
                </div>
            </div>

            <!-- Tabs Content -->
            <div class="tabs">
                <div class="tab-headers">
                    <div class="tab-header active" onclick="showTab('dashboard')">Dashboard</div>
                    <div class="tab-header" onclick="showTab('products')">Products</div>
                    <div class="tab-header" onclick="showTab('orders')">Orders</div>
                </div>
                
                <div class="tab-content">
                    <!-- Dashboard Tab -->
                    <div id="dashboard-tab" class="tab-pane active">
                        <!-- Recent Orders -->
                        <div class="table-container">
                            <div class="table-header">
                                <h3><i class="fas fa-clock"></i> Recent Orders</h3>
                                <a href="?tab=orders" class="btn btn-outline btn-sm">View All</a>
                            </div>
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Order ID</th>
                                        <th>Customer</th>
                                        <th>Amount</th>
                                        <th>Status</th>
                                        <th>Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($recent_orders)): ?>
                                        <?php foreach ($recent_orders as $order): ?>
                                        <tr>
                                            <td>#<?php echo str_pad($order['id'], 6, '0', STR_PAD_LEFT); ?></td>
                                            <td>
                                                <?php echo htmlspecialchars($order['full_name'] ?? 'Unknown Customer'); ?>
                                            </td>
                                            <td>R<?php echo number_format($order['total'], 2); ?></td>
                                            <td>
                                                <span class="status-badge status-<?php echo $order['status']; ?>">
                                                    <?php echo ucfirst($order['status']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo date('M j, Y', strtotime($order['created_at'])); ?></td>
                                            <td>
                                                <button class="btn btn-outline btn-sm" onclick="viewOrder(<?php echo $order['id']; ?>)">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="6" style="text-align: center; color: var(--text-light);">
                                                No orders found.
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>

                        <?php 
                        // Get low stock products
                        $low_stock_products = [];
                            if (is_array($products) && !empty($products)) {
                                $low_stock_products = array_filter($products, function($product) {
                                    return isset($product['stock_quantity']) && $product['stock_quantity'] < 10;
                                });
                            }
                        ?>
                        
                        <?php if (!empty($low_stock_products)): ?>
                        <div class="table-container">
                        <div class="table-header">
                            <h3><i class="fas fa-exclamation-triangle"></i> Low Stock Alert</h3>
                        </div>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Product</th>
                                        <th>Current Stock</th>
                                        <th>Price</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($low_stock_products as $product): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($product['name'] ?? 'Unknown Product'); ?></td>
                                            <td class="stock-low"><?php echo $product['stock_quantity'] ?? 0; ?></td>
                                            <td>R<?php echo number_format($product['price'] ?? 0, 2); ?></td>
                                            <td>
                                                <button class="btn btn-warning btn-sm" onclick="updateStock(<?php echo $product['product_id'] ?? 0; ?>, '<?php echo htmlspecialchars(addslashes($product['name'] ?? 'Unknown')); ?>', <?php echo $product['stock_quantity'] ?? 0; ?>)">
                                                    <i class="fas fa-edit"></i> Update Stock
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    <?php endif; ?>
                    </div>

                    <!-- Products Tab -->
                    <div id="products-tab" class="tab-pane">
                        <!-- Add Product Form -->
                        <div class="form-container">
                            <h3 style="margin-bottom: 1.5rem;"><i class="fas fa-plus-circle"></i> Add New Product</h3>
                            <form method="POST">
                                <div class="form-row">
                                    <div class="form-group">
                                        <label class="form-label">Product Name</label>
                                        <input type="text" name="name" class="form-control" required>
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Category</label>
                                        <select name="category_id" class="form-control" required>
                                            <option value="">Select Category</option>
                                            <?php foreach ($categories as $category): ?>
                                                <option value="<?php echo $category['id']; ?>">
                                                    <?php echo htmlspecialchars($category['name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Description</label>
                                    <textarea name="description" class="form-control" rows="3" required></textarea>
                                </div>
                                <div class="form-row">
                                    <div class="form-group">
                                        <label class="form-label">Price (R)</label>
                                        <input type="number" name="price" step="0.01" class="form-control" required>
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Stock Quantity</label>
                                        <input type="number" name="stock_quantity" class="form-control" required>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Image URL (Optional)</label>
                                    <input type="url" name="image" class="form-control" placeholder="https://example.com/image.jpg">
                                </div>
                                <button type="submit" name="add_product" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Add Product
                                </button>
                            </form>
                        </div>

                        <!-- Products List -->
                        <div class="table-container">
                            <div class="table-header">
                                <h3><i class="fas fa-boxes"></i> All Products</h3>
                            </div>
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Name</th>
                                        <th>Category</th>
                                        <th>Price</th>
                                        <th>Stock</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                     <?php if (!empty($products) && is_array($products)): ?>
                                        <?php foreach ($products as $product): ?>
                                            <?php 
                                            // Skip invalid products - note the change from 'id' to 'product_id'
                                            if (empty($product['product_id'])) continue;
                                            ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($product['product_id']); ?></td>
                                                <td><?php echo htmlspecialchars($product['name'] ?? 'Unknown Product'); ?></td>
                                                <td><?php echo htmlspecialchars($product['category_name'] ?? 'Uncategorized'); ?></td>
                                                <td>R<?php echo number_format($product['price'] ?? 0, 2); ?></td>
                                                <td class="<?php echo ($product['stock_quantity'] ?? 0) < 10 ? 'stock-low' : 'stock-ok'; ?>">
                                                <?php echo $product['stock_quantity'] ?? 0; ?>
                                            </td>
                                            <td>
                                                <span class="status-badge <?php echo ($product['stock_quantity'] ?? 0) > 0 ? 'status-completed' : 'status-cancelled'; ?>">
                                                    <?php echo ($product['stock_quantity'] ?? 0) > 0 ? 'In Stock' : 'Out of Stock'; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <button class="btn btn-outline btn-sm" onclick="editProduct(<?php echo $product['product_id']; ?>)">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button class="btn btn-outline btn-sm" onclick="updateStock(<?php echo $product['product_id']; ?>, '<?php echo htmlspecialchars(addslashes($product['name'] ?? 'Unknown Product')); ?>', <?php echo $product['stock_quantity'] ?? 0; ?>)">
                                                    <i class="fas fa-box"></i>
                                                </button>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                        <?php else: ?>
                                        <tr>
                                            <td colspan="7" style="text-align: center; color: var(--text-light);">
                                                No products found.
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Orders Tab -->
                    <div id="orders-tab" class="tab-pane">
                        <!-- Orders List -->
                        <div class="table-container">
                            <div class="table-header">
                                <h3><i class="fas fa-shopping-bag"></i> All Orders</h3>
                            </div>
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Order ID</th>
                                        <th>Customer</th>
                                        <th>Email</th>
                                        <th>Amount</th>
                                        <th>Status</th>
                                        <th>Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($all_orders)): ?>
                                        <?php foreach ($all_orders as $order): ?>
                                        <tr>
                                            <td>#<?php echo str_pad($order['id'], 6, '0', STR_PAD_LEFT); ?></td>
                                            <td><?php echo htmlspecialchars($order['full_name'] ?? 'Unknown'); ?></td>
                                            <td><?php echo htmlspecialchars($order['email'] ?? 'N/A'); ?></td>
                                            <td>R<?php echo number_format($order['total'], 2); ?></td>
                                            <td>
                                                <span class="status-badge status-<?php echo $order['status']; ?>">
                                                    <?php echo ucfirst($order['status']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo date('M j, Y g:i A', strtotime($order['created_at'])); ?></td>
                                            <td>
                                                <button class="btn btn-outline btn-sm" onclick="viewOrder(<?php echo $order['id']; ?>)">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <button class="btn btn-outline btn-sm" onclick="updateOrderStatus(<?php echo $order['id']; ?>, '<?php echo $order['status']; ?>')">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="7" style="text-align: center; color: var(--text-light);">
                                                No orders found.
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- JavaScript -->
    <script>
        // Tab functionality
        function showTab(tabName) {
            // Hide all tab panes
            document.querySelectorAll('.tab-pane').forEach(pane => {
                pane.classList.remove('active');
            });
            
            // Show selected tab pane
            document.getElementById(tabName + '-tab').classList.add('active');
            
            // Update tab header states
            document.querySelectorAll('.tab-header').forEach(header => {
                header.classList.remove('active');
            });
            
            // Set current tab header as active
            event.target.classList.add('active');
        }

        // Update stock modal
        function updateStock(productId, productName, currentStock) {
            const newStock = prompt(`Update stock for "${productName}"\nCurrent stock: ${currentStock}\n\nEnter new stock quantity:`, currentStock);
            
            if (newStock !== null && newStock !== '' && !isNaN(newStock)) {
                // Create form and submit
                const form = document.createElement('form');
                form.method = 'POST';
                form.style.display = 'none';
                
                const productIdInput = document.createElement('input');
                productIdInput.name = 'product_id';
                productIdInput.value = productId;
                form.appendChild(productIdInput);
                
                const stockInput = document.createElement('input');
                stockInput.name = 'stock_quantity';
                stockInput.value = newStock;
                form.appendChild(stockInput);
                
                const submitInput = document.createElement('input');
                submitInput.name = 'update_stock';
                submitInput.value = '1';
                form.appendChild(submitInput);
                
                document.body.appendChild(form);
                form.submit();
            }
        }

        // Update order status modal
        function updateOrderStatus(orderId, currentStatus) {
            const newStatus = prompt(`Update order #${orderId.toString().padStart(6, '0')}\nCurrent status: ${currentStatus}\n\nEnter new status (pending/completed/cancelled):`, currentStatus);
            
            if (newStatus && ['pending', 'completed', 'cancelled'].includes(newStatus.toLowerCase())) {
                // Create form and submit
                const form = document.createElement('form');
                form.method = 'POST';
                form.style.display = 'none';
                
                const orderIdInput = document.createElement('input');
                orderIdInput.name = 'order_id';
                orderIdInput.value = orderId;
                form.appendChild(orderIdInput);
                
                const statusInput = document.createElement('input');
                statusInput.name = 'status';
                statusInput.value = newStatus.toLowerCase();
                form.appendChild(statusInput);
                
                const submitInput = document.createElement('input');
                submitInput.name = 'update_order_status';
                submitInput.value = '1';
                form.appendChild(submitInput);
                
                document.body.appendChild(form);
                form.submit();
            }
        }

        // View order details
        function viewOrder(orderId) {
            alert(`Viewing order #${orderId.toString().padStart(6, '0')}\n\nIn a real application, this would show detailed order information including:\n- Customer details\n- Shipping address\n- Order items\n- Payment information\n- Order history`);
        }

        // Edit product
        function editProduct(productId) {
            alert(`Editing product #${productId}\n\nIn a real application, this would open a product edit form with pre-filled data.`);
        }

        // Initialize page
        document.addEventListener('DOMContentLoaded', function() {
            // Check URL for tab parameter
            const urlParams = new URLSearchParams(window.location.search);
            const tab = urlParams.get('tab');
            if (tab && ['dashboard', 'products', 'orders'].includes(tab)) {
                showTab(tab);
            }
        });
    </script>
</body>
</html>