<?php
/**
 * Logout Handler
 */

session_start();
require_once 'utils/Auth.php';

// Clear remember me token if set
if (isset($_COOKIE['remember_token'])) {
    setcookie('remember_token', '', time() - 3600, '/', '', true, true);
}

// Log the logout
if (isset($_SESSION['username'])) {
    error_log("User {$_SESSION['username']} logged out");
}

// Destroy session
Auth::logout();

// Redirect to login page
header('Location: login.php?success=logout');
exit;
?>