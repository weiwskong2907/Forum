<?php
// Include database connection
require_once __DIR__ . '/../includes/init.php';

// Get database connection
$db = Database::getInstance()->getConnection();

try {
    // Add edited_at column to forum_threads table
    $db->query("ALTER TABLE forum_threads ADD COLUMN edited_at DATETIME NULL DEFAULT NULL");
    echo "Added edited_at column to forum_threads table.\n";
    
    // Add edited_at column to forum_posts table
    $db->query("ALTER TABLE forum_posts ADD COLUMN edited_at DATETIME NULL DEFAULT NULL");
    echo "Added edited_at column to forum_posts table.\n";
    
    // Update existing records to have the same value as created_at
    $db->query("UPDATE forum_threads SET edited_at = created_at WHERE edited_at IS NULL");
    echo "Updated existing forum_threads records.\n";
    
    $db->query("UPDATE forum_posts SET edited_at = created_at WHERE edited_at IS NULL");
    echo "Updated existing forum_posts records.\n";
    
    echo "Database update completed successfully!\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}