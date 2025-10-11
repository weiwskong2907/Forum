<?php
/**
 * Forum thread edit page
 * 
 * This page handles the editing of forum threads
 */

// Include initialization file
require_once __DIR__ . '/includes/init.php';

// Check if user is logged in
if (!isLoggedIn()) {
    setFlashMessage('You must be logged in to edit a thread.', 'danger');
    redirect(BASE_URL . '/login.php');
}

// Get thread ID
$threadId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (empty($threadId)) {
    setFlashMessage('Thread not found.', 'danger');
    redirect(BASE_URL . '/forum.php');
}

// Initialize models
$threadModel = new ForumThread();
$subforumModel = new ForumSubforum();

// Get thread
$thread = $threadModel->getById($threadId);

if (!$thread) {
    setFlashMessage('Thread not found.', 'danger');
    redirect(BASE_URL . '/forum.php');
}

// Check if user is authorized to edit the thread
$isAuthorized = false;

// Thread owner can edit their own thread
if ($thread['user_id'] == $_SESSION['user_id']) {
    $isAuthorized = true;
}

// Admin can edit any thread
if (isAdmin()) {
    $isAuthorized = true;
}

if (!$isAuthorized) {
    setFlashMessage('You are not authorized to edit this thread.', 'danger');
    redirect(BASE_URL . '/forum_thread.php?slug=' . $thread['slug']);
}

// Get subforum
$subforum = $subforumModel->getById($thread['subforum_id']);

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        setFlashMessage('Invalid request. Please try again.', 'danger');
        redirect(BASE_URL . '/forum_thread_edit.php?id=' . $threadId);
    }
    
    // Get form data
    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $isSticky = isset($_POST['is_sticky']) ? 1 : 0;
    $isLocked = isset($_POST['is_locked']) ? 1 : 0;
    
    // Validate form data
    $errors = [];
    
    if (empty($title)) {
        $errors[] = 'Title is required.';
    } elseif (strlen($title) < 3) {
        $errors[] = 'Title must be at least 3 characters.';
    } elseif (strlen($title) > 100) {
        $errors[] = 'Title cannot exceed 100 characters.';
    } elseif (!preg_match('/^[a-zA-Z0-9\s\-_.,!?\'":;()[\]{}]+$/', $title)) {
        $errors[] = 'Title contains invalid characters.';
    }
    
    if (empty($content)) {
        $errors[] = 'Content is required.';
    } elseif (strlen($content) < 10) {
        $errors[] = 'Content must be at least 10 characters.';
    } elseif (strlen($content) > 50000) {
        $errors[] = 'Content is too long (maximum 50,000 characters).';
    }
    
    // If no errors, update thread
    if (empty($errors)) {
        // Generate slug
        $slug = createSlug($title);
        
        // Check if slug exists (excluding current thread)
        $existingThread = $threadModel->getBySlug($slug);
        if ($existingThread && $existingThread['thread_id'] != $threadId) {
            $slug = $slug . '-' . time();
        }
        
        // Begin transaction
        $db = Database::getInstance();
        $db->beginTransaction();
        
        try {
            // Update thread data
            $threadData = [
                'title' => $title,
                'slug' => $slug,
                'is_sticky' => $isSticky,
                'is_locked' => $isLocked,
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            // Update thread
            if ($threadModel->update($threadId, $threadData)) {
                // Get first post of the thread to update its content
                $postModel = new ForumPost();
                $firstPost = $postModel->getFirstPostByThreadId($threadId);
                
                if ($firstPost) {
                    // Update first post
                    $postData = [
                        'content' => $content,
                        'edited_by' => $_SESSION['user_id'],
                        'updated_at' => date('Y-m-d H:i:s')
                    ];
                    
                    if ($postModel->update($firstPost['post_id'], $postData)) {
                        // Add post history
                        try {
                            $historyQuery = "INSERT INTO forum_post_history (post_id, previous_content, edited_by, edited_at) 
                                            VALUES (?, ?, ?, ?)";
                            $historyParams = [$firstPost['post_id'], $firstPost['content'], $_SESSION['user_id'], date('Y-m-d H:i:s')];
                            $db->query($historyQuery, $historyParams);
                        } catch (Exception $historyEx) {
                            // Log but continue if history table doesn't exist yet
                            error_log("Could not add post history: " . $historyEx->getMessage());
                        }
                        
                        // Commit transaction
                        $db->commit();
                        
                        setFlashMessage('Thread updated successfully.', 'success');
                        redirect(BASE_URL . '/forum_thread.php?slug=' . $slug);
                    } else {
                        // Rollback transaction
                        $db->rollback();
                        setFlashMessage('Failed to update thread content. Please try again.', 'danger');
                    }
                } else {
                    // Commit transaction (only thread title was updated)
                    $db->commit();
                    setFlashMessage('Thread updated successfully, but failed to update content.', 'warning');
                    redirect(BASE_URL . '/forum_thread.php?slug=' . $slug);
                }
            } else {
                // Rollback transaction
                $db->rollback();
                setFlashMessage('Failed to update thread. Please try again.', 'danger');
                // Add redirect to prevent resubmission
                redirect(BASE_URL . '/forum_thread_edit.php?id=' . $threadId);
            }
        } catch (Exception $e) {
            // Rollback transaction
            $db->rollback();
            error_log('Error updating thread: ' . $e->getMessage());
            setFlashMessage('An error occurred while updating the thread. Please try again.', 'danger');
            redirect(BASE_URL . '/forum_thread_edit.php?id=' . $threadId);
        }
    }
}

// Get first post content
$postModel = new ForumPost();
$firstPost = $postModel->getFirstPostByThreadId($threadId);
$content = $firstPost ? $firstPost['content'] : '';

// Page title
$pageTitle = 'Edit Thread';

// Include header
include_once __DIR__ . '/includes/header.php';
?>

<div class="container mt-4">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/forum.php">Forum</a></li>
            <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/forum_subforum.php?slug=<?php echo $subforum['slug']; ?>"><?php echo $subforum['name']; ?></a></li>
            <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/forum_thread.php?slug=<?php echo $thread['slug']; ?>"><?php echo $thread['title']; ?></a></li>
            <li class="breadcrumb-item active" aria-current="page">Edit</li>
        </ol>
    </nav>
    
    <div class="card">
        <div class="card-header bg-primary text-white">
            <h1 class="h4 mb-0">Edit Thread</h1>
        </div>
        <div class="card-body">
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo $error; ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <form action="<?php echo BASE_URL; ?>/forum_thread_edit.php?id=<?php echo $threadId; ?>" method="post">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                
                <div class="mb-3">
                    <label for="title" class="form-label">Title</label>
                    <input type="text" class="form-control" id="title" name="title" value="<?php echo sanitize($thread['title']); ?>" required>
                </div>
                
                <div class="mb-3">
                    <label for="content" class="form-label">Content</label>
                    <textarea class="form-control" id="content" name="content" rows="10" required><?php echo sanitize($content); ?></textarea>
                </div>
                
                <?php if (isAdmin()): ?>
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="is_sticky" name="is_sticky" <?php echo $thread['is_sticky'] ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="is_sticky">
                                Sticky Thread
                            </label>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="is_locked" name="is_locked" <?php echo $thread['is_locked'] ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="is_locked">
                                Lock Thread
                            </label>
                        </div>
                    </div>
                <?php endif; ?>
                
                <div class="d-flex justify-content-between">
                    <a href="<?php echo BASE_URL; ?>/forum_thread.php?slug=<?php echo $thread['slug']; ?>" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">Update Thread</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include_once __DIR__ . '/includes/footer.php'; ?>