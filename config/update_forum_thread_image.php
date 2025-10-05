<?php
/**
 * Add featured_image column to forum_threads table
 */

// Include database connection
require_once __DIR__ . '/../includes/init.php';

// SQL to add featured_image column
$sql = "ALTER TABLE forum_threads ADD COLUMN featured_image VARCHAR(255) DEFAULT NULL AFTER is_locked";

try {
    // Execute the SQL
    $db = Database::getInstance()->getConnection();
    
    // Check if we're using mysqli or PDO
    if ($db instanceof mysqli) {
        $result = $db->query($sql);
        if ($result) {
            echo "Successfully added featured_image column to forum_threads table.\n";
        }
    } else {
        // For PDO
        $db->exec($sql);
        echo "Successfully added featured_image column to forum_threads table.\n";
    }
} catch (Exception $e) {
    // Check if the error is because the column already exists
    if (($e instanceof PDOException && $e->getCode() == '42S21') || 
        strpos($e->getMessage(), 'Duplicate column name') !== false) {
        echo "Column featured_image already exists in forum_threads table.\n";
    } else {
        echo "Error: " . $e->getMessage() . "\n";
    }
}