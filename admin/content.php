<?php
require_once '../includes/init.php';

// Check if user is logged in and is an admin
if (!isLoggedIn() || !isAdmin()) {
    setFlashMessage('You do not have permission to access the admin area.', 'danger');
    redirect('../index.php');
}

$blogPostModel = new BlogPost();
$blogCommentModel = new BlogComment();
$forumThreadModel = new ForumThread();
$forumPostModel = new ForumPost();

// Handle content actions
if (isset($_GET['action'])) {
    $action = $_GET['action'];
    $type = isset($_GET['type']) ? $_GET['type'] : '';
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    
    if ($id > 0) {
        switch ($action) {
            case 'delete':
                if ($type == 'blog') {
                    if ($blogPostModel->delete($id)) {
                        setFlashMessage('Blog post deleted successfully.', 'success');
                    } else {
                        setFlashMessage('Failed to delete blog post.', 'danger');
                    }
                } elseif ($type == 'blog_comment') {
                    if ($blogCommentModel->delete($id)) {
                        setFlashMessage('Blog comment deleted successfully.', 'success');
                    } else {
                        setFlashMessage('Failed to delete blog comment.', 'danger');
                    }
                } elseif ($type == 'forum_thread') {
                    if ($forumThreadModel->delete($id)) {
                        setFlashMessage('Forum thread deleted successfully.', 'success');
                    } else {
                        setFlashMessage('Failed to delete forum thread.', 'danger');
                    }
                } elseif ($type == 'forum_post') {
                    if ($forumPostModel->delete($id)) {
                        setFlashMessage('Forum post deleted successfully.', 'success');
                    } else {
                        setFlashMessage('Failed to delete forum post.', 'danger');
                    }
                }
                break;
                
            case 'approve':
                if ($type == 'blog_comment') {
                    if ($blogCommentModel->approve($id)) {
                        setFlashMessage('Blog comment approved successfully.', 'success');
                    } else {
                        setFlashMessage('Failed to approve blog comment.', 'danger');
                    }
                }
                break;
                
            case 'unapprove':
                if ($type == 'blog_comment') {
                    if ($blogCommentModel->unapprove($id)) {
                        setFlashMessage('Blog comment unapproved successfully.', 'success');
                    } else {
                        setFlashMessage('Failed to unapprove blog comment.', 'danger');
                    }
                }
                break;
        }
    }
    
    redirect('content.php');
}

// Get content for moderation
$contentType = isset($_GET['content_type']) ? $_GET['content_type'] : 'all';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 15;
$offset = ($page - 1) * $limit;

// Get content based on type
$content = [];
$totalItems = 0;

if ($contentType == 'blog' || $contentType == 'all') {
    $blogPosts = $blogPostModel->getAll($limit, $offset);
    foreach ($blogPosts as $post) {
        $content[] = [
            'id' => $post['post_id'],
            'type' => 'blog',
            'title' => $post['title'],
            'author' => $post['username'],
            'date' => $post['created_at'],
            'url' => '../blog-post.php?slug=' . $post['slug']
        ];
    }
    $totalItems += $blogPostModel->getTotalCount();
}

if ($contentType == 'blog_comment' || $contentType == 'all') {
    $blogComments = $blogCommentModel->getAll($limit, $offset);
    foreach ($blogComments as $comment) {
        $content[] = [
            'id' => $comment['comment_id'],
            'type' => 'blog_comment',
            'title' => 'Comment on: ' . $comment['post_title'],
            'author' => $comment['username'],
            'date' => $comment['created_at'],
            'url' => '../blog-post.php?slug=' . $comment['post_slug'] . '#comment-' . $comment['comment_id'],
            'is_approved' => $comment['is_approved']
        ];
    }
    $totalItems += $blogCommentModel->getTotalCount();
}

if ($contentType == 'forum_thread' || $contentType == 'all') {
    $forumThreads = $forumThreadModel->getAll($limit, $offset);
    foreach ($forumThreads as $thread) {
        $content[] = [
            'id' => $thread['thread_id'],
            'type' => 'forum_thread',
            'title' => $thread['title'],
            'author' => $thread['username'],
            'date' => $thread['created_at'],
            'url' => '../forum-thread.php?slug=' . $thread['slug']
        ];
    }
    $totalItems += $forumThreadModel->getTotalCount();
}

if ($contentType == 'forum_post' || $contentType == 'all') {
    $forumPosts = $forumPostModel->getAll($limit, $offset);
    foreach ($forumPosts as $post) {
        $content[] = [
            'id' => $post['post_id'],
            'type' => 'forum_post',
            'title' => 'Reply to: ' . $post['thread_title'],
            'author' => $post['username'],
            'date' => $post['created_at'],
            'url' => '../forum-thread.php?slug=' . $post['thread_slug'] . '#post-' . $post['post_id']
        ];
    }
    $totalItems += $forumPostModel->getTotalCount();
}

// Sort content by date (newest first)
usort($content, function($a, $b) {
    return strtotime($b['date']) - strtotime($a['date']);
});

// Limit content to the current page
$content = array_slice($content, 0, $limit);

// Calculate pagination
$totalPages = ceil($totalItems / $limit);

$pageTitle = 'Content Moderation';
include __DIR__ . '/includes/admin_header.php';
?>

<div class="container mt-4">
    <div class="row">
        <main class="col-12 px-md-4 py-4">
            <!-- Content Moderation -->
            <div class="card mb-4 shadow-sm border">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 d-flex align-items-center">
                        <i class="bi bi-file-earmark-text me-2"></i> Content Moderation
                    </h5>
                </div>
                <div class="card-body">
                    <?php displayFlashMessages(); ?>
                    
                    <!-- Content Type Filter -->
                    <div class="mb-4">
                        <h6 class="mb-3"><i class="bi bi-funnel-fill me-2"></i>Filter by Content Type:</h6>
                        <div class="btn-group shadow-sm" role="group">
                            <a href="content.php?content_type=all" class="btn btn-outline-primary <?php echo $contentType == 'all' ? 'active' : ''; ?>">
                                <i class="bi bi-collection me-1"></i> All Content
                            </a>
                            <a href="content.php?content_type=blog" class="btn btn-outline-primary <?php echo $contentType == 'blog' ? 'active' : ''; ?>">
                                <i class="bi bi-file-earmark-text me-1"></i> Blog Posts
                            </a>
                            <a href="content.php?content_type=blog_comment" class="btn btn-outline-primary <?php echo $contentType == 'blog_comment' ? 'active' : ''; ?>">
                                <i class="bi bi-chat-text me-1"></i> Blog Comments
                            </a>
                            <a href="content.php?content_type=forum_thread" class="btn btn-outline-primary <?php echo $contentType == 'forum_thread' ? 'active' : ''; ?>">
                                <i class="bi bi-chat-square-text me-1"></i> Forum Threads
                            </a>
                            <a href="content.php?content_type=forum_post" class="btn btn-outline-primary <?php echo $contentType == 'forum_post' ? 'active' : ''; ?>">
                                <i class="bi bi-chat-square me-1"></i> Forum Posts
                            </a>
                        </div>
                    </div>
                    
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Type</th>
                                    <th>Title</th>
                                    <th>Author</th>
                                    <th>Date</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($content) > 0): ?>
                                    <?php foreach ($content as $item): ?>
                                    <tr>
                                        <td>
                                            <?php if ($item['type'] == 'blog'): ?>
                                                <span class="badge badge-success">Blog Post</span>
                                            <?php elseif ($item['type'] == 'blog_comment'): ?>
                                                <span class="badge badge-info">Blog Comment</span>
                                            <?php elseif ($item['type'] == 'forum_thread'): ?>
                                                <span class="badge badge-primary">Forum Thread</span>
                                            <?php elseif ($item['type'] == 'forum_post'): ?>
                                                <span class="badge badge-warning">Forum Post</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <a href="<?php echo $item['url']; ?>" target="_blank">
                                                <?php echo htmlspecialchars($item['title']); ?>
                                            </a>
                                        </td>
                                        <td><?php echo htmlspecialchars($item['author']); ?></td>
                                        <td><?php echo date('M j, Y', strtotime($item['date'])); ?></td>
                                        <td>
                                            <?php if ($item['type'] == 'blog_comment'): ?>
                                                <?php if ($item['is_approved']): ?>
                                                    <span class="badge badge-success">Approved</span>
                                                <?php else: ?>
                                                    <span class="badge badge-danger">Pending</span>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <span class="badge badge-secondary">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <a href="<?php echo $item['url']; ?>" target="_blank" class="btn btn-sm btn-info" title="View">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                
                                                <?php if ($item['type'] == 'blog_comment'): ?>
                                                    <?php if (!$item['is_approved']): ?>
                                                        <a href="content.php?action=approve&type=blog_comment&id=<?php echo $item['id']; ?>" class="btn btn-sm btn-success" title="Approve">
                                                            <i class="fas fa-check"></i>
                                                        </a>
                                                    <?php else: ?>
                                                        <a href="content.php?action=unapprove&type=blog_comment&id=<?php echo $item['id']; ?>" class="btn btn-sm btn-warning" title="Unapprove">
                                                            <i class="fas fa-ban"></i>
                                                        </a>
                                                    <?php endif; ?>
                                                <?php endif; ?>
                                                
                                                <a href="content.php?action=delete&type=<?php echo $item['type']; ?>&id=<?php echo $item['id']; ?>" class="btn btn-sm btn-danger" title="Delete" onclick="return confirm('Are you sure you want to delete this item?')">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="text-center">No content found.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Pagination -->
                    <?php if ($totalPages > 1): ?>
                    <nav aria-label="Content pagination">
                        <ul class="pagination justify-content-center">
                            <?php if ($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="content.php?content_type=<?php echo $contentType; ?>&page=<?php echo $page - 1; ?>">Previous</a>
                                </li>
                            <?php else: ?>
                                <li class="page-item disabled">
                                    <span class="page-link">Previous</span>
                                </li>
                            <?php endif; ?>
                            
                            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                    <a class="page-link" href="content.php?content_type=<?php echo $contentType; ?>&page=<?php echo $i; ?>"><?php echo $i; ?></a>
                                </li>
                            <?php endfor; ?>
                            
                            <?php if ($page < $totalPages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="content.php?content_type=<?php echo $contentType; ?>&page=<?php echo $page + 1; ?>">Next</a>
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

<?php include __DIR__ . '/includes/admin_footer.php'; ?>
