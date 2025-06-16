<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    header("Location: ../index.php");
    exit;
}

$user_id = $_SESSION['user_id'];

if (isset($_GET['id'])) {
    $record_id = intval($_GET['id']);

    // Check if the borrow record exists and belongs to this user
    $stmt = $conn->prepare("SELECT book_id FROM borrow_records WHERE id = ? AND user_id = ? AND return_date IS NULL");
    $stmt->bind_param("ii", $record_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        header("Location: ../student/borrowed_books.php?error=Invalid+borrow+record");
        exit;
    }

    $data = $result->fetch_assoc();
    $book_id = $data['book_id'];

    // Update return date
    $return_date = date('Y-m-d');
    $updateRecord = $conn->prepare("UPDATE borrow_records SET return_date = ? WHERE id = ?");
    $updateRecord->bind_param("si", $return_date, $record_id);

    // Set book status back to Available
    $updateBook = $conn->prepare("UPDATE books SET status = 'Available' WHERE book_id = ?");
    $updateBook->bind_param("i", $book_id);

    if ($updateRecord->execute() && $updateBook->execute()) {
        header("Location: ../student/borrowed_books.php?success=Book+returned+successfully");
    } else {
        header("Location: ../student/borrowed_books.php?error=Failed+to+return+book");
    }
} else {
    header("Location: ../student/borrowed_books.php?error=Invalid+request");
}
?>
