<?php
require_once '../includes/init.php';

// Check if user is logged in and is an admin
if (!isLoggedIn() || !isAdmin()) {
    setFlashMessage('You do not have permission to access the admin area.', 'danger');
    redirect('../index.php');
}

$userModel = new User();

// Handle user actions
if (isset($_GET['action'])) {
    $userId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    
    if ($userId > 0) {
        switch ($_GET['action']) {
            case 'ban':
                if ($userModel->banUser($userId)) {
                    setFlashMessage('User has been banned successfully.', 'success');
                } else {
                    setFlashMessage('Failed to ban user.', 'danger');
                }
                break;
                
            case 'unban':
                if ($userModel->unbanUser($userId)) {
                    setFlashMessage('User has been unbanned successfully.', 'success');
                } else {
                    setFlashMessage('Failed to unban user.', 'danger');
                }
                break;
                
            case 'make_admin':
                if ($userModel->changeRole($userId, 'admin')) {
                    setFlashMessage('User has been promoted to admin.', 'success');
                } else {
                    setFlashMessage('Failed to change user role.', 'danger');
                }
                break;
                
            case 'make_moderator':
                if ($userModel->changeRole($userId, 'moderator')) {
                    setFlashMessage('User has been promoted to moderator.', 'success');
                } else {
                    setFlashMessage('Failed to change user role.', 'danger');
                }
                break;
                
            case 'make_user':
                if ($userModel->changeRole($userId, 'user')) {
                    setFlashMessage('User role has been changed to regular user.', 'success');
                } else {
                    setFlashMessage('Failed to change user role.', 'danger');
                }
                break;
        }
    }
    
    redirect('users.php');
}

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

// Get users
$users = $userModel->getAll($limit, $offset);
$totalUsers = $userModel->getTotalCount();
$totalPages = ceil($totalUsers / $limit);

$pageTitle = 'User Management';
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
                    <a href="index.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-tachometer-alt mr-2"></i> Dashboard
                    </a>
                    <a href="users.php" class="list-group-item list-group-item-action active">
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
            <!-- User Management -->
            <div class="card mb-4">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">User Management</h5>
                    <span class="badge badge-light"><?php echo $totalUsers; ?> Users</span>
                </div>
                <div class="card-body">
                    <?php displayFlashMessages(); ?>
                    
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Username</th>
                                    <th>Email</th>
                                    <th>Role</th>
                                    <th>Status</th>
                                    <th>Joined</th>
                                    <th>Content</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($users as $user): ?>
                                <tr>
                                    <td><?php echo $user['user_id']; ?></td>
                                    <td>
                                        <a href="../profile.php?username=<?php echo urlencode($user['username']); ?>">
                                            <?php echo htmlspecialchars($user['username']); ?>
                                        </a>
                                    </td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td>
                                        <?php if ($user['role'] == 'admin'): ?>
                                            <span class="badge badge-danger">Admin</span>
                                        <?php elseif ($user['role'] == 'moderator'): ?>
                                            <span class="badge badge-warning">Moderator</span>
                                        <?php else: ?>
                                            <span class="badge badge-secondary">User</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($user['is_active']): ?>
                                            <span class="badge badge-success">Active</span>
                                        <?php else: ?>
                                            <span class="badge badge-danger">Banned</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo date('M j, Y', strtotime($user['created_at'])); ?></td>
                                    <td>
                                        <span class="badge badge-info"><?php echo $user['post_count']; ?> Posts</span>
                                        <span class="badge badge-primary"><?php echo $user['thread_count']; ?> Threads</span>
                                    </td>
                                    <td>
                                        <div class="dropdown">
                                            <button class="btn btn-sm btn-secondary dropdown-toggle" type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                                Actions
                                            </button>
                                            <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                                                <!-- Status Actions -->
                                                <?php if ($user['is_active']): ?>
                                                    <a class="dropdown-item text-danger" href="users.php?action=ban&id=<?php echo $user['user_id']; ?>" onclick="return confirm('Are you sure you want to ban this user?')">
                                                        <i class="fas fa-ban mr-2"></i> Ban User
                                                    </a>
                                                <?php else: ?>
                                                    <a class="dropdown-item text-success" href="users.php?action=unban&id=<?php echo $user['user_id']; ?>">
                                                        <i class="fas fa-check mr-2"></i> Unban User
                                                    </a>
                                                <?php endif; ?>
                                                
                                                <div class="dropdown-divider"></div>
                                                
                                                <!-- Role Actions -->
                                                <?php if ($user['role'] != 'admin'): ?>
                                                    <a class="dropdown-item" href="users.php?action=make_admin&id=<?php echo $user['user_id']; ?>" onclick="return confirm('Are you sure you want to make this user an admin?')">
                                                        <i class="fas fa-user-shield mr-2"></i> Make Admin
                                                    </a>
                                                <?php endif; ?>
                                                
                                                <?php if ($user['role'] != 'moderator'): ?>
                                                    <a class="dropdown-item" href="users.php?action=make_moderator&id=<?php echo $user['user_id']; ?>">
                                                        <i class="fas fa-user-cog mr-2"></i> Make Moderator
                                                    </a>
                                                <?php endif; ?>
                                                
                                                <?php if ($user['role'] != 'user'): ?>
                                                    <a class="dropdown-item" href="users.php?action=make_user&id=<?php echo $user['user_id']; ?>">
                                                        <i class="fas fa-user mr-2"></i> Make Regular User
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Pagination -->
                    <?php if ($totalPages > 1): ?>
                    <nav aria-label="User pagination">
                        <ul class="pagination justify-content-center">
                            <?php if ($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="users.php?page=<?php echo $page - 1; ?>">Previous</a>
                                </li>
                            <?php else: ?>
                                <li class="page-item disabled">
                                    <span class="page-link">Previous</span>
                                </li>
                            <?php endif; ?>
                            
                            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                    <a class="page-link" href="users.php?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                                </li>
                            <?php endfor; ?>
                            
                            <?php if ($page < $totalPages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="users.php?page=<?php echo $page + 1; ?>">Next</a>
                                </li>
                            <?php else: ?>
                                <li class="page-item disabled">
                                    <span class="page-link">Next</span>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>