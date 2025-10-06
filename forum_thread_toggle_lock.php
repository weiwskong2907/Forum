<?php
require_once 'includes/init.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    $_SESSION['flash_message'] = ['type' => 'danger', 'message' => 'You must be logged in to perform this action.'];
    header('Location: login.php');
    exit;
}

// Get thread ID from POST or GET
$threadId = isset($_POST['thread_id']) ? $_POST['thread_id'] : (isset($_GET['thread_id']) ? $_GET['thread_id'] : 0);

// If thread_id is not provided, try to get it from slug
if (!$threadId && isset($_GET['slug'])) {
    $threadModel = new ForumThread();
    $thread = $threadModel->getBySlug($_GET['slug']);
    if ($thread) {
        $threadId = $thread['thread_id'];
    }
}

$threadId = (int)$threadId;

// Validate thread ID
if ($threadId <= 0) {
    $_SESSION['flash_message'] = ['type' => 'danger', 'message' => 'Invalid thread ID.'];
    header('Location: forum.php');
    exit;
}

// Check if user has admin privileges
if (!isAdmin()) {
    $_SESSION['flash_message'] = ['type' => 'danger', 'message' => 'You do not have permission to perform this action.'];
    header('Location: forum_thread.php?slug=' . (isset($_GET['slug']) ? $_GET['slug'] : $thread['slug']));
    exit;
}

// Load thread data
$threadQuery = "SELECT * FROM forum_threads WHERE thread_id = " . $threadId;
$result = $db->getConnection()->query($threadQuery);
$thread = null;
if ($result) {
    $thread = $result->fetch_assoc();
}

if (!$thread) {
    $_SESSION['flash_message'] = ['type' => 'danger', 'message' => 'Thread not found.'];
    header('Location: forum.php');
    exit;
}

// Toggle locked status
$newLockedStatus = isset($thread['is_locked']) && $thread['is_locked'] == 1 ? 0 : 1;

// Update thread in database
try {
    $query = "UPDATE forum_threads SET is_locked = " . $newLockedStatus . " WHERE thread_id = " . $threadId;
    $result = $db->getConnection()->query($query);
    
    if ($result) {
        $statusText = $newLockedStatus ? 'locked' : 'unlocked';
        error_log("Thread ID {$threadId} successfully {$statusText} by user ID: {$_SESSION['user_id']}");
        $_SESSION['flash_message'] = ['type' => 'success', 'message' => "Thread successfully {$statusText}."];
    } else {
        error_log("Failed to toggle lock status for thread ID: {$threadId}");
        $_SESSION['flash_message'] = ['type' => 'danger', 'message' => 'Failed to update thread status.'];
    }
} catch (Exception $e) {
    error_log("Exception when toggling lock status for thread ID {$threadId}: " . $e->getMessage());
    $_SESSION['flash_message'] = ['type' => 'danger', 'message' => 'An error occurred while updating the thread.'];
}

// Redirect back to thread
$slug = isset($_GET['slug']) ? $_GET['slug'] : $thread['slug'];
header('Location: forum_thread.php?slug=' . $slug);
exit;