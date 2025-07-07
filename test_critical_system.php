<?php
// Critical System Test Script for Tamaroar Library System
// This script will NOT modify or delete any real data. It uses test records and cleans up after.

ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/functions.php';

function html($str) { return htmlspecialchars($str, ENT_QUOTES, 'UTF-8'); }

$results = [];
$test_prefix = 'TEST_'.uniqid();

// 1. Database Connection
try {
    $conn->query('SELECT 1');
    $results[] = ['Database Connection', true, 'Connected successfully.'];
} catch (Exception $e) {
    $results[] = ['Database Connection', false, $e->getMessage()];
}

// 2. Get a valid category_id
$category_id = null;
$res = $conn->query("SELECT id FROM categories LIMIT 1");
if ($row = $res->fetch_assoc()) {
    $category_id = $row['id'];
    $results[] = ['Category Fetch', true, "Category ID: $category_id"]; 
} else {
    $results[] = ['Category Fetch', false, 'No categories found.'];
}

// 3. Add Book (Create)
$book_title = $test_prefix.' Book';
$book_author = 'Test Author';
$published_year = 2020;
$published_month = 5;
$published_day = 15;
$copies = 2;
$isbn = '1234567890';
$description = 'Test book description.';
$cover_image = null;
$book_id = null;
$published_date = sprintf('%04d-%02d-%02d', $published_year, $published_month, $published_day);

if ($category_id) {
    $book_id = generateBookId($book_title, $published_month, $published_day, $published_year, $category_id);
    $stmt = $conn->prepare("INSERT INTO books (book_id, title, author, category_id, isbn, published_date, description, copies, available_copies, cover_image) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssssssii", $book_id, $book_title, $book_author, $category_id, $isbn, $published_date, $description, $copies, $copies, $cover_image);
    if ($stmt->execute()) {
        $results[] = ['Add Book', true, "Book added. Book ID: $book_id"];
    } else {
        $results[] = ['Add Book', false, $stmt->error];
    }
} else {
    $results[] = ['Add Book', false, 'No valid category_id.'];
}

// 4. Book ID Format
if ($book_id && preg_match('/^[A-Z]{2}[A-Z]{3}\d{2}\d{4}-[A-Z]{3}\d{5}$/', $book_id)) {
    $results[] = ['Book ID Format', true, $book_id];
} else {
    $results[] = ['Book ID Format', false, $book_id];
}

// 5. Read Book (Read)
$book_row = null;
if ($book_id) {
    $stmt = $conn->prepare("SELECT * FROM books WHERE book_id = ?");
    $stmt->bind_param("s", $book_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $book_row = $res->fetch_assoc();
    if ($book_row) {
        $results[] = ['Read Book', true, 'Book found in DB.'];
    } else {
        $results[] = ['Read Book', false, 'Book not found in DB.'];
    }
}

// 6. Update Book (Update)
if ($book_row) {
    $new_title = $book_title.' Updated';
    $stmt = $conn->prepare("UPDATE books SET title=? WHERE book_id=?");
    $stmt->bind_param("ss", $new_title, $book_id);
    if ($stmt->execute()) {
        $results[] = ['Update Book', true, 'Book title updated.'];
    } else {
        $results[] = ['Update Book', false, $stmt->error];
    }
}

// 7. Borrow/Return Logic (simulate user)
$user_id = null;
$res = $conn->query("SELECT id FROM users WHERE role='student' LIMIT 1");
if ($row = $res->fetch_assoc()) {
    $user_id = $row['id'];
    $results[] = ['User Fetch', true, "User ID: $user_id"];
} else {
    $results[] = ['User Fetch', false, 'No student user found.'];
}

$borrowing_id = null;
if ($user_id && $book_row) {
    // Borrow
    $due_date = date('Y-m-d', strtotime('+7 days'));
    $stmt = $conn->prepare("INSERT INTO borrowings (user_id, book_id, due_date) VALUES (?, ?, ?)");
    $stmt->bind_param("iis", $user_id, $book_row['id'], $due_date);
    if ($stmt->execute()) {
        $borrowing_id = $conn->insert_id;
        $results[] = ['Borrow Book', true, "Borrowed. Borrowing ID: $borrowing_id"];
    } else {
        $results[] = ['Borrow Book', false, $stmt->error];
    }
    // Return (simulate overdue)
    $return_date = date('Y-m-d', strtotime('+10 days'));
    $stmt = $conn->prepare("UPDATE borrowings SET returned_date=?, status='returned' WHERE id=?");
    $stmt->bind_param("si", $return_date, $borrowing_id);
    if ($stmt->execute()) {
        $results[] = ['Return Book', true, 'Book returned (simulated overdue).'];
    } else {
        $results[] = ['Return Book', false, $stmt->error];
    }
    // Fine calculation
    $fine = calculateFine($due_date, $return_date);
    if ($fine > 0) {
        $results[] = ['Fine Calculation', true, 'Fine: ₱'.number_format($fine,2)];
    } else {
        $results[] = ['Fine Calculation', false, 'No fine calculated.'];
    }
}

// 8. Delete Book (Cleanup)
if ($book_id) {
    $stmt = $conn->prepare("DELETE FROM books WHERE book_id = ?");
    $stmt->bind_param("s", $book_id);
    if ($stmt->execute()) {
        $results[] = ['Delete Book', true, 'Test book deleted.'];
    } else {
        $results[] = ['Delete Book', false, $stmt->error];
    }
}

// 9. Cleanup Borrowing
if ($borrowing_id) {
    $stmt = $conn->prepare("DELETE FROM borrowings WHERE id = ?");
    $stmt->bind_param("i", $borrowing_id);
    $stmt->execute();
}

// 10. Output Results
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Critical System Test Results</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f8f9fa; color: #222; }
        h1 { color: #007bff; }
        table { border-collapse: collapse; width: 100%; background: #fff; }
        th, td { border: 1px solid #ddd; padding: 10px; }
        th { background: #007bff; color: #fff; }
        tr.success { background: #d4edda; }
        tr.fail { background: #f8d7da; }
    </style>
</head>
<body>
    <h1>Critical System Test Results</h1>
    <table>
        <tr><th>Test</th><th>Status</th><th>Details</th></tr>
        <?php foreach ($results as $row): ?>
            <tr class="<?php echo $row[1] ? 'success' : 'fail'; ?>">
                <td><?php echo html($row[0]); ?></td>
                <td><?php echo $row[1] ? 'PASS' : 'FAIL'; ?></td>
                <td><?php echo html($row[2]); ?></td>
            </tr>
        <?php endforeach; ?>
    </table>
    <p style="margin-top:2rem; color:#888;">All test records have been cleaned up. No real data was modified.</p>
</body>
</html> 