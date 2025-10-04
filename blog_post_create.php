<?php
/**
 * Blog Post Create
 * 
 * This page allows authorized users to create new blog posts
 */

// Include initialization file
require_once __DIR__ . '/includes/init.php';

// Check if user is logged in and has permission to create posts
if (!isLoggedIn() || !hasPermission('create_blog_posts')) {
    setFlashMessage('You do not have permission to create blog posts.', 'danger');
    redirect(BASE_URL . '/blog.php');
}

// Initialize models
$blogModel = new BlogPost();
$categoryModel = new BlogCategory();

// Get categories for dropdown
$categories = $categoryModel->getAll();

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate input
    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $categoryId = (int)($_POST['category_id'] ?? 0);
    $status = $_POST['status'] ?? 'draft';
    $featuredImage = $_FILES['featured_image'] ?? null;
    
    $errors = [];
    
    // Validate title
    if (empty($title)) {
        $errors[] = 'Title is required.';
    } elseif (strlen($title) > 255) {
        $errors[] = 'Title must be less than 255 characters.';
    }
    
    // Validate content
    if (empty($content)) {
        $errors[] = 'Content is required.';
    }
    
    // Validate category
    if ($categoryId <= 0) {
        $errors[] = 'Please select a valid category.';
    } else {
        $category = $categoryModel->getById($categoryId);
        if (!$category) {
            $errors[] = 'Selected category does not exist.';
        }
    }
    
    // Validate status
    $allowedStatuses = ['draft', 'published'];
    if (!in_array($status, $allowedStatuses)) {
        $errors[] = 'Invalid status selected.';
    }
    
    // Process featured image if uploaded
    $featuredImagePath = null;
    if ($featuredImage && $featuredImage['error'] === UPLOAD_ERR_OK) {
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        $maxSize = 2 * 1024 * 1024; // 2MB
        
        // Validate image type
        if (!in_array($featuredImage['type'], $allowedTypes)) {
            $errors[] = 'Only JPG, PNG, and GIF images are allowed.';
        }
        
        // Validate image size
        if ($featuredImage['size'] > $maxSize) {
            $errors[] = 'Image size must be less than 2MB.';
        }
        
        // Upload image if no errors
        if (empty($errors)) {
            $uploadDir = __DIR__ . '/uploads/blog/';
            
            // Create directory if it doesn't exist
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            // Generate unique filename
            $filename = uniqid() . '_' . basename($featuredImage['name']);
            $targetPath = $uploadDir . $filename;
            
            // Move uploaded file
            if (move_uploaded_file($featuredImage['tmp_name'], $targetPath)) {
                $featuredImagePath = 'uploads/blog/' . $filename;
            } else {
                $errors[] = 'Failed to upload image.';
            }
        }
    }
    
    // If no errors, create post
    if (empty($errors)) {
        // Generate slug from title
        $slug = createSlug($title);
        
        // Check if slug already exists
        if ($blogModel->slugExists($slug)) {
            $slug = $slug . '-' . uniqid();
        }
        
        // Prepare post data
        $postData = [
            'title' => $title,
            'slug' => $slug,
            'content' => $content,
            'category_id' => $categoryId,
            'user_id' => $_SESSION['user_id'],
            'status' => $status,
            'featured_image' => $featuredImagePath,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        // Add published_at if status is published
        if ($status === 'published') {
            $postData['published_at'] = date('Y-m-d H:i:s');
        }
        
        // Create post
        $postId = $blogModel->create($postData);
        
        if ($postId) {
            setFlashMessage('Blog post created successfully.', 'success');
            redirect(BASE_URL . '/blog_post.php?slug=' . $slug);
        } else {
            $errors[] = 'Failed to create blog post.';
        }
    }
}

// Page title
$pageTitle = 'Create Blog Post';

// Include header
include_once __DIR__ . '/includes/header.php';
?>

<div class="container py-4">
    <div class="row">
        <div class="col-lg-8 mx-auto">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h1 class="h3 mb-0">Create New Blog Post</h1>
                </div>
                <div class="card-body">
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                <?php foreach ($errors as $error): ?>
                                    <li><?php echo htmlspecialchars($error); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                    
                    <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label for="title" class="form-label">Title <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="title" name="title" value="<?php echo htmlspecialchars($title ?? ''); ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="category_id" class="form-label">Category <span class="text-danger">*</span></label>
                            <select class="form-select" id="category_id" name="category_id" required>
                                <option value="">Select Category</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo $category['category_id']; ?>" <?php echo (isset($categoryId) && $categoryId == $category['category_id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($category['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="content" class="form-label">Content <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="content" name="content" rows="10" required><?php echo htmlspecialchars($content ?? ''); ?></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label for="featured_image" class="form-label">Featured Image</label>
                            <input type="file" class="form-control" id="featured_image" name="featured_image">
                            <div class="form-text">Max file size: 2MB. Allowed formats: JPG, PNG, GIF</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select" id="status" name="status">
                                <option value="draft" <?php echo (isset($status) && $status == 'draft') ? 'selected' : ''; ?>>Draft</option>
                                <option value="published" <?php echo (isset($status) && $status == 'published') ? 'selected' : ''; ?>>Published</option>
                            </select>
                        </div>
                        
                        <div class="d-flex justify-content-between">
                            <a href="<?php echo BASE_URL; ?>/blog.php" class="btn btn-secondary">Cancel</a>
                            <button type="submit" class="btn btn-primary">Create Post</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Include footer
include_once __DIR__ . '/includes/footer.php';
?>