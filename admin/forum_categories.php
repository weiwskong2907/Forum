<?php
/**
 * Admin Forum Categories Management
 * 
 * Provides tools for managing forum categories (create, edit, delete)
 */

// Include initialization file
require_once __DIR__ . '/../includes/init.php';

// Check if user is admin
if (!isAdmin()) {
    setFlashMessage('You do not have permission to access this page.', 'danger');
    redirect(BASE_URL . '/index.php');
}

// Initialize models
$categoryModel = new ForumCategory();

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!Security::verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        setFlashMessage('Invalid request. Please try again.', 'danger');
        redirect(BASE_URL . '/admin/forum_categories.php');
    }
    
    // Add new category
    if (isset($_POST['add_category'])) {
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $displayOrder = (int)($_POST['display_order'] ?? 0);
        
        if (empty($name)) {
            setFlashMessage('Category name is required.', 'danger');
        } else {
            $categoryId = $categoryModel->create([
                'name' => $name,
                'description' => $description,
                'display_order' => $displayOrder
            ]);
            
            if ($categoryId) {
                setFlashMessage('Category created successfully.', 'success');
                redirect(BASE_URL . '/admin/forum_categories.php');
            } else {
                setFlashMessage('Failed to create category.', 'danger');
            }
        }
    }
    
    // Update category
    if (isset($_POST['update_category'])) {
        $categoryId = (int)$_POST['category_id'];
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $displayOrder = (int)($_POST['display_order'] ?? 0);
        
        if (empty($name)) {
            setFlashMessage('Category name is required.', 'danger');
        } else {
            $success = $categoryModel->update($categoryId, [
                'name' => $name,
                'description' => $description,
                'display_order' => $displayOrder
            ]);
            
            if ($success) {
                setFlashMessage('Category updated successfully.', 'success');
                redirect(BASE_URL . '/admin/forum_categories.php');
            } else {
                setFlashMessage('Failed to update category.', 'danger');
            }
        }
    }
    
    // Delete category
    if (isset($_POST['delete_category'])) {
        $categoryId = (int)$_POST['category_id'];
        
        // Check if category has subforums
        $subforums = $subforumModel->getByCategoryId($categoryId);
        if (!empty($subforums)) {
            setFlashMessage('Cannot delete category that contains subforums. Please move or delete the subforums first.', 'danger');
        } else {
            if ($categoryModel->delete($categoryId)) {
                setFlashMessage('Category deleted successfully.', 'success');
            } else {
                setFlashMessage('Failed to delete category.', 'danger');
            }
        }
        redirect(BASE_URL . '/admin/forum_categories.php');
    }
}

// Get all categories
$categories = $categoryModel->getAll();

// Include header
$pageTitle = 'Manage Forum Categories';
include __DIR__ . '/includes/admin_header.php';
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3">Manage Forum Categories</h1>
        <a href="<?php echo BASE_URL; ?>/admin/forum.php" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Back to Forum Management
        </a>
    </div>
    
    <?php displayFlashMessages(); ?>
    
    <div class="row">
        <!-- Categories List -->
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-header bg-light">
                    <h2 class="h5 mb-0">Categories</h2>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Description</th>
                                    <th>Order</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($categories)): ?>
                                    <tr>
                                        <td colspan="5" class="text-center py-3">No categories found.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($categories as $category): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($category['category_id']); ?></td>
                                            <td><?php echo htmlspecialchars($category['name']); ?></td>
                                            <td><?php echo htmlspecialchars($category['description']); ?></td>
                                            <td><?php echo htmlspecialchars($category['display_order']); ?></td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <button type="button" class="btn btn-outline-primary edit-category-btn" 
                                                            data-id="<?php echo $category['category_id']; ?>"
                                                            data-name="<?php echo htmlspecialchars($category['name']); ?>"
                                                            data-description="<?php echo htmlspecialchars($category['description']); ?>"
                                                            data-order="<?php echo $category['display_order']; ?>">
                                                        <i class="bi bi-pencil"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-outline-danger delete-category-btn"
                                                            data-id="<?php echo $category['category_id']; ?>"
                                                            data-name="<?php echo htmlspecialchars($category['name']); ?>">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Add/Edit Category Form -->
        <div class="col-md-4">
            <div class="card shadow-sm">
                <div class="card-header bg-light">
                    <h2 class="h5 mb-0" id="form-title">Add New Category</h2>
                </div>
                <div class="card-body">
                    <form id="category-form" method="post" action="<?php echo BASE_URL; ?>/admin/forum_categories.php">
                        <input type="hidden" name="csrf_token" value="<?php echo Security::generateCSRFToken(); ?>">
                        <input type="hidden" name="category_id" id="category-id" value="">
                        
                        <div class="mb-3">
                            <label for="name" class="form-label">Name</label>
                            <input type="text" class="form-control" id="name" name="name" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label for="display-order" class="form-label">Display Order</label>
                            <input type="number" class="form-control" id="display-order" name="display_order" value="0" min="0">
                            <div class="form-text">Lower numbers appear first.</div>
                        </div>
                        
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary" id="submit-btn" name="add_category">
                                Add Category
                            </button>
                            <button type="button" class="btn btn-outline-secondary d-none" id="cancel-btn">
                                Cancel
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="delete-modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirm Delete</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete the category "<span id="delete-category-name"></span>"?</p>
                    <p class="text-danger">This action cannot be undone.</p>
                </div>
                <div class="modal-footer">
                    <form method="post" action="<?php echo BASE_URL; ?>/admin/forum_categories.php">
                        <input type="hidden" name="csrf_token" value="<?php echo Security::generateCSRFToken(); ?>">
                        <input type="hidden" name="category_id" id="delete-category-id" value="">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger" name="delete_category">Delete</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Edit category
    const editButtons = document.querySelectorAll('.edit-category-btn');
    const categoryForm = document.getElementById('category-form');
    const formTitle = document.getElementById('form-title');
    const submitBtn = document.getElementById('submit-btn');
    const cancelBtn = document.getElementById('cancel-btn');
    const categoryIdInput = document.getElementById('category-id');
    const nameInput = document.getElementById('name');
    const descriptionInput = document.getElementById('description');
    const displayOrderInput = document.getElementById('display-order');
    
    editButtons.forEach(button => {
        button.addEventListener('click', function() {
            const id = this.dataset.id;
            const name = this.dataset.name;
            const description = this.dataset.description;
            const order = this.dataset.order;
            
            // Update form
            formTitle.textContent = 'Edit Category';
            categoryIdInput.value = id;
            nameInput.value = name;
            descriptionInput.value = description;
            displayOrderInput.value = order;
            submitBtn.textContent = 'Update Category';
            submitBtn.name = 'update_category';
            cancelBtn.classList.remove('d-none');
            
            // Scroll to form on mobile
            if (window.innerWidth < 768) {
                categoryForm.scrollIntoView({ behavior: 'smooth' });
            }
        });
    });
    
    // Cancel edit
    cancelBtn.addEventListener('click', function() {
        resetForm();
    });
    
    // Reset form
    function resetForm() {
        formTitle.textContent = 'Add New Category';
        categoryForm.reset();
        categoryIdInput.value = '';
        submitBtn.textContent = 'Add Category';
        submitBtn.name = 'add_category';
        cancelBtn.classList.add('d-none');
    }
    
    // Delete category
    const deleteButtons = document.querySelectorAll('.delete-category-btn');
    const deleteModal = new bootstrap.Modal(document.getElementById('delete-modal'));
    const deleteCategoryName = document.getElementById('delete-category-name');
    const deleteCategoryId = document.getElementById('delete-category-id');
    
    deleteButtons.forEach(button => {
        button.addEventListener('click', function() {
            const id = this.dataset.id;
            const name = this.dataset.name;
            
            deleteCategoryName.textContent = name;
            deleteCategoryId.value = id;
            deleteModal.show();
        });
    });
});
</script>

<?php include __DIR__ . '/includes/admin_footer.php'; ?>