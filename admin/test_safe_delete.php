<?php
session_start();
require_once '../config/db.php';
require_once '../config/enhanced_functions.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

// Get some test books
$stmt = $pdo->prepare("SELECT b.*, c.name as category_name FROM books b LEFT JOIN categories c ON b.category_id = c.id ORDER BY b.created_at DESC LIMIT 5");
$stmt->execute();
$books = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Safe Delete - Admin Panel</title>
    <link rel="stylesheet" href="../assets/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', Arial, sans-serif;
            margin: 0;
            padding: 2rem;
            background: #f5f5f5;
        }
        
        .test-container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .test-header {
            text-align: center;
            margin-bottom: 2rem;
            color: #006400;
        }
        
        .test-book {
            border: 1px solid #ddd;
            padding: 1rem;
            margin: 1rem 0;
            border-radius: 5px;
        }
        
        .book-title {
            font-weight: bold;
            color: #006400;
        }
        
        .delete-status {
            margin: 1rem 0;
            padding: 0.5rem;
            border-radius: 5px;
        }
        
        .status-safe {
            background: #d4edda;
            color: #155724;
        }
        
        .status-unsafe {
            background: #f8d7da;
            color: #721c24;
        }
        
        .btn {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            margin: 0.25rem;
        }
        
        .btn-danger {
            background: #dc3545;
            color: white;
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
    </style>
</head>
<body>
    <div class="test-container">
        <div class="test-header">
            <h1><i class="fas fa-shield-alt"></i> Safe Delete Test</h1>
            <p>Testing the safe delete functionality for books</p>
        </div>
        
        <?php foreach ($books as $book): ?>
            <div class="test-book">
                <div class="book-title"><?php echo htmlspecialchars($book['title']); ?></div>
                <div>Author: <?php echo htmlspecialchars($book['author']); ?></div>
                <div>Copies: <?php echo $book['copies']; ?> | Available: <?php echo $book['available_copies']; ?></div>
                <div>Status: <?php echo ucfirst($book['status']); ?></div>
                
                <?php
                // Test if book can be deleted
                $canDelete = canDeleteBook($book['id']);
                ?>
                
                <div class="delete-status <?php echo $canDelete['can_delete'] ? 'status-safe' : 'status-unsafe'; ?>">
                    <strong>Delete Status:</strong> <?php echo $canDelete['reason']; ?>
                    <?php if (!$canDelete['can_delete']): ?>
                        <br><small>Active borrowings: <?php echo $canDelete['active_borrowings']; ?></small>
                    <?php endif; ?>
                    <?php if (isset($canDelete['borrowing_history'])): ?>
                        <br><small>Borrowing history: <?php echo $canDelete['borrowing_history']; ?> records</small>
                    <?php endif; ?>
                </div>
                
                <div>
                    <?php if ($canDelete['can_delete']): ?>
                        <a href="confirm_delete_book.php?id=<?php echo $book['id']; ?>" class="btn btn-danger">
                            <i class="fas fa-trash"></i> Test Delete
                        </a>
                    <?php else: ?>
                        <span class="btn btn-secondary" style="cursor: not-allowed;">
                            <i class="fas fa-ban"></i> Cannot Delete
                        </span>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
        
        <div style="text-align: center; margin-top: 2rem;">
            <a href="enhanced_manage_books.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Manage Books
            </a>
        </div>
    </div>
</body>
</html> 