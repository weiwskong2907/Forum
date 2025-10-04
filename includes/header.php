<?php
/**
 * Header template
 * 
 * This file contains the header HTML for all pages
 */

// Get current page for navigation highlighting
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle . ' - ' : ''; ?>Blog & Forum</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Custom CSS -->
    <style>
        body {
            padding-top: 56px;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        
        main {
            flex: 1;
        }
        
        .navbar-brand {
            font-weight: bold;
        }
        
        .footer {
            margin-top: auto;
            padding: 1rem 0;
            background-color: #f8f9fa;
            border-top: 1px solid #e9ecef;
        }
        
        .btn-primary {
            background-color: #0d6efd;
            border-color: #0d6efd;
        }
        
        .btn-primary:hover {
            background-color: #0b5ed7;
            border-color: #0a58ca;
        }
        
        .card {
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
            border-radius: 0.5rem;
            border: 1px solid rgba(0, 0, 0, 0.125);
        }
        
        .card-header {
            background-color: rgba(0, 0, 0, 0.03);
            border-bottom: 1px solid rgba(0, 0, 0, 0.125);
        }
        
        .forum-item {
            transition: all 0.2s ease;
        }
        
        .forum-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
        }
        
        .post-meta {
            font-size: 0.875rem;
            color: #6c757d;
        }
        
        .avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
        }
        
        .avatar-sm {
            width: 30px;
            height: 30px;
        }
        
        .avatar-lg {
            width: 80px;
            height: 80px;
        }
        
        @media (max-width: 767.98px) {
            .card-title {
                font-size: 1.25rem;
            }
            
            .h1, h1 {
                font-size: 1.75rem;
            }
            
            .h2, h2 {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark fixed-top">
        <div class="container">
            <a class="navbar-brand" href="<?php echo BASE_URL; ?>">Blog & Forum</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link <?php echo $currentPage === 'index.php' ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $currentPage === 'blog.php' || strpos($currentPage, 'blog_') === 0 ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>/blog.php">Blog</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $currentPage === 'forum.php' || strpos($currentPage, 'forum_') === 0 ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>/forum.php">Forum</a>
                    </li>
                </ul>
                
                <div class="d-flex">
                    <form class="d-flex me-2" action="<?php echo BASE_URL; ?>/search.php" method="get">
                        <input class="form-control me-2" type="search" name="q" placeholder="Search" aria-label="Search">
                        <button class="btn btn-outline-light" type="submit">Search</button>
                    </form>
                    
                    <ul class="navbar-nav">
                        <?php if (isLoggedIn()): ?>
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    <?php echo $_SESSION['username']; ?>
                                </a>
                                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                                    <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/profile.php">My Profile</a></li>
                                    
                                    <?php if (isAdmin()): ?>
                                        <li><hr class="dropdown-divider"></li>
                                        <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/admin/index.php">Admin Dashboard</a></li>
                                    <?php endif; ?>
                                    
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/logout.php">Logout</a></li>
                                </ul>
                            </li>
                        <?php else: ?>
                            <li class="nav-item">
                                <a class="nav-link <?php echo $currentPage === 'login.php' ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>/login.php">Login</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?php echo $currentPage === 'register.php' ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>/register.php">Register</a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </div>
    </nav>
    
    <!-- Main Content -->
    <main>
        <?php
        // Display flash message if any
        $flashMessage = flashMessage();
        if ($flashMessage) {
            echo '<div class="container mt-4">' . $flashMessage . '</div>';
        }
        ?>