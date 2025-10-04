<?php
/**
 * System Health Check
 * 
 * This script performs various checks to ensure the system is properly configured
 * Access is restricted to Super Admin users only
 */

// Include initialization file
require_once __DIR__ . '/../includes/init.php';

// Check if user is logged in and is a super admin
if (!isLoggedIn() || !isSuperAdmin()) {
    // Redirect to login page with error message
    setFlashMessage('You must be logged in as a Super Admin to access this page.', 'danger');
    redirect(BASE_URL . '/login.php');
}

// Initialize results array
$results = [
    'php_version' => [
        'status' => 'error',
        'message' => 'PHP version is not supported. Required: 8.2.0 or higher.'
    ],
    'extensions' => [
        'status' => 'error',
        'message' => 'Required extensions are missing.',
        'details' => []
    ],
    'database' => [
        'status' => 'error',
        'message' => 'Database connection failed.'
    ],
    'file_system' => [
        'status' => 'error',
        'message' => 'File system permissions are not properly set.',
        'details' => []
    ]
];

// Check PHP version
$requiredVersion = '8.2.0';
$currentVersion = phpversion();

if (version_compare($currentVersion, $requiredVersion, '>=')) {
    $results['php_version'] = [
        'status' => 'success',
        'message' => "PHP version is supported. Current: $currentVersion"
    ];
}

// Check required extensions
$requiredExtensions = ['mysqli', 'json', 'session', 'mbstring', 'fileinfo', 'gd'];
$missingExtensions = [];

foreach ($requiredExtensions as $extension) {
    if (!extension_loaded($extension)) {
        $missingExtensions[] = $extension;
        $results['extensions']['details'][] = [
            'name' => $extension,
            'status' => 'error',
            'message' => "Extension $extension is not loaded."
        ];
    } else {
        $results['extensions']['details'][] = [
            'name' => $extension,
            'status' => 'success',
            'message' => "Extension $extension is loaded."
        ];
    }
}

if (empty($missingExtensions)) {
    $results['extensions']['status'] = 'success';
    $results['extensions']['message'] = 'All required extensions are loaded.';
} else {
    $results['extensions']['message'] = 'Missing extensions: ' . implode(', ', $missingExtensions);
}

// Check database connection
try {
    $db = Database::getInstance();
    $connection = $db->getConnection();
    
    if ($connection->ping()) {
        $results['database']['status'] = 'success';
        $results['database']['message'] = 'Database connection successful.';
        
        // Check if required tables exist
        $requiredTables = ['users', 'user_profiles', 'blog_posts', 'blog_categories', 'blog_comments', 'forum_categories', 'forum_subforums', 'forum_threads', 'forum_posts'];
        $missingTables = [];
        
        $query = "SHOW TABLES";
        $tables = $db->fetchAll($query);
        $existingTables = [];
        
        foreach ($tables as $table) {
            $existingTables[] = reset($table);
        }
        
        foreach ($requiredTables as $table) {
            if (!in_array($table, $existingTables)) {
                $missingTables[] = $table;
            }
        }
        
        if (empty($missingTables)) {
            $results['database']['tables'] = [
                'status' => 'success',
                'message' => 'All required tables exist.'
            ];
        } else {
            $results['database']['tables'] = [
                'status' => 'error',
                'message' => 'Missing tables: ' . implode(', ', $missingTables)
            ];
        }
        
        // Check MySQL version
        $query = "SELECT VERSION() as version";
        $versionResult = $db->fetchRow($query);
        $mysqlVersion = $versionResult['version'];
        
        if (version_compare($mysqlVersion, '8.0.0', '>=')) {
            $results['database']['mysql_version'] = [
                'status' => 'success',
                'message' => "MySQL version is supported. Current: $mysqlVersion"
            ];
        } else {
            $results['database']['mysql_version'] = [
                'status' => 'error',
                'message' => "MySQL version is not supported. Required: 8.0.0 or higher. Current: $mysqlVersion"
            ];
        }
        
        // Check required columns in tables
        $requiredColumns = [
            'blog_posts' => ['post_id', 'user_id', 'category_id', 'title', 'slug', 'content', 'status', 'published_at', 'created_at', 'updated_at'],
            'users' => ['user_id', 'username', 'email', 'password', 'role', 'is_active', 'created_at'],
            'user_profiles' => ['profile_id', 'user_id', 'avatar', 'bio', 'website', 'updated_at']
        ];
        
        $missingColumns = [];
        
        foreach ($requiredColumns as $table => $columns) {
            if (in_array($table, $existingTables)) {
                $query = "SHOW COLUMNS FROM $table";
                $tableColumns = $db->fetchAll($query);
                $existingColumns = [];
                
                foreach ($tableColumns as $column) {
                    $existingColumns[] = $column['Field'];
                }
                
                foreach ($columns as $column) {
                    if (!in_array($column, $existingColumns)) {
                        if (!isset($missingColumns[$table])) {
                            $missingColumns[$table] = [];
                        }
                        $missingColumns[$table][] = $column;
                    }
                }
            }
        }
        
        if (empty($missingColumns)) {
            $results['database']['columns'] = [
                'status' => 'success',
                'message' => 'All required columns exist in database tables.'
            ];
        } else {
            $columnErrors = [];
            foreach ($missingColumns as $table => $columns) {
                $columnErrors[] = "$table: " . implode(', ', $columns);
            }
            
            $results['database']['columns'] = [
                'status' => 'error',
                'message' => 'Missing columns: ' . implode('; ', $columnErrors)
            ];
        }
    }
} catch (Exception $e) {
    $results['database']['message'] = 'Database connection failed: ' . $e->getMessage();
}

// Check file system permissions
$directoriesToCheck = [
    ROOT_PATH . '/uploads',
    ROOT_PATH . '/uploads/avatars',
    ROOT_PATH . '/uploads/blog',
    ROOT_PATH . '/logs'
];

// Create directories if they don't exist
foreach ($directoriesToCheck as $directory) {
    if (!file_exists($directory)) {
        mkdir($directory, 0755, true);
    }
}

$allWritable = true;

foreach ($directoriesToCheck as $directory) {
    if (!is_writable($directory)) {
        $allWritable = false;
        $results['file_system']['details'][] = [
            'path' => $directory,
            'status' => 'error',
            'message' => "Directory is not writable."
        ];
    } else {
        $results['file_system']['details'][] = [
            'path' => $directory,
            'status' => 'success',
            'message' => "Directory is writable."
        ];
    }
}

if ($allWritable) {
    $results['file_system']['status'] = 'success';
    $results['file_system']['message'] = 'All required directories are writable.';
}

// Check overall status
$overallStatus = 'success';

foreach ($results as $check) {
    if ($check['status'] === 'error') {
        $overallStatus = 'error';
        break;
    }
}

// Page title
$pageTitle = 'System Health Check';

// Include header
include_once __DIR__ . '/includes/admin_header.php';
?>

<div class="container mt-4">
    <div class="row">
        <main class="col-12 px-md-4 py-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="mb-0"><i class="bi bi-activity me-2"></i>System Health Check</h1>
                <div>
                    <a href="<?php echo BASE_URL; ?>/admin/index.php" class="btn btn-outline-primary me-2">
                        <i class="bi bi-arrow-left me-1"></i> Dashboard
                    </a>
                    <button class="btn btn-primary" onclick="window.location.reload()">
                        <i class="bi bi-arrow-clockwise me-1"></i> Run Check Again
                    </button>
                </div>
            </div>
    
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center">
                        <?php if ($overallStatus === 'success'): ?>
                            <div class="rounded-circle bg-success p-3 me-3">
                                <i class="bi bi-check-lg text-white fs-4"></i>
                            </div>
                            <div>
                                <h4 class="mb-1">All Systems Operational</h4>
                                <p class="text-muted mb-0">Last checked: <?php echo date('Y-m-d H:i:s'); ?></p>
                            </div>
                        <?php else: ?>
                            <div class="rounded-circle bg-danger p-3 me-3">
                                <i class="bi bi-exclamation-triangle text-white fs-4"></i>
                            </div>
                            <div>
                                <h4 class="mb-1">System Issues Detected</h4>
                                <p class="text-muted mb-0">Last checked: <?php echo date('Y-m-d H:i:s'); ?></p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="card border-0 shadow-sm h-100" style="border-left: 4px solid #0d6efd !important;">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h5 class="card-title mb-0">PHP Version</h5>
                                <i class="bi bi-filetype-php fs-1 text-primary opacity-50"></i>
                            </div>
                            <h2 class="mb-0"><?php echo $currentVersion; ?></h2>
                            <div class="mt-3">
                                <span class="badge <?php echo $results['php_version']['status'] === 'success' ? 'bg-success' : 'bg-danger'; ?> rounded-pill px-3 py-2">
                                    <?php echo $results['php_version']['status'] === 'success' ? 'Compatible' : 'Incompatible'; ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="card border-0 shadow-sm h-100" style="border-left: 4px solid #198754 !important;">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h5 class="card-title mb-0">Database</h5>
                                <i class="bi bi-database fs-1 text-success opacity-50"></i>
                            </div>
                            <h2 class="mb-0">
                                <?php echo $results['database']['status'] === 'success' ? 'Connected' : 'Failed'; ?>
                            </h2>
                            <div class="mt-3">
                                <span class="badge <?php echo $results['database']['status'] === 'success' ? 'bg-success' : 'bg-danger'; ?> rounded-pill px-3 py-2">
                                    <?php echo $results['database']['status'] === 'success' ? 'Operational' : 'Error'; ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="card border-0 shadow-sm h-100" style="border-left: 4px solid #ffc107 !important;">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h5 class="card-title mb-0">File System</h5>
                                <i class="bi bi-folder fs-1 text-warning opacity-50"></i>
                            </div>
                            <h2 class="mb-0">
                                <?php echo $results['file_system']['status'] === 'success' ? 'Writable' : 'Issues'; ?>
                            </h2>
                            <div class="mt-3">
                                <span class="badge <?php echo $results['file_system']['status'] === 'success' ? 'bg-success' : 'bg-danger'; ?> rounded-pill px-3 py-2">
                                    <?php echo $results['file_system']['status'] === 'success' ? 'Accessible' : 'Permission Errors'; ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="card border-0 shadow-sm h-100" style="border-left: 4px solid #dc3545 !important;">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h5 class="card-title mb-0">Extensions</h5>
                                <i class="bi bi-puzzle fs-1 text-danger opacity-50"></i>
                            </div>
                            <h2 class="mb-0">
                                <?php echo $results['extensions']['status'] === 'success' ? 'All Loaded' : 'Missing'; ?>
                            </h2>
                            <div class="mt-3">
                                <span class="badge <?php echo $results['extensions']['status'] === 'success' ? 'bg-success' : 'bg-danger'; ?> rounded-pill px-3 py-2">
                                    <?php echo $results['extensions']['status'] === 'success' ? 'Complete' : 'Incomplete'; ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mt-4">
                <div class="col-md-6 mb-4">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="bi bi-gear-fill me-2"></i>PHP Environment</h5>
                        </div>
                        <div class="card-body p-0">
                            <div class="p-4 border-bottom">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-1">PHP Version</h6>
                                        <p class="small text-muted mb-0"><?php echo $results['php_version']['message']; ?></p>
                                    </div>
                                    <span class="badge bg-<?php echo $results['php_version']['status'] === 'success' ? 'success' : 'danger'; ?> rounded-pill px-3 py-2">
                                        <?php echo $currentVersion; ?>
                                    </span>
                                </div>
                            </div>
                            
                            <div class="p-4">
                                <h6 class="mb-3">Required Extensions</h6>
                                <div class="table-responsive">
                                    <table class="table table-hover mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Extension</th>
                                                <th class="text-end">Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($results['extensions']['details'] as $extension): ?>
                                                <tr>
                                                    <td><?php echo $extension['name']; ?></td>
                                                    <td class="text-end">
                                                        <?php if ($extension['status'] === 'success'): ?>
                                                            <span class="badge bg-success rounded-pill"><i class="bi bi-check-lg me-1"></i>Loaded</span>
                                                        <?php else: ?>
                                                            <span class="badge bg-danger rounded-pill"><i class="bi bi-x-lg me-1"></i>Missing</span>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-6 mb-4">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="bi bi-server me-2"></i>Server Information</h5>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <tbody>
                                        <tr>
                                            <td><strong>Server Software</strong></td>
                                            <td class="text-end"><?php echo $_SERVER['SERVER_SOFTWARE']; ?></td>
                                        </tr>
                                        <tr>
                                            <td><strong>Operating System</strong></td>
                                            <td class="text-end"><?php echo PHP_OS; ?></td>
                                        </tr>
                                        <tr>
                                            <td><strong>Max Upload Size</strong></td>
                                            <td class="text-end"><?php echo ini_get('upload_max_filesize'); ?></td>
                                        </tr>
                                        <tr>
                                            <td><strong>Max Post Size</strong></td>
                                            <td class="text-end"><?php echo ini_get('post_max_size'); ?></td>
                                        </tr>
                                        <tr>
                                            <td><strong>Memory Limit</strong></td>
                                            <td class="text-end"><?php echo ini_get('memory_limit'); ?></td>
                                        </tr>
                                        <tr>
                                            <td><strong>Max Execution Time</strong></td>
                                            <td class="text-end"><?php echo ini_get('max_execution_time'); ?> seconds</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <?php if ($results['database']['status'] === 'success'): ?>
            <div class="row">
                <div class="col-12 mb-4">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="bi bi-table me-2"></i>Database Structure</h5>
                        </div>
                        <div class="card-body p-0">
                            <div class="p-4 border-bottom">
                                <div class="d-flex align-items-center">
                                    <?php if (isset($results['database']['tables']) && $results['database']['tables']['status'] === 'success'): ?>
                                        <span class="badge bg-success rounded-pill me-2"><i class="bi bi-check-lg me-1"></i>All Tables Exist</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger rounded-pill me-2"><i class="bi bi-x-lg me-1"></i>Missing Tables</span>
                                    <?php endif; ?>
                                    <span class="text-muted">
                                        <?php echo isset($results['database']['tables']['message']) ? $results['database']['tables']['message'] : ''; ?>
                                    </span>
                                </div>
                            </div>
                            
                            <div class="p-4">
                                <div class="d-flex align-items-center">
                                    <?php if (isset($results['database']['columns']) && $results['database']['columns']['status'] === 'success'): ?>
                                        <span class="badge bg-success rounded-pill me-2"><i class="bi bi-check-lg me-1"></i>All Columns Exist</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger rounded-pill me-2"><i class="bi bi-x-lg me-1"></i>Missing Columns</span>
                                    <?php endif; ?>
                                    <span class="text-muted">
                                        <?php echo isset($results['database']['columns']['message']) ? $results['database']['columns']['message'] : ''; ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <?php if (isset($results['file_system']['details']) && !empty($results['file_system']['details'])): ?>
            <div class="row">
                <div class="col-12 mb-4">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="bi bi-folder me-2"></i>Directory Permissions</h5>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Directory Path</th>
                                            <th class="text-end">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($results['file_system']['details'] as $dir): ?>
                                            <tr>
                                                <td class="text-truncate" style="max-width: 500px;" title="<?php echo $dir['path']; ?>">
                                                    <?php echo $dir['path']; ?>
                                                </td>
                                                <td class="text-end">
                                                    <?php if ($dir['status'] === 'success'): ?>
                                                        <span class="badge bg-success rounded-pill"><i class="bi bi-check-lg me-1"></i>Writable</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-danger rounded-pill"><i class="bi bi-x-lg me-1"></i>Not Writable</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </main>
    </div>
</div>

<?php
// Include footer
include_once __DIR__ . '/includes/admin_footer.php';
?>