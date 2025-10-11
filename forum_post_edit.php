<?php
/**
 * Forum post edit page
 * 
 * This page allows users to edit their forum posts
 */

// Include initialization file
require_once __DIR__ . '/includes/init.php';

// Check if user is logged in
if (!isLoggedIn()) {
    setFlashMessage('You must be logged in to edit posts.', 'warning');
    redirect(BASE_URL . '/login.php');
}

// Check if post ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    setFlashMessage('Invalid post ID.', 'danger');
    redirect(BASE_URL . '/forum.php');
}

$postId = (int)$_GET['id'];

// Get post data
$postModel = new ForumPost();
$post = $postModel->getById($postId);

// Check if post exists
if (!$post) {
    setFlashMessage('Post not found.', 'danger');
    redirect(BASE_URL . '/forum.php');
}

// Check if user is the post author or an admin
if ($post['user_id'] != $_SESSION['user_id'] && !isAdmin()) {
    setFlashMessage('You do not have permission to edit this post.', 'danger');
    redirect(BASE_URL . '/forum_thread.php?id=' . $post['thread_id']);
}

// Get thread data
$threadModel = new ForumThread();
$thread = $threadModel->getById($post['thread_id']);

// Get subforum data for category selection
$subforumModel = new ForumSubforum();
$subforums = $subforumModel->getAll();

// Get all categories for the new subforum form
// Initialize ForumCategory model
$categoryModel = new ForumCategory();
$categories = $categoryModel->getAllWithSubforums();

// Initialize errors array
$errors = [];

// Check if form was submitted
if (isset($_POST['edit_post'])) {
    // Verify CSRF token
    if (!Security::verifyCSRFToken($_POST['csrf_token'])) {
        setFlashMessage('Invalid CSRF token. Please try again.', 'danger');
        redirect(BASE_URL . '/forum_post_edit.php?id=' . $postId);
    }
    
    // Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!Security::verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        setFlashMessage('Invalid request. Please try again.', 'danger');
        redirect(BASE_URL . '/forum_post_edit.php?id=' . $postId);
    }
    
    // Sanitize and validate content
    $content = trim($_POST['content'] ?? '');
    if (empty($content)) {
        $errors[] = 'Post content is required.';
    } elseif (strlen($content) < 10) {
        $errors[] = 'Post content must be at least 10 characters long.';
    }
        
        // Check if it's a new subforum creation
        if (isset($_POST['create_subforum'])) {
            $categoryId = isset($_POST['category_id']) ? (int)$_POST['category_id'] : 0;
            $subforumName = trim($_POST['subforum_name'] ?? '');
            $subforumDescription = trim($_POST['subforum_description'] ?? '');
            
            // Validate input
            if (empty($categoryId)) {
                $errors[] = 'Please select a category.';
            }
            
            if (empty($subforumName)) {
                $errors[] = 'Subforum name is required.';
            }
            
            // Create subforum if no errors
            if (empty($errors)) {
                $subforumData = [
                    'category_id' => $categoryId,
                    'name' => $subforumName,
                    'description' => $subforumDescription
                ];
                
                $newSubforumId = $subforumModel->create($subforumData);
                
                if ($newSubforumId) {
                    // Set success message and redirect to forum page
                    setFlashMessage('Subforum created successfully.', 'success');
                    redirect(BASE_URL . '/forum.php');
                } else {
                    // Log the error for debugging
                    error_log('Failed to create subforum. Data: ' . print_r($subforumData, true));
                    $errors[] = 'Failed to create subforum. Please try again.';
                }
            }
        }
        
        // Check if it's a post update
        $title = trim($_POST['title'] ?? '');
        $content = isset($_POST['content']) ? $_POST['content'] : '';  // Don't trim content as it may remove valid HTML whitespace
        $subforum_id = isset($_POST['subforum_id']) ? (int)$_POST['subforum_id'] : 0;
        
        // Debug content value
        error_log("Content value: " . substr($content, 0, 100));
        
        // Validate title
        if (empty($title)) {
            $errors[] = 'Post title is required.';
        }
        
        // Validate content - ensure we're checking properly
        if (!isset($_POST['content']) || trim($_POST['content']) === '') {
            $errors[] = 'Post content is required.';
        }
        
        // Validate subforum
        if ($subforum_id <= 0) {
            $errors[] = 'Please select a valid subforum.';
        }
        
        // Handle featured image upload
        $featured_image = $thread['featured_image'] ?? null;
        if (isset($_FILES['featured_image']) && $_FILES['featured_image']['size'] > 0) {
            $upload_dir = __DIR__ . '/uploads/forum/';
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
            $max_size = 5 * 1024 * 1024; // 5MB
            
            // Check file type
            if (!in_array($_FILES['featured_image']['type'], $allowed_types)) {
                $errors[] = 'Invalid file type. Only JPG, PNG, and GIF are allowed.';
            }
            
            // Check file size
            if ($_FILES['featured_image']['size'] > $max_size) {
                $errors[] = 'File size too large. Maximum size is 5MB.';
            }
            
            // Upload file if no errors
            if (empty($errors)) {
                // Create directory if it doesn't exist
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }
                
                // Generate unique filename
                $filename = uniqid() . '_' . $_FILES['featured_image']['name'];
                $upload_path = $upload_dir . $filename;
                
                // Move uploaded file
                if (move_uploaded_file($_FILES['featured_image']['tmp_name'], $upload_path)) {
                    $featured_image = $filename;
                } else {
                    $errors[] = 'Failed to upload image. Please try again.';
                }
            }
        }
        
        // If no errors, update post and thread
        if (empty($errors)) {
            // Update post content
            $postData = [
                'content' => $content
            ];
            
            // Try to add edited_at if the column exists
            try {
                $result = $db->query("SHOW COLUMNS FROM forum_posts LIKE 'edited_at'");
                if ($result && $result->num_rows > 0) {
                    $postData['edited_at'] = date('Y-m-d H:i:s');
                }
                // Free the result set
                $result->free_result();
            } catch (Exception $e) {
                // Column doesn't exist, continue without it
            }
            
            // Update thread data
            $threadData = [
                'title' => $title,
                'subforum_id' => $subforum_id
            ];
            
            // Try to add edited_at if the column exists
            try {
                $result = $db->query("SHOW COLUMNS FROM forum_threads LIKE 'edited_at'");
                if ($result && $result->num_rows > 0) {
                    $threadData['edited_at'] = date('Y-m-d H:i:s');
                }
                // Free the result set
                $result->free_result();
            } catch (Exception $e) {
                // Column doesn't exist, continue without it
            }
            
            // Add featured image if uploaded
            if ($featured_image) {
                $threadData['featured_image'] = $featured_image;
            }
            
            // Set success flag
            $success = true;
            
            try {
                // Use the ForumPost model to update the post content
                $postModel = new ForumPost();
                
                // Ensure content is properly formatted for database storage
                $postData = [
                    'content' => $content,
                    'edited_at' => date('Y-m-d H:i:s')
                ];
                
                // Add edit count and edited_by if columns exist
                try {
                    $result = $db->query("SHOW COLUMNS FROM forum_posts LIKE 'edit_count'");
                    if ($result && $result->num_rows > 0) {
                        $postData['edit_count'] = isset($post['edit_count']) ? $post['edit_count'] + 1 : 1;
                    }
                    $result->free_result();
                    
                    $result = $db->query("SHOW COLUMNS FROM forum_posts LIKE 'edited_by'");
                    if ($result && $result->num_rows > 0) {
                        $postData['edited_by'] = $_SESSION['user_id'];
                    }
                    $result->free_result();
                } catch (Exception $e) {
                    // Columns don't exist, continue without them
                }
                
                // Log the content being saved for debugging
                error_log("Updating post ID: $postId with content length: " . strlen($content));
                
                // Store original content for history
                $originalContent = $post['content'];
                
                $updateResult = $postModel->update($postId, $postData);
                
                if (!$updateResult) {
                    error_log("Failed to update post content for post ID: $postId");
                    throw new Exception("Failed to update post content using model");
                }
                
                // Add post history
                try {
                    $db->query("INSERT INTO forum_post_history (post_id, previous_content, edited_by, edited_at) 
                               VALUES (?, ?, ?, ?)", 
                              [$postId, $originalContent, $_SESSION['user_id'], date('Y-m-d H:i:s')]);
                } catch (Exception $historyEx) {
                    // Log but continue if history table doesn't exist yet
                    error_log("Could not add post history: " . $historyEx->getMessage());
                }
                
                // Update thread using the model
                $threadModel = new ForumThread();
                $threadData = [
                    'title' => $title,
                    'subforum_id' => $subforum_id
                ];
                
                // Add featured image to thread data if provided
                if ($featured_image) {
                    $threadData['featured_image'] = $featured_image;
                }
                
                // Generate slug for the thread title
                $slug = createSlug($title);
                
                // Check if slug exists (excluding current thread)
                $existingThread = $threadModel->getBySlug($slug);
                if ($existingThread && $existingThread['thread_id'] != $thread['thread_id']) {
                    $slug = $slug . '-' . substr(md5(uniqid()), 0, 6);
                }
                
                // Add slug to thread data
                $threadData['slug'] = $slug;
                
                $threadUpdateResult = $threadModel->update($thread['thread_id'], $threadData);
                
                if (!$threadUpdateResult) {
                    error_log("Thread update failed. Thread ID: {$thread['thread_id']}, Data: " . print_r($threadData, true));
                    throw new Exception("Failed to update thread information");
                }
                
                setFlashMessage('Post updated successfully.', 'success');
                redirect(BASE_URL . '/forum_thread.php?slug=' . $thread['slug'] . '#post-' . $postId);
            } catch (Exception $e) {
                setFlashMessage('An error occurred: ' . $e->getMessage(), 'danger');
            }
        }
    }
}

// Page title
$pageTitle = 'Edit Post';

// Include header
include_once __DIR__ . '/includes/header.php';
?>

<style>
/* Styling for the standard textarea */
#content {
    min-height: 500px;
    padding: 15px;
    font-size: 16px;
    line-height: 1.5;
    border: 1px solid #ccc;
    border-radius: 4px;
    width: 100%;
    box-sizing: border-box;
    margin: 0px 0;
    font-family: Arial, sans-serif;
}
</style>

<script>
tinymce.init({
    selector: 'textarea#content',  // Targets the textarea with id="content"
    plugins: 'code table lists image link autoresize',
    toolbar: 'undo redo | blocks | bold italic | alignleft aligncenter alignright | indent outdent | bullist numlist | code | table | image | link',
    menubar: 'edit view insert format tools table help',
    autoresize_bottom_margin: 25,
    height: 500,
    setup: function (editor) {
        editor.on('change', function () {
            editor.save();
        });
    }
  });
document.addEventListener('DOMContentLoaded', function() {
    // No need for manual textarea resize when using TinyMCE
});
</script>

<div class="container mt-4">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/forum.php">Forum</a></li>
            <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/forum_thread.php?slug=<?php echo $thread['slug']; ?>"><?php echo htmlspecialchars($thread['title']); ?></a></li>
            <li class="breadcrumb-item active" aria-current="page">Edit Post</li>
        </ol>
    </nav>
    
    <div class="card mb-4 shadow">
        <div class="card-header bg-primary text-white">
            <h1 class="h4 mb-0">Edit Post</h1>
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
            
            <form method="post" action="" id="post-form" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?php echo Security::generateCSRFToken(); ?>">
                
                <div class="row">
                    <div class="col-md-8">
                        <div class="mb-3">
                            <label for="title" class="form-label fw-bold">Thread Title</label>
                            <input type="text" class="form-control" id="title" name="title" value="<?php echo isset($_POST['title']) ? htmlspecialchars($_POST['title']) : htmlspecialchars($thread['title']); ?>" required>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label for="subforum_id" class="form-label fw-bold">Subforum</label>
                            <div class="input-group">
                                <select class="form-select" id="subforum_id" name="subforum_id" required>
                                    <option value="">Select Subforum</option>
                                    <?php foreach ($subforums as $subforum): ?>
                                        <option value="<?php echo $subforum['subforum_id']; ?>" <?php echo (isset($_POST['subforum_id']) && $_POST['subforum_id'] == $subforum['subforum_id']) || $thread['subforum_id'] == $subforum['subforum_id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($subforum['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="input-group-append">
                                    <button type="button" class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#newSubforumModal">New</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-12">
                        <label for="featured_image" class="form-label fw-bold">Featured Image</label>
                        <?php if (!empty($thread['featured_image'])): ?>
                            <div class="mb-2">
                                <img src="<?php echo BASE_URL; ?>/uploads/forum/<?php echo htmlspecialchars($thread['featured_image']); ?>" alt="Featured Image" class="img-thumbnail" style="max-height: 150px;">
                                <p class="text-muted small">Current featured image</p>
                            </div>
                        <?php endif; ?>
                        <input type="file" class="form-control" id="featured_image" name="featured_image" accept="image/jpeg,image/png,image/gif">
                        <div class="form-text">Optional. Max size: 5MB. Allowed types: JPG, PNG, GIF.</div>
                    </div>
                </div>
                
                <div class="mb-4">
                    <label for="content" class="form-label fw-bold">Content</label>
                    <textarea class="form-control" id="content" name="content" rows="10" required 
                        style="width: 100%; min-height: 400px; padding: 15px; font-size: 16px; border: 1px solid #ced4da; 
                        border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); margin: 10px 0; 
                        font-family: system-ui, -apple-system, sans-serif;"><?php echo htmlspecialchars_decode(isset($_POST['content']) ? $_POST['content'] : $post['content']); ?></textarea>
                    <div class="form-text text-muted">Format your post with clear paragraphs and headings for better readability</div>
                </div>
                
                <script>
                // Auto-resize textarea based on content
                document.getElementById('content').addEventListener('input', function() {
                    this.style.height = 'auto';
                    this.style.height = (this.scrollHeight + 10) + 'px';
                });
                
                // Initialize height on page load
                window.addEventListener('load', function() {
                    const textarea = document.getElementById('content');
                    textarea.style.height = 'auto';
                    textarea.style.height = (textarea.scrollHeight + 10) + 'px';
                });
                </script>
                
                <div class="row mt-4">
                    <div class="col-md-12">
                        <div class="d-flex justify-content-between">
                            <a href="<?php echo BASE_URL; ?>/forum_thread.php?slug=<?php echo $thread['slug']; ?>#post-<?php echo $postId; ?>" class="btn btn-secondary">
                                <i class="fas fa-arrow-left me-1"></i> Cancel
                            </a>
                            <button type="submit" name="edit_post" class="btn btn-primary btn-lg px-4">
                                <i class="bi bi-check-circle me-2"></i>Update Post
                            </button>
                        </div>
                    </div>
                </div>
                
                <div class="alert alert-info mt-3">
                    <small><i class="bi bi-info-circle me-2"></i>Your changes will be visible immediately after updating.</small>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Add client-side validation before form submission
    const postForm = document.getElementById('post-form');
    
    postForm.addEventListener('submit', function(e) {
        const title = document.getElementById('title').value.trim();
        const subforum = document.getElementById('subforum_id').value;
        const content = tinymce.get('content') ? tinymce.get('content').getContent().trim() : '';
        let hasErrors = false;
        let errorMessage = '';
        
        if (!title) {
            hasErrors = true;
            errorMessage += '- Thread title is required.\n';
        }
        
        if (!subforum) {
            hasErrors = true;
            errorMessage += '- Please select a subforum.\n';
        }
        
        if (!content) {
            hasErrors = true;
            errorMessage += '- Post content is required.\n';
        }
        
        if (hasErrors) {
            e.preventDefault();
            alert('Please fix the following errors:\n' + errorMessage);
            return false;
        }
    });
});
</script>

<?php include_once __DIR__ . '/includes/footer.php'; ?>

<!-- New Subforum Modal -->
<div class="modal fade" id="newSubforumModal" tabindex="-1" aria-labelledby="newSubforumModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post" action="">
                <input type="hidden" name="csrf_token" value="<?= Security::generateCSRFToken() ?>">
                <input type="hidden" name="create_subforum" value="1">
                
                <div class="modal-header">
                    <h5 class="modal-title" id="newSubforumModalLabel">Create New Subforum</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <!-- Modal content -->
                    <div class="form-group">
                        <label for="category_id">Category</label>
                        <select name="category_id" id="category_id" class="form-control" required>
                            <option value="">Select Category</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?= $category['category_id'] ?>"><?= htmlspecialchars($category['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="subforum_name">Subforum Name</label>
                        <input type="text" name="subforum_name" id="subforum_name" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="subforum_description">Description</label>
                        <textarea name="subforum_description" id="subforum_description" class="form-control" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Subforum</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Add this to your existing JavaScript
document.addEventListener('DOMContentLoaded', function() {
    // Handle successful subforum creation
    <?php if (isset($_POST['create_subforum']) && empty($errors)): ?>
    var subforumModal = document.getElementById('newSubforumModal');
    var modal = bootstrap.Modal.getInstance(subforumModal);
    if (modal) {
        modal.hide();
    }
    <?php endif; ?>
});
</script>