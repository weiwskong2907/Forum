<?php
/**
 * Admin Forum Management
 * 
 * Provides tools for moderating forum threads (pin, lock, move)
 */

// Include initialization file
require_once __DIR__ . '/../includes/init.php';

// Check if user is admin
if (!isAdmin()) {
    setFlashMessage('You do not have permission to access this page.', 'danger');
    redirect(BASE_URL . '/index.php');
}

// Initialize models
$threadModel = new ForumThread();
$subforumModel = new ForumSubforum();
$categoryModel = new ForumCategory();

// Process actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        setFlashMessage('Invalid request. Please try again.', 'danger');
        redirect(BASE_URL . '/admin/forum.php');
    }
    
    // Toggle sticky status
    if (isset($_POST['toggle_sticky'])) {
        $threadId = (int)$_POST['thread_id'];
        if ($threadModel->toggleSticky($threadId)) {
            setFlashMessage('Thread sticky status updated successfully.', 'success');
        } else {
            setFlashMessage('Failed to update thread sticky status.', 'danger');
        }
        redirect(BASE_URL . '/admin/forum.php');
    }
    
    // Toggle locked status
    if (isset($_POST['toggle_locked'])) {
        $threadId = (int)$_POST['thread_id'];
        if ($threadModel->toggleLocked($threadId)) {
            setFlashMessage('Thread locked status updated successfully.', 'success');
        } else {
            setFlashMessage('Failed to update thread locked status.', 'danger');
        }
        redirect(BASE_URL . '/admin/forum.php');
    }
    
    // Move thread
    if (isset($_POST['move_thread'])) {
        $threadId = (int)$_POST['thread_id'];
        $newSubforumId = (int)$_POST['new_subforum_id'];
        
        if ($threadModel->moveThread($threadId, $newSubforumId)) {
            setFlashMessage('Thread moved successfully.', 'success');
        } else {
            setFlashMessage('Failed to move thread.', 'danger');
        }
        redirect(BASE_URL . '/admin/forum.php');
    }
}

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 20;
$offset = ($page - 1) * $perPage;

// Get all threads with subforum info
$query = "SELECT t.*, s.name as subforum_name, u.username 
         FROM forum_threads t 
         JOIN forum_subforums s ON t.subforum_id = s.subforum_id 
         JOIN users u ON t.user_id = u.user_id 
         ORDER BY t.created_at DESC 
         LIMIT ?, ?";
$threads = Database::getInstance()->fetchAll($query, [$offset, $perPage]);

// Count total threads
$totalThreads = $threadModel->countAll();
$totalPages = ceil($totalThreads / $perPage);

// Get all subforums for move dropdown
$subforums = $subforumModel->getAll();

// Page title
$pageTitle = 'Forum Moderation';
?>

<?php include __DIR__ . '/includes/admin_header.php'; ?>

<div class="container-fluid">
    <div class="row">
        <main class="col-12 px-md-4 py-4">
            <div class="row">
                <div class="col-12">
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0 d-flex align-items-center">
                                <i class="bi bi-chat-square-text me-2"></i> Forum Moderation
                            </h5>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="row mb-4">
                <div class="col-md-6 col-xl-3 mb-4 mb-xl-0">
                    <div class="card shadow h-100 py-2" style="border-left: 4px solid #4e73df; transition: transform 0.3s;">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Threads</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo count($threads); ?></div>
                                </div>
                                <div class="col-auto">
                                    <i class="bi bi-chat-square-text fs-1 text-primary opacity-50"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <style>
                    .card:hover {
                        transform: translateY(-5px);
                        box-shadow: 0 .5rem 1rem rgba(0,0,0,.15)!important;
                    }
                    .table-hover tbody tr:hover {
                        background-color: rgba(78, 115, 223, 0.05);
                    }
                    .card-header {
                        border-bottom: 0;
                    }
                    .badge {
                        font-weight: 500;
                        padding: 0.5em 0.8em;
                    }
                    .table th {
                        font-weight: 600;
                        border-top: 0;
                    }
                    </style>
                </div>
                
                <div class="col-md-6 col-xl-3 mb-4 mb-xl-0">
                    <div class="card shadow h-100 py-2" style="border-left: 4px solid #1cc88a; transition: transform 0.3s;">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Active Discussions</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo count(array_filter($threads, function($t) { return !$t['is_locked']; })); ?></div>
                                </div>
                                <div class="col-auto">
                                    <i class="bi bi-chat-dots fs-1 text-success opacity-50"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6 col-xl-3 mb-4 mb-xl-0">
                    <div class="card shadow h-100 py-2" style="border-left: 4px solid #f6c23e; transition: transform 0.3s;">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Pinned Threads</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo count(array_filter($threads, function($t) { return $t['is_sticky']; })); ?></div>
                                </div>
                                <div class="col-auto">
                                    <i class="bi bi-pin-angle fs-1 text-warning opacity-50"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6 col-xl-3 mb-4 mb-xl-0">
                    <div class="card shadow h-100 py-2" style="border-left: 4px solid #e74a3b; transition: transform 0.3s;">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">Locked Threads</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo count(array_filter($threads, function($t) { return $t['is_locked']; })); ?></div>
                                </div>
                                <div class="col-auto">
                                    <i class="bi bi-lock fs-1 text-danger opacity-50"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <?php displayFlashMessages(); ?>
            
            <!-- Filter Modal -->
            <div class="modal fade" id="filterModal" tabindex="-1" aria-labelledby="filterModalLabel" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header bg-primary text-white">
                            <h5 class="modal-title" id="filterModalLabel">Filter Threads</h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <form>
                                <div class="mb-3">
                                    <label for="filterStatus" class="form-label">Status</label>
                                    <select class="form-select" id="filterStatus">
                                        <option value="">All</option>
                                        <option value="pinned">Pinned</option>
                                        <option value="locked">Locked</option>
                                        <option value="active">Active</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label for="filterSubforum" class="form-label">Subforum</label>
                                    <select class="form-select" id="filterSubforum">
                                        <option value="">All</option>
                                        <!-- Subforums will be populated dynamically -->
                                    </select>
                                </div>
                            </form>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            <button type="button" class="btn btn-primary">Apply Filters</button>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card mb-4 shadow">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0"><i class="bi bi-list-task me-2"></i>Thread Management</h5>
                    <div>
                        <a href="#" class="btn btn-sm btn-light" data-bs-toggle="modal" data-bs-target="#filterModal">
                            <i class="bi bi-funnel-fill me-1"></i> Filter
                        </a>
                        <a href="#" class="btn btn-sm btn-light ms-2">
                            <i class="bi bi-download me-1"></i> Export
                        </a>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-primary">
                                <tr>
                                    <th>Title</th>
                                    <th>Author</th>
                                    <th>Subforum</th>
                                    <th>Created</th>
                                    <th>Status</th>
                                    <th class="text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($threads)): ?>
                                    <?php foreach ($threads as $thread): ?>
                                        <tr <?php echo $thread['is_sticky'] ? 'class="table-warning"' : ''; ?>>
                                            <td>
                                                <a href="<?php echo BASE_URL; ?>/forum_thread.php?slug=<?php echo $thread['slug']; ?>" target="_blank" class="fw-bold text-decoration-none">
                                                    <?php echo htmlspecialchars($thread['title']); ?>
                                                </a>
                                            </td>
                                            <td><span class="badge bg-secondary"><?php echo htmlspecialchars($thread['username']); ?></span></td>
                                            <td><span class="badge bg-info text-dark"><?php echo htmlspecialchars($thread['subforum_name']); ?></span></td>
                                            <td><?php echo formatDate($thread['created_at']); ?></td>
                                            <td>
                                                <?php if ($thread['is_sticky']): ?>
                                                    <span class="badge bg-warning text-dark">Pinned</span>
                                                <?php endif; ?>
                                                
                                                <?php if ($thread['is_locked']): ?>
                                                    <span class="badge bg-danger">Locked</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-center">
                                                <div class="btn-group">
                                                    <!-- Pin/Unpin Button -->
                                                    <form action="<?php echo BASE_URL; ?>/admin/forum.php" method="post" class="d-inline me-1">
                                                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                                        <input type="hidden" name="thread_id" value="<?php echo $thread['thread_id']; ?>">
                                                        <button type="submit" name="toggle_sticky" class="btn btn-sm <?php echo $thread['is_sticky'] ? 'btn-warning' : 'btn-outline-warning'; ?>" title="<?php echo $thread['is_sticky'] ? 'Unpin' : 'Pin'; ?>">
                                                            <i class="bi bi-pin-angle"></i>
                                                        </button>
                                                    </form>
                                                    
                                                    <!-- Lock/Unlock Button -->
                                                    <form action="<?php echo BASE_URL; ?>/admin/forum.php" method="post" class="d-inline me-1">
                                                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                                        <input type="hidden" name="thread_id" value="<?php echo $thread['thread_id']; ?>">
                                                        <button type="submit" name="toggle_locked" class="btn btn-sm <?php echo $thread['is_locked'] ? 'btn-danger' : 'btn-outline-danger'; ?>" title="<?php echo $thread['is_locked'] ? 'Unlock' : 'Lock'; ?>">
                                                            <i class="bi <?php echo $thread['is_locked'] ? 'bi-unlock' : 'bi-lock'; ?>"></i>
                                                        </button>
                                                    </form>
                                                    
                                                    <!-- Move Thread Button -->
                                                    <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#moveThreadModal<?php echo $thread['thread_id']; ?>" title="Move">
                                                        <i class="bi bi-arrow-left-right"></i>
                                                    </button>
                                                    
                                                    <!-- Move Thread Modal -->
                                                    <div class="modal fade" id="moveThreadModal<?php echo $thread['thread_id']; ?>" tabindex="-1" aria-labelledby="moveThreadModalLabel<?php echo $thread['thread_id']; ?>" aria-hidden="true">
                                                        <div class="modal-dialog">
                                                            <div class="modal-content">
                                                                <form action="<?php echo BASE_URL; ?>/admin/forum.php" method="post">
                                                                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                                                    <input type="hidden" name="thread_id" value="<?php echo $thread['thread_id']; ?>">
                                                                    
                                                                    <div class="modal-header">
                                                                        <h5 class="modal-title" id="moveThreadModalLabel<?php echo $thread['thread_id']; ?>">Move Thread</h5>
                                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                                    </div>
                                                                    
                                                                    <div class="modal-body">
                                                                        <div class="mb-3">
                                                                            <label for="new_subforum_id" class="form-label">Select Destination Subforum</label>
                                                                            <select class="form-select" id="new_subforum_id" name="new_subforum_id" required>
                                                                                <?php foreach ($subforums as $subforum): ?>
                                                                                    <option value="<?php echo $subforum['subforum_id']; ?>" <?php echo ($subforum['subforum_id'] == $thread['subforum_id']) ? 'selected' : ''; ?>>
                                                                                        <?php echo htmlspecialchars($subforum['name']); ?>
                                                                                    </option>
                                                                                <?php endforeach; ?>
                                                                            </select>
                                                                        </div>
                                                                    </div>
                                                                    
                                                                    <div class="modal-footer">
                                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                                        <button type="submit" name="move_thread" class="btn btn-primary">Move Thread</button>
                                                                    </div>
                                                                </form>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="text-center py-3">No threads found.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
                <div class="card-footer bg-light">
                    <nav aria-label="Page navigation">
                        <ul class="pagination pagination-sm justify-content-center mb-0">
                            <?php if ($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="<?php echo BASE_URL; ?>/admin/forum.php?page=<?php echo $page - 1; ?>" aria-label="Previous">
                                        <i class="bi bi-chevron-left"></i>
                                    </a>
                                </li>
                            <?php endif; ?>
                            
                            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                    <a class="page-link" href="<?php echo BASE_URL; ?>/admin/forum.php?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                                </li>
                            <?php endfor; ?>
                            
                            <?php if ($page < $totalPages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="<?php echo BASE_URL; ?>/admin/forum.php?page=<?php echo $page + 1; ?>" aria-label="Next">
                                        <i class="bi bi-chevron-right"></i>
                                    </a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                </div>
            <?php endif; ?>
        </main>
    </div>
</div>

<?php include __DIR__ . '/includes/admin_footer.php'; ?>