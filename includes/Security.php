<?php
/**
 * Security class
 * 
 * Handles security-related functionality
 */
class Security {
    /**
     * Sanitize user input
     * 
     * @param string $input Input to sanitize
     * @return string
     */
    public static function sanitize($input) {
        return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }
    
    /**
     * Generate CSRF token
     * 
     * @return string
     */
    public static function generateCSRFToken() {
        if (!isset($_SESSION['csrf_token']) || empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        
        return $_SESSION['csrf_token'];
    }
    
    /**
     * Verify CSRF token
     * 
     * @param string $token Token to verify
     * @return bool
     */
    public static function verifyCSRFToken($token) {
        if (!isset($_SESSION['csrf_token']) || $token !== $_SESSION['csrf_token']) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Regenerate CSRF token
     * 
     * @return string
     */
    public static function regenerateCSRFToken() {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        
        return $_SESSION['csrf_token'];
    }
    
    /**
     * Hash password
     * 
     * @param string $password Password to hash
     * @return string
     */
    public static function hashPassword($password) {
        return password_hash($password, PASSWORD_BCRYPT, ['cost' => HASH_COST]);
    }
    
    /**
     * Verify password
     * 
     * @param string $password Password to verify
     * @param string $hash Hash to verify against
     * @return bool
     */
    public static function verifyPassword($password, $hash) {
        return password_verify($password, $hash);
    }
    
    /**
     * Generate random token
     * 
     * @param int $length Token length
     * @return string
     */
    public static function generateToken($length = 32) {
        return bin2hex(random_bytes($length / 2));
    }
    
    /**
     * Set secure headers
     * 
     * @return void
     */
    public static function setSecureHeaders() {
        // Content Security Policy
        header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com; style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com; img-src 'self' data: https://via.placeholder.com; font-src 'self' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com; connect-src 'self' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com;");
        
        // X-Content-Type-Options
        header("X-Content-Type-Options: nosniff");
        
        // X-Frame-Options
        header("X-Frame-Options: SAMEORIGIN");
        
        // X-XSS-Protection
        header("X-XSS-Protection: 1; mode=block");
        
        // Referrer-Policy
        header("Referrer-Policy: strict-origin-when-cross-origin");
        
        // Feature-Policy
        header("Feature-Policy: geolocation 'none'; microphone 'none'; camera 'none'");
        
        // Strict-Transport-Security (HSTS)
        header("Strict-Transport-Security: max-age=31536000; includeSubDomains; preload");
    }
    
    /**
     * Check if request is HTTPS
     * 
     * @return bool
     */
    public static function isHttps() {
        return (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') || 
               (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
    }
    
    /**
     * Redirect to HTTPS
     * 
     * @return void
     */
    public static function redirectToHttps() {
        if (!self::isHttps()) {
            $redirect = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
            header("Location: $redirect");
            exit;
        }
    }
    
    /**
     * Validate email
     * 
     * @param string $email Email to validate
     * @return bool
     */
    public static function validateEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
    
    /**
     * Validate username
     * 
     * @param string $username Username to validate
     * @return bool
     */
    public static function validateUsername($username) {
        return preg_match('/^[a-zA-Z0-9_]{3,50}$/', $username) === 1;
    }
    
    /**
     * Validate password strength
     * 
     * @param string $password Password to validate
     * @return bool
     */
    public static function validatePasswordStrength($password) {
        // Password must be at least 8 characters long
        if (strlen($password) < 8) {
            return false;
        }
        
        // Password must contain at least one uppercase letter, one lowercase letter, one number, and one special character
        $uppercase = preg_match('/[A-Z]/', $password);
        $lowercase = preg_match('/[a-z]/', $password);
        $number = preg_match('/[0-9]/', $password);
        $special = preg_match('/[^A-Za-z0-9]/', $password);
        
        return $uppercase && $lowercase && $number && $special;
    }
    
    /**
     * Rate limit check
     * 
     * @param string $key Rate limit key
     * @param int $limit Number of attempts allowed
     * @param int $seconds Time period in seconds
     * @return bool True if rate limit not exceeded, false otherwise
     */
    public static function checkRateLimit($key, $limit = 5, $seconds = 60) {
        $rateLimitKey = 'rate_limit_' . $key;
        $rateLimitTime = 'rate_limit_time_' . $key;
        
        // Initialize rate limit if not set
        if (!isset($_SESSION[$rateLimitKey])) {
            $_SESSION[$rateLimitKey] = 0;
            $_SESSION[$rateLimitTime] = time();
        }
        
        // Reset rate limit if time period has passed
        if (time() - $_SESSION[$rateLimitTime] > $seconds) {
            $_SESSION[$rateLimitKey] = 0;
            $_SESSION[$rateLimitTime] = time();
        }
        
        // Increment rate limit counter
        $_SESSION[$rateLimitKey]++;
        
        // Check if rate limit exceeded
        if ($_SESSION[$rateLimitKey] > $limit) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Log security event
     * 
     * @param string $event Event description
     * @param string $level Event level (info, warning, error)
     * @return void
     */
    public static function logSecurityEvent($event, $level = 'info') {
        $logFile = ROOT_PATH . '/logs/security.log';
        $logDir = dirname($logFile);
        
        // Create logs directory if it doesn't exist
        if (!file_exists($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        // Format log entry
        $timestamp = date('Y-m-d H:i:s');
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $user = isset($_SESSION['user_id']) ? $_SESSION['username'] : 'guest';
        $url = $_SERVER['REQUEST_URI'] ?? 'unknown';
        
        $logEntry = "[$timestamp] [$level] [$ip] [$user] [$url] $event" . PHP_EOL;
        
        // Write to log file
        file_put_contents($logFile, $logEntry, FILE_APPEND);
    }
}