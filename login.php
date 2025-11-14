<?php
/**
 * Login View
 * Displays the login form for users with role selection
 */

require_once 'config/config.php';
require_once 'utils/Auth.php';

// Redirect if already logged in
if (Auth::isLoggedIn()) {
    // Determine redirect based on user role
    $user_role = $_SESSION['role_name'] ?? '';
    $redirect = $_GET['redirect'] ?? getDefaultRedirect($user_role);
    header('Location: ' . $redirect);
    exit();
}

// Helper function to determine default redirect based on role
function getDefaultRedirect($role) {
    switch ($role) {
        case 'admin':
        case 'staff':
            return BASE_PATH . 'views/admin/admin-dashboard.php';
        case 'customer':
        default:
            return BASE_PATH . '/dashboard.php';
    }
}

// Generate CSRF token
$csrf_token = Auth::generateCSRFToken();

// Get form data from session if available (for form repopulation after errors)
$form_data = [
    'username' => $_SESSION['form_data']['username'] ?? '',
    'remember_me' => $_SESSION['form_data']['remember_me'] ?? false,
    'login_type' => $_SESSION['form_data']['login_type'] ?? 'customer'
];

// Clear form data from session after use
unset($_SESSION['form_data']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/auth.min.css">
</head>
<body>
    <div class="container">
        <div class="left-panel">
            <div class="logo">
                <i class="fas fa-store"></i>
                <?php echo SITE_NAME; ?>
            </div>
            <h1>Welcome Back</h1>
            <p>Sign in to access your <?php echo SITE_NAME; ?> account and continue your shopping experience.</p>
            <ul class="features">
                <li><i class="fas fa-check"></i> Track your orders</li>
                <li><i class="fas fa-check"></i> Manage your profile</li>
                <li><i class="fas fa-check"></i> Save your preferences</li>
                <li><i class="fas fa-check"></i> Faster checkout</li>
            </ul>
        </div>
        
        <div class="right-panel">
            <h2 style="text-align: center; margin-bottom: 1rem; color: #1f2937;">Sign In to Your Account</h2>

            <!-- Login Type Selector -->
            <div class="login-type-selector">
                <button type="button" class="login-type-btn customer <?php echo $form_data['login_type'] === 'customer' ? 'active' : ''; ?>" 
                        onclick="selectLoginType('customer')">
                    <i class="fas fa-user login-type-icon"></i>
                    Customer Login
                </button>
                <button type="button" class="login-type-btn admin <?php echo $form_data['login_type'] === 'admin' ? 'active' : ''; ?>" 
                        onclick="selectLoginType('admin')">
                    <i class="fas fa-user-shield login-type-icon"></i>
                    Staff/Admin Login
                </button>
            </div>

            <p class="role-hint" id="roleHint">
                <?php echo $form_data['login_type'] === 'customer' 
                    ? 'Sign in to shop and manage your orders' 
                    : 'Access the admin dashboard to manage products, orders, and customers'; ?>
            </p>

            <!-- Error/Success Messages -->
            <?php if (isset($_GET['error'])): ?>
                <div class="alert alert-error">
                    <?php 
                    $errors = [
                        'invalid' => 'Invalid username or password',
                        'inactive' => 'Your account has been deactivated',
                        'locked' => 'Account temporarily locked due to too many failed attempts',
                        'timeout' => 'Your session has expired. Please log in again.',
                        'access_denied' => 'Access denied. Please log in to continue.',
                        'role_mismatch' => 'This account does not have access to the selected login type',
                        'staff_access_denied' => 'Staff accounts must use the Staff/Admin login option'
                    ];
                    echo $errors[$_GET['error']] ?? 'An error occurred during login';
                    ?>
                </div>
            <?php endif; ?>

            <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success">
                    <?php
                    $success_messages = [
                        'registered' => 'Registration successful! Please log in.',
                        'logout' => 'You have been successfully logged out.',
                        'password_changed' => 'Password changed successfully. Please log in with your new password.'
                    ];
                    echo $success_messages[$_GET['success']] ?? 'Success!';
                    ?>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['error_message'])): ?>
                <div class="alert alert-error">
                    <?php echo htmlspecialchars($_SESSION['error_message']); ?>
                    <?php unset($_SESSION['error_message']); ?>
                </div>
            <?php endif; ?>

        
            <!-- Form action path -->
            <form id="loginForm" action="<?php echo BASE_PATH; ?>/api/auth/login.php" method="POST">
                <!-- CSRF Token -->
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                
                <!-- Redirect URL -->
                <input type="hidden" name="redirect" value="<?php echo htmlspecialchars($_GET['redirect'] ?? ''); ?>">

                <!-- Login Type -->
                <input type="hidden" name="login_type" id="loginType" value="<?php echo $form_data['login_type']; ?>">

                <div class="form-group">
                    <label class="form-label" for="username">Username or Email *</label>
                    <input type="text" id="username" name="username" class="form-input" required 
                           autocomplete="username" value="<?php echo htmlspecialchars($form_data['username']); ?>">
                    <div class="error-message" id="username_error">Please enter your username or email</div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="password">Password *</label>
                    <div class="password-toggle">
                        <input type="password" id="password" name="password" class="form-input" required 
                               autocomplete="current-password">
                        <button type="button" class="toggle-btn" onclick="togglePassword()">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                    <div class="error-message" id="password_error">Please enter your password</div>
                </div>

                <div class="form-options">
                    <label class="remember-me">
                        <input type="checkbox" name="remember_me" value="1" <?php echo $form_data['remember_me'] ? 'checked' : ''; ?>>
                        <span>Remember me</span>
                    </label>
                    <!-- FIXED: Forgot password link -->
                    <a href="reset-password.php" class="forgot-password">Forgot password?</a>
                </div>

                <button type="submit" class="btn-submit" id="submitBtn">
                    <span id="btnText">
                        <?php echo $form_data['login_type'] === 'customer' ? 'Sign In as Customer' : 'Sign In as Staff/Admin'; ?>
                    </span>
                    <div id="btnSpinner" class="loading-spinner" style="display: none;"></div>
                </button>
            </form>

            <!-- Demo Accounts (for development) -->
            <?php if (defined('ENVIRONMENT') && ENVIRONMENT === 'development'): ?>
            <div class="demo-accounts">
                <h4>Demo Accounts (Click to auto-fill):</h4>
                <div class="demo-account" onclick="fillDemoCredentials('customer', 'customer123', 'customer')">
                    <strong>Customer:</strong> customer / customer123
                </div>
                <div class="demo-account" onclick="fillDemoCredentials('admin', 'admin123', 'admin')">
                    <strong>Admin:</strong> admin / admin123
                </div>
                <div class="demo-account" onclick="fillDemoCredentials('staff', 'staff123', 'admin')">
                    <strong>Staff:</strong> staff / staff123
                </div>
            </div>
            <?php endif; ?>

            <div class="auth-links">
                <p>Don't have an account? <a href="register.php">Sign up here</a></p>
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

    <script>
    // Demo account auto-fill (for development)
    <?php 
    $showDemoAccounts = (defined('ENVIRONMENT') && ENVIRONMENT === 'development') 
        || (isset($_GET['demo']) && $_GET['demo'] === 'true');
    if ($showDemoAccounts): 
    ?>
    function fillDemoCredentials(username, password, type) {
        document.getElementById('username').value = username;
        document.getElementById('password').value = password;
        selectLoginType(type);
        
        // Clear any existing errors
        document.querySelectorAll('.error-message').forEach(el => el.style.display = 'none');
        document.querySelectorAll('.form-input').forEach(el => el.classList.remove('error'));
        
        // Show success message
        showAlert(`Demo credentials filled: ${username} / ${password} (${type})`, 'success');
    }
    <?php endif; ?>
    </script>
</body>
</html>