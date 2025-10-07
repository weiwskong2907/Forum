<?php
/**
 * Forum post edit page
 * 
 * This page handles the editing of forum posts
 */

// Include initialization file
require_once __DIR__ . '/includes/init.php';

// Check if user is logged in
if (!isLoggedIn()) {
    setFlashMessage('You must be logged in to edit a post.', 'danger');
    redirect(BASE_URL . '/login.php');
}

// Get post ID
$postId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (empty($postId)) {
    setFlashMessage('Post not found.', 'danger');
    redirect(BASE_URL . '/forum.php');
}

// Initialize models
$postModel = new ForumPost();
$threadModel = new ForumThread();
$subforumModel = new ForumSubforum();
$categoryModel = new ForumCategory();

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

// Check if user is authorized to edit the post
$isAuthorized = false;

// Post owner can edit their own post
if ($post['user_id'] == $_SESSION['user_id']) {
    $isAuthorized = true;
}

// Admin can edit any post
if (isAdmin()) {
    $isAuthorized = true;
}

if (!$isAuthorized) {
    setFlashMessage('You are not authorized to edit this post.', 'danger');
    redirect(BASE_URL . '/forum_thread.php?slug=' . $thread['slug']);
}

// Get subforum
$subforum = $subforumModel->getById($thread['subforum_id']);

// Get category
$category = $categoryModel->getById($subforum['category_id']);

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        setFlashMessage('Invalid request. Please try again.', 'danger');
        redirect(BASE_URL . '/forum_post_edit.php?id=' . $postId);
    }
    
    // Get form data
    $content = trim($_POST['content'] ?? '');
    
    // Validate form data
    $errors = [];
    
    if (empty($content) || strlen(trim($content)) < 10) {
        $errors[] = 'Post content is required and must be at least 10 characters.';
    }
    
    // If no errors, update post
    if (empty($errors)) {
        // Update post data
        $postData = [
            'content' => $content,
            'edited_at' => date('Y-m-d H:i:s')
        ];
        
        // Update post
        if ($postModel->update($postId, $postData)) {
            setFlashMessage('Post updated successfully.', 'success');
            redirect(BASE_URL . '/forum_thread.php?slug=' . $thread['slug']);
        } else {
            setFlashMessage('Failed to update post. Please try again.', 'danger');
        }
    }
}

// Page title
$pageTitle = 'Edit Post';

// Include header
include_once __DIR__ . '/includes/header.php';
?>

<div class="container mt-4">
    <nav aria-label="breadcrumb" class="bg-light p-3 rounded shadow-sm">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/forum.php" class="text-decoration-none"><i class="bi bi-house-door"></i> Forum</a></li>
            <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/forum_subforum.php?slug=<?php echo $category['slug']; ?>" class="text-decoration-none"><?php echo htmlspecialchars($category['name']); ?></a></li>
            <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/forum_subforum.php?slug=<?php echo $subforum['slug']; ?>" class="text-decoration-none"><?php echo htmlspecialchars($subforum['name']); ?></a></li>
            <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/forum_thread.php?slug=<?php echo $thread['slug']; ?>" class="text-decoration-none"><?php echo htmlspecialchars($thread['title']); ?></a></li>
            <li class="breadcrumb-item active" aria-current="page">Edit Post</li>
        </ol>
    </nav>

    <div class="card shadow-sm mt-4">
        <div class="card-header bg-primary text-white">
            <h1 class="h5 mb-0"><i class="bi bi-pencil-square me-2"></i>Edit Post</h1>
        </div>
        <div class="card-body">
            <?php if (isset($errors) && !empty($errors)): ?>
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo $error; ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form action="<?php echo BASE_URL; ?>/forum_post_edit.php?id=<?php echo $postId; ?>" method="post">
                <input type="hidden" name="csrf_token" value="<?php echo createCSRFToken(); ?>">
                
                <div class="mb-3">
                    <label for="content" class="form-label">Post Content</label>
                    <textarea 
                        name="content" 
                        id="content" 
                        class="form-control" 
                        rows="10" 
                        required
                        style="min-height: 400px; padding: 15px; font-size: 16px; border: 1px solid #ced4da; border-radius: 8px; box-shadow: inset 0 1px 2px rgba(0,0,0,.075); margin-bottom: 20px; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;"
                    ><?php echo htmlspecialchars_decode(isset($_POST['content']) ? $_POST['content'] : $post['content']); ?></textarea>
                    <div class="form-text">Format your post with clear paragraphs and headings for better readability</div>
                </div>
                
                <div class="d-flex justify-content-between align-items-center">
                    <a href="<?php echo BASE_URL; ?>/forum_thread.php?slug=<?php echo $thread['slug']; ?>" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left me-1"></i> Cancel
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-lg me-1"></i> Update Post
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <div class="alert alert-info mt-3">
        <i class="bi bi-info-circle me-2"></i> Your changes will be visible immediately after updating.
    </div>
</div>

<script>
// Auto-resize textarea based on content
const textarea = document.getElementById('content');
if (textarea) {
    const adjustHeight = () => {
        textarea.style.height = 'auto';
        textarea.style.height = Math.max(400, textarea.scrollHeight) + 'px';
    };
    
    // Adjust on input
    textarea.addEventListener('input', adjustHeight);
    
    // Adjust on page load
    window.addEventListener('load', adjustHeight);
}
</script>

<?php
// Include footer
include_once __DIR__ . '/includes/footer.php';
?>