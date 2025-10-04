<?php
require_once '../includes/init.php';

// Check if user is logged in and is an admin
if (!isLoggedIn() || !isAdmin()) {
    setFlashMessage('You do not have permission to access the admin area.', 'danger');
    redirect('../index.php');
}

$pageTitle = 'Admin Dashboard';
include '../includes/header.php';
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-3">
            <!-- Admin Sidebar -->
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Admin Dashboard</h5>
                </div>
                <div class="list-group list-group-flush">
                    <a href="index.php" class="list-group-item list-group-item-action active">
                        <i class="fas fa-tachometer-alt mr-2"></i> Dashboard
                    </a>
                    <a href="users.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-users mr-2"></i> User Management
                    </a>
                    <a href="content.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-file-alt mr-2"></i> Content Moderation
                    </a>
                    <a href="forum.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-comments mr-2"></i> Forum Management
                    </a>
                    <a href="system_health_check.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-heartbeat mr-2"></i> System Health
                    </a>
                </div>
            </div>
        </div>
        
        <div class="col-md-9">
            <!-- Dashboard Overview -->
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Dashboard Overview</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <!-- User Stats -->
                        <div class="col-md-4 mb-3">
                            <div class="card bg-light">
                                <div class="card-body text-center">
                                    <h1 class="display-4">
                                        <?php 
                                        $userModel = new User();
                                        echo $userModel->getTotalCount(); 
                                        ?>
                                    </h1>
                                    <p class="lead">Total Users</p>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Blog Stats -->
                        <div class="col-md-4 mb-3">
                            <div class="card bg-light">
                                <div class="card-body text-center">
                                    <h1 class="display-4">
                                        <?php 
                                        $blogModel = new BlogPost();
                                        echo $blogModel->getTotalCount(); 
                                        ?>
                                    </h1>
                                    <p class="lead">Blog Posts</p>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Forum Stats -->
                        <div class="col-md-4 mb-3">
                            <div class="card bg-light">
                                <div class="card-body text-center">
                                    <h1 class="display-4">
                                        <?php 
                                        $threadModel = new ForumThread();
                                        echo $threadModel->getTotalCount(); 
                                        ?>
                                    </h1>
                                    <p class="lead">Forum Threads</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Recent Activity -->
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Recent Activity</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Type</th>
                                    <th>Title</th>
                                    <th>User</th>
                                    <th>Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                // Get recent blog posts
                                $recentPosts = $blogModel->getRecent(5);
                                foreach ($recentPosts as $post) {
                                    echo '<tr>';
                                    echo '<td><span class="badge badge-success">Blog</span></td>';
                                    echo '<td><a href="../blog-post.php?slug=' . $post['slug'] . '">' . htmlspecialchars($post['title']) . '</a></td>';
                                    echo '<td>' . htmlspecialchars($post['username']) . '</td>';
                                    echo '<td>' . date('M j, Y', strtotime($post['created_at'])) . '</td>';
                                    echo '<td>';
                                    echo '<a href="content.php?action=edit&type=blog&id=' . $post['post_id'] . '" class="btn btn-sm btn-primary mr-1"><i class="fas fa-edit"></i></a>';
                                    echo '<a href="content.php?action=delete&type=blog&id=' . $post['post_id'] . '" class="btn btn-sm btn-danger" onclick="return confirm(\'Are you sure?\')"><i class="fas fa-trash"></i></a>';
                                    echo '</td>';
                                    echo '</tr>';
                                }
                                
                                // Get recent forum threads
                                $recentThreads = $threadModel->getRecent(5);
                                foreach ($recentThreads as $thread) {
                                    echo '<tr>';
                                    echo '<td><span class="badge badge-primary">Forum</span></td>';
                                    echo '<td><a href="../forum-thread.php?slug=' . $thread['slug'] . '">' . htmlspecialchars($thread['title']) . '</a></td>';
                                    echo '<td>' . htmlspecialchars($thread['username']) . '</td>';
                                    echo '<td>' . date('M j, Y', strtotime($thread['created_at'])) . '</td>';
                                    echo '<td>';
                                    echo '<a href="forum.php?action=edit&id=' . $thread['thread_id'] . '" class="btn btn-sm btn-primary mr-1"><i class="fas fa-edit"></i></a>';
                                    echo '<a href="forum.php?action=delete&id=' . $thread['thread_id'] . '" class="btn btn-sm btn-danger" onclick="return confirm(\'Are you sure?\')"><i class="fas fa-trash"></i></a>';
                                    echo '</td>';
                                    echo '</tr>';
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>