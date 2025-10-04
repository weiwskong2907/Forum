<?php
require_once '../includes/init.php';

// Check if user is logged in and is an admin
if (!isLoggedIn() || !isAdmin()) {
    setFlashMessage('You do not have permission to access the admin area.', 'danger');
    redirect('../index.php');
}

$pageTitle = 'Admin Dashboard';
include 'includes/admin_header.php';
?>

<div class="container mt-4">
    <div class="row">
        <main class="col-12 px-md-4 py-4">
            <!-- Dashboard Overview -->
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Dashboard Overview</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <!-- User Stats -->
                        <div class="col-md-4 mb-3">
                            <div class="card shadow-sm border-0 bg-primary bg-opacity-10">
                                <div class="card-body text-center p-4">
                                    <div class="rounded-circle bg-primary text-white d-inline-flex align-items-center justify-content-center mb-3" style="width: 60px; height: 60px;">
                                        <i class="bi bi-people-fill fs-2"></i>
                                    </div>
                                    <h1 class="display-4 fw-bold text-primary">
                                        <?php 
                                        $userModel = new User();
                                        echo $userModel->getTotalCount(); 
                                        ?>
                                    </h1>
                                    <p class="lead fw-bold text-dark mb-0">Total Users</p>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Blog Stats -->
                        <div class="col-md-4 mb-3">
                            <div class="card shadow-sm border-0 bg-success bg-opacity-10">
                                <div class="card-body text-center p-4">
                                    <div class="rounded-circle bg-success text-white d-inline-flex align-items-center justify-content-center mb-3" style="width: 60px; height: 60px;">
                                        <i class="bi bi-file-earmark-text-fill fs-2"></i>
                                    </div>
                                    <h1 class="display-4 fw-bold text-success">
                                        <?php 
                                        $blogModel = new BlogPost();
                                        echo $blogModel->getTotalCount(); 
                                        ?>
                                    </h1>
                                    <p class="lead fw-bold text-dark mb-0">Blog Posts</p>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Forum Stats -->
                        <div class="col-md-4 mb-3">
                            <div class="card shadow-sm border-0 bg-info bg-opacity-10">
                                <div class="card-body text-center p-4">
                                    <div class="rounded-circle bg-info text-white d-inline-flex align-items-center justify-content-center mb-3" style="width: 60px; height: 60px;">
                                        <i class="bi bi-chat-square-text-fill fs-2"></i>
                                    </div>
                                    <h1 class="display-4 fw-bold text-info">
                                        <?php 
                                        $threadModel = new ForumThread();
                                        echo $threadModel->getTotalCount(); 
                                        ?>
                                    </h1>
                                    <p class="lead fw-bold text-dark mb-0">Forum Threads</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Recent Activity -->
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0 d-flex align-items-center">
                        <i class="bi bi-activity me-2"></i> Recent Activity
                    </h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th class="fw-bold">Type</th>
                                    <th class="fw-bold">Title</th>
                                    <th class="fw-bold">User</th>
                                    <th class="fw-bold">Date</th>
                                    <th class="fw-bold">Actions</th>
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

<?php include 'includes/admin_footer.php'; ?>