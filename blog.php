<?php
/**
 * Blog page
 * 
 * This page displays blog posts
 */

// Include initialization file
require_once __DIR__ . '/includes/init.php';

// Initialize models
$blogModel = new BlogPost();
$categoryModel = new BlogCategory();

// Get categories
$categories = $categoryModel->getAll();

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 5;
$offset = ($page - 1) * $perPage;

// Filter by category
$categorySlug = $_GET['category'] ?? null;
$category = null;

if ($categorySlug) {
    $category = $categoryModel->getBySlug($categorySlug);
    
    if (!$category) {
        setFlashMessage('Category not found.', 'danger');
        redirect(BASE_URL . '/blog.php');
    }
    
    $posts = $blogModel->getByCategory($category['category_id'], $perPage, $offset);
    $totalPosts = $blogModel->countByCategory($category['category_id']);
} else {
    // Get all posts
    $posts = $blogModel->getAll($perPage, $offset);
    $totalPosts = $blogModel->countAll();
}

// Calculate total pages
$totalPages = ceil($totalPosts / $perPage);

// Page title
$pageTitle = $category ? 'Blog - ' . $category['name'] : 'Blog';

// Include header
include_once __DIR__ . '/includes/header.php';
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-lg-8">
            <h1 class="mb-4">
                <?php echo $category ? 'Category: ' . $category['name'] : 'Blog'; ?>
            </h1>
            
            <?php if (!empty($posts)): ?>
                <?php foreach ($posts as $post): ?>
                    <div class="card mb-4">
                        <div class="card-body">
                            <h2 class="card-title h4">
                                <a href="<?php echo BASE_URL; ?>/blog_post.php?slug=<?php echo $post['slug']; ?>" class="text-decoration-none text-dark">
                                    <?php echo $post['title']; ?>
                                </a>
                            </h2>
                            
                            <div class="post-meta mb-2">
                                <span><i class="fas fa-user me-1"></i> <?php echo $post['username']; ?></span>
                                <span class="ms-3"><i class="fas fa-calendar me-1"></i> <?php echo formatDate($post['published_at']); ?></span>
                                <span class="ms-3"><i class="fas fa-folder me-1"></i> <?php echo $post['category_name']; ?></span>
                            </div>
                            
                            <p class="card-text"><?php echo $post['excerpt']; ?></p>
                            
                            <a href="<?php echo BASE_URL; ?>/blog_post.php?slug=<?php echo $post['slug']; ?>" class="btn btn-sm btn-primary">Read More</a>
                        </div>
                    </div>
                <?php endforeach; ?>
                
                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                    <nav aria-label="Page navigation">
                        <ul class="pagination justify-content-center">
                            <?php if ($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="<?php echo BASE_URL; ?>/blog.php?<?php echo $categorySlug ? 'category=' . $categorySlug . '&' : ''; ?>page=<?php echo $page - 1; ?>" aria-label="Previous">
                                        <span aria-hidden="true">&laquo;</span>
                                    </a>
                                </li>
                            <?php else: ?>
                                <li class="page-item disabled">
                                    <span class="page-link" aria-hidden="true">&laquo;</span>
                                </li>
                            <?php endif; ?>
                            
                            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                    <a class="page-link" href="<?php echo BASE_URL; ?>/blog.php?<?php echo $categorySlug ? 'category=' . $categorySlug . '&' : ''; ?>page=<?php echo $i; ?>">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                            <?php endfor; ?>
                            
                            <?php if ($page < $totalPages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="<?php echo BASE_URL; ?>/blog.php?<?php echo $categorySlug ? 'category=' . $categorySlug . '&' : ''; ?>page=<?php echo $page + 1; ?>" aria-label="Next">
                                        <span aria-hidden="true">&raquo;</span>
                                    </a>
                                </li>
                            <?php else: ?>
                                <li class="page-item disabled">
                                    <span class="page-link" aria-hidden="true">&raquo;</span>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                <?php endif; ?>
            <?php else: ?>
                <div class="alert alert-info">No blog posts found.</div>
            <?php endif; ?>
        </div>
        
        <div class="col-lg-4">
            <!-- Categories -->
            <div class="card mb-4">
                <div class="card-header">
                    <h2 class="h5 mb-0">Categories</h2>
                </div>
                <div class="card-body">
                    <ul class="list-group list-group-flush">
                        <?php foreach ($categories as $cat): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <a href="<?php echo BASE_URL; ?>/blog.php?category=<?php echo $cat['slug']; ?>" class="text-decoration-none <?php echo $categorySlug === $cat['slug'] ? 'fw-bold' : ''; ?>">
                                    <?php echo $cat['name']; ?>
                                </a>
                                <span class="badge bg-primary rounded-pill"><?php echo $cat['post_count']; ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
            
            <!-- Create Post Button (for logged-in users) -->
            <?php if (isLoggedIn()): ?>
                <div class="card mb-4">
                    <div class="card-body">
                        <div class="d-grid">
                            <a href="<?php echo BASE_URL; ?>/blog_post_create.php" class="btn btn-success">
                                <i class="fas fa-plus me-2"></i> Create New Post
                            </a>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- About Blog -->
            <div class="card">
                <div class="card-header">
                    <h2 class="h5 mb-0">About This Blog</h2>
                </div>
                <div class="card-body">
                    <p>Welcome to our blog! Here you'll find articles on various topics. Feel free to browse through the categories and leave comments on posts that interest you.</p>
                    
                    <?php if (!isLoggedIn()): ?>
                        <p>To create your own posts or leave comments, please <a href="<?php echo BASE_URL; ?>/login.php">login</a> or <a href="<?php echo BASE_URL; ?>/register.php">register</a>.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Include footer
include_once __DIR__ . '/includes/footer.php';
?>