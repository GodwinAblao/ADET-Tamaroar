<?php
session_start();
file_put_contents(__DIR__.'/debug_borrow.txt', "\n==== New Request ====\n", FILE_APPEND);
file_put_contents(__DIR__.'/debug_borrow.txt', 'POST: '.print_r($_POST, true), FILE_APPEND);
require_once '../config/db.php';
require_once '../config/functions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    file_put_contents(__DIR__.'/debug_borrow.txt', "Redirect: not logged in\n", FILE_APPEND);
    header('Location: ../index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    $book_id = intval($_POST['book_id']);

    // Validate book ID
    if ($book_id <= 0) {
        $_SESSION['error'] = 'Invalid book selection.';
        file_put_contents(__DIR__.'/debug_borrow.txt', "Redirect: invalid book id\n", FILE_APPEND);
        header("Location: ../student/enhanced_browse_books.php");
        exit;
    }

    // Check if user can borrow more books (max 2)
    if (!canUserBorrow($user_id)) {
        // Check if user is suspended specifically
        if (isUserSuspended($user_id)) {
            $_SESSION['error'] = 'Your account has been suspended. You cannot borrow books. Please contact the administrator.';
        } else {
            $_SESSION['error'] = 'You can only borrow 2 books at a time. Please return a book first.';
        }
        file_put_contents(__DIR__.'/debug_borrow.txt', "Redirect: borrow limit\n", FILE_APPEND);
        header("Location: ../student/enhanced_browse_books.php");
        exit;
    }

    // Check if book is available
    if (!isBookAvailable($book_id)) {
        $_SESSION['error'] = 'This book is not available for borrowing.';
        file_put_contents(__DIR__.'/debug_borrow.txt', "Redirect: not available\n", FILE_APPEND);
        header("Location: ../student/enhanced_browse_books.php");
        exit;
    }

    // Check if user has already borrowed this book
    $stmt = $conn->prepare("SELECT id FROM borrowings WHERE user_id = ? AND book_id = ? AND status = 'borrowed'");
    $stmt->bind_param("ii", $user_id, $book_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $_SESSION['error'] = 'You have already borrowed this book.';
        file_put_contents(__DIR__.'/debug_borrow.txt', "Redirect: already borrowed\n", FILE_APPEND);
        header("Location: ../student/enhanced_browse_books.php");
        exit;
    }

    // Calculate due date (7 days from now, including weekends)
    $borrow_date = date('Y-m-d');
    $due_date = date('Y-m-d', strtotime('+7 days'));

    // Start transaction
    $conn->begin_transaction();

    try {
        // Insert borrowing record
        $stmt = $conn->prepare("INSERT INTO borrowings (user_id, book_id, borrowed_date, due_date, status) VALUES (?, ?, ?, ?, 'borrowed')");
        $stmt->bind_param("iiss", $user_id, $book_id, $borrow_date, $due_date);

        if (!$stmt->execute()) {
            throw new Exception('Error creating borrowing record.');
        }

        // Update book availability
        if (!updateBookAvailability($book_id, 'borrow')) {
            throw new Exception('Error updating book availability.');
        }

        // Commit transaction
        $conn->commit();

        $_SESSION['success'] = 'Book borrowed successfully! Due date: ' . date('F j, Y', strtotime($due_date));
        file_put_contents(__DIR__.'/debug_borrow.txt', "Redirect: success to borrow_books.php\n", FILE_APPEND);
        header("Location: ../student/borrow_books.php");
        exit;

    } catch (Exception $e) {
        // Rollback transaction
        $conn->rollback();
        $_SESSION['error'] = 'Error borrowing book: ' . $e->getMessage();
        file_put_contents(__DIR__.'/debug_borrow.txt', "Redirect: exception - ".$e->getMessage()."\n", FILE_APPEND);
        header("Location: ../student/enhanced_browse_books.php");
        exit;
    }
} else {
    file_put_contents(__DIR__.'/debug_borrow.txt', "Redirect: not POST\n", FILE_APPEND);
    header("Location: ../student/enhanced_browse_books.php");
    exit;
}
?>
