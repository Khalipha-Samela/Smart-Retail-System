<?php
/**
 * Registration API Endpoint - Working Version
 */

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Set headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

session_start();

try {
    // Only allow POST requests
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Only POST requests are allowed');
    }

    // Get POST data
    $input = file_get_contents('php://input');
    parse_str($input, $_POST);
    
    // Required fields
    $required_fields = ['username', 'email', 'password', 'confirm_password', 'full_name', 'role', 'csrf_token'];
    $data = [];
    $errors = [];

    // Validate required fields
    foreach ($required_fields as $field) {
        if (empty($_POST[$field])) {
            $errors[$field] = "$field is required";
        } else {
            $data[$field] = trim($_POST[$field]);
        }
    }

    // Return errors if any required fields are missing
    if (!empty($errors)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Please fill in all required fields',
            'errors' => $errors
        ]);
        exit();
    }

    // Field-specific validation
    if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Please enter a valid email address';
    }

    if (!preg_match('/^[a-zA-Z0-9_]{3,50}$/', $data['username'])) {
        $errors['username'] = 'Username must be 3-50 characters and contain only letters, numbers, and underscores';
    }

    if (strlen($data['password']) < 8) {
        $errors['password'] = 'Password must be at least 8 characters long';
    }

    if ($data['password'] !== $data['confirm_password']) {
        $errors['confirm_password'] = 'Passwords do not match';
    }

    if (!in_array($data['role'], ['customer', 'staff', 'admin'])) {
        $errors['role'] = 'Please select a valid account type';
    }

    // Return validation errors if any
    if (!empty($errors)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Please fix the validation errors',
            'errors' => $errors
        ]);
        exit();
    }

    // SIMULATE SUCCESSFUL REGISTRATION (without database for now)
    $_SESSION['user_id'] = rand(1000, 9999);
    $_SESSION['username'] = $data['username'];
    $_SESSION['email'] = $data['email'];
    $_SESSION['full_name'] = $data['full_name'];
    $_SESSION['role_name'] = $data['role'];
    $_SESSION['authenticated'] = true;
    $_SESSION['registration_complete'] = true;

    // Success response
    http_response_code(201);
    echo json_encode([
        'success' => true,
        'message' => 'Registration successful! Welcome to SmartRetail.',
        'user' => [
            'username' => $data['username'],
            'email' => $data['email'],
            'full_name' => $data['full_name'],
            'role' => $data['role']
        ],
        'redirect' => 'dashboard.php'
    ]);

} catch (Exception $e) {
    error_log("Registration error: " . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Registration failed: ' . $e->getMessage()
    ]);
}
?>