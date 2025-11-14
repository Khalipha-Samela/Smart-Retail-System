<?php
/**
 * Customer Registration View
 * Displays the registration form for new customers with role selection
 */
session_start();

require_once 'config/config.php';
require_once 'config/database.php';
require_once 'utils/Auth.php';

// Redirect if already logged in
if (Auth::isLoggedIn()) {
    header('Location: /dashboard.php');
    exit();
}

// Generate CSRF token
$csrf_token = Auth::generateCSRFToken();

// Get form data from session if available (for form repopulation after errors)
$form_data = [
    'full_name' => $_SESSION['form_data']['full_name'] ?? '',
    'email' => $_SESSION['form_data']['email'] ?? '',
    'username' => $_SESSION['form_data']['username'] ?? '',
    'role' => $_SESSION['form_data']['role'] ?? 'customer' // New: role selection
];

// Clear form data from session after use
unset($_SESSION['form_data']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registration - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="assets/css/auth.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="container">
        <div class="left-panel">
            <div class="logo">
                <i class="fas fa-store"></i>
                <?php echo SITE_NAME; ?>
            </div>
            <h1>Create your account</h1>
            <p>Join our platform and choose the right account type for your needs.</p>
            <ul class="features">
                <li><i class="fas fa-check"></i> Fast and secure checkout</li>
                <li><i class="fas fa-check"></i> Personalized recommendations</li>
                <li><i class="fas fa-check"></i> Order tracking and history</li>
                <li><i class="fas fa-check"></i> Exclusive member discounts</li>
            </ul>
        </div>
        
        <div class="right-panel">
            <h2 style="text-align: center; margin-bottom: 1rem; color: #1f2937;">Create Your Account</h2>
            
            <!-- Display error/success messages -->
            <?php if (isset($_SESSION['error_message'])): ?>
                <div class="alert alert-error">
                    <?php echo htmlspecialchars($_SESSION['error_message']); ?>
                    <?php unset($_SESSION['error_message']); ?>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['success_message'])): ?>
                <div class="alert alert-success">
                    <?php echo htmlspecialchars($_SESSION['success_message']); ?>
                    <?php unset($_SESSION['success_message']); ?>
                </div>
            <?php endif; ?>

            <form id="registrationForm" method="POST" novalidate>
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
    <input type="hidden" name="role" id="roleInput" value="<?php echo $form_data['role']; ?>">

                <!-- Role Selection - Same as login page -->
                <div class="login-type-selector">
                    <button type="button" class="login-type-btn customer <?php echo $form_data['role'] === 'customer' ? 'active' : ''; ?>" 
                            onclick="selectRole('customer')">
                        <i class="fas fa-user login-type-icon"></i>
                        Customer
                    </button>
                    <button type="button" class="login-type-btn staff <?php echo $form_data['role'] === 'staff' ? 'active' : ''; ?>" 
                            onclick="selectRole('staff')">
                        <i class="fas fa-user-tie login-type-icon"></i>
                        Staff
                    </button>
                    <button type="button" class="login-type-btn admin <?php echo $form_data['role'] === 'admin' ? 'active' : ''; ?>" 
                            onclick="selectRole('admin')">
                        <i class="fas fa-user-shield login-type-icon"></i>
                        Admin
                    </button>
                </div>

                <p class="role-hint" id="roleHint">
                    <?php 
                    $role_hints = [
                        'customer' => 'Create a customer account to shop and manage orders',
                        'staff' => 'Create a staff account to manage store operations',
                        'admin' => 'Create an admin account for full system access'
                    ];
                    echo $role_hints[$form_data['role']] ?? $role_hints['customer'];
                    ?>
                </p>

                <div class="form-group">
                    <label class="form-label" for="full_name">Full Name *</label>
                    <input type="text" id="full_name" name="full_name" class="form-input" 
                           value="<?php echo htmlspecialchars($form_data['full_name']); ?>" required>
                    <div class="error-message" id="full_name_error">Please enter your full name</div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="email">Email Address *</label>
                    <input type="email" id="email" name="email" class="form-input" 
                           value="<?php echo htmlspecialchars($form_data['email']); ?>" required>
                    <div class="error-message" id="email_error">Please enter a valid email address</div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="username">Username *</label>
                    <input type="text" id="username" name="username" class="form-input" 
                           value="<?php echo htmlspecialchars($form_data['username']); ?>" required>
                    <div class="error-message" id="username_error">Please enter a username (3-50 characters, letters, numbers, and underscores only)</div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="password">Password *</label>
                    <div class="password-toggle">
                        <input type="password" id="password" name="password" class="form-input" required minlength="8">
                        <button type="button" class="toggle-btn" onclick="togglePassword('password')">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                    <div class="error-message" id="password_error">Password must be at least 8 characters long and contain at least one letter and one number</div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="confirm_password">Confirm Password *</label>
                    <div class="password-toggle">
                        <input type="password" id="confirm_password" name="confirm_password" class="form-input" required>
                        <button type="button" class="toggle-btn" onclick="togglePassword('confirm_password')">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                    <div class="error-message" id="confirm_password_error">Passwords do not match</div>
                </div>

                <button type="submit" class="btn-submit" id="submitBtn">
                    <span id="btnText">
                        <?php 
                        $button_texts = [
                            'customer' => 'Create Customer Account',
                            'staff' => 'Create Staff Account', 
                            'admin' => 'Create Admin Account'
                        ];
                        echo $button_texts[$form_data['role']] ?? $button_texts['customer'];
                        ?>
                    </span>
                    <div id="btnSpinner" class="loading-spinner" style="display: none;"></div>
                </button>
            </form>

            <div class="login-link">
                <p>Already have an account? <a href="login.php">Sign in here</a></p>
            </div>

            <!-- Back to Home in right panel (mobile friendly) -->
            <div class="back-to-home">
                <a href="<?php echo BASE_PATH; ?>/index.php">
                    <i class="fas fa-arrow-left"></i>
                    Back to Home Page
                </a>
            </div>
        </div>
    </div>

    <!-- JavaScript -->
    <script src="assets/js/auth.js"></script>
</body>
</html>