<?php
session_start();
require_once '../config/db.php';
require_once '../config/enhanced_functions.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

// Test book ID (you can change this)
$test_book_id = 1;

echo "<h1>Delete Function Debug</h1>";

// Test 1: Check if canDeleteBook function works
echo "<h2>Test 1: canDeleteBook Function</h2>";
try {
    $canDelete = canDeleteBook($test_book_id);
    echo "<pre>";
    print_r($canDelete);
    echo "</pre>";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}

// Test 2: Check if book exists
echo "<h2>Test 2: Book Existence Check</h2>";
try {
    $stmt = $pdo->prepare("SELECT * FROM books WHERE id = ?");
    $stmt->execute([$test_book_id]);
    $book = $stmt->fetch();
    
    if ($book) {
        echo "Book found: " . $book['title'] . " by " . $book['author'];
    } else {
        echo "Book not found with ID: $test_book_id";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}

// Test 3: Check borrowings
echo "<h2>Test 3: Borrowing Check</h2>";
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM borrowings WHERE book_id = ? AND status IN ('borrowed', 'overdue')");
    $stmt->execute([$test_book_id]);
    $active = $stmt->fetch()['count'];
    
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM borrowings WHERE book_id = ?");
    $stmt->execute([$test_book_id]);
    $total = $stmt->fetch()['count'];
    
    echo "Active borrowings: $active<br>";
    echo "Total borrowing records: $total<br>";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}

// Test 4: Test safeDeleteBook function (without actually deleting)
echo "<h2>Test 4: safeDeleteBook Function (Simulation)</h2>";
try {
    $result = safeDeleteBook($test_book_id, $_SESSION['user_id']);
    echo "<pre>";
    print_r($result);
    echo "</pre>";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}

echo "<br><a href='enhanced_manage_books.php'>Back to Manage Books</a>";
?> 