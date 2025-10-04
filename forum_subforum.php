<?php
/**
 * Forum subforum page
 * 
 * This page displays the threads in a subforum
 */

// Include initialization file
require_once __DIR__ . '/includes/init.php';

// Get subforum slug
$slug = $_GET['slug'] ?? '';

if (empty($slug)) {
    setFlashMessage('Subforum not found.', 'danger');
    redirect(BASE_URL . '/forum.php');
}

// Initialize models
$subforumModel = new ForumSubforum();
$threadModel = new ForumThread();

// Get subforum
$subforum = $subforumModel->getBySlug($slug);

if (!$subforum) {
    setFlashMessage('Subforum not found.', 'danger');
    redirect(BASE_URL . '/forum.php');
}

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 20;
$offset = ($page - 1) * $perPage;

// Get threads
$threads = $threadModel->getBySubforumId($subforum['subforum_id'], $perPage, $offset);
$totalThreads = $threadModel->countBySubforumId($subforum['subforum_id']);

// Calculate total pages
$totalPages = ceil($totalThreads / $perPage);

// Page title
$pageTitle = $subforum['name'];

// Include header
include_once __DIR__ . '/includes/header.php';
?>

<div class="container mt-4">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/forum.php">Forum</a></li>
            <li class="breadcrumb-item active" aria-current="page"><?php echo $subforum['name']; ?></li>
        </ol>
    </nav>
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><?php echo $subforum['name']; ?></h1>
        
        <?php if (isLoggedIn()): ?>
            <a href="<?php echo BASE_URL; ?>/forum_thread_create.php?subforum_id=<?php echo $subforum['subforum_id']; ?>" class="btn btn-primary">
                <i class="fas fa-plus me-1"></i> New Thread
            </a>
        <?php endif; ?>
    </div>
    
    <?php if (!empty($subforum['description'])): ?>
        <div class="alert alert-info mb-4"><?php echo $subforum['description']; ?></div>
    <?php endif; ?>
    
    <?php if (!empty($threads)): ?>
        <div class="card mb-4">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Thread</th>
                                <th class="text-center">Replies</th>
                                <th class="text-center">Views</th>
                                <th>Last Post</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($threads as $thread): ?>
                                <tr <?php echo $thread['is_sticky'] ? 'class="table-warning"' : ''; ?>>
                                    <td>
                                        <?php if ($thread['is_sticky']): ?>
                                            <span class="badge bg-warning text-dark me-1">Sticky</span>
                                        <?php endif; ?>
                                        
                                        <?php if ($thread['is_locked']): ?>
                                            <span class="badge bg-secondary me-1">Locked</span>
                                        <?php endif; ?>
                                        
                                        <a href="<?php echo BASE_URL; ?>/forum_thread.php?slug=<?php echo $thread['slug']; ?>" class="text-decoration-none fw-bold">
                                            <?php echo $thread['title']; ?>
                                        </a>
                                        
                                        <div class="small text-muted">
                                            by <a href="<?php echo BASE_URL; ?>/profile.php?username=<?php echo $thread['username']; ?>" class="text-decoration-none"><?php echo $thread['username']; ?></a>
                                            <span class="ms-2"><?php echo formatDate($thread['created_at']); ?></span>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <?php
                                        $postCount = $thread['post_count'] ?? 0;
                                        echo $postCount > 0 ? $postCount - 1 : 0; // Subtract 1 for the initial post
                                        ?>
                                    </td>
                                    <td class="text-center"><?php echo $thread['view_count'] ?? 0; ?></td>
                                    <td>
                                        <?php if (!empty($thread['last_post'])): ?>
                                            <div class="small">
                                                by <a href="<?php echo BASE_URL; ?>/profile.php?username=<?php echo $thread['last_post_username']; ?>" class="text-decoration-none"><?php echo $thread['last_post_username']; ?></a>
                                            </div>
                                            <div class="small text-muted">
                                                <?php echo formatDate($thread['last_post_at']); ?>
                                            </div>
                                        <?php else: ?>
                                            <div class="small text-muted">No replies yet</div>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
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
                            <a class="page-link" href="<?php echo BASE_URL; ?>/forum_subforum.php?slug=<?php echo $slug; ?>&page=<?php echo $page - 1; ?>" aria-label="Previous">
                                <span aria-hidden="true">&laquo;</span>
                            </a>
                        </li>
                    <?php else: ?>
                        <li class="page-item disabled">
                            <span class="page-link" aria-hidden="true">&laquo;</span>
                        </li>
                    <?php endif; ?>
                    
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                            <a class="page-link" href="<?php echo BASE_URL; ?>/forum_subforum.php?slug=<?php echo $slug; ?>&page=<?php echo $i; ?>">
                                <?php echo $i; ?>
                            </a>
                        </li>
                    <?php endfor; ?>
                    
                    <?php if ($page < $totalPages): ?>
                        <li class="page-item">
                            <a class="page-link" href="<?php echo BASE_URL; ?>/forum_subforum.php?slug=<?php echo $slug; ?>&page=<?php echo $page + 1; ?>" aria-label="Next">
                                <span aria-hidden="true">&raquo;</span>
                            </a>
                        </li>
                    <?php else: ?>
                        <li class="page-item disabled">
                            <span class="page-link" aria-hidden="true">&raquo;</span>
                        </li>
                    <?php endif; ?>
                </ul>
            </nav>
        <?php endif; ?>
    <?php else: ?>
        <div class="alert alert-info mb-4">No threads found in this subforum.</div>
    <?php endif; ?>
    
    <?php if (isLoggedIn()): ?>
        <div class="mt-4">
            <a href="<?php echo BASE_URL; ?>/forum_thread_create.php?subforum_id=<?php echo $subforum['subforum_id']; ?>" class="btn btn-primary">
                <i class="fas fa-plus me-1"></i> New Thread
            </a>
        </div>
    <?php else: ?>
        <div class="alert alert-warning mt-4">
            Please <a href="<?php echo BASE_URL; ?>/login.php">login</a> to create a new thread.
        </div>
    <?php endif; ?>
    
    <div class="mt-4">
        <a href="<?php echo BASE_URL; ?>/forum.php" class="btn btn-outline-primary">
            <i class="fas fa-arrow-left me-1"></i> Back to Forum
        </a>
    </div>
</div>

<?php
// Include footer
include_once __DIR__ . '/includes/footer.php';
?>