<?php
/**
 * Logout page
 * 
 * This page handles user logout
 */

// Include initialization file
require_once __DIR__ . '/includes/init.php';

// Logout user
$auth->logout();

// Redirect to home page
setFlashMessage('You have been logged out successfully.', 'success');
redirect(BASE_URL . '/index.php');
?>