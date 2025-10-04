<?php
/**
 * Homepage
 * 
 * This is the main landing page of the website
 */

// Include initialization file
require_once __DIR__ . '/includes/init.php';

// Page title
$pageTitle = 'Home';

// Include header
include_once __DIR__ . '/includes/header.php';
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-lg-8">
            <div class="card mb-4">
                <div class="card-body">
                    <h1 class="card-title">Welcome to Blog & Forum</h1>
                    <p class="card-text">This is a combined blog and forum platform where you can read interesting articles and participate in community discussions.</p>
                    
                    <?php if (!isLoggedIn()): ?>
                        <div class="mt-3">
                            <a href="<?php echo BASE_URL; ?>/register.php" class="btn btn-primary me-2">Register</a>
                            <a href="<?php echo BASE_URL; ?>/login.php" class="btn btn-outline-primary">Login</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <h2 class="mb-3">Latest Blog Posts</h2>
            
            <div class="alert alert-info">No blog posts found.</div>
            
            <div class="text-end mb-4">
                <a href="<?php echo BASE_URL; ?>/blog.php" class="btn btn-outline-primary">View All Posts</a>
            </div>
        </div>
        
        <div class="col-lg-4">
            <div class="card mb-4">
                <div class="card-header">
                    <h2 class="h5 mb-0">Latest Forum Threads</h2>
                </div>
                <div class="card-body p-0">
                    <div class="p-3">
                        <div class="alert alert-info mb-0">No forum threads found.</div>
                    </div>
                </div>
                <div class="card-footer text-end">
                    <a href="<?php echo BASE_URL; ?>/forum.php" class="btn btn-sm btn-outline-primary">View All Threads</a>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h2 class="h5 mb-0">Quick Links</h2>
                </div>
                <div class="card-body">
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item">
                            <a href="<?php echo BASE_URL; ?>/blog.php" class="text-decoration-none">
                                <i class="fas fa-rss me-2"></i> Blog
                            </a>
                        </li>
                        <li class="list-group-item">
                            <a href="<?php echo BASE_URL; ?>/forum.php" class="text-decoration-none">
                                <i class="fas fa-comments me-2"></i> Forum
                            </a>
                        </li>
                        <?php if (isLoggedIn()): ?>
                            <li class="list-group-item">
                                <a href="<?php echo BASE_URL; ?>/profile.php" class="text-decoration-none">
                                    <i class="fas fa-user me-2"></i> My Profile
                                </a>
                            </li>
                        <?php else: ?>
                            <li class="list-group-item">
                                <a href="<?php echo BASE_URL; ?>/login.php" class="text-decoration-none">
                                    <i class="fas fa-sign-in-alt me-2"></i> Login
                                </a>
                            </li>
                            <li class="list-group-item">
                                <a href="<?php echo BASE_URL; ?>/register.php" class="text-decoration-none">
                                    <i class="fas fa-user-plus me-2"></i> Register
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Include footer
include_once __DIR__ . '/includes/footer.php';
?>