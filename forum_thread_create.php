<?php
/**
 * Forum thread create page
 * 
 * This page allows users to create a new thread in a subforum
 */

// Include initialization file
require_once __DIR__ . '/includes/init.php';

// Ensure the featured_image column exists in the forum_threads table
try {
    $db = Database::getInstance()->getConnection();
    $checkColumnQuery = "SHOW COLUMNS FROM forum_threads LIKE 'featured_image'";
    
    // Handle different database connection types
    if ($db instanceof mysqli) {
        $result = $db->query($checkColumnQuery);
        $columnExists = $result && $result->num_rows > 0;
        
        if (!$columnExists) {
            $addColumnQuery = "ALTER TABLE forum_threads ADD COLUMN featured_image VARCHAR(255) DEFAULT NULL AFTER is_locked";
            $db->query($addColumnQuery);
        }
    } else {
        // For PDO
        $columnExists = $db->query($checkColumnQuery)->rowCount() > 0;
        
        if (!$columnExists) {
            $addColumnQuery = "ALTER TABLE forum_threads ADD COLUMN featured_image VARCHAR(255) DEFAULT NULL AFTER is_locked";
            $db->exec($addColumnQuery);
        }
    }
} catch (Exception $e) {
    // Silently continue if there's an error
}

// Check if user is logged in
if (!isLoggedIn()) {
    setFlashMessage('You must be logged in to create a thread.', 'danger');
    redirect(BASE_URL . '/login.php');
}

// Get subforum ID
$subforumId = isset($_GET['subforum_id']) ? (int)$_GET['subforum_id'] : 0;

if (empty($subforumId)) {
    setFlashMessage('Subforum not found.', 'danger');
    redirect(BASE_URL . '/forum.php');
}

// Initialize models
$subforumModel = new ForumSubforum();
$threadModel = new ForumThread();

// Get subforum
$subforum = $subforumModel->getById($subforumId);

if (!$subforum) {
    setFlashMessage('Subforum not found.', 'danger');
    redirect(BASE_URL . '/forum.php');
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_thread'])) {
    // Verify CSRF token
    if (!Security::verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        setFlashMessage('Invalid request. Please try again.', 'danger');
        redirect(BASE_URL . '/forum_thread_create.php?subforum_id=' . $subforumId);
    }
    
    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $tags = isset($_POST['tags']) ? explode(',', $_POST['tags']) : [];
    
    $errors = [];
    $featuredImage = '';
    
    // Validate input
    if (empty($title)) {
        $errors[] = 'Thread title is required.';
    } elseif (strlen($title) > 100) {
        $errors[] = 'Thread title cannot exceed 100 characters.';
    }
    
    if (empty($content)) {
        $errors[] = 'Thread content is required.';
    }
    
    // Handle image upload
    if (!empty($_FILES['thread_image']['name'])) {
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $maxSize = 2 * 1024 * 1024; // 2MB
        
        if (!in_array($_FILES['thread_image']['type'], $allowedTypes)) {
            $errors[] = 'Invalid image format. Allowed formats: JPG, PNG, GIF, WEBP.';
        } elseif ($_FILES['thread_image']['size'] > $maxSize) {
            $errors[] = 'Image size exceeds the maximum allowed (2MB).';
        } else {
            // Create uploads/forum directory if it doesn't exist
            $uploadDir = __DIR__ . '/uploads/forum';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            // Generate unique filename
            $filename = uniqid() . '_' . $_FILES['thread_image']['name'];
            $uploadPath = $uploadDir . '/' . $filename;
            
            if (move_uploaded_file($_FILES['thread_image']['tmp_name'], $uploadPath)) {
                $featuredImage = 'uploads/forum/' . $filename;
            } else {
                $errors[] = 'Failed to upload image. Please try again.';
            }
        }
    }
    
    if (empty($errors)) {
        // Create thread
        $userId = $_SESSION['user_id'];
        $slug = createSlug($title);
        
        // Check if slug exists
        $existingThread = $threadModel->getBySlug($slug);
        if ($existingThread) {
            $slug = $slug . '-' . time();
        }
        
        $threadData = [
            'subforum_id' => $subforumId,
            'user_id' => $userId,
            'title' => $title,
            'slug' => $slug,
            'is_sticky' => 0,
            'is_locked' => 0,
            'featured_image' => $featuredImage,
            'created_at' => date('Y-m-d H:i:s'),
            'last_post_at' => date('Y-m-d H:i:s')
        ];
        
        $threadId = $threadModel->create($threadData);
        
        if ($threadId) {
            // Create first post
            $postModel = new ForumPost();
            $postData = [
                'thread_id' => $threadId,
                'user_id' => $userId,
                'content' => $content,
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            $postId = $postModel->create($postData);
            
            if ($postId) {
                setFlashMessage('Thread created successfully.', 'success');
                redirect(BASE_URL . '/forum_thread.php?slug=' . $slug);
            } else {
                // If post creation fails, delete the thread
                $threadModel->delete($threadId);
                setFlashMessage('Failed to create thread. Please try again.', 'danger');
            }
        } else {
            setFlashMessage('Failed to create thread. Please try again.', 'danger');
        }
    }
}

// Page title
$pageTitle = 'Create Thread in ' . $subforum['name'];

// Include header
include_once __DIR__ . '/includes/header.php';
?>

<?php include_once __DIR__ . '/config/tinymce_config.php'; ?>
<!-- Add TinyMCE -->
<script src="https://cdn.jsdelivr.net/npm/tinymce@6.8.2/tinymce.min.js" referrerpolicy="origin"></script>
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

<div class="container mt-4">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/forum.php">Forum</a></li>
            <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/forum_subforum.php?slug=<?php echo $subforum['slug']; ?>"><?php echo $subforum['name']; ?></a></li>
            <li class="breadcrumb-item active" aria-current="page">Create Thread</li>
        </ol>
    </nav>
    
    <div class="card mb-4 shadow">
        <div class="card-header bg-primary text-white">
            <h1 class="h4 mb-0">Create New Thread in <?php echo htmlspecialchars($subforum['name']); ?></h1>
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
            
            <form method="post" action="" id="thread-form" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?php echo Security::generateCSRFToken(); ?>">
                
                <div class="mb-3">
                    <label for="title" class="form-label fw-bold">Thread Title</label>
                    <input type="text" class="form-control form-control-lg" id="title" name="title" 
                           value="<?php echo isset($_POST['title']) ? htmlspecialchars($_POST['title']) : ''; ?>" 
                           required maxlength="100" placeholder="Enter a descriptive title">
                    <div class="form-text">A clear, specific title helps others find your thread</div>
                </div>
                
                <div class="mb-3">
                    <label for="tags" class="form-label fw-bold">Tags</label>
                    <input type="text" class="form-control" id="tags" name="tags" 
                           value="<?php echo isset($_POST['tags']) ? htmlspecialchars($_POST['tags']) : ''; ?>" 
                           placeholder="tag1, tag2, tag3">
                    <div class="form-text">Separate tags with commas (optional)</div>
                </div>
                
                <div class="mb-3">
                    <label for="thread_image" class="form-label fw-bold">Featured Image (Optional)</label>
                    <input type="file" class="form-control" id="thread_image" name="thread_image" accept="image/*">
                    <div class="form-text">Upload an image to be displayed with your thread (max 2MB)</div>
                </div>
                
                <div class="mb-4">
                    <label for="content" class="form-label fw-bold">Content</label>
                    <textarea class="form-control" id="content" name="content" rows="10" style="width: 100%; min-height: 300px;"><?php echo isset($_POST['content']) ? htmlspecialchars($_POST['content']) : ''; ?></textarea>
                    <div class="form-text">Format your post using the editor tools above</div>
                </div>
                
                <div class="d-flex justify-content-between">
                    <a href="<?php echo BASE_URL; ?>/forum_subforum.php?slug=<?php echo $subforum['slug']; ?>" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-1"></i> Cancel
                    </a>
                    <div>
                        <button type="button" id="preview-btn" class="btn btn-outline-primary me-2">
                            <i class="fas fa-eye me-1"></i> Preview
                        </button>
                        <button type="submit" name="create_thread" class="btn btn-primary">
                            <i class="fas fa-plus-circle me-1"></i> Create Thread
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Preview Modal -->
    <div class="modal fade" id="previewModal" tabindex="-1" aria-labelledby="previewModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="previewModalLabel">Thread Preview</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <h3 id="preview-title"></h3>
                    <hr>
                    <div id="preview-content"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Preview functionality
    const previewBtn = document.getElementById('preview-btn');
    const titleInput = document.getElementById('title');
    const previewTitle = document.getElementById('preview-title');
    const previewContent = document.getElementById('preview-content');
    
    previewBtn.addEventListener('click', function() {
        previewTitle.textContent = titleInput.value || 'Thread Title';
        previewContent.innerHTML = tinymce.get('content').getContent() || 'Thread content will appear here';
        
        const previewModal = new bootstrap.Modal(document.getElementById('previewModal'));
        previewModal.show();
    });
    
    // Auto-save draft
    const threadForm = document.getElementById('thread-form');
    const autoSave = function() {
        const title = titleInput.value;
        const content = tinymce.get('content').getContent();
        const tags = document.getElementById('tags').value;
        
        if (title || content || tags) {
            localStorage.setItem('thread_draft_title', title);
            localStorage.setItem('thread_draft_content', content);
            localStorage.setItem('thread_draft_tags', tags);
            localStorage.setItem('thread_draft_subforum', '<?php echo $subforumId; ?>');
            localStorage.setItem('thread_draft_time', new Date().toISOString());
        }
    };
    
    // Auto-save every 30 seconds
    setInterval(autoSave, 30000);
    
    // Load draft if available for this subforum
    const savedSubforum = localStorage.getItem('thread_draft_subforum');
    if (savedSubforum === '<?php echo $subforumId; ?>') {
        const savedTitle = localStorage.getItem('thread_draft_title');
        const savedContent = localStorage.getItem('thread_draft_content');
        const savedTags = localStorage.getItem('thread_draft_tags');
        
        if (!titleInput.value && savedTitle) {
            titleInput.value = savedTitle;
        }
        
        if (savedTags) {
            document.getElementById('tags').value = savedTags;
        }
        
        // Need to wait for TinyMCE to initialize
        if (tinymce.get('content')) {
            if (savedContent) {
                tinymce.get('content').setContent(savedContent);
            }
            
            tinymce.get('content').on('init', function() {
                if (savedContent) {
                    tinymce.get('content').setContent(savedContent);
                }
            });
        }
    }
    
    // Add client-side validation before form submission
    threadForm.addEventListener('submit', function(e) {
        const title = titleInput.value.trim();
        const content = tinymce.get('content') ? tinymce.get('content').getContent().trim() : '';
        
        let isValid = true;
        const errorMessages = [];
        
        if (!title) {
            isValid = false;
            errorMessages.push('Thread title is required.');
        }
        
        if (!content) {
            isValid = false;
            errorMessages.push('Thread content is required.');
        }
        
        if (!isValid) {
            e.preventDefault();
            alert('Please fix the following errors:\n' + errorMessages.join('\n'));
            return false;
        }
        
        // Clear draft when form is submitted successfully
        localStorage.removeItem('thread_draft_title');
        localStorage.removeItem('thread_draft_content');
        localStorage.removeItem('thread_draft_tags');
        localStorage.removeItem('thread_draft_subforum');
        localStorage.removeItem('thread_draft_time');
    });
});
</script>

<?php include_once __DIR__ . '/includes/footer.php'; ?>