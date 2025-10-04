<div class="col-md-3 col-lg-2">
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">Admin Dashboard</h5>
        </div>
        <div class="list-group list-group-flush">
            <a href="<?php echo BASE_URL; ?>/admin/index.php" class="list-group-item list-group-item-action <?php echo basename($_SERVER['PHP_SELF']) === 'index.php' ? 'active' : ''; ?>">
                <i class="fas fa-tachometer-alt me-2"></i> Dashboard
            </a>
            <a href="<?php echo BASE_URL; ?>/admin/users.php" class="list-group-item list-group-item-action <?php echo basename($_SERVER['PHP_SELF']) === 'users.php' ? 'active' : ''; ?>">
                <i class="fas fa-users me-2"></i> User Management
            </a>
            <a href="<?php echo BASE_URL; ?>/admin/content.php" class="list-group-item list-group-item-action <?php echo basename($_SERVER['PHP_SELF']) === 'content.php' ? 'active' : ''; ?>">
                <i class="fas fa-file-alt me-2"></i> Content Moderation
            </a>
            <a href="<?php echo BASE_URL; ?>/admin/forum.php" class="list-group-item list-group-item-action <?php echo basename($_SERVER['PHP_SELF']) === 'forum.php' ? 'active' : ''; ?>">
                <i class="fas fa-comments me-2"></i> Forum Management
            </a>
            <a href="<?php echo BASE_URL; ?>/admin/system_health_check.php" class="list-group-item list-group-item-action <?php echo basename($_SERVER['PHP_SELF']) === 'system_health_check.php' ? 'active' : ''; ?>">
                <i class="fas fa-heartbeat me-2"></i> System Health
            </a>
        </div>
    </div>
</div>