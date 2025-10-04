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
include __DIR__ . '/includes/admin_header.php';
?>

<div class="container mt-4">
    <div class="row">
        <main class="col-12 px-md-4 py-4">
            <!-- User Management -->
            <div class="card mb-4 shadow-sm border">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 d-flex align-items-center">
                        <i class="bi bi-people-fill me-2"></i> User Management
                    </h5>
                    <span class="badge bg-light text-dark rounded-pill px-3 py-2"><?php echo $totalUsers; ?> Users</span>
                </div>
                <div class="card-body">
                    <?php displayFlashMessages(); ?>
                    
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead class="table-light">
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
                                            <span class="badge bg-danger">Admin</span>
                                        <?php elseif ($user['role'] == 'moderator'): ?>
                                            <span class="badge bg-warning text-dark">Moderator</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">User</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($user['is_active']): ?>
                                            <span class="badge bg-success">Active</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger">Banned</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo date('M j, Y', strtotime($user['created_at'])); ?></td>
                                    <td>
                                        <span class="badge bg-info text-white me-1"><?php echo $user['post_count']; ?> Posts</span>
                                        <span class="badge bg-primary"><?php echo $user['thread_count']; ?> Threads</span>
                                    </td>
                                    <td>
                                        <div class="dropdown">
                                            <button class="btn btn-sm btn-outline-primary dropdown-toggle" type="button" id="dropdownMenuButton" data-bs-toggle="dropdown" aria-expanded="false">
                                                <i class="bi bi-gear-fill me-1"></i> Actions
                                            </button>
                                            <div class="dropdown-menu shadow" aria-labelledby="dropdownMenuButton">
                                                <!-- Status Actions -->
                                                <?php if ($user['is_active']): ?>
                                                    <a class="dropdown-item text-danger" href="users.php?action=ban&id=<?php echo $user['user_id']; ?>" onclick="return confirm('Are you sure you want to ban this user?')">
                                                        <i class="bi bi-slash-circle me-2"></i> Ban User
                                                    </a>
                                                <?php else: ?>
                                                    <a class="dropdown-item text-success" href="users.php?action=unban&id=<?php echo $user['user_id']; ?>">
                                                        <i class="bi bi-check-circle me-2"></i> Unban User
                                                    </a>
                                                <?php endif; ?>
                                                
                                                <div class="dropdown-divider"></div>
                                                
                                                <!-- Role Actions -->
                                                <?php if ($user['role'] != 'admin'): ?>
                                                    <a class="dropdown-item" href="users.php?action=make_admin&id=<?php echo $user['user_id']; ?>" onclick="return confirm('Are you sure you want to make this user an admin?')">
                                                        <i class="bi bi-shield-fill me-2"></i> Make Admin
                                                    </a>
                                                <?php endif; ?>
                                                
                                                <?php if ($user['role'] != 'moderator'): ?>
                                                    <a class="dropdown-item" href="users.php?action=make_moderator&id=<?php echo $user['user_id']; ?>">
                                                        <i class="bi bi-person-gear me-2"></i> Make Moderator
                                                    </a>
                                                <?php endif; ?>
                                                
                                                <?php if ($user['role'] != 'user'): ?>
                                                    <a class="dropdown-item" href="users.php?action=make_user&id=<?php echo $user['user_id']; ?>">
                                                        <i class="bi bi-person me-2"></i> Make Regular User
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
                    <div class="card-footer bg-light">
                        <nav aria-label="Page navigation">
                            <ul class="pagination pagination-sm justify-content-center mb-0">
                                <?php if ($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="users.php?page=<?php echo $page - 1; ?>" aria-label="Previous">
                                        <i class="bi bi-chevron-left"></i>
                                    </a>
                                </li>
                                <?php else: ?>
                                <li class="page-item disabled">
                                    <span class="page-link"><i class="bi bi-chevron-left"></i></span>
                                </li>
                                <?php endif; ?>
                                
                                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                <li class="page-item <?php echo ($i == $page) ? 'active' : ''; ?>">
                                    <a class="page-link" href="users.php?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                                </li>
                                <?php endfor; ?>
                                
                                <?php if ($page < $totalPages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="users.php?page=<?php echo $page + 1; ?>" aria-label="Next">
                                        <i class="bi bi-chevron-right"></i>
                                    </a>
                                </li>
                                <?php else: ?>
                                <li class="page-item disabled">
                                    <span class="page-link"><i class="bi bi-chevron-right"></i></span>
                                </li>
                                <?php endif; ?>
                            </ul>
                        </nav>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/admin_footer.php'; ?>
