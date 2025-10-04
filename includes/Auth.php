<?php
/**
 * Authentication class
 * 
 * Handles user authentication, registration, and session management
 */
class Auth {
    private $db;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Register a new user
     * 
     * @param string $username Username
     * @param string $email Email
     * @param string $password Password
     * @return int|bool User ID or false on failure
     */
    public function register($username, $email, $password) {
        // Validate input
        if (!Security::validateUsername($username)) {
            return false; // Invalid username
        }
        
        if (!Security::validateEmail($email)) {
            return false; // Invalid email
        }
        
        // Check if username or email already exists
        $query = "SELECT user_id FROM users WHERE username = ? OR email = ? LIMIT 1";
        $result = $this->db->fetchRow($query, [$username, $email]);
        
        if ($result) {
            return false; // User already exists
        }
        
        // Hash password using Security class
        $hashedPassword = Security::hashPassword($password);
        
        // Insert user
        $userData = [
            'username' => $username,
            'email' => $email,
            'password' => $hashedPassword,
            'role' => 'user'
        ];
        
        $userId = $this->db->insert('users', $userData);
        
        if ($userId) {
            // Create user profile
            $profileData = [
                'user_id' => $userId
            ];
            
            $this->db->insert('user_profiles', $profileData);
            
            // Log security event
            Security::logSecurityEvent("User registered: $username (ID: $userId)", 'info');
            
            return $userId;
        }
        
        return false;
    }
    
    /**
     * Login a user
     * 
     * @param string $username Username or email
     * @param string $password Password
     * @param bool $remember Remember me
     * @return bool Success or failure
     */
    public function login($username, $password, $remember = false) {
        // Check if input is email or username
        $isEmail = filter_var($username, FILTER_VALIDATE_EMAIL);
        
        if ($isEmail) {
            $query = "SELECT user_id, username, email, password, role FROM users WHERE email = ? AND is_active = 1 LIMIT 1";
        } else {
            $query = "SELECT user_id, username, email, password, role FROM users WHERE username = ? AND is_active = 1 LIMIT 1";
        }
        
        $user = $this->db->fetchRow($query, [$username]);
        
        if (!$user) {
            return false; // User not found or inactive
        }
        
        // Verify password
        if (password_verify($password, $user['password'])) {
            // Update last login
            $this->db->update('users', ['last_login' => date('Y-m-d H:i:s')], 'user_id = ?', [$user['user_id']]);
            
            // Set session variables
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['user_role'] = $user['role'];
            
            // Set remember me cookie if requested
            if ($remember) {
                $token = bin2hex(random_bytes(32));
                $expires = time() + (86400 * 30); // 30 days
                
                setcookie('remember_user', $user['user_id'], $expires, '/', '', true, true);
                
                // Note: remember_token column doesn't exist in the database
                // Using session-based authentication instead
            }
            
            return true;
        }
        
        return false;
    }
    
    /**
     * Logout a user
     * 
     * @return void
     */
    public function logout() {
        // Clear session
        $_SESSION = [];
        
        // Clear remember me cookie
        if (isset($_COOKIE['remember_token'])) {
            setcookie('remember_token', '', time() - 3600, '/', '', true, true);
        }
        
        // Destroy session
        session_destroy();
    }
    
    /**
     * Check if user is logged in
     * 
     * @return bool
     */
    public function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }
    
    /**
     * Get current user ID
     * 
     * @return int|null User ID or null if not logged in
     */
    public function getUserId() {
        return isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
    }
    
    /**
     * Get current user
     * 
     * @return array|null User data or null if not logged in
     */
    public function getCurrentUser() {
        if (!$this->isLoggedIn()) {
            // Remember me functionality is disabled until database is updated
            // Removing remember_token check to prevent errors
            return null;
        }
        
        // Get user data
        $query = "SELECT u.user_id, u.username, u.email, u.role, u.created_at, p.avatar, p.bio, p.website 
                 FROM users u 
                 LEFT JOIN user_profiles p ON u.user_id = p.user_id 
                 WHERE u.user_id = ? AND u.is_active = 1 
                 LIMIT 1";
        
        return $this->db->fetchRow($query, [$_SESSION['user_id']]);
    }
    
    /**
     * Check if user has a specific role
     * 
     * @param string|array $roles Role(s) to check
     * @return bool
     */
    public function hasRole($roles) {
        if (!$this->isLoggedIn()) {
            return false;
        }
        
        if (!is_array($roles)) {
            $roles = [$roles];
        }
        
        return in_array($_SESSION['user_role'], $roles);
    }
    
    /**
     * Update user password
     * 
     * @param int $userId User ID
     * @param string $currentPassword Current password
     * @param string $newPassword New password
     * @return bool Success or failure
     */
    public function updatePassword($userId, $currentPassword, $newPassword) {
        // Get user
        $query = "SELECT password FROM users WHERE user_id = ? LIMIT 1";
        $user = $this->db->fetchRow($query, [$userId]);
        
        if (!$user) {
            return false;
        }
        
        // Verify current password
        if (!password_verify($currentPassword, $user['password'])) {
            return false;
        }
        
        // Hash new password
        $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => HASH_COST]);
        
        // Update password
        return $this->db->update('users', ['password' => $hashedPassword], 'user_id = ?', [$userId]);
    }
    
    /**
     * Reset password (for forgotten passwords)
     * 
     * @param string $email User email
     * @return bool Success or failure
     */
    public function resetPassword($email) {
        // Check if email exists
        $query = "SELECT user_id FROM users WHERE email = ? AND is_active = 1 LIMIT 1";
        $user = $this->db->fetchRow($query, [$email]);
        
        if (!$user) {
            return false;
        }
        
        // Generate reset token
        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', time() + 3600); // 1 hour
        
        // Store token in database
        $this->db->update('users', [
            'reset_token' => $token,
            'reset_expires' => $expires
        ], 'user_id = ?', [$user['user_id']]);
        
        // In a real application, send an email with the reset link
        // For this example, we'll just return the token
        return $token;
    }
    
    /**
     * Validate reset token
     * 
     * @param string $token Reset token
     * @return int|bool User ID or false if invalid
     */
    public function validateResetToken($token) {
        $query = "SELECT user_id FROM users WHERE reset_token = ? AND reset_expires > NOW() LIMIT 1";
        $user = $this->db->fetchRow($query, [$token]);
        
        if ($user) {
            return $user['user_id'];
        }
        
        return false;
    }
    
    /**
     * Set new password after reset
     * 
     * @param int $userId User ID
     * @param string $password New password
     * @return bool Success or failure
     */
    public function setNewPassword($userId, $password) {
        // Hash password
        $hashedPassword = password_hash($password, PASSWORD_BCRYPT, ['cost' => HASH_COST]);
        
        // Update password and clear reset token
        return $this->db->update('users', [
            'password' => $hashedPassword,
            'reset_token' => null,
            'reset_expires' => null
        ], 'user_id = ?', [$userId]);
    }
}