<?php
session_start();
require_once '../config/db.php';
require_once '../config/functions.php';

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
        header("Location: ../student/borrow_books.php");
        exit;
    }

    // Check if the borrow record exists and belongs to this user
    $stmt = $conn->prepare("SELECT b.*, bk.title FROM borrowings b JOIN books bk ON b.book_id = bk.id WHERE b.id = ? AND b.user_id = ? AND b.status = 'borrowed'");
    $stmt->bind_param("ii", $borrowing_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        $_SESSION['error'] = 'Invalid borrow record or book already returned.';
        header("Location: ../student/borrow_books.php");
        exit;
    }

    $borrowing = $result->fetch_assoc();
    $book_id = $borrowing['book_id'];
    $due_date = $borrowing['due_date'];

    // Calculate fine if overdue
    $return_date = date('Y-m-d');
    $fine_amount = calculateFine($due_date, $return_date);

    // Start transaction
    $conn->begin_transaction();

    try {
        // Update borrowing record
        $stmt = $conn->prepare("UPDATE borrowings SET return_date = ?, fine_amount = ?, status = 'returned' WHERE id = ?");
        $stmt->bind_param("sdi", $return_date, $fine_amount, $borrowing_id);

        if (!$stmt->execute()) {
            throw new Exception('Error updating borrowing record.');
        }

        // Update book availability
        if (!updateBookAvailability($book_id, 'return')) {
            throw new Exception('Error updating book availability.');
        }

        // Commit transaction
        $conn->commit();

        if ($fine_amount > 0) {
            $_SESSION['success'] = 'Book returned successfully! Fine amount: ' . formatCurrency($fine_amount);
        } else {
            $_SESSION['success'] = 'Book returned successfully! No fine incurred.';
        }
        
        header("Location: ../student/borrow_books.php");
        exit;

    } catch (Exception $e) {
        // Rollback transaction
        $conn->rollback();
        $_SESSION['error'] = 'Error returning book: ' . $e->getMessage();
        header("Location: ../student/borrow_books.php");
        exit;
    }
} else {
    header("Location: ../student/borrow_books.php");
    exit;
}
?>
