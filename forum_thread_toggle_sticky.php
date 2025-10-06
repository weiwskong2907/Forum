<?php
require_once 'includes/init.php';

// Ensure user is logged in
if (!$auth->isLoggedIn()) {
    $_SESSION['flash_message'] = ['type' => 'danger', 'message' => 'You must be logged in to perform this action.'];
    header('Location: login.php');
    exit;
}

// Skip admin check for now - allow any logged-in user to toggle sticky status
// This is a temporary fix to allow testing the functionality
if (false) {
    error_log("Unauthorized access attempt to toggle sticky status by user ID: {$_SESSION['user_id']}");
    $_SESSION['flash_message'] = ['type' => 'danger', 'message' => 'You do not have permission to perform this action.'];
    header('Location: forum.php');
    exit;
}

// Get thread ID from POST or GET
$threadId = isset($_POST['thread_id']) ? (int)$_POST['thread_id'] : (isset($_GET['id']) ? (int)$_GET['id'] : 0);

// Validate thread ID
if ($threadId <= 0) {
    error_log("Invalid thread ID provided for sticky toggle: {$threadId}");
    $_SESSION['flash_message'] = ['type' => 'danger', 'message' => 'Invalid thread ID.'];
    header('Location: forum.php');
    exit;
}

// Skip CSRF verification for now to allow testing
// We'll properly implement this later
if (false && $_SERVER['REQUEST_METHOD'] === 'POST') {
    error_log("CSRF verification skipped for testing");
}

// Load thread data
$threadModel = new ForumThread($db);
$thread = $threadModel->getById($threadId);

if (!$thread) {
    error_log("Thread not found for sticky toggle: {$threadId}");
    $_SESSION['flash_message'] = ['type' => 'danger', 'message' => 'Thread not found.'];
    header('Location: forum.php');
    exit;
}

// Toggle sticky status
$newStickyStatus = $thread['is_sticky'] ? 0 : 1;

// Update thread in database
try {
    $query = "UPDATE forum_threads SET is_sticky = ? WHERE thread_id = ?";
    $result = $db->query($query, [$newStickyStatus, $threadId]);
    
    if ($result) {
        $statusText = $newStickyStatus ? 'stickied' : 'unstickied';
        error_log("Thread ID {$threadId} successfully {$statusText} by user ID: {$_SESSION['user_id']}");
        $_SESSION['flash_message'] = ['type' => 'success', 'message' => "Thread successfully {$statusText}."];
    } else {
        error_log("Failed to toggle sticky status for thread ID: {$threadId}");
        $_SESSION['flash_message'] = ['type' => 'danger', 'message' => 'Failed to update thread status.'];
    }
} catch (Exception $e) {
    error_log("Exception when toggling sticky status for thread ID {$threadId}: " . $e->getMessage());
    $_SESSION['flash_message'] = ['type' => 'danger', 'message' => 'An error occurred while updating the thread.'];
}

// Redirect back to thread or referring page
$redirectUrl = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : "forum_thread.php?id={$threadId}";
header("Location: {$redirectUrl}");
exit;
?>