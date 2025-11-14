<?php
/**
 * Login API Endpoint
 */
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: ' . ($_SERVER['HTTP_ORIGIN'] ?? '*'));
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Credentials: true');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

// Get POST data
$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';
$remember_me = isset($_POST['remember_me']) && $_POST['remember_me'] == '1';
$redirect = $_POST['redirect'] ?? '';
$login_type = $_POST['login_type'] ?? 'customer';

// Validate required fields
if (empty($username) || empty($password)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Username and password are required'
    ]);
    exit;
}

try {
    // Database connection
    $pdo = new PDO("mysql:host=localhost;dbname=smartretail_db;charset=utf8", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Query user - also check by email for better UX
    $stmt = $pdo->prepare("
        SELECT u.*, r.role_name 
        FROM users u 
        JOIN roles r ON u.role_id = r.role_id 
        WHERE (u.username = ? OR u.email = ?) AND u.is_active = TRUE
    ");
    $stmt->execute([$username, $username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user && password_verify($password, $user['password_hash'])) {
        // Check role access
        $user_role = strtolower($user['role_name']);
        $login_type = strtolower($login_type);
        
        $role_access = [
            'customer' => ['customer'],
            'admin' => ['admin', 'staff']
        ];
        
        // Enhanced role validation with better error messages
        if (!in_array($user_role, $role_access[$login_type] ?? [])) {
            $error_messages = [
                'customer' => "This account does not have customer access privileges.",
                'admin' => "This account does not have staff/admin access privileges. Please use the Staff/Admin login option."
            ];
            
            http_response_code(403);
            echo json_encode([
                'success' => false,
                'message' => $error_messages[$login_type] ?? 'Access denied for this login type.'
            ]);
            exit;
        }
        
        // Start session
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Set session variables
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['role_id'] = $user['role_id'];
        $_SESSION['role_name'] = $user['role_name'];
        $_SESSION['authenticated'] = true;
        $_SESSION['login_time'] = time();
        
        // Update last login
        $updateStmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE user_id = ?");
        $updateStmt->execute([$user['user_id']]);
        
        // Determine redirect URL with fallbacks
        $final_redirect = $redirect;
        if (empty($final_redirect)) {
            switch ($user_role) {
                case 'admin':
                case 'staff':
                    $final_redirect = '/smart-retail/views/admin/admin-dashboard.php';
                    break;
                case 'customer':
                default:
                    $final_redirect = '/smart-retail/dashboard.php';
                    break;
            }
        }
        
        // Handle remember me functionality
        if ($remember_me) {
            // Generate remember token
            $remember_token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', strtotime('+30 days'));
            
            $tokenStmt = $pdo->prepare("
                INSERT INTO remember_tokens (user_id, token, expires_at) 
                VALUES (?, ?, ?)
            ");
            $tokenStmt->execute([$user['user_id'], $remember_token, $expires]);
            
            // Set cookie
            setcookie('remember_token', $remember_token, [
                'expires' => strtotime('+30 days'),
                'path' => '/',
                'domain' => '',
                'secure' => false, // Set to true in production with HTTPS
                'httponly' => true,
                'samesite' => 'Lax'
            ]);
        }
        
        // Success response with user-friendly message
        $welcome_messages = [
            'admin' => 'Welcome back, Administrator!',
            'staff' => 'Welcome back, Staff Member!', 
            'customer' => 'Welcome back! Happy shopping!'
        ];
        
        echo json_encode([
            'success' => true,
            'message' => $welcome_messages[$user_role] ?? 'Login successful!',
            'user' => [
                'id' => $user['user_id'],
                'username' => $user['username'],
                'email' => $user['email'],
                'full_name' => $user['full_name'],
                'role' => $user['role_name']
            ],
            'redirect' => $final_redirect,
            'user_role' => $user_role // Helpful for frontend
        ]);
        
    } else {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => 'Invalid username or password. Please try again.'
        ]);
    }
} catch (Exception $e) {
    error_log("Login API error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'A system error occurred. Please try again later.'
    ]);
}
?>