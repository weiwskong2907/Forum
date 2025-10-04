<?php
/**
 * Blog post page
 * 
 * This page displays a single blog post and its comments
 */

// Include initialization file
require_once __DIR__ . '/includes/init.php';

// Get post slug
$slug = $_GET['slug'] ?? '';

if (empty($slug)) {
    setFlashMessage('Post not found.', 'danger');
    redirect(BASE_URL . '/blog.php');
}

// Initialize models
$blogModel = new BlogPost();
$commentModel = new BlogComment();
$categoryModel = new BlogCategory();

// Get post
$post = $blogModel->getBySlug($slug);

if (!$post) {
    setFlashMessage('Post not found.', 'danger');
    redirect(BASE_URL . '/blog.php');
}

// Get comments
$comments = $commentModel->getByPostId($post['post_id']);

// Process comment form
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_comment'])) {
    // Check if user is logged in
    if (!isLoggedIn()) {
        setFlashMessage('You must be logged in to comment.', 'danger');
        redirect(BASE_URL . '/login.php');
    }
    
    // Verify CSRF token
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        setFlashMessage('Invalid request. Please try again.', 'danger');
        redirect(BASE_URL . '/blog_post.php?slug=' . $slug);
    }
    
    $content = trim($_POST['content'] ?? '');
    
    if (empty($content)) {
        setFlashMessage('Comment cannot be empty.', 'danger');
    } else {
        // Add comment
        $commentData = [
            'post_id' => $post['post_id'],
            'user_id' => $_SESSION['user_id'],
            'content' => $content
        ];
        
        if ($commentModel->create($commentData)) {
            setFlashMessage('Comment added successfully.', 'success');
            redirect(BASE_URL . '/blog_post.php?slug=' . $slug);
        } else {
            setFlashMessage('Failed to add comment. Please try again.', 'danger');
        }
    }
}

// Get categories for sidebar
$categories = $categoryModel->getAll();

// Page title
$pageTitle = $post['title'];

// Include header
include_once __DIR__ . '/includes/header.php';
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-lg-8">
            <article class="blog-post">
                <h1 class="mb-3"><?php echo $post['title']; ?></h1>
                
                <div class="post-meta mb-4">
                    <span><i class="fas fa-user me-1"></i> <?php echo $post['username']; ?></span>
                    <span class="ms-3"><i class="fas fa-calendar me-1"></i> <?php echo formatDate($post['published_at']); ?></span>
                    <span class="ms-3"><i class="fas fa-folder me-1"></i> <?php echo $post['category_name']; ?></span>
                </div>
                
                <?php if (!empty($post['featured_image'])): ?>
                <div class="post-image mb-4">
                    <img src="<?php echo BASE_URL . '/' . $post['featured_image']; ?>" class="img-fluid" alt="<?php echo $post['title']; ?>">
                </div>
                <?php endif; ?>
                
                <div class="post-content mb-4">
                    <?php echo markdownToHtml($post['content']); ?>
                </div>
            </article>
            
            <div class="card mt-5">
                <div class="card-header">
                    <h2 class="h5 mb-0">Comments (<?php echo count($comments); ?>)</h2>
                </div>
                <div class="card-body">
                    <?php if (!empty($comments)): ?>
                        <?php foreach ($comments as $comment): ?>
                            <div class="comment mb-4">
                                <div class="d-flex">
                                    <img src="<?php echo !empty($comment['avatar']) ? BASE_URL . '/' . $comment['avatar'] : 'https://via.placeholder.com/40'; ?>" alt="<?php echo $comment['username']; ?>" class="avatar me-3">
                                    
                                    <div>
                                        <div class="fw-bold"><?php echo $comment['username']; ?></div>
                                        <div class="text-muted small mb-2"><?php echo formatDate($comment['created_at']); ?></div>
                                        <div><?php echo nl2br(sanitize($comment['content'])); ?></div>
                                    </div>
                                </div>
                            </div>
                            
                            <?php if (!$comment === end($comments)): ?>
                                <hr>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="alert alert-info mb-0">No comments yet. Be the first to comment!</div>
                    <?php endif; ?>
                    
                    <?php if (isLoggedIn()): ?>
                        <div class="mt-4">
                            <h3 class="h5">Add a Comment</h3>
                            
                            <form action="<?php echo BASE_URL; ?>/blog_post.php?slug=<?php echo $slug; ?>" method="post">
                                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                <input type="hidden" name="add_comment" value="1">
                                
                                <div class="mb-3">
                                    <textarea class="form-control" name="content" rows="4" required></textarea>
                                </div>
                                
                                <button type="submit" class="btn btn-primary">Submit Comment</button>
                            </form>
                        </div>
                    <?php else: ?>
                        <div class="mt-4 alert alert-info">
                            Please <a href="<?php echo BASE_URL; ?>/login.php">login</a> to leave a comment.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4">
            <!-- Categories -->
            <div class="card mb-4">
                <div class="card-header">
                    <h2 class="h5 mb-0">Categories</h2>
                </div>
                <div class="card-body">
                    <ul class="list-group list-group-flush">
                        <?php foreach ($categories as $category): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <a href="<?php echo BASE_URL; ?>/blog.php?category=<?php echo $category['slug']; ?>" class="text-decoration-none <?php echo $post['category_id'] === $category['category_id'] ? 'fw-bold' : ''; ?>">
                                    <?php echo $category['name']; ?>
                                </a>
                                <span class="badge bg-primary rounded-pill"><?php echo $category['post_count']; ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
            
            <!-- Post Actions -->
            <?php if (isLoggedIn() && ($post['user_id'] === $_SESSION['user_id'] || isAdmin())): ?>
                <div class="card mb-4">
                    <div class="card-header">
                        <h2 class="h5 mb-0">Post Actions</h2>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <a href="<?php echo BASE_URL; ?>/blog_post_edit.php?id=<?php echo $post['post_id']; ?>" class="btn btn-warning">
                                <i class="fas fa-edit me-2"></i> Edit Post
                            </a>
                            
                            <?php if (isAdmin()): ?>
                                <a href="<?php echo BASE_URL; ?>/admin/blog_posts.php" class="btn btn-secondary">
                                    <i class="fas fa-cog me-2"></i> Manage Posts
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- Back to Blog -->
            <div class="card">
                <div class="card-body">
                    <div class="d-grid">
                        <a href="<?php echo BASE_URL; ?>/blog.php" class="btn btn-outline-primary">
                            <i class="fas fa-arrow-left me-2"></i> Back to Blog
                        </a>
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