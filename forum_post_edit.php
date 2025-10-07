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
    
    // Content validation
    if (empty($content)) {
        $errors[] = 'Post content is required.';
    } elseif (strlen(trim($content)) < 10) {
        $errors[] = 'Post content must be at least 10 characters.';
    } elseif (strlen($content) > 50000) {
        $errors[] = 'Post content exceeds maximum allowed length (50,000 characters).';
    }
    
    // Additional security check - prevent XSS in content
    $content = htmlspecialchars($content, ENT_QUOTES, 'UTF-8');
    
    // If no errors, update post
    if (empty($errors)) {
        // Store original content for history
        $originalContent = $post['content'];
        
        // Update post data
        $postData = [
            'content' => $content,
            'edited_at' => date('Y-m-d H:i:s'),
            'edit_count' => ($post['edit_count'] ?? 0) + 1,
            'edited_by' => $_SESSION['user_id']
        ];
        
        // Update post directly without transaction for now
        $updateSuccess = $postModel->update($postId, $postData);
        
        if ($updateSuccess) {
            setFlashMessage('Post updated successfully.', 'success');
            redirect(BASE_URL . '/forum_thread.php?slug=' . $thread['slug'] . '#post-' . $postId);
        } else {
            error_log('Post update error: Failed to update post');
            setFlashMessage('Failed to update post. Please try again.', 'danger');
        }
    }
}

// Page title
$pageTitle = 'Edit Post';

// Include header
include_once __DIR__ . '/includes/header.php';

// Include TinyMCE configuration
include_once __DIR__ . '/config/tinymce_config.php';
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

    <div class="card shadow mt-4">
        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
            <h1 class="h5 mb-0"><i class="bi bi-pencil-square me-2"></i>Edit Post</h1>
            <span class="badge bg-light text-dark">Thread: <?php echo htmlspecialchars($thread['title']); ?></span>
        </div>
        <div class="card-body">
            <?php if (isset($errors) && !empty($errors)): ?>
                <div class="alert alert-danger">
                    <h6 class="alert-heading"><i class="bi bi-exclamation-triangle-fill me-2"></i>Please fix the following errors:</h6>
                    <ul class="mb-0 mt-2">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo $error; ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <div class="alert alert-light border mb-4">
                <div class="d-flex align-items-center">
                    <i class="bi bi-info-circle-fill text-primary me-2 fs-5"></i>
                    <div>
                        <strong>Editing post in thread:</strong> <?php echo htmlspecialchars($thread['title']); ?><br>
                        <small class="text-muted">Last edited: <?php echo isset($post['edited_at']) ? formatDate($post['edited_at']) : 'Never'; ?></small>
                    </div>
                </div>
            </div>

            <form action="<?php echo BASE_URL; ?>/forum_post_edit.php?id=<?php echo $postId; ?>" method="post">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                
                <div class="mb-3">
                    <label for="content" class="form-label fw-bold">Post Content</label>
                    <textarea 
                        name="content" 
                        id="content" 
                        class="form-control tinymce-editor" 
                        rows="12" 
                        required
                    ><?php echo htmlspecialchars_decode(isset($_POST['content']) ? $_POST['content'] : $post['content']); ?></textarea>
                    <div class="form-text mt-2">
                        <i class="bi bi-info-circle me-1"></i> Use the formatting tools above to enhance your post with headings, lists, and more
                    </div>
                </div>
                
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-center gap-3">
                    <div>
                        <a href="<?php echo BASE_URL; ?>/forum_thread.php?slug=<?php echo $thread['slug']; ?>" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left me-1"></i> Cancel
                        </a>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="button" id="preview-btn" class="btn btn-outline-primary">
                            <i class="bi bi-eye me-1"></i> Preview
                        </button>
                        <button type="submit" class="btn btn-success">
                            <i class="bi bi-check-lg me-1"></i> Save Changes
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    
    <div class="card mt-3 shadow-sm border-info">
        <div class="card-header bg-info text-white">
            <i class="bi bi-info-circle me-2"></i> Important Information
        </div>
        <div class="card-body">
            <ul class="mb-0">
                <li>Your changes will be visible immediately after updating</li>
                <li>A revision history of your post will be maintained</li>
                <li>Use the formatting tools to enhance your post's readability</li>
            </ul>
        </div>
    </div>
    
    <!-- Preview Modal -->
    <div class="modal fade" id="previewModal" tabindex="-1" aria-labelledby="previewModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="previewModalLabel">Post Preview</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="preview-content" class="p-3 border rounded"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
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

// Preview functionality
document.getElementById('preview-btn').addEventListener('click', function() {
    // Get content from TinyMCE editor
    const content = tinymce.get('content').getContent();
    
    // Set content in preview modal
    document.getElementById('preview-content').innerHTML = content;
    
    // Show the modal
    const previewModal = new bootstrap.Modal(document.getElementById('previewModal'));
    previewModal.show();
});
</script>

<?php
// Include footer
include_once __DIR__ . '/includes/footer.php';
?>