<?php
/**
 * Forum thread delete page
 * 
 * This page handles the deletion of forum threads
 */

// Include initialization file
require_once __DIR__ . '/includes/init.php';

// Check if user is logged in
if (!isLoggedIn()) {
    setFlashMessage('You must be logged in to delete a thread.', 'danger');
    redirect(BASE_URL . '/login.php');
}

// Get thread ID
$threadId = isset($_POST['thread_id']) ? (int)$_POST['thread_id'] : (isset($_GET['id']) ? (int)$_GET['id'] : 0);

if (empty($threadId)) {
    error_log("Thread ID not found in request");
    setFlashMessage('Thread not found.', 'danger');
    redirect(BASE_URL . '/forum.php');
}

// Initialize models
$threadModel = new ForumThread();
$postModel = new ForumPost();

// Get thread
$thread = $threadModel->getById($threadId);

if (!$thread) {
    error_log("Thread with ID {$threadId} not found");
    setFlashMessage('Thread not found.', 'danger');
    redirect(BASE_URL . '/forum.php');
}

// Verify CSRF token if it's a POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        error_log("CSRF token verification failed for thread deletion");
        setFlashMessage('Invalid request. Please try again.', 'danger');
        redirect(BASE_URL . '/forum_thread.php?slug=' . $thread['slug']);
    }
}

// Check if user is authorized to delete the thread
$isAuthorized = false;

// Thread owner can delete their own thread
if ($thread['user_id'] == $_SESSION['user_id']) {
    $isAuthorized = true;
    error_log("User {$_SESSION['user_id']} authorized to delete their own thread {$threadId}");
}

// Admin can delete any thread
if (isAdmin()) {
    $isAuthorized = true;
    error_log("Admin user {$_SESSION['user_id']} authorized to delete thread {$threadId}");
}

if (!$isAuthorized) {
    error_log("User {$_SESSION['user_id']} not authorized to delete thread {$threadId}");
    setFlashMessage('You are not authorized to delete this thread.', 'danger');
    redirect(BASE_URL . '/forum_thread.php?slug=' . $thread['slug']);
}

// Confirmation page
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['confirm_delete'])) {
    // Page title
    $pageTitle = 'Delete Thread';
    
    // Include header
    include_once __DIR__ . '/includes/header.php';
    ?>
    
    <div class="container mt-4">
        <div class="card">
            <div class="card-header bg-danger text-white">
                <h1 class="h4 mb-0">Delete Thread</h1>
            </div>
            <div class="card-body">
                <p class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Are you sure you want to delete the thread "<strong><?php echo sanitize($thread['title']); ?></strong>"?
                </p>
                <p>This action will permanently delete the thread and all its posts. This cannot be undone.</p>
                
                <form action="<?php echo BASE_URL; ?>/forum_thread_delete.php" method="post">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    <input type="hidden" name="thread_id" value="<?php echo $thread['thread_id']; ?>">
                    <input type="hidden" name="confirm_delete" value="1">
                    
                    <div class="d-flex justify-content-between">
                        <a href="<?php echo BASE_URL; ?>/forum_thread.php?slug=<?php echo $thread['slug']; ?>" class="btn btn-secondary">
                            <i class="fas fa-times me-1"></i> Cancel
                        </a>
                        <button type="submit" class="btn btn-danger">
                            <i class="fas fa-trash me-1"></i> Delete Thread
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <?php
    include_once __DIR__ . '/includes/footer.php';
    exit;
}

// Process thread deletion
try {
    // Log deletion attempt
    error_log("Attempting to delete thread ID: {$threadId}");
    
    // Get database instance
    $db = Database::getInstance();
    
    // Begin transaction
    $db->beginTransaction();
    
    // Delete all posts in the thread first
    $deletePostsQuery = "DELETE FROM forum_posts WHERE thread_id = ?";
    $postsStmt = $db->query($deletePostsQuery, [$threadId]);
    
    // Delete the thread
    $deleteThreadQuery = "DELETE FROM forum_threads WHERE thread_id = ?";
    $threadStmt = $db->query($deleteThreadQuery, [$threadId]);
    
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
        error_log("Failed to delete thread ID: {$threadId} - Database operation returned false");
        setFlashMessage('Failed to delete thread. Please try again.', 'danger');
        redirect(BASE_URL . '/forum_thread.php?slug=' . $thread['slug']);
    }
} catch (Exception $e) {
    // Rollback transaction
    if (isset($db)) {
        $db->rollback();
    }
    error_log("Exception when deleting thread ID: {$threadId} - " . $e->getMessage());
    setFlashMessage('An error occurred while deleting the thread. Please try again.', 'danger');
    redirect(BASE_URL . '/forum_thread.php?slug=' . $thread['slug']);
}