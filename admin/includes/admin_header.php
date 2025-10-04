<?php
// Check if user is admin
if (!isAdmin()) {
    setFlashMessage('You do not have permission to access this page.', 'danger');
    redirect(BASE_URL . '/index.php');
}

// Set page title if not already set
if (!isset($pageTitle)) {
    $pageTitle = 'Admin Dashboard';
}

// Include initialization file if not already included
if (!defined('BASE_URL')) {
    require_once __DIR__ . '/../../includes/init.php';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.3/font/bootstrap-icons.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/custom.css">
    <style>
        html, body {
            height: 100%;
        }
        body {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }
        .container-fluid {
            flex: 1 0 auto;
        }
        footer {
            flex-shrink: 0;
            margin-top: auto !important;
        }
    </style>
</head>
<style>
    body {
        padding-top: 0px;
        
    }
</style>
<body>
    <!-- Admin Navigation Bar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow">
        <div class="container-fluid">
            <a class="navbar-brand fw-bold" href="<?php echo BASE_URL; ?>/admin/index.php">
                <i class="bi bi-gear-fill me-2"></i>Admin Panel
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#adminNavbar">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="adminNavbar">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link d-flex align-items-center" href="<?php echo BASE_URL; ?>/admin/index.php">
                            <i class="bi bi-speedometer2 me-1"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link d-flex align-items-center" href="<?php echo BASE_URL; ?>/admin/users.php">
                            <i class="bi bi-people-fill me-1"></i> Users
                        </a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="contentDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-file-earmark-text-fill me-1"></i> Content
                        </a>
                        <ul class="dropdown-menu" aria-labelledby="contentDropdown">
                            <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/admin/content.php">All Content</a></li>
                            <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/admin/blog_posts.php">Blog Posts</a></li>
                        </ul>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link d-flex align-items-center" href="<?php echo BASE_URL; ?>/admin/forum.php">
                            <i class="bi bi-chat-square-text-fill me-1"></i> Forum
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link d-flex align-items-center" href="<?php echo BASE_URL; ?>/admin/system_health_check.php">
                            <i class="bi bi-activity me-1"></i> System
                        </a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link d-flex align-items-center btn btn-outline-light btn-sm me-2" href="<?php echo BASE_URL; ?>/index.php">
                            <i class="bi bi-house-door-fill me-1"></i> Back to Site
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link d-flex align-items-center btn btn-danger btn-sm" href="<?php echo BASE_URL; ?>/logout.php">
                            <i class="bi bi-box-arrow-right me-1"></i> Logout
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Display flash messages -->
    <?php displayFlashMessages(); ?>

    <div class="container-fluid mt-3">