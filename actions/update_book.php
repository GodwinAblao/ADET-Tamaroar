<?php
session_start();
require_once '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? null;
    $title = trim($_POST['title'] ?? '');
    $author = trim($_POST['author'] ?? '');
    $category_id = isset($_POST['category_id']) ? intval($_POST['category_id']) : null;
    $isbn = isset($_POST['isbn']) ? sanitizeInput($_POST['isbn']) : null;
    $description = isset($_POST['description']) ? sanitizeInput($_POST['description']) : null;
    $published_year = isset($_POST['published_year']) ? intval($_POST['published_year']) : null;
    $published_month = isset($_POST['published_month']) ? intval($_POST['published_month']) : null;
    $published_day = isset($_POST['published_day']) ? intval($_POST['published_day']) : null;
    $copies = isset($_POST['copies']) ? intval($_POST['copies']) : 1;

    if (empty($id) || empty($title) || empty($author) || empty($category_id) || empty($published_year) || empty($published_month) || empty($published_day)) {
        $_SESSION['error'] = "All fields are required.";
        header("Location: ../admin/edit_book.php?id=" . urlencode($id));
        exit;
    }

    // Combine published date
    $published_date = sprintf('%04d-%02d-%02d', $published_year, $published_month, $published_day);

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

    // Update book in DB
    $sql = "UPDATE books SET title=?, author=?, category_id=?, isbn=?, published_date=?, description=?, copies=?, available_copies=?";
    $params = [$title, $author, $category_id, $isbn, $published_date, $description, $copies, $copies];
    if ($cover_image) {
        $sql .= ", cover_image=?";
        $params[] = $cover_image;
    }
    $sql .= " WHERE id=?";
    $params[] = $id;

    $stmt = $conn->prepare($sql);
    $stmt->bind_param(str_repeat('s', count($params)), ...$params);

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
