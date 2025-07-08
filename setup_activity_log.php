<?php
require_once 'config/db.php';

echo "<h1>Setting up Activity Log Table</h1>";

try {
    // Create activity_log table
    $sql = "CREATE TABLE IF NOT EXISTS `activity_log` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `user_id` int(11) NOT NULL,
        `activity_type` varchar(50) NOT NULL,
        `description` text NOT NULL,
        `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `user_id` (`user_id`),
        KEY `activity_type` (`activity_type`),
        KEY `created_at` (`created_at`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $pdo->exec($sql);
    echo "<p style='color: green;'>✅ Activity log table created successfully!</p>";
    
    // Check if table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'activity_log'");
    if ($stmt->rowCount() > 0) {
        echo "<p style='color: green;'>✅ Activity log table exists and is ready to use!</p>";
    } else {
        echo "<p style='color: red;'>❌ Activity log table was not created properly.</p>";
    }
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>❌ Error creating activity log table: " . $e->getMessage() . "</p>";
}

echo "<br><a href='admin/enhanced_manage_books.php'>Back to Manage Books</a>";
?> 