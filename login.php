<?php
/**
 * Login page
 * 
 * This page handles user login
 */

// Include initialization file
require_once __DIR__ . '/includes/init.php';

// Redirect if already logged in
if (isLoggedIn()) {
    redirect(BASE_URL);
}

// Process login form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        setFlashMessage('Invalid request. Please try again.', 'danger');
        redirect(BASE_URL . '/login.php');
    }
    
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']) && $_POST['remember'] === '1';
    
    if (empty($username) || empty($password)) {
        setFlashMessage('Please enter both username/email and password.', 'danger');
    } else {
        // Attempt login
        if ($auth->login($username, $password, $remember)) {
            // Redirect to intended page or home
            $redirect = $_SESSION['redirect_after_login'] ?? BASE_URL;
            unset($_SESSION['redirect_after_login']);
            
            redirect($redirect);
        } else {
            setFlashMessage('Invalid username/email or password.', 'danger');
        }
    }
}

// Page title
$pageTitle = 'Login';

// Include header
include_once __DIR__ . '/includes/header.php';
?>

<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h1 class="h4 mb-0">Login</h1>
                </div>
                <div class="card-body">
                    <form action="<?php echo BASE_URL; ?>/login.php" method="post">
                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                        
                        <div class="mb-3">
                            <label for="username" class="form-label">Username or Email</label>
                            <input type="text" class="form-control" id="username" name="username" required autofocus>
                        </div>
                        
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="remember" name="remember" value="1">
                            <label class="form-check-label" for="remember">Remember me</label>
                        </div>
                        
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">Login</button>
                        </div>
                    </form>
                </div>
                <div class="card-footer text-center">
                    <p class="mb-0">Don't have an account? <a href="<?php echo BASE_URL; ?>/register.php">Register</a></p>
                    <p class="mb-0 mt-2"><a href="<?php echo BASE_URL; ?>/forgot_password.php">Forgot your password?</a></p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Include footer
include_once __DIR__ . '/includes/footer.php';
?>