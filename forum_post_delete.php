<?php
/**
 * Forum post delete page
 * 
 * This page handles the deletion of forum posts
 */

// Include initialization file
require_once __DIR__ . '/includes/init.php';

// Check if user is logged in
if (!isLoggedIn()) {
    setFlashMessage('You must be logged in to delete a post.', 'danger');
    redirect(BASE_URL . '/login.php');
}

// Get post ID (from either GET or POST)
$postId = isset($_POST['post_id']) ? (int)$_POST['post_id'] : (isset($_GET['id']) ? (int)$_GET['id'] : 0);

if (empty($postId)) {
    error_log("Post ID not found in request");
    setFlashMessage('Post not found.', 'danger');
    redirect(BASE_URL . '/forum.php');
}

// Initialize models
$postModel = new ForumPost();
$threadModel = new ForumThread();

// Get post
$post = $postModel->getById($postId);

if (!$post) {
    setFlashMessage('Post not found.', 'danger');
    redirect(BASE_URL . '/forum.php');
}

// Get thread
$thread = $threadModel->getById($post['thread_id']);

if (!$thread) {
    setFlashMessage('Thread not found.', 'danger');
    redirect(BASE_URL . '/forum.php');
}

// Verify CSRF token if it's a POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        error_log("CSRF token verification failed");
        setFlashMessage('Invalid request. Please try again.', 'danger');
        redirect(BASE_URL . '/forum_thread.php?slug=' . $thread['slug']);
    }
}

// Check if user is authorized to delete the post
$isAuthorized = false;

// Post owner can delete their own post
if ($post['user_id'] == $_SESSION['user_id']) {
    $isAuthorized = true;
    error_log("User {$_SESSION['user_id']} authorized to delete their own post {$postId}");
}

// Admin can delete any post
if (isAdmin()) {
    $isAuthorized = true;
    error_log("Admin user {$_SESSION['user_id']} authorized to delete post {$postId}");
}

if (!$isAuthorized) {
    error_log("User {$_SESSION['user_id']} not authorized to delete post {$postId}");
    setFlashMessage('You are not authorized to delete this post.', 'danger');
    redirect(BASE_URL . '/forum_thread.php?slug=' . $thread['slug']);
}

// Check if it's the first post in the thread (thread starter)
$isFirstPost = ($post['post_id'] == $thread['first_post_id']);

// Process deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!Security::verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        setFlashMessage('Invalid request. Please try again.', 'danger');
        redirect(BASE_URL . '/forum_thread.php?slug=' . $thread['slug']);
    }
    
    // If it's the first post, delete the entire thread
    if ($isFirstPost) {
        try {
            // Log deletion attempt
            error_log("Attempting to delete thread ID: {$thread['thread_id']}");
            
            // Get database instance
            $db = Database::getInstance();
            
            // Begin transaction
            $db->beginTransaction();
            
            // Delete all posts in the thread first
            $deletePostsQuery = "DELETE FROM forum_posts WHERE thread_id = ?";
            $postsStmt = $db->query($deletePostsQuery, [$thread['thread_id']]);
            
            // Delete the thread
            $deleteThreadQuery = "DELETE FROM forum_threads WHERE thread_id = ?";
            $threadStmt = $db->query($deleteThreadQuery, [$thread['thread_id']]);
            
            // Check if thread was deleted
            $success = ($threadStmt && $threadStmt->affected_rows > 0);
            
            if ($success) {
                // Commit transaction
                $db->commit();
                setFlashMessage('Thread deleted successfully.', 'success');
                redirect(BASE_URL . '/forum_subforum.php?slug=' . $thread['subforum_slug']);
            } else {
                // Rollback transaction
                $db->rollback();
                error_log("Failed to delete thread ID: {$thread['thread_id']} - Database operation returned false");
                setFlashMessage('Failed to delete thread. Please try again.', 'danger');
                redirect(BASE_URL . '/forum_thread.php?slug=' . $thread['slug']);
            }
        } catch (Exception $e) {
            // Rollback transaction
            if (isset($db)) {
                $db->rollback();
            }
            error_log("Exception when deleting thread ID: {$thread['thread_id']} - " . $e->getMessage());
            setFlashMessage('An error occurred while deleting the thread. Please try again.', 'danger');
            redirect(BASE_URL . '/forum_thread.php?slug=' . $thread['slug']);
        }
    } else {
        // Delete the post
        try {
            // Log deletion attempt
            error_log("Attempting to delete post ID: $postId from thread ID: {$thread['thread_id']}");
            
            // Get database instance
            $db = Database::getInstance();
            
            // Use direct database query instead of model method
            $deleteQuery = "DELETE FROM forum_posts WHERE post_id = ?";
            $stmt = $db->query($deleteQuery, [$postId]);
            $success = ($stmt && $stmt->affected_rows > 0);
            
            if ($success) {
                // Update thread's last post information manually
                $lastPostQuery = "SELECT p.post_id, p.created_at, p.user_id 
                                 FROM forum_posts p 
                                 WHERE p.thread_id = ? 
                                 ORDER BY p.created_at DESC 
                                 LIMIT 1";
                
                $lastPost = $db->fetchRow($lastPostQuery, [$thread['thread_id']]);
                
                if ($lastPost) {
                    // Update thread with last post information
                    $updateQuery = "UPDATE forum_threads 
                                   SET last_post_id = ?, last_post_at = ?, last_post_user_id = ? 
                                   WHERE thread_id = ?";
                    $db->query($updateQuery, [
                        $lastPost['post_id'],
                        $lastPost['created_at'],
                        $lastPost['user_id'],
                        $thread['thread_id']
                    ]);
                }
                
                setFlashMessage('Post deleted successfully.', 'success');
                redirect(BASE_URL . '/forum_thread.php?slug=' . $thread['slug']);
            } else {
                error_log("Failed to delete post ID: $postId - Database operation returned false");
                setFlashMessage('Failed to delete post. Please try again.', 'danger');
                redirect(BASE_URL . '/forum_thread.php?slug=' . $thread['slug'] . '#post-' . $postId);
            }
        } catch (Exception $e) {
            error_log("Exception when deleting post ID: $postId - " . $e->getMessage());
            setFlashMessage('An error occurred while deleting the post. Please try again.', 'danger');
            redirect(BASE_URL . '/forum_thread.php?slug=' . $thread['slug'] . '#post-' . $postId);
        }
    }
}

// Page title
$pageTitle = 'Delete Post';

// Include header
include_once __DIR__ . '/includes/header.php';
?>

<div class="container mt-4">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/forum.php">Forum</a></li>
            <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/forum_subforum.php?slug=<?php echo $thread['subforum_slug']; ?>"><?php echo $thread['subforum_name']; ?></a></li>
            <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/forum_thread.php?slug=<?php echo $thread['slug']; ?>"><?php echo $thread['title']; ?></a></li>
            <li class="breadcrumb-item active" aria-current="page">Delete Post</li>
        </ol>
    </nav>
    
    <div class="card mb-4">
        <div class="card-header bg-danger text-white">
            <h1 class="h4 mb-0">Delete Post</h1>
        </div>
        <div class="card-body">
            <div class="alert alert-warning">
                <?php if ($isFirstPost): ?>
                    <p><strong>Warning:</strong> This is the first post in the thread. Deleting it will delete the entire thread and all replies.</p>
                <?php else: ?>
                    <p><strong>Warning:</strong> Are you sure you want to delete this post? This action cannot be undone.</p>
                <?php endif; ?>
            </div>
            
            <div class="card mb-4">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="fw-bold"><?php echo $post['username']; ?></span>
                            <span class="text-muted ms-2"><?php echo formatDate($post['created_at']); ?></span>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <?php echo nl2br(htmlspecialchars($post['content'])); ?>
                </div>
            </div>
            
            <form method="post" action="">
                <input type="hidden" name="csrf_token" value="<?php echo Security::generateCSRFToken(); ?>">
                
                <div class="d-flex justify-content-between">
                    <a href="<?php echo BASE_URL; ?>/forum_thread.php?slug=<?php echo $thread['slug']; ?>" class="btn btn-secondary">
                        <i class="fas fa-times me-1"></i> Cancel
                    </a>
                    
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-trash-alt me-1"></i> <?php echo $isFirstPost ? 'Delete Thread' : 'Delete Post'; ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
// Include footer
include_once __DIR__ . '/includes/footer.php';
?>