<?php
session_start();
require_once '../config/db.php';

// Only allow admins
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

$book_id = isset($_GET['id']) ? intval($_GET['id']) : (isset($_POST['id']) ? intval($_POST['id']) : 0);

if ($book_id > 0) {
    $stmt = $conn->prepare("UPDATE books SET status = 'available' WHERE id = ?");
    $stmt->bind_param('i', $book_id);
    if ($stmt->execute()) {
        $_SESSION['success'] = 'Book restored successfully!';
    } else {
        $_SESSION['error'] = 'Failed to restore book.';
    }
} else {
    $_SESSION['error'] = 'Invalid book ID.';
}

header('Location: ../admin/enhanced_manage_books.php');
exit; 