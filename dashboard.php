<?php
/**
 * SmartRetail - User Dashboard
 * Modern, responsive dashboard with real products from database
 */

require_once 'config/config.php';
require_once 'utils/Auth.php';
require_once 'config/Database.php';
require_once 'models/Product.php';
require_once 'models/Category.php';

// Require authentication
Auth::requireAuth();

// Get user information
$user_id = Auth::getUserId();
$user_role = Auth::getUserRole();
$username = $_SESSION['username'];
$full_name = $_SESSION['full_name'];
$email = $_SESSION['email'];

// Initialize database connection
try {
    $database = new Database();
    $db = $database->getConnection();
    $productModel = new Product($db);
    $categoryModel = new Category($db);
} catch (Exception $e) {
    error_log("Database connection failed: " . $e->getMessage());
    $productModel = null;
    $categoryModel = null;
}

// Initialize cart if not exists
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Get cart items from session and calculate proper cart count
$cart_items = $_SESSION['cart'];
$cart_total = 0;
$cart_count = 0;

// Calculate total quantity of all items in cart
foreach ($cart_items as $item) {
    $cart_count += $item['quantity'] ?? 1;
}

// Get featured products from database
if ($productModel) {
    try {
        $products_data = $productModel->getAllProductsWithCategory();
        
        // Process products for display - limit to 6 featured products
        $featured_products = [];
        $count = 0;
        foreach ($products_data as $row) {
            if ($count >= 6) break; // Show only 6 products on dashboard
            
            $row['icon'] = Product::getProductIcon($row['name']);
            $row['rating'] = $row['rating'] ?? 4.5;
            $row['stock'] = $row['stock_quantity'] ?? 10;
            $featured_products[] = $row;
            $count++;
        }
        
        // If no products in database, use demo data
        if (empty($featured_products)) {
            $featured_products = getDemoProducts();
        }
        
    } catch (Exception $e) {
        error_log("Error fetching products: " . $e->getMessage());
        $featured_products = getDemoProducts();
    }
} else {
    $featured_products = getDemoProducts();
}

// Helper function for demo data (fallback)
function getDemoProducts() {
    return [
        [
            'id' => 1, 
            'name' => 'Wireless Headphones', 
            'price' => 129.99, 
            'rating' => 4.6, 
            'stock' => 120, 
            'icon' => 'fas fa-headphones',
            'description' => 'High-quality wireless headphones with noise cancellation and premium sound quality.',
            'category_name' => 'Electronics'
        ],
        [
            'id' => 2, 
            'name' => 'Smart Watch', 
            'price' => 299.99, 
            'rating' => 4.4, 
            'stock' => 85, 
            'icon' => 'fas fa-clock',
            'description' => 'Feature-rich smartwatch with health monitoring, GPS, and long battery life.',
            'category_name' => 'Electronics'
        ],
        [
            'id' => 3, 
            'name' => 'Laptop Backpack', 
            'price' => 59.99, 
            'rating' => 4.8, 
            'stock' => 200, 
            'icon' => 'fas fa-briefcase',
            'description' => 'Durable laptop backpack with multiple compartments and waterproof material.',
            'category_name' => 'Accessories'
        ],
        [
            'id' => 4, 
            'name' => 'Bluetooth Speaker', 
            'price' => 89.99, 
            'rating' => 4.5, 
            'stock' => 150, 
            'icon' => 'fas fa-volume-up',
            'description' => 'Portable Bluetooth speaker with crystal clear sound and long battery life.',
            'category_name' => 'Electronics'
        ],
        [
            'id' => 5, 
            'name' => 'Gaming Mouse', 
            'price' => 49.99, 
            'rating' => 4.7, 
            'stock' => 75, 
            'icon' => 'fas fa-mouse',
            'description' => 'High-precision gaming mouse with RGB lighting and programmable buttons.',
            'category_name' => 'Electronics'
        ],
        [
            'id' => 6, 
            'name' => 'USB-C Hub', 
            'price' => 39.99, 
            'rating' => 4.3, 
            'stock' => 120, 
            'icon' => 'fas fa-plug',
            'description' => 'Multi-port USB-C hub for expanding your connectivity options.',
            'category_name' => 'Electronics'
        ]
    ];
}

// Handle add to cart from dashboard
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {
    $product_id = $_POST['product_id'];
    $product_name = $_POST['product_name'];
    $product_price = (float)$_POST['product_price'];
    $product_icon = $_POST['product_icon'];
    
    // Initialize cart in session if not exists
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }
    
    // Check if item already exists in cart
    $item_exists = false;
    foreach ($_SESSION['cart'] as &$item) {
        if ($item['id'] == $product_id) {
            $item['quantity'] += 1;
            $item_exists = true;
            break;
        }
    }
    
    if (!$item_exists) {
        $new_item = [
            'id' => $product_id,
            'product_id' => $product_id,
            'name' => $product_name,
            'price' => $product_price,
            'quantity' => 1,
            'icon' => $product_icon,
            'stock_quantity' => 10
        ];
        $_SESSION['cart'][] = $new_item;
    }
    
    // Update cart count
    $cart_count = 0;
    foreach ($_SESSION['cart'] as $item) {
        $cart_count += $item['quantity'] ?? 1;
    }
    
    // Redirect to prevent form resubmission
    header('Location: dashboard.php?added=1');
    exit;
}

// Update cart count after potential cart modification
$cart_count = 0;
foreach ($_SESSION['cart'] as $item) {
    $cart_count += $item['quantity'] ?? 1;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - SmartRetail</title>
    <link rel="stylesheet" href="assets/css/style.min.css">
    
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
                        <li><a href="views/products/products.php">Products</a></li>
                        <li><a href="views/categories/category.php">Categories</a></li>
                        <li><a href="dashboard.php">Dashboard</a></li>
                    </ul>
                </nav>
                
                <!-- Header Actions -->
                <div class="header-actions">    
                    <!-- Shopping Cart -->
                    <a href="views/cart/cart.php" class="cart-icon" title="Shopping Cart">
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
                                <a href="views/order/orders.php" class="user-dropdown-link">
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
                                <a href="logout.php" class="user-dropdown-link logout">
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
                    <li><a href="views/products/products.php">
                        <i class="fas fa-shopping-bag"></i>
                        All Products
                    </a></li>
                    <li><a href="views/categories/category.php">
                        <i class="fas fa-list"></i>
                        Categories
                    </a></li>
                    <li><a href="views/order/orders.php">
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
                    <li><a href="logout.php" class="logout-link">
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

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container">
            <div class="hero-content">
                <h1>Welcome to Smart Retail</h1>
                <p>Discover amazing products with intelligent recommendations and seamless checkout</p>
                <div class="search-container">
                    <form action="/products.php" method="GET" class="search-form">
                        <input type="text" name="search" placeholder="Search products..." class="search-input">
                        <button type="submit" class="search-button">
                            <i class="fas fa-search"></i>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </section>

    <!-- Featured Products Section -->
    <section class="products-section">
        <div class="container">
            <div class="section-header">
                <h2>Featured Products</h2>
                <p>Browse our collection of quality products</p>
            </div>
        
            <div class="products-grid">
                <?php foreach ($featured_products as $product): 
                    // Use product_id from database, fallback to id for demo data
                    $product_id = $product['product_id'] ?? $product['id'];
                    $product_name = $product['name'];
                    $product_price = $product['price'];
                    $product_icon = $product['icon'];
                    $product_rating = $product['rating'] ?? 4.5;
                    $product_stock = $product['stock_quantity'] ?? $product['stock'] ?? 10;
                    $product_description = $product['description'] ?? 'Quality product from our collection.';
                ?>
                <div class="product-card">
                    <div class="product-icon">
                        <i class="<?php echo $product_icon; ?>"></i>
                    </div>
                    <div class="product-info">
                        <div class="product-header">
                            <h3 class="product-name"><?php echo htmlspecialchars($product_name); ?></h3>
                            <div class="product-price">R<?php echo number_format($product_price, 2); ?></div>
                        </div>
                    
                        <p class="product-description"><?php echo htmlspecialchars($product_description); ?></p>
                    
                        <div class="product-rating">
                            <div class="rating-stars">
                                <?php
                                $fullStars = floor($product_rating);
                                $hasHalfStar = ($product_rating - $fullStars) >= 0.5;
                            
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
                            <span class="rating-value"><?php echo number_format($product_rating, 1); ?></span>
                        </div>
                    
                        <div class="product-stock">
                            <i class="fas fa-check-circle"></i>
                            <?php echo $product_stock; ?> items in stock
                        </div>
                
                        <!-- Add to Cart Form -->
                        <form method="POST" style="width: 100%;">
                            <input type="hidden" name="product_id" value="<?php echo $product_id; ?>">
                            <input type="hidden" name="product_name" value="<?php echo htmlspecialchars($product_name); ?>">
                            <input type="hidden" name="product_price" value="<?php echo $product_price; ?>">
                            <input type="hidden" name="product_icon" value="<?php echo $product_icon; ?>">
                            <button type="submit" name="add_to_cart" class="btn btn-primary" style="width: 100%; justify-content: center; padding: 1rem; font-size: 1rem;">
                                <i class="fas fa-shopping-cart"></i>
                                Add to Cart
                            </button>
                        </form>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
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
    <script src="assets/js/dashboard.js"></script>
    <script src="../../assets/js/enhanced-features.js"></script>
</body>
</html>