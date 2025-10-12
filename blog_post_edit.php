<?php
/**
 * Blog Post Edit
 * 
 * This page allows authorized users to edit existing blog posts
 */

// Include initialization file
require_once __DIR__ . '/includes/init.php';

// Check if post ID is provided
$postId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($postId <= 0) {
    setFlashMessage('Invalid post ID.', 'danger');
    redirect(BASE_URL . '/blog.php');
}

// Initialize models
$blogModel = new BlogPost();
$categoryModel = new BlogCategory();

// Get post data
$post = $blogModel->getById($postId);

if (!$post) {
    setFlashMessage('Post not found.', 'danger');
    redirect(BASE_URL . '/blog.php');
}

// Check if user is logged in and has permission to edit posts
if (!isLoggedIn() || (!hasPermission('edit_blog_posts') && $post['user_id'] != $_SESSION['user_id'])) {
    setFlashMessage('You do not have permission to edit this post.', 'danger');
    redirect(BASE_URL . '/blog.php');
}

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
    $customSlug = trim($_POST['custom_slug'] ?? '');
    $metaTitle = trim($_POST['meta_title'] ?? '');
    $metaDescription = trim($_POST['meta_description'] ?? '');
    
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
    $featuredImagePath = isset($post['featured_image']) ? $post['featured_image'] : ''; // Keep existing image by default
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
                
                // Delete old image if it exists
                if (isset($post['featured_image']) && $post['featured_image'] && file_exists(__DIR__ . '/' . $post['featured_image'])) {
                    unlink(__DIR__ . '/' . $post['featured_image']);
                }
            } else {
                $errors[] = 'Failed to upload image.';
            }
        }
    }
    
    // If no errors, update post
    if (empty($errors)) {
        // Check if title changed, if so, update slug
        if ($title !== $post['title']) {
            // Generate slug from title
            $slug = createSlug($title);
            
            // Check if slug already exists (excluding current post)
            if ($blogModel->slugExistsExcept($slug, $postId)) {
                $slug = $slug . '-' . uniqid();
            }
        } else {
            $slug = $post['slug']; // Keep existing slug
        }
        
        // Prepare post data
        $postData = [
            'title' => $title,
            'slug' => $slug,
            'content' => $content,
            'category_id' => $categoryId,
            'status' => $status,
            'custom_slug' => $customSlug,
            'meta_title' => $metaTitle,
            'meta_description' => $metaDescription,
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        // Add published_at if status changed to published
        if ($status === 'published' && $post['status'] !== 'published') {
            $postData['published_at'] = date('Y-m-d H:i:s');
        }
        
        // Update post
        $success = $blogModel->update($postId, $postData);
        
        if ($success) {
            setFlashMessage('Blog post updated successfully.', 'success');
            redirect(BASE_URL . '/blog_post.php?slug=' . $slug);
        } else {
            $errors[] = 'Failed to update blog post.';
        }
    }
} else {
    // Pre-fill form with existing data
    $title = $post['title'];
    $content = $post['content'];
    $categoryId = $post['category_id'];
    $status = $post['status'];
}

// Page title
$pageTitle = 'Edit Blog Post';

// Include header
include_once __DIR__ . '/includes/header.php';
?>

<div class="container py-4">
    <div class="row">
        <div class="col-lg-8 mx-auto">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h1 class="h3 mb-0">Edit Blog Post</h1>
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
                    
                    <form action="<?php echo htmlspecialchars($_SERVER['REQUEST_URI']); ?>" method="post" enctype="multipart/form-data">
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
                            <?php if (!empty($post['featured_image'])): ?>
                                <div class="mb-2">
                                    <img src="<?php echo htmlspecialchars(BASE_URL . '/' . $post['featured_image']); ?>" alt="Current featured image" class="img-thumbnail" style="max-height: 200px;">
                                    <p class="form-text">Current image. Upload a new one to replace it.</p>
                                </div>
                            <?php endif; ?>
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
                        
                        <!-- SEO Settings -->
                        <div class="card mb-3">
                            <div class="card-header bg-light">
                                <h5 class="mb-0">SEO Settings</h5>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label for="custom_slug" class="form-label">Custom URL Slug</label>
                                    <input type="text" class="form-control" id="custom_slug" name="custom_slug" value="<?php echo htmlspecialchars($post['custom_slug'] ?? ''); ?>">
                                    <div class="form-text">Leave empty to use auto-generated slug from title.</div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="meta_title" class="form-label">Meta Title</label>
                                    <input type="text" class="form-control" id="meta_title" name="meta_title" value="<?php echo htmlspecialchars($post['meta_title'] ?? ''); ?>">
                                    <div class="form-text">Leave empty to use post title.</div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="meta_description" class="form-label">Meta Description</label>
                                    <textarea class="form-control" id="meta_description" name="meta_description" rows="3"><?php echo htmlspecialchars($post['meta_description'] ?? ''); ?></textarea>
                                    <div class="form-text">Recommended length: 150-160 characters.</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="d-flex justify-content-between">
                            <a href="<?php echo BASE_URL; ?>/blog_post.php?slug=<?php echo htmlspecialchars($post['slug']); ?>" class="btn btn-secondary">Cancel</a>
                            <button type="submit" class="btn btn-primary">Update Post</button>
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