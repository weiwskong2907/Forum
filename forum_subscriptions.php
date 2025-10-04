<?php
/**
 * Forum Subscriptions page
 * 
 * This page allows users to manage their forum thread subscriptions
 */

// Include initialization file
require_once __DIR__ . '/includes/init.php';

// Redirect if not logged in
$auth = new Auth();
if (!$auth->isLoggedIn()) {
    redirect('login.php');
}

// Get current user ID
$userId = $auth->getUserId();

// Initialize models
$threadModel = new ForumThread();

// Handle unsubscribe action
if (isset($_GET['unsubscribe']) && is_numeric($_GET['unsubscribe'])) {
    $threadId = (int)$_GET['unsubscribe'];
    $threadModel->unsubscribeUser($userId, $threadId);
    setFlashMessage('success', 'You have been unsubscribed from the thread.');
    redirect('forum_subscriptions.php');
}

// Get user's subscriptions
$subscriptions = $threadModel->getUserSubscriptions($userId);

// Page title
$pageTitle = 'My Forum Subscriptions';

// Include header
include_once __DIR__ . '/includes/header.php';
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-12">
            <h1 class="mb-4">My Forum Subscriptions</h1>
            
            <?php displayFlashMessages(); ?>
            
            <div class="card">
                <div class="card-body">
                    <?php if (empty($subscriptions)): ?>
                        <div class="alert alert-info">
                            You are not subscribed to any forum threads.
                        </div>
                        <p class="mt-3">
                            <a href="<?php echo BASE_URL; ?>/forum.php" class="btn btn-primary">
                                <i class="fas fa-comments me-1"></i> Browse Forums
                            </a>
                        </p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Thread</th>
                                        <th>Subforum</th>
                                        <th>Last Activity</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($subscriptions as $subscription): ?>
                                        <tr>
                                            <td>
                                                <a href="<?php echo BASE_URL; ?>/forum_thread.php?slug=<?php echo $subscription['thread_slug']; ?>">
                                                    <?php echo $subscription['thread_title']; ?>
                                                </a>
                                            </td>
                                            <td>
                                                <a href="<?php echo BASE_URL; ?>/forum_subforum.php?slug=<?php echo $subscription['subforum_slug']; ?>">
                                                    <?php echo $subscription['subforum_name']; ?>
                                                </a>
                                            </td>
                                            <td>
                                                <?php echo formatDate($subscription['last_activity']); ?>
                                            </td>
                                            <td>
                                                <a href="<?php echo BASE_URL; ?>/forum_subscriptions.php?unsubscribe=<?php echo $subscription['thread_id']; ?>" 
                                                   class="btn btn-sm btn-danger"
                                                   onclick="return confirm('Are you sure you want to unsubscribe from this thread?');">
                                                    <i class="fas fa-bell-slash me-1"></i> Unsubscribe
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>