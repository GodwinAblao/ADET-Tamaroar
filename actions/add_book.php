<?php
session_start();
require_once '../config/db.php';
require_once '../config/functions.php';

// Check if user is admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../index.php');
    exit;
}

// Only handle POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Sanitize and assign inputs
    $title = isset($_POST['title']) ? sanitizeInput($_POST['title']) : null;
    $author = isset($_POST['author']) ? sanitizeInput($_POST['author']) : null;
    $category_id = isset($_POST['category_id']) ? intval($_POST['category_id']) : null;

    $published_year = isset($_POST['published_year']) ? intval($_POST['published_year']) : null;
    $published_month = isset($_POST['published_month']) ? intval($_POST['published_month']) : null;
    $published_day = isset($_POST['published_day']) ? intval($_POST['published_day']) : null;

    $copies = isset($_POST['copies']) ? intval($_POST['copies']) : 1;
    $isbn = isset($_POST['isbn']) ? sanitizeInput($_POST['isbn']) : null;
    $description = isset($_POST['description']) ? sanitizeInput($_POST['description']) : null;

    // Validate required fields
    if (!$title || !$author || !$category_id || !$published_year || !$published_month || !$published_day || $copies < 1) {
        $_SESSION['error'] = 'Please fill in all required fields correctly.';
        header('Location: ../admin/add_book.php');
        exit;
    }

    // Validate date
    if (!checkdate($published_month, $published_day, $published_year)) {
        $_SESSION['error'] = 'Invalid publication date.';
        header('Location: ../admin/add_book.php');
        exit;
    }

    // Combine published date
    $published_date = sprintf('%04d-%02d-%02d', $published_year, $published_month, $published_day);

    // Handle cover image upload
    $cover_image = null;
    if (isset($_FILES['cover_image']) && $_FILES['cover_image']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['cover_image']['tmp_name'];
        $fileName = $_FILES['cover_image']['name'];
        $fileNameCmps = explode('.', $fileName);
        $fileExtension = strtolower(end($fileNameCmps));
        $allowedfileExtensions = ['jpg', 'jpeg', 'png', 'gif'];

        if (in_array($fileExtension, $allowedfileExtensions)) {
            $newFileName = md5(time() . $fileName) . '.' . $fileExtension;
            $uploadFileDir = '../uploads/';
            if (!is_dir($uploadFileDir)) {
                mkdir($uploadFileDir, 0755, true);
            }
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

    // Generate book ID (pass category_id)
    $book_id = generateBookId($title, $published_month, $published_day, $published_year, $category_id);

    // Insert book into DB (align with schema)
    $stmt = $conn->prepare("INSERT INTO books (book_id, title, author, category_id, isbn, published_date, description, copies, available_copies, cover_image) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssssssii", $book_id, $title, $author, $category_id, $isbn, $published_date, $description, $copies, $copies, $cover_image);

    if ($stmt->execute()) {
        $_SESSION['success'] = "Book added successfully! Book ID: " . $book_id;
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
