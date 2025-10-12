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
$reactionModel = new ForumReaction();

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

// Check if specific post is requested in URL fragment
$requestedPostId = null;
if (isset($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], '#post-') !== false) {
    $parts = explode('#post-', $_SERVER['REQUEST_URI']);
    if (isset($parts[1])) {
        $requestedPostId = intval($parts[1]);
    }
}

// Get posts
$posts = $postModel->getByThreadId($thread['thread_id'], $perPage, $offset);
$totalPosts = $postModel->countByThreadId($thread['thread_id']);

// Calculate total pages
$totalPages = ceil($totalPosts / $perPage);

// If a specific post is requested but not on current page, find its page
if ($requestedPostId !== null) {
    $postExists = false;
    foreach ($posts as $post) {
        if ($post['post_id'] == $requestedPostId) {
            $postExists = true;
            break;
        }
    }
    
    if (!$postExists) {
        // Find which page contains the post
        $postPosition = $postModel->getPostPosition($thread['thread_id'], $requestedPostId);
        if ($postPosition > 0) {
            $correctPage = ceil($postPosition / $perPage);
            if ($correctPage != $page) {
                redirect(BASE_URL . '/forum_thread.php?slug=' . $slug . '&page=' . $correctPage . '#post-' . $requestedPostId);
            }
        }
    }
}

// Process reactions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['react'])) {
    // Check if user is logged in
    if (!isLoggedIn()) {
        setFlashMessage('You must be logged in to react to posts.', 'danger');
        redirect(BASE_URL . '/login.php');
    }
    
    // Verify CSRF token
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        setFlashMessage('Invalid request. Please try again.', 'danger');
        redirect(BASE_URL . '/forum_thread.php?slug=' . $slug);
    }
    
    $postId = (int)($_POST['post_id'] ?? 0);
    $reactionType = $_POST['reaction_type'] ?? '';
    
    if ($postId > 0 && in_array($reactionType, ['like', 'heart'])) {
        $reactionModel->toggleReaction($postId, $_SESSION['user_id'], $reactionType);
    }
    
    // Redirect back to the same page and post
    redirect(BASE_URL . '/forum_thread.php?slug=' . $slug . '&page=' . $page . '#post-' . $postId);
}

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

// Set browser cache for this page
$cacheData = $thread['thread_id'] . '_' . $thread['updated_at'] . '_' . $page;
setDynamicContentCache($cacheData, 300); // 5 minutes cache

// Page title
$pageTitle = $thread['title'];

// Include header
include_once __DIR__ . '/includes/header.php';
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-12">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb bg-light p-3 rounded shadow-sm">
                    <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/forum.php" class="text-decoration-none"><i class="bi bi-house-door me-1"></i>Forum</a></li>
                    <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/forum_subforum.php?slug=<?php echo $subforum['slug']; ?>" class="text-decoration-none"><?php echo htmlspecialchars($subforum['name']); ?></a></li>
                    <li class="breadcrumb-item active fw-bold" aria-current="page"><?php echo htmlspecialchars($thread['title']); ?></li>
                </ol>
            </nav>
        </div>
    </div>
    
    <div class="card mb-4 shadow-sm border-0 rounded-3">
        <div class="card-body p-4">
            <div class="d-flex justify-content-between align-items-center">
                <h1 class="mb-0"><?php echo htmlspecialchars($thread['title']); ?></h1>
                
                <div class="d-flex gap-2">
                    <?php if ($auth->isLoggedIn()): ?>
                        <?php if ($isSubscribed): ?>
                            <form method="post" class="d-inline">
                                <button type="submit" name="unsubscribe" class="btn btn-outline-secondary">
                                    <i class="bi bi-bell-slash me-1"></i> Unsubscribe
                                </button>
                            </form>
                        <?php else: ?>
                            <form method="post" class="d-inline">
                                <button type="submit" name="subscribe" class="btn btn-outline-primary">
                                    <i class="bi bi-bell me-1"></i> Subscribe
                                </button>
                            </form>
                        <?php endif; ?>
                    <?php endif; ?>
                    
                    <?php if (isLoggedIn() && !$thread['is_locked']): ?>
                        <a href="#reply-form" class="btn btn-primary">
                            <i class="bi bi-reply-fill me-1"></i> Reply
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="mt-3 text-muted">
                <small>
                    <i class="bi bi-person me-1"></i> Started by <?php echo htmlspecialchars($thread['username']); ?> 
                    <i class="bi bi-clock ms-3 me-1"></i> <?php echo date('M j, Y g:i A', strtotime($thread['created_at'])); ?>
                    <i class="bi bi-eye ms-3 me-1"></i> <?php echo number_format($thread['view_count']); ?> views
                </small>
            </div>
        </div>
    </div>
    
    <?php if (!empty($thread['featured_image'])): ?>
    <div class="card mb-4">
        <div class="card-body text-center">
            <img src="<?php echo BASE_URL . '/uploads/forum/' . basename($thread['featured_image']); ?>" alt="Thread featured image" class="img-fluid rounded" style="max-height: 500px; width: auto;">
        </div>
    </div>
    <?php endif; ?>
    
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
            <div class="card mb-4 shadow-sm border-0 rounded-3" id="post-<?php echo $post['post_id']; ?>">
                <div class="card-header d-flex justify-content-between align-items-center bg-light py-3">
                    <div class="d-flex align-items-center">
                        <a href="<?php echo BASE_URL; ?>/profile.php?username=<?php echo $post['username']; ?>" class="text-decoration-none fw-bold me-2">
                            <?php echo $post['username']; ?>
                        </a>
                        <span class="badge bg-secondary rounded-pill ms-2">#<?php echo ($page - 1) * $perPage + $index + 1; ?></span>
                        <span class="text-muted ms-3"><i class="bi bi-clock me-1"></i><?php echo formatDate($post['created_at']); ?></span>
                    </div>
                    <div>
                        <a href="<?php echo BASE_URL; ?>/forum_thread.php?slug=<?php echo $slug; ?>&page=<?php echo $page; ?>#post-<?php echo $post['post_id']; ?>" class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-link-45deg"></i> Link
                        </a>
                    </div>
                </div>
                <div class="card-body p-4">
                    <div class="row">
                        <div class="col-md-2 text-center mb-3 mb-md-0">
                            <img src="<?php echo !empty($post['avatar']) ? BASE_URL . '/' . $post['avatar'] : BASE_URL . '/assets/default-avatar.svg'; ?>" alt="<?php echo $post['username']; ?>" class="avatar-lg rounded-circle mb-3" style="width: 80px; height: 80px; object-fit: cover;">
                            
                            <div class="badge bg-primary mb-2">
                                Posts: <?php echo $postModel->countByUserId($post['user_id']); ?>
                            </div>
                        </div>
                        <div class="col-md-10">
                            <div class="post-content">
                                <?php 
                                // Fix image paths and ensure HTML renders correctly
                                $content = $post['content'];
                                // First decode any HTML entities
                                $content = html_entity_decode($content);
                                // Then ensure all image paths are absolute
                                $content = preg_replace('/(src=["\']((?!http|https|ftp|\/\/)[^"\']+)["\'])/i', 'src="' . BASE_URL . '/$2"', $content);
                                echo $content; 
                                ?>
                            </div>
                            
                            <!-- Post Reactions -->
                            <div class="post-reactions mt-3 pt-3 border-top">
                                <?php 
                                // Get reactions for this post
                                $postReactions = $reactionModel->getReactionsByPostId($post['post_id']);
                                
                                // Get user's reactions if logged in
                                $userReactions = [];
                                if (isLoggedIn()) {
                                    $userReactions = $reactionModel->getUserReactions($post['post_id'], $_SESSION['user_id']);
                                }
                                
                                // Reaction types and their icons
                                $reactionTypes = [
                                    'like' => ['icon' => 'bi-hand-thumbs-up', 'label' => 'Like'],
                                    'heart' => ['icon' => 'bi-heart', 'label' => 'Heart']
                                ];
                                ?>
                                
                                <div class="d-flex align-items-center">
                                    <?php if (isLoggedIn()): ?>
                                        <?php foreach ($reactionTypes as $type => $reaction): ?>
                                            <form method="post" class="me-3">
                                                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                                <input type="hidden" name="post_id" value="<?php echo $post['post_id']; ?>">
                                                <input type="hidden" name="reaction_type" value="<?php echo $type; ?>">
                                                
                                                <button type="submit" name="react" class="btn btn-sm <?php echo in_array($type, $userReactions) ? 'btn-primary' : 'btn-outline-primary'; ?>">
                                                    <i class="bi <?php echo $reaction['icon']; ?> me-1"></i>
                                                    <?php echo $reaction['label']; ?>
                                                    <?php if (isset($postReactions[$type]) && $postReactions[$type] > 0): ?>
                                                        <span class="badge bg-light text-dark ms-1"><?php echo $postReactions[$type]; ?></span>
                                                    <?php endif; ?>
                                                </button>
                                            </form>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <?php foreach ($reactionTypes as $type => $reaction): ?>
                                            <div class="me-3">
                                                <button class="btn btn-sm btn-outline-secondary" disabled>
                                                    <i class="bi <?php echo $reaction['icon']; ?> me-1"></i>
                                                    <?php echo $reaction['label']; ?>
                                                    <?php if (isset($postReactions[$type]) && $postReactions[$type] > 0): ?>
                                                        <span class="badge bg-light text-dark ms-1"><?php echo $postReactions[$type]; ?></span>
                                                    <?php endif; ?>
                                                </button>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
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
        <div class="card mt-5 shadow-sm border-0 rounded-3" id="reply-form">
            <div class="card-header bg-light py-3">
                <h5 class="mb-0"><i class="bi bi-chat-dots me-2"></i>Post Reply</h5>
            </div>
            <div class="card-body p-4">
                <form action="<?php echo BASE_URL; ?>/forum_thread.php?slug=<?php echo $slug; ?>" method="post">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    <input type="hidden" name="add_reply" value="1">
                    
                    <div class="mb-3">
                        <label for="reply-editor" class="form-label fw-bold">Your Reply</label>
                        <textarea class="form-control" id="reply-editor" name="content" rows="6" required
                            style="width: 100%; min-height: 200px; padding: 15px; font-size: 16px; border: 1px solid #ced4da; 
                            border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); 
                            font-family: system-ui, -apple-system, sans-serif;"></textarea>
                        <div class="form-text text-muted">Format your reply with clear paragraphs for better readability</div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary btn-lg px-4">
                        <i class="bi bi-send me-2"></i>Submit Reply
                    </button>
                    
                    <script>
                    // Auto-resize textarea based on content
                    document.addEventListener('DOMContentLoaded', function() {
                        const replyEditor = document.getElementById('reply-editor');
                        if (replyEditor) {
                            // Auto-resize functionality
                            replyEditor.addEventListener('input', function() {
                                this.style.height = 'auto';
                                this.style.height = (this.scrollHeight + 10) + 'px';
                            });
                            
                            // Focus the textarea when reply form is visible
                            document.querySelector('#reply-form button').addEventListener('click', function() {
                                replyEditor.focus();
                            });
                        }
                    });
                    </script>
                </form>
            </div>
        </div>
    <?php elseif (!isLoggedIn()): ?>
        <div class="alert alert-warning mt-4 p-3 rounded-3 shadow-sm">
            Please <a href="<?php echo BASE_URL; ?>/login.php" class="fw-bold">login</a> to reply to this thread.
        </div>
    <?php endif; ?>
    
    <!-- Thread Actions -->
    <?php if (isAdmin() || (isset($_SESSION['user_id']) && $thread['user_id'] === $_SESSION['user_id'] && !$thread['is_locked'])): ?>
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