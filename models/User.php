<?php
/**
 * User Model with Authentication
 */

class User {
    private $conn;
    private $table_name = "users";
    
    // Password requirements
    private $min_password_length = 8;
    private $max_login_attempts = 5;
    private $lockout_duration = 900; // 15 minutes

    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * Create new user
     */
    public function createUser($data) {
        $query = "INSERT INTO users (username, email, password_hash, full_name, role_id) 
              VALUES (:username, :email, :password_hash, :full_name, :role_id)";
    
        $stmt = $this->conn->prepare($query);
    
        // Hash password
        $password_hash = password_hash($data['password'], PASSWORD_DEFAULT);
    
        $stmt->bindParam(":username", $data['username']);
        $stmt->bindParam(":email", $data['email']);
        $stmt->bindParam(":password_hash", $password_hash);
        $stmt->bindParam(":full_name", $data['full_name']);
        $stmt->bindParam(":role_id", $data['role_id']);
    
        return $stmt->execute() ? $this->conn->lastInsertId() : false;
    }

    /**
     * Authenticate user
     */
    public function authenticate($username, $password) {
        $query = "SELECT u.*, r.role_name 
              FROM users u 
              JOIN roles r ON u.role_id = r.role_id 
              WHERE (u.username = :username OR u.email = :username) 
              AND u.is_active = TRUE";
    
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':username', $username);
        $stmt->execute();
    
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
        if ($user && password_verify($password, $user['password_hash'])) {
            return $user;
        }
    
        throw new Exception('Invalid username or password');
    }

    /**
     * Check if username exists
     */
    public function usernameExists($username) {
        $query = "SELECT user_id FROM " . $this->table_name . " 
                 WHERE username = :username";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(':username', $username);
        $stmt->execute();
        
        return $stmt->rowCount() > 0;
    }

    /**
     * Check if email exists
     */
    public function emailExists($email) {
        $query = "SELECT user_id FROM " . $this->table_name . " 
                 WHERE email = :email";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(':email', $email);
        $stmt->execute();
        
        return $stmt->rowCount() > 0;
    }

    /**
     * Validate user data
     */
    private function validateUserData($data) {
        // Validate username
        if (!preg_match('/^[a-zA-Z0-9_]{3,50}$/', $data['username'])) {
            throw new Exception('Username must be 3-50 characters and contain only letters, numbers, and underscores');
        }

        // Validate email
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Invalid email address');
        }

        // Validate password strength
        if (!$this->validatePassword($data['password'])) {
            throw new Exception('Password must be at least 8 characters long and contain at least one letter and one number');
        }

        // Validate full name
        if (strlen(trim($data['full_name'])) < 2) {
            throw new Exception('Full name must be at least 2 characters long');
        }

        return true;
    }

    /**
     * Validate password strength
     */
    private function validatePassword($password) {
        if (strlen($password) < $this->min_password_length) {
            return false;
        }

        // Check for at least one letter and one number
        if (!preg_match('/[A-Za-z]/', $password) || !preg_match('/[0-9]/', $password)) {
            return false;
        }

        return true;
    }

    /**
     * Record login attempt
     */
    private function recordLoginAttempt($username, $success) {
        $query = "INSERT INTO login_attempts (ip_address, username, success) 
                 VALUES (:ip_address, :username, :success)";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(':ip_address', $_SERVER['REMOTE_ADDR']);
        $stmt->bindValue(':username', $username);
        $stmt->bindValue(':success', $success);
        $stmt->execute();
    }

    /**
     * Check if account is locked out
     */
    public function isLockedOut($username) {
        $query = "SELECT COUNT(*) as attempt_count 
                FROM login_attempts 
                WHERE username = :username 
                AND attempted_at > DATE_SUB(NOW(), INTERVAL :lockout_duration SECOND)
                AND success = FALSE";
    
        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(':username', $username);
        $stmt->bindValue(':lockout_duration', $this->lockout_duration);
        $stmt->execute();
    
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['attempt_count'] >= $this->max_login_attempts;
    }

    /**
     * Update last login timestamp
     */
    public function updateLastLogin($user_id) {
        $query = "UPDATE " . $this->table_name . " 
                SET last_login = NOW() 
                WHERE user_id = :user_id";
    
        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(':user_id', $user_id);
        return $stmt->execute();
    }

    /**
    * Store remember me token (optional - for persistent sessions)
    */
    public function storeRememberToken($user_id, $token, $expires) {
        // First, clear any existing tokens for this user
        $clear_query = "DELETE FROM remember_tokens WHERE user_id = :user_id";
        $clear_stmt = $this->conn->prepare($clear_query);
        $clear_stmt->bindValue(':user_id', $user_id);
        $clear_stmt->execute();
    
        // Insert new token
        $query = "INSERT INTO remember_tokens (user_id, token, expires_at) 
                VALUES (:user_id, :token, FROM_UNIXTIME(:expires))";
    
        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(':user_id', $user_id);
        $stmt->bindValue(':token', password_hash($token, PASSWORD_DEFAULT));
        $stmt->bindValue(':expires', $expires);
        return $stmt->execute();
    }

    /**
     * Update password hash
     */
    private function updatePasswordHash($user_id, $password) {
        $new_hash = password_hash($password, PASSWORD_DEFAULT);
        
        $query = "UPDATE " . $this->table_name . " 
                 SET password_hash = :password_hash 
                 WHERE user_id = :user_id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(':password_hash', $new_hash);
        $stmt->bindValue(':user_id', $user_id);
        $stmt->execute();
    }

    /**
    * Validate remember me token (optional)
    */
    public function validateRememberToken($token) {
        $query = "SELECT rt.*, u.user_id, u.username, u.email, u.full_name, u.role_id 
                FROM remember_tokens rt 
                JOIN users u ON rt.user_id = u.user_id 
                WHERE rt.token = :token 
                AND rt.expires_at > NOW() 
                AND u.is_active = TRUE";
    
        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(':token', $token);
        $stmt->execute();
    
        if ($stmt->rowCount() === 1) {
            $data = $stmt->fetch(PDO::FETCH_ASSOC);
        
            // Verify token (you might want to use hash_equals for timing attack protection)
            if (password_verify($token, $data['token'])) {
                return [
                    'user_id' => $data['user_id'],
                    'username' => $data['username'],
                    'email' => $data['email'],
                    'full_name' => $data['full_name'],
                    'role_id' => $data['role_id']
                ];
            }
        }
    
        return false;
    }

    /**
     * Get user by ID
     */
    public function getUserById($user_id) {
        $query = "SELECT u.*, r.role_name 
                 FROM " . $this->table_name . " u 
                 JOIN roles r ON u.role_id = r.role_id 
                 WHERE u.user_id = :user_id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(':user_id', $user_id);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Change user password
     */
    public function changePassword($user_id, $current_password, $new_password) {
        try {
            // Get current password hash
            $query = "SELECT password_hash FROM " . $this->table_name . " 
                     WHERE user_id = :user_id";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindValue(':user_id', $user_id);
            $stmt->execute();
            
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!password_verify($current_password, $user['password_hash'])) {
                throw new Exception('Current password is incorrect');
            }

            // Validate new password
            if (!$this->validatePassword($new_password)) {
                throw new Exception('New password does not meet requirements');
            }

            // Update password
            $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
            
            $update_query = "UPDATE " . $this->table_name . " 
                           SET password_hash = :password_hash 
                           WHERE user_id = :user_id";
            
            $update_stmt = $this->conn->prepare($update_query);
            $update_stmt->bindValue(':password_hash', $new_hash);
            $update_stmt->bindValue(':user_id', $user_id);
            
            return $update_stmt->execute();

        } catch (Exception $e) {
            error_log("Change password error: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get total number of customers
     */
    public function getTotalCustomers() {
        $query = "SELECT COUNT(*) as total FROM users u 
              JOIN roles r ON u.role_id = r.role_id 
              WHERE r.role_name = 'customer'";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total'] ?? 0;
    }

    public function getUsersCount() {
        try {
            $query = "SELECT COUNT(*) as count FROM users";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['count'] ?? 0;
        } catch (PDOException $e) {
            error_log("Error getting users count: " . $e->getMessage());
            return 0;
        }
    }
}
?>