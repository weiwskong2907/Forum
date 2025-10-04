<?php
/**
 * Helper functions
 * 
 * This file contains helper functions used throughout the application
 */

/**
 * Redirect to a URL
 * 
 * @param string $url URL to redirect to
 * @return void
 */
function redirect($url) {
    header("Location: $url");
    exit;
}

/**
 * Create a URL slug from a string
 * 
 * @param string $string String to convert to slug
 * @return string
 */
function createSlug($string) {
    // Replace non-alphanumeric characters with hyphens
    $slug = preg_replace('/[^a-z0-9]+/i', '-', strtolower(trim($string)));
    // Remove leading/trailing hyphens
    $slug = trim($slug, '-');
    return $slug;
}

/**
 * Sanitize user input
 * 
 * @param string $input Input to sanitize
 * @return string
 */
function sanitize($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

/**
 * Format date
 * 
 * @param string $date Date string
 * @param string $format Date format
 * @return string
 */
function formatDate($date, $format = 'M j, Y g:i a') {
    $timestamp = strtotime($date);
    return date($format, $timestamp);
}

/**
 * Check if user is logged in
 * 
 * @return bool
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

/**
 * Check if user is admin
 * 
 * @return bool
 */
function isAdmin() {
    return isset($_SESSION['user_role']) && ($_SESSION['user_role'] === 'admin' || $_SESSION['user_role'] === 'super_admin');
}

/**
 * Check if user is super admin
 * 
 * @return bool
 */
function isSuperAdmin() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'super_admin';
}

/**
 * Check if user has a specific permission
 * 
 * @param string $permission Permission to check
 * @return bool
 */
function hasPermission($permission) {
    // For now, we'll implement a simple permission system
    // Super admins have all permissions
    if (isSuperAdmin()) {
        return true;
    }
    
    // Admins have most permissions
    if (isAdmin()) {
        // List of permissions that admins have
        $adminPermissions = [
            'create_blog_posts',
            'edit_blog_posts',
            'delete_blog_posts',
            'manage_users',
            'manage_forums',
            'manage_comments'
        ];
        
        return in_array($permission, $adminPermissions);
    }
    
    // Regular users have limited permissions
    if (isLoggedIn()) {
        // List of permissions that regular users have
        $userPermissions = [
            'create_forum_posts',
            'edit_own_posts',
            'delete_own_posts'
        ];
        
        return in_array($permission, $userPermissions);
    }
    
    // Not logged in users have no permissions
    return false;
}

/**
 * Generate CSRF token
 * 
 * @return string
 */
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
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
function verifyCSRFToken($token) {
    if (!isset($_SESSION['csrf_token']) || $token !== $_SESSION['csrf_token']) {
        return false;
    }
    return true;
}

/**
 * Display flash message
 * 
 * @return string|null
 */
function flashMessage() {
    if (isset($_SESSION['flash_message'])) {
        $message = $_SESSION['flash_message'];
        $type = $_SESSION['flash_type'] ?? 'info';
        
        // Clear the flash message
        unset($_SESSION['flash_message']);
        unset($_SESSION['flash_type']);
        
        return '<div class="alert alert-' . $type . '">' . $message . '</div>';
    }
    
    return null;
}

/**
 * Set flash message
 * 
 * @param string $message Message to display
 * @param string $type Message type (success, info, warning, danger)
 * @return void
 */
function setFlashMessage($message, $type = 'info') {
    $_SESSION['flash_message'] = $message;
    $_SESSION['flash_type'] = $type;
}

/**
 * Truncate text to a specified length
 * 
 * @param string $text Text to truncate
 * @param int $length Maximum length
 * @param string $append String to append if truncated
 * @return string
 */
function truncateText($text, $length = 100, $append = '...') {
    if (strlen($text) <= $length) {
        return $text;
    }
    
    $text = substr($text, 0, $length);
    $text = substr($text, 0, strrpos($text, ' '));
    
    return $text . $append;
}

/**
 * Get current URL
 * 
 * @return string
 */
function getCurrentUrl() {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $uri = $_SERVER['REQUEST_URI'];
    
    return $protocol . '://' . $host . $uri;
}

/**
 * Check if string contains HTML
 * 
 * @param string $string String to check
 * @return bool
 */
function containsHtml($string) {
    return $string !== strip_tags($string);
}

/**
 * Convert Markdown to HTML
 * 
 * @param string $markdown Markdown text
 * @return string HTML
 */
function markdownToHtml($markdown) {
    // Simple Markdown conversion (for a more robust solution, use a library like Parsedown)
    
    // Convert headers
    $html = preg_replace('/^# (.*?)$/m', '<h1>$1</h1>', $markdown);
    $html = preg_replace('/^## (.*?)$/m', '<h2>$1</h2>', $html);
    $html = preg_replace('/^### (.*?)$/m', '<h3>$1</h3>', $html);
    
    // Convert bold and italic
    $html = preg_replace('/\*\*(.*?)\*\*/s', '<strong>$1</strong>', $html);
    $html = preg_replace('/\*(.*?)\*/s', '<em>$1</em>', $html);
    
    // Convert links
    $html = preg_replace('/\[(.*?)\]\((.*?)\)/s', '<a href="$2">$1</a>', $html);
    
    // Convert lists
    $html = preg_replace('/^- (.*?)$/m', '<li>$1</li>', $html);
    $html = preg_replace('/((?:<li>.*<\/li>\n)+)/s', '<ul>$1</ul>', $html);
    
    // Convert paragraphs
    $html = preg_replace('/^(?!<h|<ul|<li)(.*?)$/m', '<p>$1</p>', $html);
    
    return $html;
}

/**
 * Debug function
 * 
 * @param mixed $data Data to debug
 * @param bool $die Whether to die after output
 * @return void
 */
function debug($data, $die = true) {
    echo '<pre>';
    print_r($data);
    echo '</pre>';
    
    if ($die) {
        die();
    }
}

/**
 * Display flash messages
 * 
 * @return void
 */
function displayFlashMessages() {
    echo flashMessage();
}