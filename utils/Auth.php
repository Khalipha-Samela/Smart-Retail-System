<?php
/**
 * Authentication Utility Class
 * Handles user authentication, session management, and security
 */

class Auth {
    private static $session_timeout = 3600; // 1 hour
    
    /**
     * Start secure session
     */
    public static function startSession() {
        if (session_status() === PHP_SESSION_NONE) {
            // Secure session configuration
            ini_set('session.cookie_httponly', 1);
            ini_set('session.cookie_secure', 1);
            ini_set('session.use_strict_mode', 1);
            ini_set('session.cookie_samesite', 'Strict');
            
            session_start();
            
            // Regenerate session ID periodically
            if (!isset($_SESSION['last_regeneration'])) {
                self::regenerateSession();
            } elseif (time() - $_SESSION['last_regeneration'] > 1800) {
                self::regenerateSession();
            }
            
            // Check session timeout
            self::checkTimeout();
        }
    }
    
    /**
     * Initialize user session after successful login
     */
    public static function initUserSession($user_id, $username, $email, $full_name, $role_id, $role_name) {
        self::startSession();
    
        $_SESSION['user_id'] = $user_id;
        $_SESSION['username'] = $username;
        $_SESSION['email'] = $email;
        $_SESSION['full_name'] = $full_name;
        $_SESSION['role_id'] = $role_id;
        $_SESSION['role_name'] = $role_name;
        $_SESSION['authenticated'] = true;
        $_SESSION['last_activity'] = time();
    
        self::regenerateSession();
    }
    
    /**
     * Regenerate session ID
     */
    private static function regenerateSession() {
        session_regenerate_id(true);
        $_SESSION['last_regeneration'] = time();
    }
    
    /**
     * Check session timeout
     */
    private static function checkTimeout() {
        if (isset($_SESSION['last_activity']) && 
            (time() - $_SESSION['last_activity'] > self::$session_timeout)) {
            self::logout();
            header('Location: /login.php?timeout=1');
            exit;
        }
        $_SESSION['last_activity'] = time();
    }
    
    /**
     * Check if user is logged in
     */
    public static function isLoggedIn() {
        return isset($_SESSION['user_id']) && isset($_SESSION['authenticated']);
    }
    
    /**
     * Check if user has specific role
     */
    public static function hasRole($role) {
        if (!self::isLoggedIn()) return false;
        return $_SESSION['role_name'] === $role;
    }
    
    /**
     * Check if user has permission
     */
    public static function hasPermission($permission) {
        if (!self::isLoggedIn()) return false;
        
        // Admin has all permissions
        if ($_SESSION['role_name'] === 'admin') return true;
        
        // Check role-based permissions
        $permissions = self::getRolePermissions($_SESSION['role_id']);
        return in_array($permission, $permissions);
    }
    
    /**
     * Get permissions for a role
     */
    private static function getRolePermissions($role_id) {
        $permissions = [
            1 => ['manage_users', 'manage_products', 'view_reports', 'manage_orders'], // admin
            2 => ['place_orders', 'view_orders', 'update_profile'], // customer
            3 => ['manage_products', 'view_orders', 'update_inventory'] // staff
        ];
        
        return $permissions[$role_id] ?? [];
    }
    
    /**
     * Redirect if not authenticated
     */
    public static function requireAuth() {
        if (!self::isLoggedIn()) {
            header('Location: /login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
            exit;
        }
    
        // Ensure required session variables exist
        self::ensureSessionVariables();
    }
    
    /**
     * Ensure all required session variables are set
     */
    private static function ensureSessionVariables() {
        if (!isset($_SESSION['full_name'])) {
            $_SESSION['full_name'] = $_SESSION['username'] ?? 'User';
        }
        if (!isset($_SESSION['email'])) {
            $_SESSION['email'] = 'No email provided';
        }
        if (!isset($_SESSION['role_name'])) {
            $_SESSION['role_name'] = 'user';
        }
    }
    
    /**
     * Redirect if not authorized
     */
    public static function requireRole($role) {
        self::requireAuth();
        if (!self::hasRole($role)) {
            http_response_code(403);
            include '../views/errors/403.php';
            exit;
        }
    }
    
    /**
     * Redirect if no permission
     */
    public static function requirePermission($permission) {
        self::requireAuth();
        if (!self::hasPermission($permission)) {
            http_response_code(403);
            include '../views/errors/403.php';
            exit;
        }
    }
    
    /**
     * Logout user
     */
    public static function logout() {
        // Clear session data
        $_SESSION = array();
        
        // Delete session cookie
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        
        session_destroy();
    }
    
    /**
     * Generate CSRF token
     */
    public static function generateCSRFToken() {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
    
    /**
     * Validate CSRF token
     */
    public static function validateCSRFToken($token) {
        return isset($_SESSION['csrf_token']) && 
               hash_equals($_SESSION['csrf_token'], $token);
    }
    
    /**
     * Get current user ID
     */
    public static function getUserId() {
        return $_SESSION['user_id'] ?? null;
    }
    
    /**
     * Get current user role
     */
    public static function getUserRole() {
        return $_SESSION['role_name'] ?? null;
    }
    
    /**
     * Get current username
     */
    public static function getUsername() {
        return $_SESSION['username'] ?? null;
    }
    
    /**
     * Get current user email
     */
    public static function getEmail() {
        return $_SESSION['email'] ?? null;
    }
    
    /**
     * Get current user full name
     */
    public static function getFullName() {
        return $_SESSION['full_name'] ?? null;
    }
}
?>