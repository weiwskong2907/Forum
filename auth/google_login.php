<?php
/**
 * Google Sign-In Authentication
 * 
 * Handles Google OAuth authentication flow
 */

// Include initialization file
require_once __DIR__ . '/../includes/init.php';

// Google API credentials
$clientID = 'YOUR_GOOGLE_CLIENT_ID';
$clientSecret = 'YOUR_GOOGLE_CLIENT_SECRET';
$redirectURI = BASE_URL . '/auth/google_callback.php';

// Create Google OAuth URL
$googleAuthURL = 'https://accounts.google.com/o/oauth2/v2/auth';
$googleAuthURL .= '?client_id=' . urlencode($clientID);
$googleAuthURL .= '&redirect_uri=' . urlencode($redirectURI);
$googleAuthURL .= '&response_type=code';
$googleAuthURL .= '&scope=' . urlencode('email profile');
$googleAuthURL .= '&access_type=online';
$googleAuthURL .= '&prompt=select_account';

// Store CSRF token in session to prevent CSRF attacks
$_SESSION['google_auth_state'] = bin2hex(random_bytes(16));
$googleAuthURL .= '&state=' . $_SESSION['google_auth_state'];

// Redirect to Google's OAuth server
header('Location: ' . $googleAuthURL);
exit;