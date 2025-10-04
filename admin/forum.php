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
        <?php include __DIR__ . '/includes/admin_sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Forum Moderation</h1>
            </div>
            
            <?php include __DIR__ . '/../includes/flash_messages.php'; ?>
            
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Thread Management</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Title</th>
                                    <th>Author</th>
                                    <th>Subforum</th>
                                    <th>Created</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($threads)): ?>
                                    <?php foreach ($threads as $thread): ?>
                                        <tr <?php echo $thread['is_sticky'] ? 'class="table-warning"' : ''; ?>>
                                            <td>
                                                <a href="<?php echo BASE_URL; ?>/forum_thread.php?slug=<?php echo $thread['slug']; ?>" target="_blank">
                                                    <?php echo htmlspecialchars($thread['title']); ?>
                                                </a>
                                            </td>
                                            <td><?php echo htmlspecialchars($thread['username']); ?></td>
                                            <td><?php echo htmlspecialchars($thread['subforum_name']); ?></td>
                                            <td><?php echo formatDate($thread['created_at']); ?></td>
                                            <td>
                                                <?php if ($thread['is_sticky']): ?>
                                                    <span class="badge bg-warning text-dark">Pinned</span>
                                                <?php endif; ?>
                                                
                                                <?php if ($thread['is_locked']): ?>
                                                    <span class="badge bg-danger">Locked</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="btn-group">
                                                    <!-- Pin/Unpin Button -->
                                                    <form action="<?php echo BASE_URL; ?>/admin/forum.php" method="post" class="d-inline me-1">
                                                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                                        <input type="hidden" name="thread_id" value="<?php echo $thread['thread_id']; ?>">
                                                        <button type="submit" name="toggle_sticky" class="btn btn-sm <?php echo $thread['is_sticky'] ? 'btn-warning' : 'btn-outline-warning'; ?>" title="<?php echo $thread['is_sticky'] ? 'Unpin' : 'Pin'; ?>">
                                                            <i class="fas fa-thumbtack"></i>
                                                        </button>
                                                    </form>
                                                    
                                                    <!-- Lock/Unlock Button -->
                                                    <form action="<?php echo BASE_URL; ?>/admin/forum.php" method="post" class="d-inline me-1">
                                                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                                        <input type="hidden" name="thread_id" value="<?php echo $thread['thread_id']; ?>">
                                                        <button type="submit" name="toggle_locked" class="btn btn-sm <?php echo $thread['is_locked'] ? 'btn-danger' : 'btn-outline-danger'; ?>" title="<?php echo $thread['is_locked'] ? 'Unlock' : 'Lock'; ?>">
                                                            <i class="fas <?php echo $thread['is_locked'] ? 'fa-unlock' : 'fa-lock'; ?>"></i>
                                                        </button>
                                                    </form>
                                                    
                                                    <!-- Move Thread Button -->
                                                    <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#moveThreadModal<?php echo $thread['thread_id']; ?>" title="Move">
                                                        <i class="fas fa-exchange-alt"></i>
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
                <nav aria-label="Page navigation">
                    <ul class="pagination justify-content-center">
                        <?php if ($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="<?php echo BASE_URL; ?>/admin/forum.php?page=<?php echo $page - 1; ?>" aria-label="Previous">
                                    <span aria-hidden="true">&laquo;</span>
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
                                    <span aria-hidden="true">&raquo;</span>
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            <?php endif; ?>
        </main>
    </div>
</div>

<?php include __DIR__ . '/includes/admin_footer.php'; ?>