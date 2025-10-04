<?php
/**
 * Forgot Password page
 * 
 * This page handles password reset requests
 */

// Include initialization file
require_once __DIR__ . '/includes/init.php';

// Set page title
$pageTitle = 'Forgot Password';

// Check if user is already logged in
if (isLoggedIn()) {
    redirect(BASE_URL . '/index.php');
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!verifyCSRFToken($_POST['csrf_token'])) {
        setFlashMessage('Invalid request. Please try again.', 'danger');
        redirect(BASE_URL . '/forgot_password.php');
    }
    
    // Get email from form
    $email = trim($_POST['email']);
    
    // Validate email
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        setFlashMessage('Please enter a valid email address.', 'danger');
    } else {
        // Check if email exists in database
        $userModel = new User();
        $user = $userModel->getByEmail($email);
        
        if ($user) {
            // Generate reset token
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', time() + 3600); // 1 hour expiration
            
            // Save token to database
            $userModel->saveResetToken($user['user_id'], $token, $expires);
            
            // Build reset URL
            $resetUrl = BASE_URL . '/reset_password.php?token=' . $token;
            
            // Send email (placeholder - would use a proper email library in production)
            $to = $email;
            $subject = 'Password Reset Request';
            $message = "Hello {$user['username']},\n\n";
            $message .= "You have requested to reset your password. Please click the link below to reset your password:\n\n";
            $message .= $resetUrl . "\n\n";
            $message .= "This link will expire in 1 hour.\n\n";
            $message .= "If you did not request this password reset, please ignore this email.\n\n";
            $message .= "Regards,\nThe Forum Team";
            $headers = "From: noreply@forum.com";
            
            // For development, just show the reset link
            setFlashMessage('Password reset instructions have been sent to your email. <br><strong>Development mode:</strong> <a href="' . $resetUrl . '">Click here to reset password</a>', 'success');
            
            // In production, would use:
            // mail($to, $subject, $message, $headers);
            // setFlashMessage('Password reset instructions have been sent to your email.', 'success');
        } else {
            // Don't reveal if email exists or not for security
            setFlashMessage('If your email is registered, you will receive password reset instructions.', 'info');
        }
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
                    <h4>Forgot Password</h4>
                </div>
                <div class="card-body">
                    <?php displayFlashMessages(); ?>
                    
                    <form method="post" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                        
                        <div class="mb-3">
                            <label for="email" class="form-label">Email Address</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                            <div class="form-text">Enter the email address associated with your account.</div>
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