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
        $content = isset($_POST['content']) ? $_POST['content'] : '';
        $subforum_id = isset($_POST['subforum_id']) ? (int)$_POST['subforum_id'] : 0;
        
        // Validate title
        if (empty($title)) {
            $errors[] = 'Post title is required.';
        }
        
        // Validate content
        if (empty($content)) {
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
                // Direct database update for post
                $postUpdateQuery = "UPDATE forum_posts SET content = ? WHERE post_id = ?";
                $db->query($postUpdateQuery, [$content, $postId]);
                
                // Direct database update for thread
                $threadUpdateQuery = "UPDATE forum_threads SET title = ?, subforum_id = ? WHERE thread_id = ?";
                $db->query($threadUpdateQuery, [$title, $subforum_id, $thread['thread_id']]);
                
                // Update featured image if provided
                if ($featured_image) {
                    $imageUpdateQuery = "UPDATE forum_threads SET featured_image = ? WHERE thread_id = ?";
                $db->query($imageUpdateQuery, [$featured_image, $thread['thread_id']]);
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

<!-- Add TinyMCE -->
<script src="https://cdn.jsdelivr.net/npm/tinymce@6.8.2/tinymce.min.js" referrerpolicy="origin"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize TinyMCE
    tinymce.init({
        selector: '#content',
        plugins: 'anchor autolink charmap codesample emoticons image link lists media searchreplace table visualblocks wordcount',
        toolbar: 'undo redo | blocks fontfamily fontsize | bold italic underline strikethrough | link image media table | align lineheight | numlist bullist indent outdent | emoticons charmap | removeformat',
        images_upload_url: '<?php echo BASE_URL; ?>/upload_image.php',
        images_upload_credentials: true,
        automatic_uploads: true,
        images_reuse_filename: true,
        relative_urls: false,
        remove_script_host: false,
        convert_urls: false,
        height: 400,
        api_key: 'xj1pomo1mrpu7fz9gus1zulblwty6ajfd4c76gtbmsx5fhwn',
        branding: false,
        promotion: false,
        readonly: false,
        setup: function (editor) {
            editor.on('init', function() {
                // Make sure the editor container is visible
                const editorContainer = document.querySelector('.tox.tox-tinymce');
                if (editorContainer) {
                    editorContainer.style.display = 'block';
                }
                
                // Ensure the editor is editable
                editor.mode.set('design');
                
                // Force focus to make sure it's interactive
                setTimeout(function() {
                    editor.focus();
                }, 100);
            });
            editor.on('change', function () {
                editor.save();
            });
        }
    });
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
                
                <div class="mb-3">
                    <label for="title" class="form-label fw-bold">Thread Title</label>
                    <input type="text" class="form-control" id="title" name="title" value="<?php echo isset($_POST['title']) ? htmlspecialchars($_POST['title']) : htmlspecialchars($thread['title']); ?>" required>
                </div>
                
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
                
                <div class="mb-3">
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
                
                <div class="mb-4">
                    <label for="content" class="form-label fw-bold">Content</label>
                    <textarea class="form-control" id="content" name="content" rows="10"><?php echo isset($_POST['content']) ? $_POST['content'] : $post['content']; ?></textarea>
                </div>
                
                <!-- Add TinyMCE -->
                <?php include_once __DIR__ . '/config/tinymce_config.php'; ?>
<script src="<?php echo getTinymceCdnUrl(); ?>" referrerpolicy="origin"></script>
                <script>
                document.addEventListener('DOMContentLoaded', function() {
                    tinymce.init({
                        selector: '#content',
                        height: 400,
                        menubar: false,
                        plugins: [
                            'advlist', 'autolink', 'lists', 'link', 'image', 'charmap', 'preview',
                            'anchor', 'searchreplace', 'visualblocks', 'code', 'fullscreen',
                            'insertdatetime', 'media', 'table', 'help', 'wordcount'
                        ],
                        toolbar: 'undo redo | blocks | ' +
                            'bold italic backcolor | alignleft aligncenter ' +
                            'alignright alignjustify | bullist numlist outdent indent | ' +
                            'removeformat | help',
                        content_style: 'body { font-family:Helvetica,Arial,sans-serif; font-size:16px }',
                        setup: function(editor) {
                            editor.on('init', function() {
                                editor.getBody().style.backgroundColor = '#ffffff';
                                editor.getBody().style.color = '#000000';
                            });
                        }
                    });
                });
                </script>
                
                <div class="d-flex justify-content-between">
                    <a href="<?php echo BASE_URL; ?>/forum_thread.php?slug=<?php echo $thread['slug']; ?>#post-<?php echo $postId; ?>" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-1"></i> Cancel
                    </a>
                    <button type="submit" name="edit_post" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i> Save Changes
                    </button>
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