<?php
session_start();
require_once '../config/db.php';

// Only handle POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Sanitize and assign inputs
    $title = isset($_POST['title']) ? trim($_POST['title']) : null;
    $author = isset($_POST['author']) ? trim($_POST['author']) : null;
    $category = isset($_POST['category']) ? trim($_POST['category']) : null;

    $published_year = isset($_POST['published_year']) ? intval($_POST['published_year']) : null;
    $published_month = isset($_POST['published_month']) ? intval($_POST['published_month']) : null;
    $published_day = isset($_POST['published_day']) ? intval($_POST['published_day']) : null;

    $copies = isset($_POST['copies']) ? intval($_POST['copies']) : 1;

    // Validate required fields
    if (!$title || !$author || !$category || !$published_year || !$published_month || !$published_day || $copies < 1) {
        $_SESSION['error'] = 'Please fill in all required fields correctly.';
        header('Location: ../admin/add_book.php');
        exit;
    }

    // Handle cover image upload
    $cover_image = null;
    if (isset($_FILES['cover_image']) && $_FILES['cover_image']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['cover_image']['tmp_name'];
        $fileName = $_FILES['cover_image']['name'];
        $fileNameCmps = explode(".", $fileName);
        $fileExtension = strtolower(end($fileNameCmps));
        $allowedfileExtensions = ['jpg', 'jpeg', 'png', 'gif'];

        if (in_array($fileExtension, $allowedfileExtensions)) {
            $newFileName = md5(time() . $fileName) . '.' . $fileExtension;
            $uploadFileDir = '../uploads/';
            $dest_path = $uploadFileDir . $newFileName;

            if (move_uploaded_file($fileTmpPath, $dest_path)) {
                $cover_image = $newFileName;
            } else {
                $_SESSION['error'] = 'Error moving the uploaded file.';
                header('Location: ../admin/add_book.php');
                exit;
            }
        } else {
            $_SESSION['error'] = 'Allowed file types: ' . implode(', ', $allowedfileExtensions);
            header('Location: ../admin/add_book.php');
            exit;
        }
    }

    // Insert book into DB
    $stmt = $conn->prepare("INSERT INTO books (title, author, category, published_year, published_month, published_day, copies, cover_image) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssiiiis", $title, $author, $category, $published_year, $published_month, $published_day, $copies, $cover_image);

    if ($stmt->execute()) {
        $_SESSION['success'] = "Book added successfully!";
        header("Location: ../admin/manage_books.php");
        exit;
    } else {
        $_SESSION['error'] = "Database error: " . $stmt->error;
        header('Location: ../admin/add_book.php');
        exit;
    }
} else {
    // If accessed directly without POST
    header('Location: ../admin/add_book.php');
    exit;
}
