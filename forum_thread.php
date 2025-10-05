<?php
/**
 * Forum thread page
 * 
 * This page displays a thread and its posts
 */

// Include initialization file
require_once __DIR__ . '/includes/init.php';

// Get thread slug
$slug = $_GET['slug'] ?? '';

if (empty($slug)) {
    setFlashMessage('Thread not found.', 'danger');
    redirect(BASE_URL . '/forum.php');
}

// Initialize models
$threadModel = new ForumThread();
$postModel = new ForumPost();
$subforumModel = new ForumSubforum();

// Get thread
$thread = $threadModel->getBySlug($slug);

if (!$thread) {
    setFlashMessage('Thread not found.', 'danger');
    redirect(BASE_URL . '/forum.php');
}

// Get subforum
$subforum = $subforumModel->getById($thread['subforum_id']);

// Increment view count
$threadModel->incrementViewCount($thread['thread_id']);

// Check if user is subscribed
$isSubscribed = false;
$auth = new Auth();
if ($auth->isLoggedIn()) {
    $userId = $auth->getUserId();
    $isSubscribed = $threadModel->isUserSubscribed($userId, $thread['thread_id']);
    
    // Handle subscription actions
    if (isset($_POST['subscribe'])) {
        $threadModel->subscribeUser($userId, $thread['thread_id']);
        setFlashMessage('success', 'You have subscribed to this thread.');
        redirect(BASE_URL . '/forum_thread.php?slug=' . $slug);
    }
    
    if (isset($_POST['unsubscribe'])) {
        $threadModel->unsubscribeUser($userId, $thread['thread_id']);
        setFlashMessage('success', 'You have unsubscribed from this thread.');
        redirect(BASE_URL . '/forum_thread.php?slug=' . $slug);
    }
}

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 10;
$offset = ($page - 1) * $perPage;

// Get posts
$posts = $postModel->getByThreadId($thread['thread_id'], $perPage, $offset);
$totalPosts = $postModel->countByThreadId($thread['thread_id']);

// Calculate total pages
$totalPages = ceil($totalPosts / $perPage);

// Process reply form
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_reply'])) {
    // Check if user is logged in
    if (!isLoggedIn()) {
        setFlashMessage('You must be logged in to reply.', 'danger');
        redirect(BASE_URL . '/login.php');
    }
    
    // Check if thread is locked
    if ($thread['is_locked']) {
        setFlashMessage('This thread is locked. You cannot reply.', 'danger');
        redirect(BASE_URL . '/forum_thread.php?slug=' . $slug);
    }
    
    // Verify CSRF token
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        setFlashMessage('Invalid request. Please try again.', 'danger');
        redirect(BASE_URL . '/forum_thread.php?slug=' . $slug);
    }
    
    $content = trim($_POST['content'] ?? '');
    
    if (empty($content)) {
        setFlashMessage('Reply cannot be empty.', 'danger');
    } else {
        // Add reply
        $replyData = [
            'thread_id' => $thread['thread_id'],
            'user_id' => $_SESSION['user_id'],
            'content' => $content
        ];
        
        if ($postModel->create($replyData)) {
            setFlashMessage('Reply added successfully.', 'success');
            
            // Redirect to the last page
            $newTotalPosts = $postModel->countByThreadId($thread['thread_id']);
            $newTotalPages = ceil($newTotalPosts / $perPage);
            
            redirect(BASE_URL . '/forum_thread.php?slug=' . $slug . '&page=' . $newTotalPages);
        } else {
            setFlashMessage('Failed to add reply. Please try again.', 'danger');
        }
    }
}

// Page title
$pageTitle = $thread['title'];

// Include header
include_once __DIR__ . '/includes/header.php';
?>

<div class="container mt-4">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/forum.php">Forum</a></li>
            <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/forum_subforum.php?slug=<?php echo $subforum['slug']; ?>"><?php echo $subforum['name']; ?></a></li>
            <li class="breadcrumb-item active" aria-current="page"><?php echo $thread['title']; ?></li>
        </ol>
    </nav>
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><?php echo $thread['title']; ?></h1>
        
        <div>
            <?php if ($auth->isLoggedIn()): ?>
                <?php if ($isSubscribed): ?>
                    <form method="post" class="d-inline me-2">
                        <button type="submit" name="unsubscribe" class="btn btn-outline-secondary">
                            <i class="fas fa-bell-slash"></i> Unsubscribe
                        </button>
                    </form>
                <?php else: ?>
                    <form method="post" class="d-inline me-2">
                        <button type="submit" name="subscribe" class="btn btn-outline-primary">
                            <i class="fas fa-bell"></i> Subscribe
                        </button>
                    </form>
                <?php endif; ?>
            <?php endif; ?>
            
            <?php if (isLoggedIn() && !$thread['is_locked']): ?>
                <a href="#reply-form" class="btn btn-primary">
                    <i class="fas fa-reply me-1"></i> Reply
                </a>
            <?php endif; ?>
        </div>
    </div>
    
    <?php if ($thread['is_locked']): ?>
        <div class="alert alert-warning mb-4">
            <i class="fas fa-lock me-2"></i> This thread is locked. You cannot reply.
        </div>
    <?php endif; ?>
    
    <!-- Pagination (Top) -->
    <?php if ($totalPages > 1): ?>
        <nav aria-label="Page navigation" class="mb-4">
            <ul class="pagination justify-content-center">
                <?php if ($page > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="<?php echo BASE_URL; ?>/forum_thread.php?slug=<?php echo $slug; ?>&page=<?php echo $page - 1; ?>" aria-label="Previous">
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
                        <a class="page-link" href="<?php echo BASE_URL; ?>/forum_thread.php?slug=<?php echo $slug; ?>&page=<?php echo $i; ?>">
                            <?php echo $i; ?>
                        </a>
                    </li>
                <?php endfor; ?>
                
                <?php if ($page < $totalPages): ?>
                    <li class="page-item">
                        <a class="page-link" href="<?php echo BASE_URL; ?>/forum_thread.php?slug=<?php echo $slug; ?>&page=<?php echo $page + 1; ?>" aria-label="Next">
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
    
    <!-- Posts -->
    <?php if (!empty($posts)): ?>
        <?php foreach ($posts as $index => $post): ?>
            <div class="card mb-4" id="post-<?php echo $post['post_id']; ?>">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <a href="<?php echo BASE_URL; ?>/profile.php?username=<?php echo $post['username']; ?>" class="text-decoration-none fw-bold">
                            <?php echo $post['username']; ?>
                        </a>
                        <span class="text-muted ms-2"><?php echo formatDate($post['created_at']); ?></span>
                    </div>
                    <div>
                        <a href="<?php echo BASE_URL; ?>/forum_thread.php?slug=<?php echo $slug; ?>&page=<?php echo $page; ?>#post-<?php echo $post['post_id']; ?>" class="text-decoration-none text-muted">
                            #<?php echo ($page - 1) * $perPage + $index + 1; ?>
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-2 text-center mb-3 mb-md-0">
                            <img src="<?php echo !empty($post['avatar']) ? BASE_URL . '/' . $post['avatar'] : BASE_URL . '/assets/default-avatar.svg'; ?>" alt="<?php echo $post['username']; ?>" class="avatar-lg mb-2">
                            
                            <div class="small text-muted">
                                Posts: <?php echo $postModel->countByUserId($post['user_id']); ?>
                            </div>
                        </div>
                        <div class="col-md-10">
                            <div class="post-content">
                                <?php echo nl2br(sanitize($post['content'])); ?>
                            </div>
                            
                            <?php if ($post['created_at'] !== $post['updated_at']): ?>
                                <div class="small text-muted mt-3">
                                    <em>Last edited: <?php echo formatDate($post['updated_at']); ?></em>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="card-footer d-flex justify-content-end">
                    <?php if (isLoggedIn() && ($post['user_id'] === $_SESSION['user_id'] || isAdmin())): ?>
                        <a href="<?php echo BASE_URL; ?>/forum_post_edit.php?id=<?php echo $post['post_id']; ?>" class="btn btn-sm btn-warning me-2">
                            <i class="fas fa-edit me-1"></i> Edit
                        </a>
                        
                        <?php if (isAdmin() || ($post['user_id'] === $_SESSION['user_id'] && strtotime($post['created_at']) > strtotime('-15 minutes'))): ?>
                            <form action="<?php echo BASE_URL; ?>/forum_post_delete.php" method="post" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this post?');">
                                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                <input type="hidden" name="post_id" value="<?php echo $post['post_id']; ?>">
                                <button type="submit" class="btn btn-sm btn-danger">
                                    <i class="fas fa-trash me-1"></i> Delete
                                </button>
                            </form>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="alert alert-info mb-4">No posts found in this thread.</div>
    <?php endif; ?>
    
    <!-- Pagination (Bottom) -->
    <?php if ($totalPages > 1): ?>
        <nav aria-label="Page navigation" class="mb-4">
            <ul class="pagination justify-content-center">
                <?php if ($page > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="<?php echo BASE_URL; ?>/forum_thread.php?slug=<?php echo $slug; ?>&page=<?php echo $page - 1; ?>" aria-label="Previous">
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
                        <a class="page-link" href="<?php echo BASE_URL; ?>/forum_thread.php?slug=<?php echo $slug; ?>&page=<?php echo $i; ?>">
                            <?php echo $i; ?>
                        </a>
                    </li>
                <?php endfor; ?>
                
                <?php if ($page < $totalPages): ?>
                    <li class="page-item">
                        <a class="page-link" href="<?php echo BASE_URL; ?>/forum_thread.php?slug=<?php echo $slug; ?>&page=<?php echo $page + 1; ?>" aria-label="Next">
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
    
    <!-- Reply Form -->
    <?php if (isLoggedIn() && !$thread['is_locked']): ?>
        <div class="card mt-4" id="reply-form">
            <div class="card-header">
                <h2 class="h5 mb-0">Post Reply</h2>
            </div>
            <div class="card-body">
                <form action="<?php echo BASE_URL; ?>/forum_thread.php?slug=<?php echo $slug; ?>" method="post">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    <input type="hidden" name="add_reply" value="1">
                    
                    <div class="mb-3">
                        <textarea class="form-control" name="content" rows="5" required></textarea>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-reply me-1"></i> Post Reply
                    </button>
                </form>
            </div>
        </div>
    <?php elseif (!isLoggedIn()): ?>
        <div class="alert alert-warning mt-4">
            Please <a href="<?php echo BASE_URL; ?>/login.php">login</a> to reply to this thread.
        </div>
    <?php endif; ?>
    
    <!-- Thread Actions -->
    <?php if (isAdmin() || ($thread['user_id'] === $_SESSION['user_id'] && !$thread['is_locked'])): ?>
        <div class="card mt-4">
            <div class="card-header">
                <h2 class="h5 mb-0">Thread Actions</h2>
            </div>
            <div class="card-body">
                <div class="d-flex flex-wrap gap-2">
                    <a href="<?php echo BASE_URL; ?>/forum_thread_edit.php?id=<?php echo $thread['thread_id']; ?>" class="btn btn-warning">
                        <i class="fas fa-edit me-1"></i> Edit Thread
                    </a>
                    
                    <?php if (isAdmin()): ?>
                        <form action="<?php echo BASE_URL; ?>/forum_thread_toggle_sticky.php" method="post" class="d-inline">
                            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                            <input type="hidden" name="thread_id" value="<?php echo $thread['thread_id']; ?>">
                            <button type="submit" class="btn btn-secondary">
                                <i class="fas <?php echo $thread['is_sticky'] ? 'fa-thumbtack fa-rotate-270' : 'fa-thumbtack'; ?> me-1"></i>
                                <?php echo $thread['is_sticky'] ? 'Unsticky Thread' : 'Sticky Thread'; ?>
                            </button>
                        </form>
                        
                        <form action="<?php echo BASE_URL; ?>/forum_thread_toggle_lock.php" method="post" class="d-inline">
                            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                            <input type="hidden" name="thread_id" value="<?php echo $thread['thread_id']; ?>">
                            <button type="submit" class="btn btn-secondary">
                                <i class="fas <?php echo $thread['is_locked'] ? 'fa-unlock' : 'fa-lock'; ?> me-1"></i>
                                <?php echo $thread['is_locked'] ? 'Unlock Thread' : 'Lock Thread'; ?>
                            </button>
                        </form>
                        
                        <form action="<?php echo BASE_URL; ?>/forum_thread_delete.php" method="post" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this thread? This action cannot be undone.');">
                            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                            <input type="hidden" name="thread_id" value="<?php echo $thread['thread_id']; ?>">
                            <button type="submit" class="btn btn-danger">
                                <i class="fas fa-trash me-1"></i> Delete Thread
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>
    
    <div class="mt-4">
        <a href="<?php echo BASE_URL; ?>/forum_subforum.php?slug=<?php echo $subforum['slug']; ?>" class="btn btn-outline-primary">
            <i class="fas fa-arrow-left me-1"></i> Back to <?php echo $subforum['name']; ?>
        </a>
    </div>
</div>

<?php
// Include footer
include_once __DIR__ . '/includes/footer.php';
?>