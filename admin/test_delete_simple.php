<?php
session_start();
require_once '../config/db.php';
require_once '../config/enhanced_functions.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

// Find a book with no borrowings
$stmt = $pdo->prepare("
    SELECT b.*, c.name as category_name 
    FROM books b 
    LEFT JOIN categories c ON b.category_id = c.id 
    WHERE b.id NOT IN (
        SELECT DISTINCT book_id FROM borrowings WHERE status IN ('borrowed', 'overdue')
    )
    ORDER BY b.created_at DESC 
    LIMIT 1
");
$stmt->execute();
$book = $stmt->fetch();

if (!$book) {
    echo "<h1>No books available for deletion test</h1>";
    echo "<p>All books have active borrowings. Please return some books first.</p>";
    echo "<a href='enhanced_manage_books.php'>Back to Manage Books</a>";
    exit;
}

echo "<h1>Simple Delete Test</h1>";
echo "<h2>Testing deletion of: " . htmlspecialchars($book['title']) . "</h2>";
echo "<p>Book ID: " . $book['id'] . "</p>";
echo "<p>Author: " . htmlspecialchars($book['author']) . "</p>";

// Test canDeleteBook
$canDelete = canDeleteBook($book['id']);
echo "<h3>Can Delete Check:</h3>";
echo "<pre>" . print_r($canDelete, true) . "</pre>";

if ($canDelete['can_delete']) {
    echo "<p><strong>✅ This book can be deleted!</strong></p>";
    echo "<a href='confirm_delete_book.php?id=" . $book['id'] . "' style='background: #dc3545; color: white; padding: 1rem; text-decoration: none; border-radius: 5px;'>Test Delete This Book</a>";
} else {
    echo "<p><strong>❌ This book cannot be deleted: " . $canDelete['reason'] . "</strong></p>";
}

echo "<br><br><a href='enhanced_manage_books.php'>Back to Manage Books</a>";
?> 