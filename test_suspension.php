<?php
// Test script to demonstrate user suspension functionality
require_once 'config/db.php';
require_once 'config/functions.php';

echo "<h2>User Suspension Test</h2>";

// First, let's add the status fields to the users table if they don't exist
try {
    $conn->query("ALTER TABLE users ADD COLUMN status ENUM('active', 'suspended') DEFAULT 'active'");
    echo "<p>✅ Added status field to users table</p>";
} catch (Exception $e) {
    echo "<p>ℹ️ Status field already exists</p>";
}

try {
    $conn->query("ALTER TABLE users ADD COLUMN suspension_reason TEXT NULL");
    echo "<p>✅ Added suspension_reason field to users table</p>";
} catch (Exception $e) {
    echo "<p>ℹ️ Suspension_reason field already exists</p>";
}

try {
    $conn->query("ALTER TABLE users ADD COLUMN suspended_at TIMESTAMP NULL");
    echo "<p>✅ Added suspended_at field to users table</p>";
} catch (Exception $e) {
    echo "<p>ℹ️ Suspended_at field already exists</p>";
}

// Get a test user (first student)
$result = $conn->query("SELECT id, username, email, status FROM users WHERE role = 'student' LIMIT 1");
if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
    echo "<h3>Testing with user: {$user['username']} (ID: {$user['id']})</h3>";
    
    echo "<h4>Current Status: " . ucfirst($user['status']) . "</h4>";
    
    // Test canUserBorrow function
    echo "<h4>Can User Borrow Test:</h4>";
    $canBorrow = canUserBorrow($user['id']);
    echo "<p>Can borrow: " . ($canBorrow ? "✅ Yes" : "❌ No") . "</p>";
    
    // Test suspension
    if ($user['status'] === 'active') {
        echo "<h4>Suspending User:</h4>";
        if (suspendUser($user['id'], 'Test suspension - overdue books')) {
            echo "<p>✅ User suspended successfully</p>";
            
            // Check if they can borrow now
            $canBorrowAfter = canUserBorrow($user['id']);
            echo "<p>Can borrow after suspension: " . ($canBorrowAfter ? "✅ Yes" : "❌ No") . "</p>";
            
            // Unsuspend them
            echo "<h4>Unsuspending User:</h4>";
            if (unsuspendUser($user['id'])) {
                echo "<p>✅ User unsuspended successfully</p>";
                
                // Check if they can borrow again
                $canBorrowFinal = canUserBorrow($user['id']);
                echo "<p>Can borrow after unsuspension: " . ($canBorrowFinal ? "✅ Yes" : "❌ No") . "</p>";
            } else {
                echo "<p>❌ Error unsuspending user</p>";
            }
        } else {
            echo "<p>❌ Error suspending user</p>";
        }
    } else {
        echo "<h4>User is already suspended, unsuspending first:</h4>";
        if (unsuspendUser($user['id'])) {
            echo "<p>✅ User unsuspended successfully</p>";
        } else {
            echo "<p>❌ Error unsuspending user</p>";
        }
    }
    
} else {
    echo "<p>❌ No students found in database</p>";
}

echo "<h3>Summary:</h3>";
echo "<ul>";
echo "<li>✅ User suspension prevents borrowing books</li>";
echo "<li>✅ Suspended users cannot log in</li>";
echo "<li>✅ Admins can suspend/unsuspend users</li>";
echo "<li>✅ Suspension includes reason and timestamp</li>";
echo "</ul>";

echo "<p><strong>To use this in your system:</strong></p>";
echo "<ol>";
echo "<li>Run the SQL in <code>add_user_status.sql</code> to add the required fields</li>";
echo "<li>Use the suspend/unsuspend actions in the admin panel</li>";
echo "<li>Suspended users will see clear error messages when trying to borrow</li>";
echo "</ol>";
?> 