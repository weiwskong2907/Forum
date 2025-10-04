<?php
require_once '../includes/init.php';

// Check if user is logged in and is an admin
if (!isLoggedIn() || !isAdmin()) {
    setFlashMessage('You do not have permission to access the admin area.', 'danger');
    redirect('../index.php');
}

$blogPostModel = new BlogPost();
$blogCategoryModel = new BlogCategory();

// Handle actions
if (isset($_GET['action']) && isset($_GET['id'])) {
    $action = $_GET['action'];
    $id = (int)$_GET['id'];
    
    if ($action === 'delete' && $id > 0) {
        if ($blogPostModel->delete($id)) {
            setFlashMessage('Blog post deleted successfully.', 'success');
        } else {
            setFlashMessage('Failed to delete blog post.', 'danger');
        }
        redirect('blog_posts.php');
    }
}

// Handle form submission for updating featured image
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_featured_image'])) {
    $postId = (int)$_POST['post_id'];
    $featuredImage = $_POST['featured_image'];
    
    // Update the featured image
    if ($blogPostModel->updateFeaturedImage($postId, $featuredImage)) {
        setFlashMessage('Featured image updated successfully.', 'success');
    } else {
        setFlashMessage('Failed to update featured image.', 'danger');
    }
    redirect('blog_posts.php');
}

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Get all blog posts including drafts for admin
$blogPosts = $blogPostModel->getAll($limit, $offset, 'published_at', 'DESC', true);
$totalPosts = $blogPostModel->getTotalCount();
$totalPages = ceil($totalPosts / $limit);

// Get available images for selection
$blogImages = [];
$blogImagesDir = '../uploads/blog/';
if (is_dir($blogImagesDir)) {
    $files = scandir($blogImagesDir);
    foreach ($files as $file) {
        if ($file !== '.' && $file !== '..' && !is_dir($blogImagesDir . $file)) {
            $blogImages[] = 'uploads/blog/' . $file;
        }
    }
}

$pageTitle = 'Blog Posts Management';
include __DIR__ . '/includes/admin_header.php';
?>

<div class="container mt-4">
    <div class="row">
        <main class="col-12 px-md-4 py-4">
            <!-- Blog Posts Management -->
            <div class="card mb-4 shadow-sm border">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 d-flex align-items-center">
                        <i class="bi bi-file-earmark-text-fill me-2"></i> Blog Posts Management
                    </h5>
                    <a href="<?php echo BASE_URL; ?>/blog_post_create.php" class="btn btn-light btn-sm">
                        <i class="bi bi-plus-circle me-1"></i> Add New Post
                    </a>
                </div>
                <div class="card-body">
                    <?php if (empty($blogPosts)): ?>
                        <div class="alert alert-info">
                            No blog posts found.
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover table-striped">
                                <thead class="table-light">
                                    <tr>
                                        <th>ID</th>
                                        <th>Title</th>
                                        <th>Category</th>
                                        <th>Status</th>
                                        <th>Featured Image</th>
                                        <th>Created</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($blogPosts as $post): 
                                        $category = $blogCategoryModel->getById($post['category_id']);
                                    ?>
                                        <tr>
                                            <td><?php echo $post['post_id']; ?></td>
                                            <td>
                                                <a href="<?php echo BASE_URL; ?>/blog_post.php?slug=<?php echo $post['slug']; ?>" target="_blank">
                                                    <?php echo htmlspecialchars($post['title']); ?>
                                                </a>
                                            </td>
                                            <td><?php echo htmlspecialchars($category['name'] ?? 'Unknown'); ?></td>
                                            <td>
                                                <span class="badge bg-<?php echo $post['status'] === 'published' ? 'success' : 'warning'; ?>">
                                                    <?php echo ucfirst($post['status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if (!empty($post['featured_image'])): ?>
                                                    <img src="<?php echo BASE_URL . '/' . $post['featured_image']; ?>" alt="Featured Image" class="img-thumbnail" style="max-width: 100px;">
                                                <?php else: ?>
                                                    <span class="text-muted">No image</span>
                                                <?php endif; ?>
                                                
                                                <!-- Button to open modal -->
                                                <button type="button" class="btn btn-sm btn-outline-primary ms-2" data-bs-toggle="modal" data-bs-target="#imageModal<?php echo $post['post_id']; ?>">
                                                    <i class="bi bi-image"></i> Change
                                                </button>
                                                
                                                <!-- Modal for image selection -->
                                                <div class="modal fade" id="imageModal<?php echo $post['post_id']; ?>" tabindex="-1" aria-labelledby="imageModalLabel<?php echo $post['post_id']; ?>" aria-hidden="true">
                                                    <div class="modal-dialog modal-lg">
                                                        <div class="modal-content">
                                                            <div class="modal-header">
                                                                <h5 class="modal-title" id="imageModalLabel<?php echo $post['post_id']; ?>">Select Featured Image</h5>
                                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                            </div>
                                                            <div class="modal-body">
                                                                <form action="blog_posts.php" method="post">
                                                                    <input type="hidden" name="post_id" value="<?php echo $post['post_id']; ?>">
                                                                    <input type="hidden" name="update_featured_image" value="1">
                                                                    
                                                                    <div class="mb-3">
                                                                        <label for="featuredImage<?php echo $post['post_id']; ?>" class="form-label">Select Image</label>
                                                                        <select class="form-select" id="featuredImage<?php echo $post['post_id']; ?>" name="featured_image">
                                                                            <option value="">None</option>
                                                                            <?php foreach ($blogImages as $image): ?>
                                                                                <option value="<?php echo $image; ?>" <?php echo $post['featured_image'] === $image ? 'selected' : ''; ?>>
                                                                                    <?php echo basename($image); ?>
                                                                                </option>
                                                                            <?php endforeach; ?>
                                                                        </select>
                                                                    </div>
                                                                    
                                                                    <div class="image-preview-container mt-3">
                                                                        <div class="row">
                                                                            <?php foreach ($blogImages as $image): ?>
                                                                                <div class="col-md-4 mb-3">
                                                                                    <div class="card h-100 <?php echo $post['featured_image'] === $image ? 'border-primary' : ''; ?>">
                                                                                        <img src="<?php echo BASE_URL . '/' . $image; ?>" class="card-img-top" alt="<?php echo basename($image); ?>">
                                                                                        <div class="card-body">
                                                                                            <div class="form-check">
                                                                                                <input class="form-check-input image-selector" type="radio" name="featured_image_radio" id="image<?php echo md5($image); ?>" value="<?php echo $image; ?>" <?php echo $post['featured_image'] === $image ? 'checked' : ''; ?>>
                                                                                                <label class="form-check-label" for="image<?php echo md5($image); ?>">
                                                                                                    <?php echo basename($image); ?>
                                                                                                </label>
                                                                                            </div>
                                                                                        </div>
                                                                                    </div>
                                                                                </div>
                                                                            <?php endforeach; ?>
                                                                        </div>
                                                                    </div>
                                                                    
                                                                    <div class="text-end mt-3">
                                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                                        <button type="submit" class="btn btn-primary">Save Changes</button>
                                                                    </div>
                                                                </form>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td><?php echo date('Y-m-d', strtotime($post['created_at'])); ?></td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <a href="<?php echo BASE_URL; ?>/blog_post_edit.php?id=<?php echo $post['post_id']; ?>" class="btn btn-sm btn-outline-primary">
                                                        <i class="bi bi-pencil"></i> Edit
                                                    </a>
                                                    <a href="<?php echo BASE_URL; ?>/admin/blog_posts.php?action=delete&id=<?php echo $post['post_id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure you want to delete this post?');">
                                                        <i class="bi bi-trash"></i> Delete
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- Pagination -->
                        <?php if ($totalPages > 1): ?>
                        <nav aria-label="Page navigation" class="mt-4">
                            <ul class="pagination justify-content-center">
                                <?php if ($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="<?php echo BASE_URL; ?>/admin/blog_posts.php?page=<?php echo $page - 1; ?>" aria-label="Previous">
                                        <i class="bi bi-chevron-left"></i>
                                    </a>
                                </li>
                                <?php else: ?>
                                <li class="page-item disabled">
                                    <span class="page-link"><i class="bi bi-chevron-left"></i></span>
                                </li>
                                <?php endif; ?>
                                
                                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                    <a class="page-link" href="<?php echo BASE_URL; ?>/admin/blog_posts.php?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                                </li>
                                <?php endfor; ?>
                                
                                <?php if ($page < $totalPages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="<?php echo BASE_URL; ?>/admin/blog_posts.php?page=<?php echo $page + 1; ?>" aria-label="Next">
                                        <i class="bi bi-chevron-right"></i>
                                    </a>
                                </li>
                                <?php else: ?>
                                <li class="page-item disabled">
                                    <span class="page-link"><i class="bi bi-chevron-right"></i></span>
                                </li>
                                <?php endif; ?>
                            </ul>
                        </nav>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</div>

<script>
// Script to sync the radio buttons with the select dropdown
document.addEventListener('DOMContentLoaded', function() {
    const modals = document.querySelectorAll('.modal');
    
    modals.forEach(modal => {
        const select = modal.querySelector('select[name="featured_image"]');
        const radios = modal.querySelectorAll('input[name="featured_image_radio"]');
        
        // Update select when radio is clicked
        radios.forEach(radio => {
            radio.addEventListener('change', function() {
                if (this.checked) {
                    select.value = this.value;
                }
            });
        });
        
        // Update radio when select is changed
        select.addEventListener('change', function() {
            const value = this.value;
            radios.forEach(radio => {
                if (radio.value === value) {
                    radio.checked = true;
                } else {
                    radio.checked = false;
                }
            });
        });
    });
});
</script>

<?php include __DIR__ . '/includes/admin_footer.php'; ?>