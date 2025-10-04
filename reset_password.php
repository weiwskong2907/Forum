<?php
/**
 * Reset Password page
 * 
 * This page handles password reset using tokens
 */

// Include initialization file
require_once __DIR__ . '/includes/init.php';

// Set page title
$pageTitle = 'Reset Password';

// Check if user is already logged in
if (isLoggedIn()) {
    redirect(BASE_URL . '/index.php');
}

// Check if token is provided
if (!isset($_GET['token']) || empty($_GET['token'])) {
    setFlashMessage('Invalid password reset link.', 'danger');
    redirect(BASE_URL . '/forgot_password.php');
}

$token = $_GET['token'];
$userModel = new User();
$user = $userModel->getUserByResetToken($token);

// Validate token
if (!$user || strtotime($user['reset_token_expires']) < time()) {
    setFlashMessage('Password reset link is invalid or has expired.', 'danger');
    redirect(BASE_URL . '/forgot_password.php');
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!verifyCSRFToken($_POST['csrf_token'])) {
        setFlashMessage('Invalid request. Please try again.', 'danger');
        redirect(BASE_URL . '/reset_password.php?token=' . $token);
    }
    
    // Get passwords from form
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirm_password'];
    
    // Validate passwords
    if (empty($password) || strlen($password) < 8) {
        setFlashMessage('Password must be at least 8 characters long.', 'danger');
    } elseif ($password !== $confirmPassword) {
        setFlashMessage('Passwords do not match.', 'danger');
    } else {
        // Update password
        $hashedPassword = Security::hashPassword($password);
        $userModel->updateUserPassword($user['user_id'], $hashedPassword);
        
        // Clear reset token
        $userModel->clearResetToken($user['user_id']);
        
        // Set success message
        setFlashMessage('Your password has been reset successfully. You can now log in with your new password.', 'success');
        redirect(BASE_URL . '/login.php');
    }
}

// Include header
include_once __DIR__ . '/includes/header.php';
?>

<div class="container mt-5 pt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h4>Reset Password</h4>
                </div>
                <div class="card-body">
                    <?php displayFlashMessages(); ?>
                    
                    <form method="post" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF'] . '?token=' . $token); ?>">
                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                        
                        <div class="mb-3">
                            <label for="password" class="form-label">New Password</label>
                            <input type="password" class="form-control" id="password" name="password" required minlength="8">
                            <div class="form-text">Password must be at least 8 characters long.</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">Confirm New Password</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required minlength="8">
                        </div>
                        
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">Reset Password</button>
                        </div>
                    </form>
                </div>
                <div class="card-footer text-center">
                    <div class="small">
                        <a href="<?php echo BASE_URL; ?>/login.php">Back to Login</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Include footer
include_once __DIR__ . '/includes/footer.php';
?>