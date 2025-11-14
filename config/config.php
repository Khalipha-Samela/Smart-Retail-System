<?php
/**
 * SmartRetail - Simple Config
 */

// Basic App Settings
define('SITE_NAME', 'SmartRetail');
define('SITE_URL', 'http://localhost/smart-retail');
define('BASE_PATH', '/smart-retail');

// Environment Detection
$host = $_SERVER['HTTP_HOST'] ?? '';
if (strpos($host, 'localhost') !== false || strpos($host, '127.0.0.1') !== false) {
    define('ENVIRONMENT', 'development');
} else {
    define('ENVIRONMENT', 'production');
}

// Database Config
define('DB_HOST', 'localhost');
define('DB_NAME', 'smartretail_db');
define('DB_USER', 'root');
define('DB_PASS', '');

// Security
define('SECRET_KEY', 'your-secret-key-here');

// File Uploads
define('UPLOAD_PATH', __DIR__ . '/../images/');
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB

// App Settings
define('ITEMS_PER_PAGE', 12);
define('DEFAULT_CURRENCY', 'ZAR');

// Error Reporting (turn off in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Timezone
date_default_timezone_set('Africa/Johannesburg');

// Start session if not started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Database connection
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Simple helper functions
function redirect($url) {
    header('Location: ' . $url);
    exit();
}

function sanitize($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

function is_logged_in() {
    return isset($_SESSION['user_id']);
}
?>