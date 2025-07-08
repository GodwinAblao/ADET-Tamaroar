<?php
session_start();
require_once '../config/db.php';
require_once '../config/enhanced_functions.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

// Check if book ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error'] = "Invalid book ID provided.";
    header("Location: ../admin/enhanced_manage_books.php");
    exit;
}

$book_id = (int)$_GET['id'];
$admin_id = $_SESSION['user_id'];

// Use the safe delete function
$result = safeDeleteBook($book_id, $admin_id);

if ($result['success']) {
    $_SESSION['success'] = $result['message'];
} else {
    $_SESSION['error'] = $result['message'];
}

header("Location: ../admin/enhanced_manage_books.php");
exit;
?> 