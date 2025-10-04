<?php
/**
 * Initialization file
 * 
 * This file is included in all pages and initializes the application
 */

// Load configuration first
require_once __DIR__ . '/../config/config.php';

// Configure and start session
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', defined('SESSION_SECURE') ? SESSION_SECURE : 0);
ini_set('session.cookie_samesite', 'Lax');
session_start();

// Autoload classes
spl_autoload_register(function ($class) {
    // Convert class name to file path
    $classFile = __DIR__ . '/' . $class . '.php';
    
    // Check if file exists in includes directory
    if (file_exists($classFile)) {
        require_once $classFile;
        return;
    }
    
    // Check in controllers directory
    $controllerFile = __DIR__ . '/../controllers/' . $class . '.php';
    if (file_exists($controllerFile)) {
        require_once $controllerFile;
        return;
    }
    
    // Check in models directory
    $modelFile = __DIR__ . '/../models/' . $class . '.php';
    if (file_exists($modelFile)) {
        require_once $modelFile;
        return;
    }
});

// Initialize database connection
$db = Database::getInstance();

// Helper functions
require_once __DIR__ . '/functions.php';

// Set secure headers
Security::setSecureHeaders();

// Redirect to HTTPS if not already using it
// Uncomment the line below when deploying to production
// Security::redirectToHttps();

// Initialize authentication
$auth = new Auth();

// Check if user is logged in
$currentUser = $auth->getCurrentUser();

// Set default timezone
date_default_timezone_set('UTC');

// Regenerate session ID periodically to prevent session fixation
if (!isset($_SESSION['last_session_regenerate']) || (time() - $_SESSION['last_session_regenerate']) > 1800) {
    session_regenerate_id(true);
    $_SESSION['last_session_regenerate'] = time();
}