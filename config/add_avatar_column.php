<?php
require_once __DIR__ . '/../includes/init.php';

try {
    $db = Database::getInstance();
    $query = "ALTER TABLE users ADD COLUMN avatar VARCHAR(255) DEFAULT 'assets/default-avatar.svg' AFTER email";
    $db->query($query);
    echo "Avatar column added successfully!";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>