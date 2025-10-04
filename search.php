<?php
/**
 * Search page
 * 
 * This page handles search functionality
 */

// Include initialization file
require_once __DIR__ . '/includes/init.php';

// Get search query
$query = trim($_GET['q'] ?? '');

// Initialize models
$blogModel = new BlogPost();
$threadModel = new ForumThread();

$blogResults = [];
$forumResults = [];

if (!empty($query)) {
    // Search blog posts
    $blogResults = $blogModel->search($query, 10);
    
    // Search forum threads
    $forumResults = $threadModel->search($query, 10);
}

// Page title
$pageTitle = 'Search Results';

// Include header
include_once __DIR__ . '/includes/header.php';
?>

<div class="container mt-4">
    <h1 class="mb-4">Search Results</h1>
    
    <div class="card mb-4">
        <div class="card-body">
            <form action="<?php echo BASE_URL; ?>/search.php" method="get">
                <div class="input-group">
                    <input type="text" class="form-control" name="q" value="<?php echo sanitize($query); ?>" placeholder="Search for blog posts and forum threads..." aria-label="Search" required>
                    <button class="btn btn-primary" type="submit">Search</button>
                </div>
            </form>
        </div>
    </div>
    
    <?php if (!empty($query)): ?>
        <div class="row">
            <div class="col-md-6">
                <div class="card mb-4">
                    <div class="card-header">
                        <h2 class="h5 mb-0">Blog Posts (<?php echo count($blogResults); ?>)</h2>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($blogResults)): ?>
                            <ul class="list-group list-group-flush">
                                <?php foreach ($blogResults as $post): ?>
                                    <li class="list-group-item">
                                        <a href="<?php echo BASE_URL; ?>/blog_post.php?slug=<?php echo $post['slug']; ?>" class="text-decoration-none">
                                            <?php echo $post['title']; ?>
                                        </a>
                                        <div class="small text-muted mt-1">
                                            <i class="fas fa-user me-1"></i> <?php echo $post['username']; ?>
                                            <i class="fas fa-calendar ms-2 me-1"></i> <?php echo formatDate($post['published_at']); ?>
                                        </div>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php else: ?>
                            <div class="alert alert-info mb-0">No blog posts found matching your search.</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card mb-4">
                    <div class="card-header">
                        <h2 class="h5 mb-0">Forum Threads (<?php echo count($forumResults); ?>)</h2>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($forumResults)): ?>
                            <ul class="list-group list-group-flush">
                                <?php foreach ($forumResults as $thread): ?>
                                    <li class="list-group-item">
                                        <a href="<?php echo BASE_URL; ?>/forum_thread.php?slug=<?php echo $thread['slug']; ?>" class="text-decoration-none">
                                            <?php echo $thread['title']; ?>
                                        </a>
                                        <div class="small text-muted mt-1">
                                            <i class="fas fa-user me-1"></i> <?php echo $thread['username']; ?>
                                            <i class="fas fa-comments ms-2 me-1"></i> <?php echo $thread['subforum_name']; ?>
                                        </div>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php else: ?>
                            <div class="alert alert-info mb-0">No forum threads found matching your search.</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="alert alert-info">Please enter a search term.</div>
    <?php endif; ?>
</div>

<?php
// Include footer
include_once __DIR__ . '/includes/footer.php';
?>