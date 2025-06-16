<?php
session_start();
require_once '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? null;
    $title = trim($_POST['title'] ?? '');
    $author = trim($_POST['author'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $published_year = $_POST['published_year'] ?? null;
    $copies = $_POST['copies'] ?? 1;

    if (empty($id) || empty($title) || empty($author) || empty($category) || empty($published_year)) {
        $_SESSION['error'] = "All fields are required.";
        header("Location: ../admin/edit_book.php?id=" . urlencode($id));
        exit;
    }

    // Optional: handle cover image update
    $cover_image = null;
    if (isset($_FILES['cover_image']) && $_FILES['cover_image']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['cover_image']['tmp_name'];
        $fileName = $_FILES['cover_image']['name'];
        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];

        if (in_array($fileExtension, $allowedExtensions)) {
            $newFileName = md5(time() . $fileName) . '.' . $fileExtension;
            $uploadDir = '../uploads/';
            $destPath = $uploadDir . $newFileName;

            if (move_uploaded_file($fileTmpPath, $destPath)) {
                $cover_image = $newFileName;
            } else {
                $_SESSION['error'] = 'Failed to move uploaded file.';
                header("Location: ../admin/edit_book.php?id=" . urlencode($id));
                exit;
            }
        } else {
            $_SESSION['error'] = 'Invalid image type.';
            header("Location: ../admin/edit_book.php?id=" . urlencode($id));
            exit;
        }
    }

    // Update the database
    if ($cover_image) {
        $stmt = $conn->prepare("UPDATE books SET title=?, author=?, category=?, published_year=?, copies=?, cover_image=? WHERE id=?");
        $stmt->bind_param("sssissi", $title, $author, $category, $published_year, $copies, $cover_image, $id);
    } else {
        $stmt = $conn->prepare("UPDATE books SET title=?, author=?, category=?, published_year=?, copies=? WHERE id=?");
        $stmt->bind_param("sssisi", $title, $author, $category, $published_year, $copies, $id);
    }

    if ($stmt->execute()) {
        $_SESSION['success'] = "Book updated successfully!";
        header("Location: ../admin/manage_books.php");
        exit;
    } else {
        $_SESSION['error'] = "Error updating book.";
        header("Location: ../admin/edit_book.php?id=" . urlencode($id));
        exit;
    }
} else {
    $_SESSION['error'] = "Invalid request method.";
    header("Location: ../admin/manage_books.php");
    exit;
}
?>
