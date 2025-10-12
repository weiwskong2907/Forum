<?php
/**
 * Google Sign-In Callback
 * 
 * Processes the OAuth callback from Google
 */

// Include initialization file
require_once __DIR__ . '/../includes/init.php';

// Google API credentials
$clientID = 'YOUR_GOOGLE_CLIENT_ID';
$clientSecret = 'YOUR_GOOGLE_CLIENT_SECRET';
$redirectURI = BASE_URL . '/auth/google_callback.php';

// Verify state parameter to prevent CSRF attacks
if (!isset($_GET['state']) || $_GET['state'] !== $_SESSION['google_auth_state']) {
    setFlashMessage('Invalid authentication request. Please try again.', 'danger');
    redirect(BASE_URL . '/login.php');
}

// Clear the state from session
unset($_SESSION['google_auth_state']);

// Check for authorization code
if (!isset($_GET['code'])) {
    setFlashMessage('Authentication failed. Please try again.', 'danger');
    redirect(BASE_URL . '/login.php');
}

// Exchange authorization code for access token
$tokenURL = 'https://oauth2.googleapis.com/token';
$postData = [
    'code' => $_GET['code'],
    'client_id' => $clientID,
    'client_secret' => $clientSecret,
    'redirect_uri' => $redirectURI,
    'grant_type' => 'authorization_code'
];

// Initialize cURL session
$ch = curl_init($tokenURL);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);

// Execute cURL request
$response = curl_exec($ch);
$error = curl_error($ch);
curl_close($ch);

// Check for cURL errors
if ($error) {
    setFlashMessage('Authentication error: ' . $error, 'danger');
    redirect(BASE_URL . '/login.php');
}

// Parse the token response
$tokenData = json_decode($response, true);
if (!isset($tokenData['access_token'])) {
    setFlashMessage('Failed to obtain access token. Please try again.', 'danger');
    redirect(BASE_URL . '/login.php');
}

// Get user info with the access token
$userInfoURL = 'https://www.googleapis.com/oauth2/v3/userinfo';
$ch = curl_init($userInfoURL);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $tokenData['access_token']]);

// Execute cURL request
$userInfoResponse = curl_exec($ch);
$error = curl_error($ch);
curl_close($ch);

// Check for cURL errors
if ($error) {
    setFlashMessage('Failed to get user information: ' . $error, 'danger');
    redirect(BASE_URL . '/login.php');
}

// Parse user info
$userInfo = json_decode($userInfoResponse, true);
if (!isset($userInfo['email'])) {
    setFlashMessage('Failed to get user email. Please try again.', 'danger');
    redirect(BASE_URL . '/login.php');
}

// Check if user exists in our database
$user = new User($db);
$existingUser = $user->getByEmail($userInfo['email']);

if ($existingUser) {
    // User exists, log them in
    $auth->loginById($existingUser['id']);
    
    // Redirect to home page or intended destination
    $redirect = $_SESSION['redirect_after_login'] ?? BASE_URL;
    unset($_SESSION['redirect_after_login']);
    redirect($redirect);
} else {
    // Create new user account
    $username = generateUniqueUsername($userInfo['given_name'] ?? '' . $userInfo['family_name'] ?? '');
    $password = bin2hex(random_bytes(16)); // Random password as they'll use Google to sign in
    
    $userData = [
        'username' => $username,
        'email' => $userInfo['email'],
        'password' => password_hash($password, PASSWORD_DEFAULT),
        'google_id' => $userInfo['sub'] ?? '',
        'avatar' => $userInfo['picture'] ?? '',
        'is_active' => 1,
        'role' => 'user'
    ];
    
    $newUserId = $user->create($userData);
    if ($newUserId) {
        // Log the new user in
        $auth->loginById($newUserId);
        
        setFlashMessage('Account created successfully with Google Sign-In!', 'success');
        redirect(BASE_URL . '/profile.php');
    } else {
        setFlashMessage('Failed to create account. Please try registering manually.', 'danger');
        redirect(BASE_URL . '/register.php');
    }
}

/**
 * Generate a unique username based on Google profile
 */
function generateUniqueUsername($baseName) {
    global $db;
    
    // Clean the base name
    $username = preg_replace('/[^a-zA-Z0-9]/', '', $baseName);
    $username = strtolower($username);
    
    // If empty, use a default
    if (empty($username)) {
        $username = 'user';
    }
    
    // Check if username exists
    $stmt = $db->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
    $stmt->bind_param('s', $username);
    $stmt->execute();
    $stmt->bind_result($count);
    $stmt->fetch();
    $stmt->close();
    
    // If username exists, add a random suffix
    if ($count > 0) {
        $username .= rand(100, 9999);
    }
    
    return $username;
}