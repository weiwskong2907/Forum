<?php
/**
 * Main configuration file
 * 
 * This file contains all the configuration settings for the application
 */

// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'forum_blog');

// Application paths
define('BASE_URL', ''); // Empty base URL for relative paths
define('ROOT_PATH', dirname(__DIR__));
define('UPLOADS_PATH', ROOT_PATH . '/uploads');

// Security settings
define('HASH_COST', 12); // For password hashing

// Session security level
define('SESSION_SECURE', true); // Set to false for development without HTTPS

// Error reporting (disable in production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Time zone
date_default_timezone_set('UTC');