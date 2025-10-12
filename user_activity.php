<?php
/**
 * User Activity Feed
 * 
 * Displays user's activity and notifications
 */

// Include initialization file
require_once __DIR__ . '/includes/init.php';

// Redirect if not logged in
if (!isLoggedIn()) {
    redirect(BASE_URL . '/login.php');
}

$userId = $_SESSION['user_id'];

// Initialize models
$db = Database::getInstance();

// Mark notifications as read if requested
if (isset($_GET['mark_read']) && $_GET['mark_read'] == 'all') {
    $db->query("UPDATE user_activities SET is_read = 1 WHERE user_id = ?", [$userId]);
    setFlashMessage('All notifications marked as read.', 'success');
    redirect(BASE_URL . '/user_activity.php');
}

// Mark single notification as read
if (isset($_GET['mark_read']) && is_numeric($_GET['mark_read'])) {
    $activityId = (int)$_GET['mark_read'];
    $db->query("UPDATE user_activities SET is_read = 1 WHERE activity_id = ? AND user_id = ?", 
        [$activityId, $userId]);
    
    // Redirect to the appropriate content if specified
    if (isset($_GET['redirect']) && $_GET['redirect'] == 'content') {
        $activity = $db->fetchRow("SELECT * FROM user_activities WHERE activity_id = ?", [$activityId]);
        
        if ($activity) {
            if ($activity['activity_type'] == 'reply' || $activity['activity_type'] == 'mention') {
                // Get thread slug
                $thread = $db->fetchRow("SELECT slug FROM forum_threads WHERE thread_id = ?", 
                    [$activity['thread_id']]);
                
                if ($thread) {
                    redirect(BASE_URL . '/forum_thread.php?slug=' . $thread['slug'] . '#post-' . $activity['content_id']);
                }
            } elseif ($activity['activity_type'] == 'reaction') {
                // Get post and thread info
                $post = $db->fetchRow("SELECT p.post_id, t.slug FROM forum_posts p 
                                     JOIN forum_threads t ON p.thread_id = t.thread_id 
                                     WHERE p.post_id = ?", [$activity['content_id']]);
                
                if ($post) {
                    redirect(BASE_URL . '/forum_thread.php?slug=' . $post['slug'] . '#post-' . $post['post_id']);
                }
            }
        }
    }
    
    redirect(BASE_URL . '/user_activity.php');
}

// Get user activities with pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 20;
$offset = ($page - 1) * $perPage;

$activities = $db->fetchAll(
    "SELECT a.*, 
     t.title as thread_title, t.slug as thread_slug,
     u.username as actor_username
     FROM user_activities a
     LEFT JOIN forum_threads t ON a.thread_id = t.thread_id
     LEFT JOIN forum_posts p ON a.content_id = p.post_id AND (a.activity_type = 'reply' OR a.activity_type = 'mention')
     LEFT JOIN users u ON p.user_id = u.user_id
     WHERE a.user_id = ?
     ORDER BY a.created_at DESC
     LIMIT ?, ?",
    [$userId, $offset, $perPage]
);

// Count total activities
$totalActivities = $db->fetchRow(
    "SELECT COUNT(*) as count FROM user_activities WHERE user_id = ?", 
    [$userId]
)['count'];

$totalPages = ceil($totalActivities / $perPage);

// Count unread notifications
$unreadCount = $db->fetchRow(
    "SELECT COUNT(*) as count FROM user_activities WHERE user_id = ? AND is_read = 0", 
    [$userId]
)['count'];

// Page title
$pageTitle = 'My Activity';

// Include header
include_once __DIR__ . '/includes/header.php';
?>

<div class="container py-4">
    <div class="row">
        <div class="col-lg-10 mx-auto">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h2">My Activity</h1>
                
                <?php if ($unreadCount > 0): ?>
                <a href="<?php echo BASE_URL; ?>/user_activity.php?mark_read=all" class="btn btn-outline-primary">
                    <i class="bi bi-check-all me-1"></i> Mark All as Read
                </a>
                <?php endif; ?>
            </div>
            
            <?php displayFlashMessages(); ?>
            
            <?php if (empty($activities)): ?>
                <div class="card">
                    <div class="card-body text-center py-5">
                        <i class="bi bi-bell-slash fs-1 text-muted mb-3"></i>
                        <h3>No Activity Yet</h3>
                        <p class="text-muted">You don't have any notifications or activity to display.</p>
                        <a href="<?php echo BASE_URL; ?>/forum.php" class="btn btn-primary mt-2">
                            <i class="bi bi-chat-dots me-1"></i> Browse Forums
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <div class="card">
                    <div class="list-group list-group-flush">
                        <?php foreach ($activities as $activity): ?>
                            <div class="list-group-item <?php echo $activity['is_read'] ? '' : 'bg-light'; ?>">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <?php if ($activity['activity_type'] == 'reply'): ?>
                                            <i class="bi bi-reply-fill text-primary me-2"></i>
                                            <strong><?php echo htmlspecialchars($activity['actor_username']); ?></strong> 
                                            replied to thread 
                                            <a href="<?php echo BASE_URL; ?>/forum_thread.php?slug=<?php echo $activity['thread_slug']; ?>#post-<?php echo $activity['content_id']; ?>">
                                                <?php echo htmlspecialchars($activity['thread_title']); ?>
                                            </a>
                                        <?php elseif ($activity['activity_type'] == 'mention'): ?>
                                            <i class="bi bi-at text-primary me-2"></i>
                                            <strong><?php echo htmlspecialchars($activity['actor_username']); ?></strong> 
                                            mentioned you in 
                                            <a href="<?php echo BASE_URL; ?>/forum_thread.php?slug=<?php echo $activity['thread_slug']; ?>#post-<?php echo $activity['content_id']; ?>">
                                                <?php echo htmlspecialchars($activity['thread_title']); ?>
                                            </a>
                                        <?php elseif ($activity['activity_type'] == 'reaction'): ?>
                                            <i class="bi bi-hand-thumbs-up-fill text-primary me-2"></i>
                                            Someone reacted to your post in 
                                            <a href="<?php echo BASE_URL; ?>/forum_thread.php?slug=<?php echo $activity['thread_slug']; ?>#post-<?php echo $activity['content_id']; ?>">
                                                <?php echo htmlspecialchars($activity['thread_title']); ?>
                                            </a>
                                        <?php endif; ?>
                                        
                                        <div class="text-muted small mt-1">
                                            <i class="bi bi-clock me-1"></i> <?php echo timeAgo($activity['created_at']); ?>
                                        </div>
                                    </div>
                                    
                                    <?php if (!$activity['is_read']): ?>
                                        <div>
                                            <a href="<?php echo BASE_URL; ?>/user_activity.php?mark_read=<?php echo $activity['activity_id']; ?>&redirect=content" 
                                               class="btn btn-sm btn-primary">
                                                <i class="bi bi-eye me-1"></i> View
                                            </a>
                                            <a href="<?php echo BASE_URL; ?>/user_activity.php?mark_read=<?php echo $activity['activity_id']; ?>" 
                                               class="btn btn-sm btn-outline-secondary ms-1">
                                                <i class="bi bi-check"></i>
                                            </a>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <?php if ($totalPages > 1): ?>
                    <nav class="mt-4">
                        <ul class="pagination justify-content-center">
                            <?php if ($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="<?php echo BASE_URL; ?>/user_activity.php?page=<?php echo $page - 1; ?>">
                                        <i class="bi bi-chevron-left"></i> Previous
                                    </a>
                                </li>
                            <?php endif; ?>
                            
                            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                    <a class="page-link" href="<?php echo BASE_URL; ?>/user_activity.php?page=<?php echo $i; ?>">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                            <?php endfor; ?>
                            
                            <?php if ($page < $totalPages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="<?php echo BASE_URL; ?>/user_activity.php?page=<?php echo $page + 1; ?>">
                                        Next <i class="bi bi-chevron-right"></i>
                                    </a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include_once __DIR__ . '/includes/footer.php'; ?>