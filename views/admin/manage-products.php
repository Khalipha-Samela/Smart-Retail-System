<?php
/**
 * Admin - Manage Products
 * Allows admin to view, add, edit, and delete products
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Define base directory and fix path separators
define('DS', DIRECTORY_SEPARATOR);
define('BASE_DIR', dirname(__DIR__, 2)); // Goes up two levels from current directory

// Include required files with proper path handling
$config_files = [
    BASE_DIR . DS . 'config' . DS . 'config.php',
    BASE_DIR . DS . 'config' . DS . 'Database.php',
    BASE_DIR . DS . 'utils' . DS . 'Auth.php',
    BASE_DIR . DS . 'models' . DS . 'Product.php',
    BASE_DIR . DS . 'models' . DS . 'Category.php'
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
    $productModel = new Product($db);
    $categoryModel = new Category($db);
    
    // Handle form actions
    $action = $_POST['action'] ?? $_GET['action'] ?? '';
    $message = '';
    $message_type = '';
    
    // Get categories for dropdown
    $categories = $categoryModel->getCategoriesForDropdown();
    
    // Handle different actions
    switch ($action) {
        case 'add':
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                // Validate and sanitize input
                $product_data = [
                    'name' => trim($_POST['name'] ?? ''),
                    'description' => trim($_POST['description'] ?? ''),
                    'price' => floatval($_POST['price'] ?? 0),
                    'category_id' => !empty($_POST['category_id']) ? intval($_POST['category_id']) : null,
                    'stock_quantity' => intval($_POST['stock_quantity'] ?? 0),
                    'image' => trim($_POST['image'] ?? ''),
                    'rating' => 0.00 // Default rating
                ];
                
                // Validate required fields
                if (empty($product_data['name']) || $product_data['price'] <= 0) {
                    $message = 'Product name and price are required. Price must be greater than 0.';
                    $message_type = 'error';
                } else {
                    if ($productModel->create($product_data)) {
                        $message = 'Product added successfully!';
                        $message_type = 'success';
                    } else {
                        $message = 'Failed to add product. Please try again.';
                        $message_type = 'error';
                    }
                }
            }
            break;
            
        case 'edit':
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $product_id = intval($_POST['product_id'] ?? 0);
                $product_data = [
                    'name' => trim($_POST['name'] ?? ''),
                    'description' => trim($_POST['description'] ?? ''),
                    'price' => floatval($_POST['price'] ?? 0),
                    'category_id' => !empty($_POST['category_id']) ? intval($_POST['category_id']) : null,
                    'stock_quantity' => intval($_POST['stock_quantity'] ?? 0),
                    'image' => trim($_POST['image'] ?? '')
                ];
            
                // Debug: Check what values are being received
                error_log("Editing product ID: " . $product_id);
                error_log("Product data: " . print_r($product_data, true));
            
                if ($product_id <= 0) {
                    $message = 'Invalid product ID. Received: ' . $product_id;
                    $message_type = 'error';
                } elseif (empty($product_data['name']) || $product_data['price'] <= 0) {
                    $message = 'Product name and price are required. Price must be greater than 0.';
                    $message_type = 'error';
                } else {
                    if ($productModel->update($product_id, $product_data)) {
                        $message = 'Product updated successfully!';
                        $message_type = 'success';
                    } else {
                        $message = 'Failed to update product. Please try again.';
                        $message_type = 'error';
                    }
                }
            }
            break;
            
        case 'delete':
            $product_id = intval($_GET['product_id'] ?? 0);
            if ($product_id > 0) {
                if ($productModel->delete($product_id)) {
                    $message = 'Product deleted successfully!';
                    $message_type = 'success';
                } else {
                    $message = 'Failed to delete product. Please try again.';
                    $message_type = 'error';
                }
            } else {
                $message = 'Invalid product ID.';
                $message_type = 'error';
            }
            break;
    }
    
    // Get all products with category information
    $products = $productModel->getAllProductsWithCategory();
    
} catch (Exception $e) {
    error_log("Manage Products Error: " . $e->getMessage());
    $message = 'Error loading products: ' . $e->getMessage();
    $message_type = 'error';
    $products = [];
    $categories = [];
}

// Function to get product icon based on category
function getProductIcon($categoryName, $productName = '') {
    $categoryName = strtolower($categoryName ?? '');
    $productName = strtolower($productName ?? '');
    
    // Category-based icons
    if (strpos($categoryName, 'electron') !== false) {
        return 'fas fa-laptop';
    } elseif (strpos($categoryName, 'cloth') !== false || strpos($categoryName, 'fashion') !== false) {
        return 'fas fa-tshirt';
    } elseif (strpos($categoryName, 'book') !== false) {
        return 'fas fa-book';
    } elseif (strpos($categoryName, 'home') !== false || strpos($categoryName, 'garden') !== false) {
        return 'fas fa-home';
    } elseif (strpos($categoryName, 'sport') !== false) {
        return 'fas fa-football-ball';
    } elseif (strpos($categoryName, 'beauty') !== false) {
        return 'fas fa-spa';
    } elseif (strpos($categoryName, 'toy') !== false) {
        return 'fas fa-gamepad';
    } elseif (strpos($categoryName, 'food') !== false) {
        return 'fas fa-utensils';
    } elseif (strpos($categoryName, 'health') !== false) {
        return 'fas fa-heartbeat';
    } elseif (strpos($categoryName, 'auto') !== false) {
        return 'fas fa-car';
    } elseif (strpos($categoryName, 'jewel') !== false) {
        return 'fas fa-gem';
    } elseif (strpos($categoryName, 'furniture') !== false) {
        return 'fas fa-couch';
    } elseif (strpos($categoryName, 'music') !== false) {
        return 'fas fa-music';
    } elseif (strpos($categoryName, 'movie') !== false) {
        return 'fas fa-film';
    }
    
    // Product name based icons
    if (strpos($productName, 'phone') !== false) {
        return 'fas fa-mobile-alt';
    } elseif (strpos($productName, 'laptop') !== false || strpos($productName, 'computer') !== false) {
        return 'fas fa-laptop';
    } elseif (strpos($productName, 'headphone') !== false) {
        return 'fas fa-headphones';
    } elseif (strpos($productName, 'watch') !== false) {
        return 'fas fa-clock';
    } elseif (strpos($productName, 'camera') !== false) {
        return 'fas fa-camera';
    } elseif (strpos($productName, 'game') !== false) {
        return 'fas fa-gamepad';
    } elseif (strpos($productName, 'shoe') !== false) {
        return 'fas fa-shoe-prints';
    } elseif (strpos($productName, 'bag') !== false) {
        return 'fas fa-shopping-bag';
    }
    
    // Default icon
    return 'fas fa-box';
}

// Get site name safely
$site_name = defined('SITE_NAME') ? SITE_NAME : 'Admin Panel';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Products - <?php echo htmlspecialchars($site_name); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Your existing CSS remains the same */
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
        
        .content-grid {
            display: grid;
            grid-template-columns: 1fr 400px;
            gap: 20px;
        }
        
        .products-section, .form-section {
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
        
        .btn-danger {
            background: #e74c3c;
            color: white;
        }
        
        .btn-danger:hover {
            background: #c0392b;
        }
        
        .btn-edit {
            background: #f39c12;
            color: white;
        }
        
        .btn-edit:hover {
            background: #d35400;
        }
        
        .btn-sm {
            padding: 6px 12px;
            font-size: 0.85em;
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
        
        .product-icon {
            width: 50px;
            height: 50px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5em;
            color: white;
        }
        
        .icon-electronics { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        .icon-fashion { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); }
        .icon-books { background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); }
        .icon-home { background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%); }
        .icon-sports { background: linear-gradient(135deg, #fa709a 0%, #fee140 100%); }
        .icon-beauty { background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%); }
        .icon-default { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        
        .stock-low {
            color: #e74c3c;
            font-weight: bold;
        }
        
        .stock-ok {
            color: #27ae60;
        }
        
        .form-group {
            margin-bottom: 20px;
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
        
        .form-control:focus {
            outline: none;
            border-color: #3498db;
            box-shadow: 0 0 0 2px rgba(52, 152, 219, 0.2);
        }
        
        .form-actions {
            display: flex;
            gap: 10px;
            margin-top: 25px;
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
        
        .rating-stars {
            color: #f39c12;
        }
        
        @media (max-width: 1024px) {
            .content-grid {
                grid-template-columns: 1fr;
            }
            
            .form-section {
                order: -1;
            }
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
    </style>
</head>
<body>
    <div class="admin-container">
        <div class="header">
            <h1><i class="fas fa-boxes"></i> Manage Products</h1>
            <div class="breadcrumb">
                <a href="admin-dashboard.php"><i class="fas fa-home"></i> Dashboard</a> / Manage Products
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
                <div class="stat-number"><?php echo count($products); ?></div>
                <div class="stat-label">Total Products</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">
                    <?php 
                    $lowStock = array_filter($products, function($product) {
                        return ($product['stock_quantity'] ?? 0) < 10;
                    });
                    echo count($lowStock);
                    ?>
                </div>
                <div class="stat-label">Low Stock Items</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">
                    <?php 
                    $totalValue = array_sum(array_map(function($product) {
                        return ($product['price'] ?? 0) * ($product['stock_quantity'] ?? 0);
                    }, $products));
                    echo 'R' . number_format($totalValue, 2);
                    ?>
                </div>
                <div class="stat-label">Total Inventory Value</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">
                    <?php 
                    $categoriesCount = array_unique(array_column($products, 'category_name'));
                    echo count($categoriesCount);
                    ?>
                </div>
                <div class="stat-label">Active Categories</div>
            </div>
        </div>

        <div class="content-grid">
            <!-- Products List -->
            <div class="products-section">
                <div class="section-header">
                    <h2><i class="fas fa-list"></i> Products List</h2>
                    <button type="button" class="btn btn-primary" onclick="showAddForm()">
                        <i class="fas fa-plus"></i> Add New Product
                    </button>
                </div>

                <?php if (empty($products)): ?>
                    <div class="empty-state">
                        <i class="fas fa-box-open"></i>
                        <h3>No Products Found</h3>
                        <p>Get started by adding your first product.</p>
                    </div>
                <?php else: ?>
                    <div style="overflow-x: auto;">
                        <table>
                            <thead>
                                <tr>
                                    <th>Icon</th>
                                    <th>Name</th>
                                    <th>Category</th>
                                    <th>Price</th>
                                    <th>Stock</th>
                                    <th>Rating</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($products as $product): ?>
                                    <?php 
                                    $iconClass = 'icon-default';
                                    $categoryLower = strtolower($product['category_name'] ?? '');
                                    if (strpos($categoryLower, 'electron') !== false) $iconClass = 'icon-electronics';
                                    elseif (strpos($categoryLower, 'cloth') !== false || strpos($categoryLower, 'fashion') !== false) $iconClass = 'icon-fashion';
                                    elseif (strpos($categoryLower, 'book') !== false) $iconClass = 'icon-books';
                                    elseif (strpos($categoryLower, 'home') !== false || strpos($categoryLower, 'garden') !== false) $iconClass = 'icon-home';
                                    elseif (strpos($categoryLower, 'sport') !== false) $iconClass = 'icon-sports';
                                    elseif (strpos($categoryLower, 'beauty') !== false) $iconClass = 'icon-beauty';
                                    ?>
                                    <tr>
                                        <td>
                                            <div class="product-icon <?php echo $iconClass; ?>">
                                                <i class="<?php echo getProductIcon($product['category_name'] ?? '', $product['name'] ?? ''); ?>"></i>
                                            </div>
                                        </td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($product['name'] ?? ''); ?></strong>
                                            <?php if (!empty($product['description'])): ?>
                                                <br><small style="color: #7f8c8d;"><?php echo substr(htmlspecialchars($product['description']), 0, 50); ?>...</small>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($product['category_name'] ?? 'Uncategorized'); ?></td>
                                        <td class="price">R<?php echo number_format($product['price'] ?? 0, 2); ?></td>
                                        <td>
                                            <span class="<?php echo ($product['stock_quantity'] ?? 0) < 10 ? 'stock-low' : 'stock-ok'; ?>">
                                                <?php echo $product['stock_quantity'] ?? 0; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if (($product['rating'] ?? 0) > 0): ?>
                                                <div class="rating-stars">
                                                    <i class="fas fa-star"></i>
                                                    <?php echo number_format($product['rating'], 1); ?>
                                                </div>
                                            <?php else: ?>
                                                <span style="color: #bdc3c7;">No ratings</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="action-buttons">
                                                <button class="btn btn-edit btn-sm" onclick="editProduct(<?php echo $product['id'] ?? 0; ?>)">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button class="btn btn-danger btn-sm" onclick="deleteProduct(<?php echo $product['id'] ?? 0; ?>, '<?php echo htmlspecialchars($product['name'] ?? ''); ?>')">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Add/Edit Product Form -->
            <div class="form-section">
                <div class="section-header">
                    <h2 id="form-title"><i class="fas fa-plus"></i> Add New Product</h2>
                </div>
                
                <form id="productForm" method="POST">
                    <input type="hidden" name="action" id="formAction" value="add">
                    <input type="hidden" name="product_id" id="productId">
                    
                    <div class="form-group">
                        <label class="form-label" for="name">Product Name *</label>
                        <input type="text" id="name" name="name" class="form-control" required maxlength="255">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="description">Description</label>
                        <textarea id="description" name="description" class="form-control" rows="3" maxlength="1000"></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="price">Price (R) *</label>
                        <input type="number" id="price" name="price" class="form-control" step="0.01" min="0.01" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="category_id">Category</label>
                        <select id="category_id" name="category_id" class="form-control">
                            <option value="">Select Category</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo $category['id']; ?>">
                                    <?php echo htmlspecialchars($category['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="stock_quantity">Stock Quantity *</label>
                        <input type="number" id="stock_quantity" name="stock_quantity" class="form-control" min="0" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="image">Image URL (Optional)</label>
                        <input type="text" id="image" name="image" class="form-control" placeholder="product-image.jpg" maxlength="500">
                        <small style="color: #7f8c8d; font-size: 0.85em;">Icons will be used instead of images for product display</small>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Save Product
                        </button>
                        <button type="button" class="btn" onclick="resetForm()" style="background: #95a5a6; color: white;">
                            <i class="fas fa-times"></i> Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function showAddForm() {
            document.getElementById('form-title').innerHTML = '<i class="fas fa-plus"></i> Add New Product';
            document.getElementById('formAction').value = 'add';
            document.getElementById('productId').value = '';
            document.getElementById('productForm').reset();
        }
        
        function editProduct(productId) {
            // Use the table-based approach since get-product.php might not exist
            const buttons = document.querySelectorAll(`button[onclick="editProduct(${productId})"]`);
            if (buttons.length === 0) {
                alert('Could not find product data. Please refresh the page and try again.');
                return;
            }
            
            const productRow = buttons[0].closest('tr');
            if (productRow) {
                const cells = productRow.cells;
                
                // Get category name from the table
                const categoryName = cells[2].textContent.trim();
                
                // Find the corresponding category ID from the dropdown
                const categorySelect = document.getElementById('category_id');
                let categoryId = '';
                for (let option of categorySelect.options) {
                    if (option.text === categoryName) {
                        categoryId = option.value;
                        break;
                    }
                }
                
                // Get product name and description
                const nameElement = cells[1].querySelector('strong');
                const descriptionElement = cells[1].querySelector('small');
                
                // Get price (remove 'R' and convert to number)
                const priceText = cells[3].textContent.replace('R', '').trim();
                
                // Get stock quantity
                const stockElement = cells[4].querySelector('span');
                
                // Populate form with existing data
                document.getElementById('form-title').innerHTML = '<i class="fas fa-edit"></i> Edit Product';
                document.getElementById('formAction').value = 'edit';
                document.getElementById('productId').value = productId;
                document.getElementById('name').value = nameElement ? nameElement.textContent.trim() : '';
                
                // For description, remove the trailing '...' if present
                let description = '';
                if (descriptionElement) {
                    description = descriptionElement.textContent.trim();
                    if (description.endsWith('...')) {
                        description = description.slice(0, -3);
                    }
                }
                document.getElementById('description').value = description;
                
                document.getElementById('price').value = parseFloat(priceText).toFixed(2);
                document.getElementById('category_id').value = categoryId;
                document.getElementById('stock_quantity').value = stockElement ? parseInt(stockElement.textContent) : 0;
                
                // Note: Image URL cannot be retrieved from the table, so it will be empty
                document.getElementById('image').value = '';
                
                // Scroll to form
                document.querySelector('.form-section').scrollIntoView({ behavior: 'smooth' });
            } else {
                alert('Could not find product data. Please refresh the page and try again.');
            }
        }
        
        function deleteProduct(productId, productName) {
            if (confirm('Are you sure you want to delete "' + productName + '"?')) {
                window.location.href = 'manage-products.php?action=delete&product_id=' + productId;
            }
        }
        
        function resetForm() {
            showAddForm();
        }
        
        // Form validation
        document.getElementById('productForm').addEventListener('submit', function(e) {
            const price = parseFloat(document.getElementById('price').value);
            const stock = parseInt(document.getElementById('stock_quantity').value);
            const name = document.getElementById('name').value.trim();
            
            if (!name) {
                alert('Product name is required.');
                e.preventDefault();
                return;
            }
            
            if (price <= 0) {
                alert('Price must be greater than 0.');
                e.preventDefault();
                return;
            }
            
            if (stock < 0) {
                alert('Stock quantity cannot be negative.');
                e.preventDefault();
                return;
            }
        });

        // Preview icon based on category selection
        document.getElementById('category_id').addEventListener('change', function() {
            const categoryName = this.options[this.selectedIndex].text.toLowerCase();
            // You could add a live preview of the icon here if needed
        });
    </script>
</body>
</html>