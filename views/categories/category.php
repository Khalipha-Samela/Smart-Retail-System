<?php
/**
 * SmartRetail - Categories Page
 * Display all product categories
 */

require_once '../../config/config.php';
require_once '../../config/Database.php';
require_once '../../utils/Auth.php';
require_once '../../models/Category.php';
require_once '../../models/Cart.php';

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
    $categoryModel = new Category($db);
    $cartModel = new Cart($db);
} catch (Exception $e) {
    error_log("Database connection failed: " . $e->getMessage());
    $categoryModel = null;
}

// Initialize cart
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

$cart_count = $user_id && $cartModel ? $cartModel->getCartCount($user_id) : Cart::getSessionCartCount();

$cart_count = 0;
if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) {
        $cart_count += $item['quantity'] ?? 1;
    }
}

// Get categories with product counts
$categories = [];
if ($categoryModel) {
    try {
        $categories_data = $categoryModel->getCategoriesWithProductCount();
        
        // Process categories for display
        $categories = [];
        foreach ($categories_data as $row) {
            $row['icon'] = Category::getCategoryIcon($row['name']);
            $categories[] = $row;
        }
        
    } catch (Exception $e) {
        error_log("Error fetching categories: " . $e->getMessage());
        $categories = getDemoCategories();
    }
} else {
    $categories = getDemoCategories();
}

// Helper function for demo categories
function getDemoCategories() {
    return [
        [
            'category_id' => 1,
            'name' => 'Electronics',
            'description' => 'Latest gadgets, smartphones, laptops, and electronic devices',
            'product_count' => 15,
            'icon' => 'fas fa-laptop'
        ],
        [
            'category_id' => 2,
            'name' => 'Clothing',
            'description' => 'Fashionable clothing for men, women, and children',
            'product_count' => 28,
            'icon' => 'fas fa-tshirt'
        ],
        [
            'category_id' => 3,
            'name' => 'Home & Garden',
            'description' => 'Furniture, decor, and gardening supplies for your home',
            'product_count' => 42,
            'icon' => 'fas fa-home'
        ],
        [
            'category_id' => 4,
            'name' => 'Sports & Outdoors',
            'description' => 'Sports equipment, outdoor gear, and fitness accessories',
            'product_count' => 23,
            'icon' => 'fas fa-football-ball'
        ],
        [
            'category_id' => 5,
            'name' => 'Books & Media',
            'description' => 'Books, movies, music, and educational materials',
            'product_count' => 56,
            'icon' => 'fas fa-book'
        ],
        [
            'category_id' => 6,
            'name' => 'Beauty & Health',
            'description' => 'Cosmetics, skincare, health products, and personal care',
            'product_count' => 34,
            'icon' => 'fas fa-spa'
        ],
        [
            'category_id' => 7,
            'name' => 'Toys & Games',
            'description' => 'Toys, games, and entertainment for all ages',
            'product_count' => 19,
            'icon' => 'fas fa-gamepad'
        ],
        [
            'category_id' => 8,
            'name' => 'Automotive',
            'description' => 'Car accessories, tools, and automotive supplies',
            'product_count' => 12,
            'icon' => 'fas fa-car'
        ]
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Categories - SmartRetail</title>
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

    <!-- Categories Page -->
    <section class="categories-page">
        <div class="container">
            <div class="page-header">
                <h1>Product Categories</h1>
                <p>Browse our wide range of product categories to find exactly what you're looking for</p>
            </div>

            <!-- Stats Section -->
            <div class="stats-section">
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-tags"></i>
                        </div>
                        <div class="stat-number"><?php echo count($categories); ?></div>
                        <div class="stat-label">Total Categories</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-boxes"></i>
                        </div>
                        <div class="stat-number"><?php echo array_sum(array_column($categories, 'product_count')); ?></div>
                        <div class="stat-label">Total Products</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-star"></i>
                        </div>
                        <div class="stat-number">8</div>
                        <div class="stat-label">Main Categories</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-shipping-fast"></i>
                        </div>
                        <div class="stat-number">24/7</div>
                        <div class="stat-label">Customer Support</div>
                    </div>
                </div>
            </div>

            <!-- All Categories -->
            <div class="section-header">
                <h2>All Categories</h2>
                <p>Explore our complete collection of product categories</p>
            </div>

            <?php if (!empty($categories)): ?>
                <div class="categories-grid" id="categoriesGrid">
                    <?php foreach ($categories as $category): ?>
                    <a href="../products/products.php?category=<?php echo $category['id']; ?>" class="category-card">
                        <div class="category-icon">
                            <i class="<?php echo $category['icon']; ?>"></i>
                        </div>
                        <div class="category-info">
                            <div class="category-header">
                                <h3 class="category-name"><?php echo htmlspecialchars($category['name']); ?></h3>
                                <span class="product-count"><?php echo $category['product_count']; ?> products</span>
                            </div>
                            <p class="category-description"><?php echo htmlspecialchars($category['description'] ?? 'Discover amazing products in this category'); ?></p>
                            <div class="btn btn-primary view-products-btn">
                                <i class="fas fa-arrow-right"></i>
                                View Products
                            </div>
                        </div>
                    </a>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <!-- No Categories Message -->
                <div class="no-categories">
                    <div class="no-categories-icon">
                        <i class="fas fa-folder-open"></i>
                    </div>
                    <h2>No Categories Found</h2>
                    <p>We're working on adding more categories to our store.</p>
                    <a href="../products/products.php" class="btn btn-primary">
                        <i class="fas fa-shopping-bag"></i>
                        Browse All Products
                    </a>
                </div>
            <?php endif; ?>

            <!-- Popular Categories -->
            <?php if (!empty($categories)): ?>
            <div class="popular-categories">
                <div class="section-header">
                    <h2>Most Popular</h2>
                    <p>Check out our most shopped categories</p>
                </div>
                <div class="categories-grid">
                    <?php 
                    // Sort categories by product count (descending) and take top 4
                    $popular_categories = $categories;
                    usort($popular_categories, function($a, $b) {
                        return $b['product_count'] - $a['product_count'];
                    });
                    $popular_categories = array_slice($popular_categories, 0, 4);
                    ?>
                    <?php foreach ($popular_categories as $category): ?>
                    <a href="../products/products.php?category=<?php echo $category['id']; ?>" class="category-card">
                        <div class="category-icon" style="background: linear-gradient(135deg, var(--accent-color) 0%, #f97316 100%);">
                            <i class="<?php echo $category['icon']; ?>"></i>
                        </div>
                        <div class="category-info">
                            <div class="category-header">
                                <h3 class="category-name"><?php echo htmlspecialchars($category['name']); ?></h3>
                                <span class="product-count" style="background: var(--accent-color);"><?php echo $category['product_count']; ?> products</span>
                            </div>
                            <p class="category-description"><?php echo htmlspecialchars($category['description'] ?? 'Popular products in this category'); ?></p>
                            <div class="btn btn-primary view-products-btn" style="background: var(--accent-color);">
                                <i class="fas fa-fire"></i>
                                Shop Now
                            </div>
                        </div>
                    </a>
                    <?php endforeach; ?>
                </div>
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
    <script src="../../assets/js/enhanced-features.js"></script>

</body>
</html>