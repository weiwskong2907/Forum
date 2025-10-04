<?php
// Check if user is admin
if (!isAdmin()) {
    setFlashMessage('You do not have permission to access this page.', 'danger');
    redirect(BASE_URL . '/index.php');
}

// Set page title if not already set
if (!isset($pageTitle)) {
    $pageTitle = 'Admin Dashboard';
}

// Include main header
include_once __DIR__ . '/../../includes/header.php';
?>

<div class="container-fluid">
    <div class="row">