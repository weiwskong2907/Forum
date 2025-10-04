<?php
/**
 * Forum page
 * 
 * This page displays the forum categories and subforums
 */

// Include initialization file
require_once __DIR__ . '/includes/init.php';

// Initialize models
$categoryModel = new ForumCategory();

// Get categories with subforums
$categories = $categoryModel->getAllWithSubforums();

// Page title
$pageTitle = 'Forum';

// Include header
include_once __DIR__ . '/includes/header.php';
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Forum</h1>
        
        <div>
            <a href="<?php echo BASE_URL; ?>/forum_search.php" class="btn btn-outline-primary me-2">
                <i class="fas fa-search me-1"></i> Advanced Search
            </a>
            
            <?php if (isLoggedIn()): ?>
                <a href="<?php echo BASE_URL; ?>/forum_subscriptions.php" class="btn btn-outline-primary">
                    <i class="fas fa-bell me-1"></i> My Subscriptions
                </a>
            <?php endif; ?>
        </div>
    </div>
    
    <?php if (!empty($categories)): ?>
        <?php foreach ($categories as $category): ?>
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h2 class="h5 mb-0"><?php echo $category['name']; ?></h2>
                </div>
                
                <?php if (!empty($category['subforums'])): ?>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Forum</th>
                                        <th class="text-center">Threads</th>
                                        <th class="text-center">Posts</th>
                                        <th>Last Post</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($category['subforums'] as $subforum): ?>
                                        <tr>
                                            <td>
                                                <a href="<?php echo BASE_URL; ?>/forum_subforum.php?slug=<?php echo $subforum['slug']; ?>" class="text-decoration-none fw-bold">
                                                    <?php echo $subforum['name']; ?>
                                                </a>
                                                <?php if (!empty($subforum['description'])): ?>
                                                    <div class="small text-muted"><?php echo $subforum['description']; ?></div>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-center"><?php echo $subforum['thread_count']; ?></td>
                                            <td class="text-center"><?php echo $subforum['post_count']; ?></td>
                                            <td>
                                                <?php
                                                // Get last post for this subforum
                                                $subforumModel = new ForumSubforum();
                                                $stats = $subforumModel->getStats($subforum['subforum_id']);
                                                $lastPost = $stats['last_post'];
                                                
                                                if ($lastPost):
                                                ?>
                                                    <div class="small">
                                                        <a href="<?php echo BASE_URL; ?>/forum_thread.php?slug=<?php echo $lastPost['thread_slug']; ?>" class="text-decoration-none">
                                                            <?php echo truncateText($lastPost['thread_title'], 30); ?>
                                                        </a>
                                                    </div>
                                                    <div class="small text-muted">
                                                        by <?php echo $lastPost['username']; ?>
                                                        <br>
                                                        <?php echo formatDate($lastPost['created_at']); ?>
                                                    </div>
                                                <?php else: ?>
                                                    <div class="small text-muted">No posts yet</div>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="card-body">
                        <div class="alert alert-info mb-0">No subforums found in this category.</div>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="alert alert-info">No forum categories found.</div>
    <?php endif; ?>
    
    <div class="card mt-4">
        <div class="card-header">
            <h2 class="h5 mb-0">Forum Statistics</h2>
        </div>
        <div class="card-body">
            <?php
            // Get statistics
            $threadModel = new ForumThread();
            $postModel = new ForumPost();
            $userModel = new User();
            
            $totalThreads = $threadModel->countAll();
            $totalPosts = $postModel->countAll();
            $totalUsers = $userModel->countAll();
            $latestUser = $userModel->getLatest();
            ?>
            
            <div class="row">
                <div class="col-md-3 mb-3 mb-md-0">
                    <div class="text-center">
                        <div class="h4"><?php echo $totalThreads; ?></div>
                        <div>Threads</div>
                    </div>
                </div>
                <div class="col-md-3 mb-3 mb-md-0">
                    <div class="text-center">
                        <div class="h4"><?php echo $totalPosts; ?></div>
                        <div>Posts</div>
                    </div>
                </div>
                <div class="col-md-3 mb-3 mb-md-0">
                    <div class="text-center">
                        <div class="h4"><?php echo $totalUsers; ?></div>
                        <div>Members</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="text-center">
                        <div class="h4">
                            <?php if ($latestUser): ?>
                                <a href="<?php echo BASE_URL; ?>/profile.php?username=<?php echo $latestUser['username']; ?>" class="text-decoration-none">
                                    <?php echo $latestUser['username']; ?>
                                </a>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </div>
                        <div>Newest Member</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <?php if (isAdmin()): ?>
        <div class="mt-4">
            <a href="<?php echo BASE_URL; ?>/admin/forum_categories.php" class="btn btn-primary">
                <i class="fas fa-cog me-1"></i> Manage Forum
            </a>
        </div>
    <?php endif; ?>
</div>

<?php
// Include footer
include_once __DIR__ . '/includes/footer.php';
?>