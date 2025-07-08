<?php
session_start();
require_once '../config/db.php';
require_once '../config/enhanced_functions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header('Location: ../index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    $borrowing_id = intval($_POST['borrowing_id']);

    // Validate borrowing ID
    if ($borrowing_id <= 0) {
        $_SESSION['error'] = 'Invalid return request.';
        header("Location: ../student/my_books.php");
        exit;
    }

    // Use the enhanced processReturn function for unified logic and notifications
    $result = processReturn($borrowing_id);

    if ($result['valid']) {
        if (isset($result['fine_amount']) && $result['fine_amount'] > 0) {
            $_SESSION['success'] = 'Book returned successfully! Fine amount: ' . number_format($result['fine_amount'], 2);
        } else {
            $_SESSION['success'] = 'Book returned successfully! No fine incurred.';
        }
    } else {
        $_SESSION['error'] = $result['message'] ?? 'Error returning book.';
    }
    header("Location: ../student/my_books.php");
    exit;
} else {
    header("Location: ../student/my_books.php");
    exit;
}
?>
