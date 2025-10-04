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
include_once __DIR__ . '/../includes/header.php';
?>

<div class="container mt-4">
    <h1>System Health Check</h1>
    
    <div class="alert alert-<?php echo $overallStatus === 'success' ? 'success' : 'danger'; ?> mt-3">
        <strong>Overall Status:</strong> 
        <?php echo $overallStatus === 'success' ? 'All systems are operational.' : 'Some checks failed. Please review the details below.'; ?>
    </div>
    
    <div class="card mt-4">
        <div class="card-header">
            <h2 class="h5 mb-0">PHP Version</h2>
        </div>
        <div class="card-body">
            <div class="alert alert-<?php echo $results['php_version']['status'] === 'success' ? 'success' : 'danger'; ?>">
                <?php echo $results['php_version']['message']; ?>
            </div>
        </div>
    </div>
    
    <div class="card mt-4">
        <div class="card-header">
            <h2 class="h5 mb-0">Required Extensions</h2>
        </div>
        <div class="card-body">
            <div class="alert alert-<?php echo $results['extensions']['status'] === 'success' ? 'success' : 'danger'; ?>">
                <?php echo $results['extensions']['message']; ?>
            </div>
            
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Extension</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($results['extensions']['details'] as $extension): ?>
                    <tr>
                        <td><?php echo $extension['name']; ?></td>
                        <td>
                            <span class="badge bg-<?php echo $extension['status'] === 'success' ? 'success' : 'danger'; ?>">
                                <?php echo $extension['status'] === 'success' ? 'Loaded' : 'Not Loaded'; ?>
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <div class="card mt-4">
        <div class="card-header">
            <h2 class="h5 mb-0">Database Connection</h2>
        </div>
        <div class="card-body">
            <div class="alert alert-<?php echo $results['database']['status'] === 'success' ? 'success' : 'danger'; ?>">
                <?php echo $results['database']['message']; ?>
            </div>
            
            <?php if ($results['database']['status'] === 'success'): ?>
                <div class="alert alert-<?php echo $results['database']['mysql_version']['status'] === 'success' ? 'success' : 'danger'; ?> mt-3">
                    <?php echo $results['database']['mysql_version']['message']; ?>
                </div>
                
                <div class="alert alert-<?php echo $results['database']['tables']['status'] === 'success' ? 'success' : 'danger'; ?> mt-3">
                    <?php echo $results['database']['tables']['message']; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="card mt-4">
        <div class="card-header">
            <h2 class="h5 mb-0">File System Permissions</h2>
        </div>
        <div class="card-body">
            <div class="alert alert-<?php echo $results['file_system']['status'] === 'success' ? 'success' : 'danger'; ?>">
                <?php echo $results['file_system']['message']; ?>
            </div>
            
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Directory</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($results['file_system']['details'] as $directory): ?>
                    <tr>
                        <td><?php echo $directory['path']; ?></td>
                        <td>
                            <span class="badge bg-<?php echo $directory['status'] === 'success' ? 'success' : 'danger'; ?>">
                                <?php echo $directory['status'] === 'success' ? 'Writable' : 'Not Writable'; ?>
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <div class="mt-4 mb-5">
        <a href="<?php echo BASE_URL; ?>/admin/index.php" class="btn btn-primary">Back to Admin Dashboard</a>
        <button class="btn btn-success ms-2" onclick="window.location.reload()">Run Check Again</button>
    </div>
</div>

<?php
// Include footer
include_once __DIR__ . '/../includes/footer.php';
?>