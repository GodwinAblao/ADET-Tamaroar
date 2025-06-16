<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    header("Location: ../index.php");
    exit;
}

$user_id = $_SESSION['user_id'];

if (isset($_GET['id'])) {
    $book_id = intval($_GET['id']);

    // Check if the book exists and is available
    $bookCheck = $conn->prepare("SELECT status FROM books WHERE book_id = ?");
    $bookCheck->bind_param("i", $book_id);
    $bookCheck->execute();
    $bookResult = $bookCheck->get_result();

    if ($bookResult->num_rows === 0) {
        header("Location: ../student/browse_books.php?error=Book+not+found");
        exit;
    }

    $book = $bookResult->fetch_assoc();

    if ($book['status'] === 'Archived') {
        header("Location: ../student/browse_books.php?error=Cannot+borrow+archived+book");
        exit;
    }

    if ($book['status'] === 'Borrowed') {
        header("Location: ../student/browse_books.php?error=Book+already+borrowed");
        exit;
    }

    // Check if user already borrowed 2 books
    $borrowCheck = $conn->prepare("SELECT COUNT(*) as total FROM borrow_records WHERE user_id = ? AND return_date IS NULL");
    $borrowCheck->bind_param("i", $user_id);
    $borrowCheck->execute();
    $borrowResult = $borrowCheck->get_result();
    $borrowData = $borrowResult->fetch_assoc();

    if ($borrowData['total'] >= 2) {
        header("Location: ../student/browse_books.php?error=Limit+reached:+You+can+only+borrow+2+books");
        exit;
    }

    // Borrow the book
    $borrowDate = date('Y-m-d');
    $dueDate = date('Y-m-d', strtotime('+7 days'));

    $borrowStmt = $conn->prepare("INSERT INTO borrow_records (user_id, book_id, borrow_date, due_date) VALUES (?, ?, ?, ?)");
    $borrowStmt->bind_param("iiss", $user_id, $book_id, $borrowDate, $dueDate);

    if ($borrowStmt->execute()) {
        // Update book status
        $updateStmt = $conn->prepare("UPDATE books SET status = 'Borrowed' WHERE book_id = ?");
        $updateStmt->bind_param("i", $book_id);
        $updateStmt->execute();

        header("Location: ../student/borrowed_books.php?success=Book+borrowed+successfully");
    } else {
        header("Location: ../student/browse_books.php?error=Failed+to+borrow+book");
    }

} else {
    header("Location: ../student/browse_books.php?error=Invalid+Request");
}
?>
