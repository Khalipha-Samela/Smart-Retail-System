<?php
/**
 * SmartRetail - Products Page
 * Updated to work with database schema including categories
 */

session_start();

require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../utils/Auth.php';
require_once '../../models/Product.php';
require_once '../../models/Cart.php';
require_once '../../models/Category.php';

// Require authentication
Auth::requireAuth();

// Get user information
$user_id = Auth::getUserId();
$username = $_SESSION['username'];
$full_name = $_SESSION['full_name'];
$email = $_SESSION['email'];

// Initialize database connection and models
try {
    $database = new Database();
    $db = $database->getConnection();
    $productModel = new Product($db);
    $cartModel = new Cart($db);
    $categoryModel = new Category($db);
} catch (Exception $e) {
    error_log("Database connection failed: " . $e->getMessage());
    $productModel = null;
    $categoryModel = null;
}

// Initialize cart and cart count
if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

$cart_count = 0;
if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) {
        $cart_count += $item['quantity'] ?? 1;
    }
}

// Handle search and filtering
$search_keywords = $_GET['search'] ?? '';
$category_filter = $_GET['category'] ?? '';
$sort_by = $_GET['sort'] ?? 'newest';

// Get categories for filter dropdown
if ($categoryModel) {
    try {
        $categories = $categoryModel->getAllCategories();
        // $categories is now an array, no need to loop through it here
    } catch (Exception $e) {
        error_log("Error fetching categories: " . $e->getMessage());
        $categories = [];
    }
} else {
    $categories = [];
}

// Get products based on filters
if ($productModel) {
    try {
        if (!empty($search_keywords)) {
            $products_data = $productModel->searchProducts($search_keywords, $category_filter);
        } else if (!empty($category_filter)) {
            $products_data = $productModel->getProductsByCategory($category_filter);
        } else {
            $products_data = $productModel->getAllProductsWithCategory();
        }
        
        // Process products for display
        $products = [];
        foreach ($products_data as $row) {
            $row['icon'] = Product::getProductIcon($row['name']);
            $row['rating'] = $row['rating'] ?? 4.5;
            $products[] = $row;
        }
        
    } catch (Exception $e) {
        error_log("Error fetching products: " . $e->getMessage());
        $products = getDemoProducts(); // Fallback to demo data
    }
} else {
    $products = getDemoProducts();
}

// Handle add to cart
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {
    $product_id = $_POST['product_id'];
    $product_name = $_POST['product_name'] ?? '';
    $product_price = (float)($_POST['product_price'] ?? 0);
    $product_icon = $_POST['product_icon'] ?? 'fas fa-box';
    $quantity = intval($_POST['quantity'] ?? 1);
    
    // Validate required fields
    if (empty($product_id) || empty($product_name) || $product_price <= 0 || $quantity <= 0) {
        error_log("Add to cart failed - Missing required fields");
        header('Location: products.php?error=1');
        exit;
    }
    
    // Initialize cart in session if not exists
    if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }
    
    // SIMPLIFIED AND FIXED: Check if item already exists in cart
    $item_exists = false;
    $item_index = -1;
    
    // Use consistent ID checking - only check 'id' field
    foreach ($_SESSION['cart'] as $index => &$item) {
        if (isset($item['id']) && $item['id'] == $product_id) {
            $item_exists = true;
            $item_index = $index;
            break;
        }
    }
    
    if ($item_exists) {
        // Update existing item quantity
        $_SESSION['cart'][$item_index]['quantity'] += $quantity;
        error_log("Updated existing item: " . $product_name . " - New quantity: " . $_SESSION['cart'][$item_index]['quantity']);
    } else {
        // Add new item with consistent field names - ONLY use 'id'
        $new_item = [
            'id' => $product_id, // Use only 'id' field consistently
            'name' => $product_name,
            'price' => $product_price,
            'quantity' => $quantity,
            'icon' => $product_icon,
            'stock_quantity' => 10
        ];
        $_SESSION['cart'][] = $new_item;
        error_log("Added new item: " . $product_name . " - Quantity: " . $quantity);
    }
    
    // Update cart count for display
    $cart_count = 0;
    foreach ($_SESSION['cart'] as $item) {
        $cart_count += $item['quantity'];
    }
    
    // Debug: Log cart contents
    error_log("Cart contents after update: " . print_r($_SESSION['cart'], true));
    
    // Redirect to prevent form resubmission
    header('Location: products.php?added=' . $product_id);
    exit;
}

// Check if we just added an item and show message
$show_success_message = false;
if (isset($_GET['added']) && is_numeric($_GET['added'])) {
    $show_success_message = true;
    $added_product_id = $_GET['added'];
    
    // Find the added product name for the message
    $added_product_name = '';
    foreach ($products as $product) {
        if ($product['product_id'] == $added_product_id) {
            $added_product_name = $product['name'];
            break;
        }
    }
}

// Clear any session cart message
if (isset($_SESSION['cart_message'])) {
    unset($_SESSION['cart_message']);
}

// Helper function to sort products
function sortProducts($products, $sort_by) {
    switch ($sort_by) {
        case 'price_low':
            usort($products, function($a, $b) {
                return $a['price'] <=> $b['price'];
            });
            break;
        case 'price_high':
            usort($products, function($a, $b) {
                return $b['price'] <=> $a['price'];
            });
            break;
        case 'rating':
            usort($products, function($a, $b) {
                return $b['rating'] <=> $a['rating'];
            });
            break;
        case 'name':
            usort($products, function($a, $b) {
                return strcmp($a['name'], $b['name']);
            });
            break;
        case 'newest':
        default:
            // Keep original order (newest first based on database order)
            break;
    }
    return $products;
}

// Helper function for demo data 
function getDemoProducts() {
    return [
        [
            'product_id' => 1,
            'name' => 'Wireless Headphones',
            'description' => 'High-quality wireless headphones with noise cancellation and premium sound quality.',
            'price' => 129.99,
            'category_name' => 'Electronics',
            'icon' => 'fas fa-headphones',
            'stock_quantity' => 120,
            'rating' => 4.6
        ],
        [
            'product_id' => 2,
            'name' => 'Smart Watch',
            'description' => 'Feature-rich smartwatch with health monitoring, GPS, and long battery life.',
            'price' => 299.99,
            'category_name' => 'Electronics',
            'icon' => 'fas fa-clock',
            'stock_quantity' => 85,
            'rating' => 4.4
        ],
        [
            'product_id' => 3,
            'name' => 'Laptop Backpack',
            'description' => 'Durable laptop backpack with multiple compartments and waterproof material.',
            'price' => 59.99,
            'category_name' => 'Accessories',
            'icon' => 'fas fa-briefcase',
            'stock_quantity' => 200,
            'rating' => 4.8
        ],
        [
            'product_id' => 4,
            'name' => 'Bluetooth Speaker',
            'description' => 'Portable Bluetooth speaker with crystal clear sound and long battery life.',
            'price' => 89.99,
            'category_name' => 'Electronics',
            'icon' => 'fas fa-volume-up',
            'stock_quantity' => 150,
            'rating' => 4.5
        ],
        [
            'product_id' => 5,
            'name' => 'Gaming Mouse',
            'description' => 'High-precision gaming mouse with RGB lighting and programmable buttons.',
            'price' => 49.99,
            'category_name' => 'Electronics',
            'icon' => 'fas fa-mouse',
            'stock_quantity' => 75,
            'rating' => 4.7
        ],
        [
            'product_id' => 6,
            'name' => 'USB-C Hub',
            'description' => 'Multi-port USB-C hub for expanding your connectivity options.',
            'price' => 39.99,
            'category_name' => 'Electronics',
            'icon' => 'fas fa-plug',
            'stock_quantity' => 120,
            'rating' => 4.3
        ],
        [
            'product_id' => 7,
            'name' => 'Cotton T-Shirt',
            'description' => 'Comfortable cotton t-shirt available in multiple colors and sizes.',
            'price' => 19.99,
            'category_name' => 'Clothing',
            'icon' => 'fas fa-tshirt',
            'stock_quantity' => 200,
            'rating' => 4.2
        ],
        [
            'product_id' => 8,
            'name' => 'Garden Tools Set',
            'description' => 'Complete gardening tool set for all your home gardening needs.',
            'price' => 39.99,
            'category_name' => 'Home & Garden',
            'icon' => 'fas fa-leaf',
            'stock_quantity' => 35,
            'rating' => 4.6
        ],
        [
            'product_id' => 9,
            'name' => 'Bestseller Novel',
            'description' => 'Award-winning fiction novel by popular contemporary author.',
            'price' => 14.99,
            'category_name' => 'Books & Media',
            'icon' => 'fas fa-book',
            'stock_quantity' => 80,
            'rating' => 4.8
        ],
        [
            'product_id' => 10,
            'name' => 'Basketball',
            'description' => 'Professional quality basketball for indoor and outdoor use.',
            'price' => 29.99,
            'category_name' => 'Sports & Outdoors',
            'icon' => 'fas fa-basketball-ball',
            'stock_quantity' => 60,
            'rating' => 4.4
        ]
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Products - SmartRetail</title>
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
                <a href="../cart/cart.php" class="mobile-nav-cart">
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

    <!-- Products Page -->
    <section class="products-page">
        <div class="container">
            <div class="page-header">
                <h1>Our Products</h1>
                <p>Discover amazing products with great deals</p>
            </div>

            <!-- Filters Section -->
            <div class="filters-section">
                <form method="GET" class="filters-grid">
                    <div class="filter-group">
                        <label class="filter-label">Search Products</label>
                        <input type="text" name="search" class="filter-input" placeholder="Enter product name..." value="<?php echo htmlspecialchars($search_keywords); ?>">
                    </div>
                    
                    <div class="filter-group">
                        <label class="filter-label">Category</label>
                        <select name="category" class="filter-select">
                            <option value="">All Categories</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo htmlspecialchars($category['id']); ?>" 
                                    <?php echo $category_filter == $category['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($category['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label class="filter-label">Sort By</label>
                        <select name="sort" class="filter-select">
                            <option value="newest" <?php echo $sort_by === 'newest' ? 'selected' : ''; ?>>Newest First</option>
                            <option value="price_low" <?php echo $sort_by === 'price_low' ? 'selected' : ''; ?>>Price: Low to High</option>
                            <option value="price_high" <?php echo $sort_by === 'price_high' ? 'selected' : ''; ?>>Price: High to Low</option>
                            <option value="rating" <?php echo $sort_by === 'rating' ? 'selected' : ''; ?>>Highest Rated</option>
                            <option value="name" <?php echo $sort_by === 'name' ? 'selected' : ''; ?>>Name A-Z</option>
                        </select>
                    </div>
                    
                    <div class="filter-group" style="position: relative;">
                        <label class="filter-label">Search Products</label>
                        <input type="text" name="search" class="filter-input" placeholder="Enter product name..." 
                            value="<?php echo htmlspecialchars($search_keywords); ?>" 
                            autocomplete="off">
                    </div>
                </form>
            </div>

            <!-- Products Grid -->
            <?php if (!empty($products)): ?>
                <div class="products-grid">
                    <?php foreach ($products as $product): 
                        $stock_quantity = $product['stock_quantity'] ?? 10;
                        $stock_class = $stock_quantity > 10 ? '' : ($stock_quantity > 0 ? 'stock-low' : 'stock-out');
                    ?>
                    <div class="product-card">
                        <div class="product-icon">
                            <i class="<?php echo $product['icon']; ?>"></i>
                            <div class="category-badge">
                                <?php echo htmlspecialchars($product['category_name'] ?? 'Uncategorized'); ?>
                            </div>
                        </div>
                        <div class="product-info">
                            <div class="product-header">
                                <h3 class="product-name"><?php echo htmlspecialchars($product['name']); ?></h3>
                                <div class="product-price">R<?php echo number_format($product['price'], 2); ?></div>
                            </div>
                            
                            <p class="product-description"><?php echo htmlspecialchars($product['description']); ?></p>
                            
                            <div class="product-rating">
                                <div class="rating-stars">
                                    <?php
                                    $rating = $product['rating'] ?? 4.5;
                                    $fullStars = floor($rating);
                                    $hasHalfStar = ($rating - $fullStars) >= 0.5;
                                    
                                    for ($i = 1; $i <= 5; $i++) {
                                        if ($i <= $fullStars) {
                                            echo '<i class="fas fa-star"></i>';
                                        } elseif ($i == $fullStars + 1 && $hasHalfStar) {
                                            echo '<i class="fas fa-star-half-alt"></i>';
                                        } else {
                                            echo '<i class="far fa-star"></i>';
                                        }
                                    }
                                    ?>
                                </div>
                                <span class="rating-value"><?php echo number_format($rating, 1); ?></span>
                            </div>
                            
                            <div class="product-stock <?php echo $stock_class; ?>">
                                <i class="fas fa-<?php echo $stock_quantity > 0 ? 'check-circle' : 'times-circle'; ?>"></i>
                                <?php 
                                if ($stock_quantity > 10) {
                                    echo $stock_quantity . ' in stock';
                                } elseif ($stock_quantity > 0) {
                                    echo 'Only ' . $stock_quantity . ' left';
                                } else {
                                    echo 'Out of stock';
                                }
                                ?>
                            </div>

                            <form method="POST" class="add-to-cart-form">
                                <input type="hidden" name="product_id" value="<?php echo $product['product_id']; ?>">
                                <input type="hidden" name="product_name" value="<?php echo htmlspecialchars($product['name']); ?>">
                                <input type="hidden" name="product_price" value="<?php echo $product['price']; ?>">
                                <input type="hidden" name="product_icon" value="<?php echo $product['icon']; ?>">

                                <div class="quantity-container">
                                    <label class="quantity-label">Qty:</label>
                                    <input type="number" name="quantity" class="quantity-input" value="1" min="1" 
                                            max="<?php echo $stock_quantity; ?>" <?php echo $stock_quantity == 0 ? 'disabled' : ''; ?>>
                                </div>

                                <button type="submit" name="add_to_cart" class="btn btn-primary add-to-cart-btn" 
                                    <?php echo $stock_quantity == 0 ? 'disabled' : ''; ?> style="width: 100%;">
                                        <i class="fas fa-shopping-cart"></i>
                                    <?php echo $stock_quantity == 0 ? 'Out of Stock' : 'Add to Cart'; ?>
                                </button>
                            </form>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <!-- No Products Message -->
                <div class="no-products">
                    <div class="no-products-icon">
                        <i class="fas fa-search"></i>
                    </div>
                    <h2>No products found</h2>
                    <p>Try adjusting your search or filter criteria</p>
                    <a href="products.php" class="btn btn-primary">
                        <i class="fas fa-undo"></i>
                        Clear Filters
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
    <script src="../../assets/js/dashboard.js"></script>
    <script src="../../assets/js/stock-validator.js"></script>
    <script src="../../assets/js/search-autocomplete.js"></script>
</body>
</html>