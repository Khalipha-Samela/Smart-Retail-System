<?php
/**
 * Admin - Manage Orders
 * Allows admin to view and manage customer orders
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Define base directory and fix path separators
define('DS', DIRECTORY_SEPARATOR);
define('BASE_DIR', dirname(__DIR__, 2));

// Include required files with proper path handling
$config_files = [
    BASE_DIR . DS . 'config' . DS . 'config.php',
    BASE_DIR . DS . 'config' . DS . 'Database.php',
    BASE_DIR . DS . 'utils' . DS . 'Auth.php',
    BASE_DIR . DS . 'models' . DS . 'Order.php',
    BASE_DIR . DS . 'models' . DS . 'User.php'
];

foreach ($config_files as $file) {
    if (!file_exists($file)) {
        die("Required file not found: " . $file);
    }
    require_once $file;
}

// Check if user is authenticated and has admin/staff role
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role_name'] ?? '', ['admin', 'staff'])) {
    header('Location: ' . BASE_DIR . DS . 'login.php');
    exit();
}

try {
    // Initialize database connection
    $database = new Database();
    $db = $database->getConnection();
    
    // Initialize models
    $orderModel = new Order($db);
    $userModel = new User($db);
    
    // Handle form actions
    $action = $_POST['action'] ?? $_GET['action'] ?? '';
    $message = '';
    $message_type = '';
    
    // Handle status updates
    if ($action === 'update_status' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $order_id = intval($_POST['order_id'] ?? 0);
        $new_status = $_POST['status'] ?? '';
        
        if ($order_id > 0 && in_array($new_status, ['pending', 'completed', 'cancelled'])) {
            if ($orderModel->updateOrderStatus($order_id, $new_status)) {
                $message = 'Order status updated successfully!';
                $message_type = 'success';
            } else {
                $message = 'Failed to update order status. Please try again.';
                $message_type = 'error';
            }
        } else {
            $message = 'Invalid order ID or status.';
            $message_type = 'error';
        }
    }
    
    // Get filter parameters
    $status_filter = $_GET['status'] ?? '';
    $date_from = $_GET['date_from'] ?? '';
    $date_to = $_GET['date_to'] ?? '';
    
    // Get orders with filters
    $orders = $orderModel->getAllOrdersWithUsers($status_filter, $date_from, $date_to);
    
    // Get order statistics
    $total_orders = count($orders);
    $pending_orders = array_filter($orders, function($order) {
        return ($order['status'] ?? '') === 'pending';
    });
    $completed_orders = array_filter($orders, function($order) {
        return ($order['status'] ?? '') === 'completed';
    });
    $cancelled_orders = array_filter($orders, function($order) {
        return ($order['status'] ?? '') === 'cancelled';
    });
    
    $total_revenue = array_sum(array_map(function($order) {
        return ($order['status'] ?? '') === 'completed' ? floatval($order['total'] ?? 0) : 0;
    }, $orders));
    
} catch (Exception $e) {
    error_log("Manage Orders Error: " . $e->getMessage());
    $message = 'Error loading orders: ' . $e->getMessage();
    $message_type = 'error';
    $orders = [];
    $total_orders = $pending_orders = $completed_orders = $cancelled_orders = 0;
    $total_revenue = 0;
}

// Get site name safely
$site_name = defined('SITE_NAME') ? SITE_NAME : 'Admin Panel';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Orders - <?php echo htmlspecialchars($site_name); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background: #f5f7fa;
            color: #333;
        }
        
        .admin-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .header {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        
        .header h1 {
            color: #2c3e50;
            margin-bottom: 10px;
        }
        
        .breadcrumb {
            color: #7f8c8d;
            font-size: 0.9em;
        }
        
        .breadcrumb a {
            color: #3498db;
            text-decoration: none;
        }
        
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
            border: 1px solid transparent;
        }
        
        .alert-success {
            color: #155724;
            background-color: #d4edda;
            border-color: #c3e6cb;
        }
        
        .alert-error {
            color: #721c24;
            background-color: #f8d7da;
            border-color: #f5c6cb;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .stat-number {
            font-size: 2em;
            font-weight: bold;
            color: #2c3e50;
        }
        
        .stat-label {
            color: #7f8c8d;
            font-size: 0.9em;
        }
        
        .filters-section {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        
        .filters-form {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            align-items: end;
        }
        
        .form-group {
            margin-bottom: 0;
        }
        
        .form-label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
            color: #2c3e50;
        }
        
        .form-control {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #dcdfe6;
            border-radius: 5px;
            font-size: 14px;
            transition: border-color 0.3s;
        }
        
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 500;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s;
        }
        
        .btn-primary {
            background: #3498db;
            color: white;
        }
        
        .btn-primary:hover {
            background: #2980b9;
        }
        
        .btn-success {
            background: #27ae60;
            color: white;
        }
        
        .btn-success:hover {
            background: #219a52;
        }
        
        .btn-warning {
            background: #f39c12;
            color: white;
        }
        
        .btn-warning:hover {
            background: #d35400;
        }
        
        .btn-danger {
            background: #e74c3c;
            color: white;
        }
        
        .btn-danger:hover {
            background: #c0392b;
        }
        
        .btn-sm {
            padding: 6px 12px;
            font-size: 0.85em;
        }
        
        .orders-section {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #ecf0f1;
        }
        
        .section-header h2 {
            color: #2c3e50;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        
        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #ecf0f1;
        }
        
        th {
            background: #f8f9fa;
            font-weight: 600;
            color: #2c3e50;
        }
        
        tr:hover {
            background: #f8f9fa;
        }
        
        .status-badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.8em;
            font-weight: 500;
        }
        
        .status-pending {
            background: #fff3cd;
            color: #856404;
        }
        
        .status-completed {
            background: #d1edff;
            color: #004085;
        }
        
        .status-cancelled {
            background: #f8d7da;
            color: #721c24;
        }
        
        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: #7f8c8d;
        }
        
        .empty-state i {
            font-size: 3em;
            margin-bottom: 15px;
            color: #bdc3c7;
        }
        
        .action-buttons {
            display: flex;
            gap: 5px;
        }
        
        .price {
            font-weight: bold;
            color: #2c3e50;
        }
        
        .order-details {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-top: 10px;
            display: none;
        }
        
        .order-items {
            width: 100%;
            border-collapse: collapse;
        }
        
        .order-items th,
        .order-items td {
            padding: 8px 12px;
            border: 1px solid #dee2e6;
        }
        
        .toggle-details {
            background: none;
            border: none;
            color: #3498db;
            cursor: pointer;
            font-size: 0.9em;
        }
        
        .toggle-details:hover {
            text-decoration: underline;
        }
        
        .role-badge {
            background: #e74c3c;
            color: white;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 0.7em;
            margin-left: 5px;
        }
        
        .staff-badge {
            background: #f39c12;
        }
        
        .admin-badge {
            background: #e74c3c;
        }
        
        @media (max-width: 768px) {
            .filters-form {
                grid-template-columns: 1fr;
            }
            
            .action-buttons {
                flex-direction: column;
            }
            
            table {
                font-size: 0.9em;
            }
            
            th, td {
                padding: 8px 10px;
            }
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <div class="header">
            <h1><i class="fas fa-shopping-cart"></i> Manage Orders</h1>
            <div class="breadcrumb">
                <a href="admin-dashboard.php"><i class="fas fa-home"></i> Dashboard</a> / Manage Orders
                <?php if (isset($_SESSION['role_name'])): ?>
                    <span class="role-badge <?php echo $_SESSION['role_name'] === 'staff' ? 'staff-badge' : 'admin-badge'; ?>">
                        <?php echo ucfirst($_SESSION['role_name']); ?>
                    </span>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_type === 'success' ? 'success' : 'error'; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?php echo $total_orders; ?></div>
                <div class="stat-label">Total Orders</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo count($pending_orders); ?></div>
                <div class="stat-label">Pending Orders</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo count($completed_orders); ?></div>
                <div class="stat-label">Completed Orders</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">R<?php echo number_format($total_revenue, 2); ?></div>
                <div class="stat-label">Total Revenue</div>
            </div>
        </div>

        <!-- Filters -->
        <div class="filters-section">
            <form method="GET" class="filters-form">
                <div class="form-group">
                    <label class="form-label" for="status">Order Status</label>
                    <select id="status" name="status" class="form-control">
                        <option value="">All Statuses</option>
                        <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="completed" <?php echo $status_filter === 'completed' ? 'selected' : ''; ?>>Completed</option>
                        <option value="cancelled" <?php echo $status_filter === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="date_from">Date From</label>
                    <input type="date" id="date_from" name="date_from" class="form-control" value="<?php echo htmlspecialchars($date_from); ?>">
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="date_to">Date To</label>
                    <input type="date" id="date_to" name="date_to" class="form-control" value="<?php echo htmlspecialchars($date_to); ?>">
                </div>
                
                <div class="form-group">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-filter"></i> Apply Filters
                    </button>
                    <a href="manage-orders.php" class="btn" style="background: #95a5a6; color: white;">
                        <i class="fas fa-times"></i> Clear
                    </a>
                </div>
            </form>
        </div>

        <!-- Orders List -->
        <div class="orders-section">
            <div class="section-header">
                <h2><i class="fas fa-list"></i> Orders List</h2>
                <div>
                    <span class="stat-label">Showing <?php echo count($orders); ?> orders</span>
                </div>
            </div>

            <?php if (empty($orders)): ?>
                <div class="empty-state">
                    <i class="fas fa-shopping-cart"></i>
                    <h3>No Orders Found</h3>
                    <p>No orders match your current filters.</p>
                </div>
            <?php else: ?>
                <div style="overflow-x: auto;">
                    <table>
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Customer</th>
                                <th>Date</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($orders as $order): ?>
                                <tr>
                                    <td>
                                        <strong>#<?php echo htmlspecialchars($order['id'] ?? 'N/A'); ?></strong>
                                        <br>
                                        <button class="toggle-details" onclick="toggleOrderDetails(<?php echo $order['id'] ?? 0; ?>)">
                                            <i class="fas fa-chevron-down"></i> Details
                                        </button>
                                    </td>
                                    <td>
                                        <?php echo htmlspecialchars($order['full_name'] ?? 'Unknown Customer'); ?>
                                        <br>
                                        <small style="color: #7f8c8d;"><?php echo htmlspecialchars($order['email'] ?? ''); ?></small>
                                    </td>
                                    <td><?php echo isset($order['created_at']) ? date('M j, Y g:i A', strtotime($order['created_at'])) : 'N/A'; ?></td>
                                    <td class="price">R<?php echo isset($order['total']) ? number_format($order['total'], 2) : '0.00'; ?></td>
                                    <td>
                                        <span class="status-badge status-<?php echo htmlspecialchars($order['status'] ?? 'pending'); ?>">
                                            <?php echo ucfirst($order['status'] ?? 'Pending'); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="action" value="update_status">
                                                <input type="hidden" name="order_id" value="<?php echo $order['id'] ?? ''; ?>">
                                                <select name="status" onchange="this.form.submit()" class="form-control" style="width: 120px; display: inline-block; margin-right: 5px;">
                                                    <option value="pending" <?php echo ($order['status'] ?? '') === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                                    <option value="completed" <?php echo ($order['status'] ?? '') === 'completed' ? 'selected' : ''; ?>>Completed</option>
                                                    <option value="cancelled" <?php echo ($order['status'] ?? '') === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                                </select>
                                            </form>
                                            <button class="btn btn-primary btn-sm" onclick="viewOrder(<?php echo $order['id'] ?? 0; ?>)">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <tr id="order-details-<?php echo $order['id'] ?? ''; ?>" style="display: none;">
                                    <td colspan="6">
                                        <div class="order-details">
                                            <h4>Order Details #<?php echo htmlspecialchars($order['id'] ?? 'N/A'); ?></h4>
                                            <p><strong>Customer:</strong> <?php echo htmlspecialchars($order['full_name'] ?? 'Unknown'); ?></p>
                                            <p><strong>Email:</strong> <?php echo htmlspecialchars($order['email'] ?? 'N/A'); ?></p>
                                            <p><strong>Order Date:</strong> <?php echo isset($order['created_at']) ? date('F j, Y g:i A', strtotime($order['created_at'])) : 'N/A'; ?></p>
                                            <p><strong>Total Amount:</strong> R<?php echo isset($order['total']) ? number_format($order['total'], 2) : '0.00'; ?></p>
                                            <p><strong>Status:</strong> 
                                                <span class="status-badge status-<?php echo htmlspecialchars($order['status'] ?? 'pending'); ?>">
                                                    <?php echo ucfirst($order['status'] ?? 'Pending'); ?>
                                                </span>
                                            </p>
                                            <div style="margin-top: 15px;">
                                                <small style="color: #7f8c8d;">
                                                    <i>Order items would be displayed here in a real application</i>
                                                </small>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function toggleOrderDetails(orderId) {
            const detailsRow = document.getElementById('order-details-' + orderId);
            const button = event.target.closest('button') || event.target;
            
            if (detailsRow.style.display === 'none') {
                detailsRow.style.display = 'table-row';
                button.innerHTML = '<i class="fas fa-chevron-up"></i> Hide Details';
            } else {
                detailsRow.style.display = 'none';
                button.innerHTML = '<i class="fas fa-chevron-down"></i> Details';
            }
        }
        
        function viewOrder(orderId) {
            alert('View order details for order #' + orderId + ' - This would open a detailed order view.');
            // In a real application, this would redirect to an order details page
            // window.location.href = 'order-details.php?id=' + orderId;
        }
        
        // Auto-submit status change forms
        document.querySelectorAll('select[name="status"]').forEach(select => {
            select.addEventListener('change', function() {
                this.form.submit();
            });
        });
        
        // Date validation
        const dateFrom = document.getElementById('date_from');
        const dateTo = document.getElementById('date_to');
        
        if (dateFrom && dateTo) {
            dateFrom.addEventListener('change', function() {
                if (dateTo.value && this.value > dateTo.value) {
                    alert('Date From cannot be after Date To');
                    this.value = '';
                }
            });
            
            dateTo.addEventListener('change', function() {
                if (dateFrom.value && this.value < dateFrom.value) {
                    alert('Date To cannot be before Date From');
                    this.value = '';
                }
            });
        }
    </script>
</body>
</html>