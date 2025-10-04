<?php
/**
 * Forum Search page
 * 
 * This page handles forum-specific search functionality
 */

// Include initialization file
require_once __DIR__ . '/includes/init.php';

// Get search query
$query = trim($_GET['q'] ?? '');

// Initialize models
$threadModel = new ForumThread();
$postModel = new ForumPost();

$threadResults = [];
$postResults = [];

if (!empty($query)) {
    // Search forum threads
    $threadResults = $threadModel->search($query, 20);
    
    // Search forum posts
    $postResults = $postModel->search($query, 20);
}

// Page title
$pageTitle = 'Forum Search Results';

// Include header
include_once __DIR__ . '/includes/header.php';
?>

<div class="container mt-4">
    <h1 class="mb-4">Forum Search Results</h1>
    
    <div class="card mb-4">
        <div class="card-body">
            <form action="<?php echo BASE_URL; ?>/forum_search.php" method="get">
                <div class="input-group">
                    <input type="text" class="form-control" name="q" value="<?php echo sanitize($query); ?>" placeholder="Search forum threads and posts..." aria-label="Search" required>
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
                        <h2 class="h5 mb-0">Forum Threads (<?php echo count($threadResults); ?>)</h2>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($threadResults)): ?>
                            <ul class="list-group list-group-flush">
                                <?php foreach ($threadResults as $thread): ?>
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
            
            <div class="col-md-6">
                <div class="card mb-4">
                    <div class="card-header">
                        <h2 class="h5 mb-0">Forum Posts (<?php echo count($postResults); ?>)</h2>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($postResults)): ?>
                            <ul class="list-group list-group-flush">
                                <?php foreach ($postResults as $post): ?>
                                    <li class="list-group-item">
                                        <a href="<?php echo BASE_URL; ?>/forum_thread.php?slug=<?php echo $post['thread_slug']; ?>#post-<?php echo $post['post_id']; ?>" class="text-decoration-none">
                                            <?php echo substr($post['content'], 0, 100); ?><?php echo (strlen($post['content']) > 100) ? '...' : ''; ?>
                                        </a>
                                        <div class="small text-muted mt-1">
                                            <i class="fas fa-user me-1"></i> <?php echo $post['username']; ?>
                                            <i class="fas fa-calendar ms-2 me-1"></i> <?php echo formatDate($post['created_at']); ?>
                                        </div>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php else: ?>
                            <div class="alert alert-info mb-0">No forum posts found matching your search.</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="text-center mt-3 mb-4">
            <a href="<?php echo BASE_URL; ?>/search.php?q=<?php echo urlencode($query); ?>" class="btn btn-outline-secondary">
                Search All Content
            </a>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>