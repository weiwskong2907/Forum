<?php
/**
 * User profile page
 * 
 * This page displays and allows editing of user profiles
 */

// Include initialization file
require_once __DIR__ . '/includes/init.php';

// Redirect if not logged in
if (!isLoggedIn()) {
    $_SESSION['redirect_after_login'] = BASE_URL . '/profile.php';
    setFlashMessage('Please login to view your profile.', 'warning');
    redirect(BASE_URL . '/login.php');
}

// Get current user
$user = $currentUser;
$userId = $user['user_id'];

// Initialize user model
$userModel = new User();

// Process profile update form
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    // Verify CSRF token
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        setFlashMessage('Invalid request. Please try again.', 'danger');
        redirect(BASE_URL . '/profile.php');
    }
    
    $bio = trim($_POST['bio'] ?? '');
    $website = trim($_POST['website'] ?? '');
    
    // Validate website URL if provided
    if (!empty($website) && !filter_var($website, FILTER_VALIDATE_URL)) {
        setFlashMessage('Please enter a valid website URL.', 'danger');
    } else {
        // Handle avatar upload
        $avatar = $user['avatar']; // Keep existing avatar by default
        
        if (isset($_POST['cropped_image']) && !empty($_POST['cropped_image'])) {
            // Process base64 encoded image from cropper
            $croppedImage = $_POST['cropped_image'];
            
            // Extract the base64 data
            list($type, $data) = explode(';', $croppedImage);
            list(, $data) = explode(',', $data);
            $data = base64_decode($data);
            
            // Get image extension from mime type
            list(, $extension) = explode('/', $type);
            $extension = ($extension == 'jpeg') ? 'jpg' : $extension;
            
            // Create avatars directory if it doesn't exist
            $avatarsDir = __DIR__ . '/uploads/avatars';
            if (!file_exists($avatarsDir)) {
                mkdir($avatarsDir, 0755, true);
            }
            
            // Generate a unique filename
            $filename = $userId . '_' . time() . '_cropped.' . $extension;
            $destination = $avatarsDir . '/' . $filename;
            
            // Save the cropped image
            if (file_put_contents($destination, $data)) {
                $avatar = 'uploads/avatars/' . $filename;
                
                // Delete old avatar if it exists and is not the default
                if (!empty($user['avatar']) && file_exists(__DIR__ . '/' . $user['avatar']) && strpos($user['avatar'], 'default') === false) {
                    unlink(__DIR__ . '/' . $user['avatar']);
                }
            } else {
                setFlashMessage('Failed to save cropped avatar.', 'danger');
            }
        } elseif (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
            $maxSize = 2 * 1024 * 1024; // 2MB
            
            $file = $_FILES['avatar'];
            
            if (!in_array($file['type'], $allowedTypes)) {
                setFlashMessage('Avatar must be a JPEG, PNG, or GIF image.', 'danger');
            } elseif ($file['size'] > $maxSize) {
                setFlashMessage('Avatar must be less than 2MB.', 'danger');
            } else {
                // Create avatars directory if it doesn't exist
                $avatarsDir = __DIR__ . '/uploads/avatars';
                if (!file_exists($avatarsDir)) {
                    mkdir($avatarsDir, 0755, true);
                }
                
                // Generate unique filename
                $filename = $userId . '_' . time() . '_' . $file['name'];
                $destination = $avatarsDir . '/' . $filename;
                
                if (move_uploaded_file($file['tmp_name'], $destination)) {
                    $avatar = 'uploads/avatars/' . $filename;
                    
                    // Delete old avatar if it exists and is not the default
                    if (!empty($user['avatar']) && file_exists(__DIR__ . '/' . $user['avatar']) && strpos($user['avatar'], 'default') === false) {
                        unlink(__DIR__ . '/' . $user['avatar']);
                    }
                } else {
                    setFlashMessage('Failed to upload avatar. Please try again.', 'danger');
                }
            }
        }
        
        // Update profile
        $profileData = [
            'bio' => $bio,
            'website' => $website,
            'avatar' => $avatar
        ];
        
        if ($userModel->updateProfile($userId, $profileData)) {
            setFlashMessage('Profile updated successfully.', 'success');
            redirect('profile.php');
        } else {
            setFlashMessage('Failed to update profile. Please try again.', 'danger');
        }
    }
}

// Process password change form
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    // Verify CSRF token
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        setFlashMessage('Invalid request. Please try again.', 'danger');
        redirect(BASE_URL . '/profile.php');
    }
    
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
        setFlashMessage('All password fields are required.', 'danger');
    } elseif (strlen($newPassword) < 8) {
        setFlashMessage('New password must be at least 8 characters.', 'danger');
    } elseif ($newPassword !== $confirmPassword) {
        setFlashMessage('New passwords do not match.', 'danger');
    } else {
        // Update password
        if ($auth->updatePassword($userId, $currentPassword, $newPassword)) {
            setFlashMessage('Password changed successfully.', 'success');
            redirect('profile.php');
        } else {
            setFlashMessage('Current password is incorrect.', 'danger');
        }
    }
}

// Get user's blog posts
$blogPostModel = new BlogPost();
$userPosts = $blogPostModel->getByUser($userId, 5);

// Get user's forum threads
$forumThreadModel = new ForumThread();
$userThreads = $forumThreadModel->getByUser($userId, 5);

// Get user's blog comments
$blogCommentModel = new BlogComment();
$userBlogComments = $blogCommentModel->getCommentsByUser($userId, 5);

// Get user's forum replies
$forumPostModel = new ForumPost();
$userForumReplies = $forumPostModel->getPostsByUser($userId, 5);

// Create unified activity timeline
$activityTimeline = [];

// Add blog posts to timeline
foreach ($userPosts as $post) {
    $activityTimeline[] = [
        'type' => 'blog_post',
        'title' => $post['title'],
        'url' => 'blog-post.php?slug=' . $post['slug'],
        'date' => $post['created_at'],
        'context' => 'Published a blog post'
    ];
}

// Add forum threads to timeline
foreach ($userThreads as $thread) {
    $activityTimeline[] = [
        'type' => 'forum_thread',
        'title' => $thread['title'],
        'url' => 'forum-thread.php?slug=' . $thread['slug'],
        'date' => $thread['created_at'],
        'context' => 'Started a forum thread'
    ];
}

// Add blog comments to timeline
foreach ($userBlogComments as $comment) {
    $activityTimeline[] = [
        'type' => 'blog_comment',
        'title' => $comment['post_title'],
        'url' => 'blog-post.php?slug=' . $comment['post_slug'] . '#comment-' . $comment['comment_id'],
        'date' => $comment['created_at'],
        'context' => 'Commented on a blog post'
    ];
}

// Add forum replies to timeline
foreach ($userForumReplies as $reply) {
    $activityTimeline[] = [
        'type' => 'forum_reply',
        'title' => $reply['thread_title'],
        'url' => 'forum-thread.php?slug=' . $reply['thread_slug'] . '#post-' . $reply['post_id'],
        'date' => $reply['created_at'],
        'context' => 'Replied to a forum thread'
    ];
}

// Sort timeline by date (newest first)
usort($activityTimeline, function($a, $b) {
    return strtotime($b['date']) - strtotime($a['date']);
});

// Get user's blog comments
$commentModel = new BlogComment();
$userComments = $commentModel->getCommentsByUser($userId, 5);

// Get user's forum posts (replies)
$forumPostModel = new ForumPost();
$userForumPosts = $forumPostModel->getPostsByUser($userId, 5);

// Create unified activity timeline
$unifiedActivity = [];

// Add blog posts to timeline
foreach ($userPosts as $post) {
    $unifiedActivity[] = [
        'type' => 'blog_post',
        'title' => $post['title'],
        'url' => 'blog_post.php?slug=' . $post['slug'],
        'date' => $post['published_at'],
        'context' => 'Posted in ' . $post['category_name']
    ];
}

// Add forum threads to timeline
foreach ($userThreads as $thread) {
    $unifiedActivity[] = [
        'type' => 'forum_thread',
        'title' => $thread['title'],
        'url' => 'forum_thread.php?slug=' . $thread['slug'],
        'date' => $thread['created_at'],
        'context' => 'Started thread in ' . $thread['subforum_name']
    ];
}

// Add blog comments to timeline
foreach ($userComments as $comment) {
    $unifiedActivity[] = [
        'type' => 'blog_comment',
        'title' => 'Comment on "' . $comment['post_title'] . '"',
        'url' => 'blog_post.php?slug=' . $comment['post_slug'] . '#comment-' . $comment['comment_id'],
        'date' => $comment['created_at'],
        'context' => 'Commented on blog post'
    ];
}

// Add forum posts to timeline
foreach ($userForumPosts as $post) {
    $unifiedActivity[] = [
        'type' => 'forum_post',
        'title' => 'Reply to "' . $post['thread_title'] . '"',
        'url' => 'forum_thread.php?slug=' . $post['thread_slug'] . '#post-' . $post['post_id'],
        'date' => $post['created_at'],
        'context' => 'Replied in forum thread'
    ];
}

// Sort unified activity by date (newest first)
usort($unifiedActivity, function($a, $b) {
    return strtotime($b['date']) - strtotime($a['date']);
});

// Page title
$pageTitle = 'My Profile';

// Include header
include_once __DIR__ . '/includes/header.php';
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-header">
                    <h2 class="h5 mb-0">Profile Information</h2>
                </div>
                <div class="card-body text-center">
                    <img src="<?php echo !empty($user['avatar']) ? $user['avatar'] : 'assets/default-avatar.svg'; ?>" alt="<?php echo $user['username']; ?>" class="avatar-lg mb-3">
                    
                    <h3 class="h4"><?php echo $user['username']; ?></h3>
                    <p class="text-muted">Member since <?php echo formatDate($user['created_at'], 'M j, Y'); ?></p>
                    
                    <?php if (!empty($user['bio'])): ?>
                        <div class="mt-3">
                            <h4 class="h6">About Me</h4>
                            <p><?php echo nl2br(sanitize($user['bio'])); ?></p>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($user['website'])): ?>
                        <div class="mt-3">
                            <a href="<?php echo sanitize($user['website']); ?>" target="_blank" rel="noopener noreferrer">
                                <i class="fas fa-globe me-1"></i> Website
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="col-md-8">
            <ul class="nav nav-tabs mb-4" id="profileTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="edit-tab" data-bs-toggle="tab" data-bs-target="#edit" type="button" role="tab" aria-controls="edit" aria-selected="true">Edit Profile</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="password-tab" data-bs-toggle="tab" data-bs-target="#password" type="button" role="tab" aria-controls="password" aria-selected="false">Change Password</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="activity-tab" data-bs-toggle="tab" data-bs-target="#activity" type="button" role="tab" aria-controls="activity" aria-selected="false">Activity</button>
                </li>
            </ul>
            
            <div class="tab-content" id="profileTabsContent">
                <!-- Edit Profile Tab -->
                <div class="tab-pane fade show active" id="edit" role="tabpanel" aria-labelledby="edit-tab">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="h5 mb-0">Edit Profile</h3>
                        </div>
                        <div class="card-body">
                            <form action="<?php echo BASE_URL; ?>/profile.php" method="post" enctype="multipart/form-data">
                                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                <input type="hidden" name="update_profile" value="1">
                                
                                <div class="mb-3">
                    <label for="avatar" class="form-label">Avatar</label>
                    <input type="file" class="form-control" id="avatar" name="avatar" accept="image/*">
                    <div class="form-text">Max file size: 2MB. Allowed formats: JPEG, PNG, GIF.</div>
                    <div class="mt-3" id="image-preview-container" style="display: none;">
                        <div class="card">
                            <div class="card-header">Crop Image</div>
                            <div class="card-body">
                                <div class="img-container mb-3">
                                    <img id="image-preview" src="" style="max-width: 100%; max-height: 300px;">
                                </div>
                                <button type="button" class="btn btn-primary" id="crop-button">Crop and Set Avatar</button>
                            </div>
                        </div>
                    </div>
                    <input type="hidden" name="cropped_image" id="cropped-image-data">
                </div>
                                
                                <div class="mb-3">
                                    <label for="bio" class="form-label">Bio</label>
                                    <textarea class="form-control" id="bio" name="bio" rows="4"><?php echo isset($user['bio']) ? sanitize($user['bio']) : ''; ?></textarea>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="website" class="form-label">Website</label>
                                    <input type="url" class="form-control" id="website" name="website" value="<?php echo isset($user['website']) ? sanitize($user['website']) : ''; ?>">
                                </div>
                                
                                <div class="d-grid">
                                    <button type="submit" class="btn btn-primary">Update Profile</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                
                <!-- Change Password Tab -->
                <div class="tab-pane fade" id="password" role="tabpanel" aria-labelledby="password-tab">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="h5 mb-0">Change Password</h3>
                        </div>
                        <div class="card-body">
                            <form action="<?php echo BASE_URL; ?>/profile.php" method="post">
                                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                <input type="hidden" name="change_password" value="1">
                                
                                <div class="mb-3">
                                    <label for="current_password" class="form-label">Current Password</label>
                                    <input type="password" class="form-control" id="current_password" name="current_password" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="new_password" class="form-label">New Password</label>
                                    <input type="password" class="form-control" id="new_password" name="new_password" required>
                                    <div class="form-text">Password must be at least 8 characters long.</div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="confirm_password" class="form-label">Confirm New Password</label>
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                </div>
                                
                                <div class="d-grid">
                                    <button type="submit" class="btn btn-primary">Change Password</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                
                <!-- Activity Tab -->
                <div class="tab-pane fade" id="activity" role="tabpanel" aria-labelledby="activity-tab">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h3 class="h5 mb-0">Activity Timeline</h3>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($activityTimeline)): ?>
                                <div class="timeline">
                                    <?php foreach ($activityTimeline as $activity): ?>
                                        <div class="timeline-item mb-4">
                                            <div class="d-flex">
                                                <div class="timeline-icon me-3">
                                                    <?php if ($activity['type'] === 'blog_post'): ?>
                                                        <i class="fas fa-file-alt text-primary"></i>
                                                    <?php elseif ($activity['type'] === 'forum_thread'): ?>
                                                        <i class="fas fa-comments text-success"></i>
                                                    <?php elseif ($activity['type'] === 'blog_comment'): ?>
                                                        <i class="fas fa-comment text-info"></i>
                                                    <?php elseif ($activity['type'] === 'forum_reply'): ?>
                                                        <i class="fas fa-reply text-warning"></i>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="timeline-content">
                                                    <h5 class="mb-1">
                                                        <a href="<?php echo $activity['url']; ?>" class="text-decoration-none">
                                                            <?php echo $activity['title']; ?>
                                                        </a>
                                                    </h5>
                                                    <div class="text-muted small mb-2">
                                                        <?php echo $activity['context']; ?> • <?php echo formatDate($activity['date']); ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <div class="alert alert-info mb-0">No activity found.</div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h3 class="h5 mb-0">My Blog Posts</h3>
                                </div>
                                <div class="card-body">
                                    <?php if (!empty($userPosts)): ?>
                                        <ul class="list-group list-group-flush">
                                            <?php foreach ($userPosts as $post): ?>
                                                <li class="list-group-item">
                                                    <a href="blog-post.php?slug=<?php echo $post['slug']; ?>" class="text-decoration-none">
                                                        <?php echo $post['title']; ?>
                                                    </a>
                                                    <div class="small text-muted mt-1">
                                                        <i class="fas fa-calendar me-1"></i> <?php echo formatDate($post['published_at']); ?>
                                                    </div>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                        
                                        <div class="mt-3">
                                            <a href="blog.php?author=<?php echo $userId; ?>" class="btn btn-sm btn-outline-primary">View All My Posts</a>
                                        </div>
                                    <?php else: ?>
                                        <div class="alert alert-info mb-0">You haven't created any blog posts yet.</div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h3 class="h5 mb-0">My Forum Threads</h3>
                                </div>
                                <div class="card-body">
                                    <?php if (!empty($userThreads)): ?>
                                        <ul class="list-group list-group-flush">
                                            <?php foreach ($userThreads as $thread): ?>
                                                <li class="list-group-item">
                                                    <a href="forum-thread.php?slug=<?php echo $thread['slug']; ?>" class="text-decoration-none">
                                                        <?php echo $thread['title']; ?>
                                                    </a>
                                                    <div class="small text-muted mt-1">
                                                        <i class="fas fa-calendar me-1"></i> <?php echo formatDate($thread['created_at']); ?>
                                                    </div>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                        
                                        <div class="mt-3">
                                            <a href="forum.php?author=<?php echo $userId; ?>" class="btn btn-sm btn-outline-primary">View All My Threads</a>
                                        </div>
                                    <?php else: ?>
                                        <div class="alert alert-info mb-0">You haven't created any forum threads yet.</div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Include footer
include_once __DIR__ . '/includes/footer.php';
?>