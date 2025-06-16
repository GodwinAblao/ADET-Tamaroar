<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit;
}

if (isset($_GET['id'])) {
    $book_id = intval($_GET['id']);

    // Check if the book is archived
    $checkStmt = $conn->prepare("SELECT status FROM books WHERE book_id = ?");
    $checkStmt->bind_param("i", $book_id);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();

    if ($checkResult->num_rows > 0) {
        $book = $checkResult->fetch_assoc();

        if ($book['status'] === 'Archived') {
            header("Location: ../admin/manage_books.php?error=Cannot+delete+an+archived+book");
            exit;
        }

        // Proceed to delete the book
        $deleteStmt = $conn->prepare("DELETE FROM books WHERE book_id = ?");
        $deleteStmt->bind_param("i", $book_id);

        if ($deleteStmt->execute()) {
            header("Location: ../admin/manage_books.php?success=Book+deleted+successfully");
        } else {
            header("Location: ../admin/manage_books.php?error=Failed+to+delete+book");
        }
    } else {
        header("Location: ../admin/manage_books.php?error=Book+not+found");
    }
} else {
    header("Location: ../admin/manage_books.php?error=Invalid+Request");
}
?>
