<?php
session_start();
require_once '../config/db.php';
require_once '../config/functions.php';

// Check if user is logged in and is student
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: ../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Get current borrowings
$stmt = $pdo->prepare("
    SELECT b.*, bk.title, bk.author, bk.book_id as book_code, bk.cover_image
    FROM borrowings b
    JOIN books bk ON b.book_id = bk.id
    WHERE b.user_id = ? AND b.status IN ('borrowed', 'overdue')
    ORDER BY b.due_date ASC
");
$stmt->execute([$user_id]);
$currentBorrowings = $stmt->fetchAll();

// Get borrowing history
$stmt = $pdo->prepare("
    SELECT b.*, bk.title, bk.author, bk.book_id as book_code
    FROM borrowings b
    JOIN books bk ON b.book_id = bk.id
    WHERE b.user_id = ? AND b.status = 'returned'
    ORDER BY b.returned_date DESC
    LIMIT 20
");
$stmt->execute([$user_id]);
$borrowingHistory = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Books - Student Panel</title>
    <link rel="stylesheet" href="../assets/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary: #006400;
            --accent: #FFD700;
            --background: #F5F5F5;
            --text: #222222;
            --white: #fff;
            --gray: #e0e0e0;
        }

        body {
            background: var(--background);
            font-family: 'Poppins', Arial, sans-serif;
            margin: 0;
            padding: 0;
        }

        .student-container {
            display: flex;
            min-height: 100vh;
        }

        .sidebar {
            width: 250px;
            background: var(--primary);
            color: white;
            padding: 1rem 0;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
        }

        .sidebar-header {
            padding: 1rem 1.5rem;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            margin-bottom: 1rem;
        }

        .sidebar-header h3 {
            margin: 0;
            font-size: 1.2rem;
            color: var(--accent);
        }

        .nav-menu {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .nav-menu li {
            margin: 0;
        }

        .nav-menu a {
            display: flex;
            align-items: center;
            padding: 0.75rem 1.5rem;
            color: white;
            text-decoration: none;
            transition: all 0.3s;
        }

        .nav-menu a:hover,
        .nav-menu a.active {
            background: var(--accent);
            color: var(--primary);
        }

        .nav-menu i {
            width: 20px;
            margin-right: 0.75rem;
        }

        .main-content {
            flex: 1;
            margin-left: 250px;
            padding: 2rem;
            background: var(--background);
        }

        .page-header {
            background: var(--white);
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }

        .page-title {
            color: var(--primary);
            margin-bottom: 0.5rem;
        }

        .page-subtitle {
            color: #666;
        }

        .books-section {
            background: var(--white);
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
            overflow: hidden;
        }

        .section-header {
            background: var(--primary);
            color: var(--accent);
            padding: 1rem 2rem;
            font-weight: bold;
        }

        .section-content {
            padding: 2rem;
        }

        .book-item {
            display: flex;
            align-items: center;
            padding: 1.5rem;
            border: 1px solid var(--gray);
            border-radius: 8px;
            margin-bottom: 1rem;
            transition: all 0.3s;
            background: var(--white);
        }

        .book-item:hover {
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            transform: translateY(-2px);
        }

        .book-cover {
            width: 80px;
            height: 100px;
            background: var(--gray);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 1.5rem;
            flex-shrink: 0;
            overflow: hidden;
        }

        .book-cover img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .book-cover i {
            font-size: 2rem;
            color: var(--primary);
        }

        .book-info {
            flex: 1;
        }

        .book-info h4 {
            color: var(--primary);
            margin-bottom: 0.5rem;
            font-size: 1.1rem;
        }

        .book-info p {
            color: #666;
            margin-bottom: 0.5rem;
        }

        .book-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1rem;
            margin-top: 1rem;
        }

        .book-detail {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .book-detail i {
            color: var(--primary);
            width: 16px;
        }

        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
            margin-left: auto;
        }

        .status-borrowed {
            background: #d1ecf1;
            color: #0c5460;
        }

        .status-overdue {
            background: #f8d7da;
            color: #721c24;
        }

        .status-returned {
            background: #d4edda;
            color: #155724;
        }

        .empty-state {
            text-align: center;
            padding: 3rem;
            color: #666;
        }

        .empty-state i {
            font-size: 4rem;
            color: var(--gray);
            margin-bottom: 1rem;
        }

        .empty-state h3 {
            color: var(--primary);
            margin-bottom: 0.5rem;
        }

        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            font-size: 0.9rem;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-primary {
            background: var(--accent);
            color: var(--primary);
        }

        .btn-primary:hover {
            background: #e6c200;
            transform: translateY(-2px);
        }
    </style>
</head>
<body>
    <div class="student-container">
        <div class="sidebar">
            <div class="sidebar-header">
                <h3><i class="fas fa-user-graduate"></i> Student</h3>
            </div>
            <ul class="nav-menu">
                <li><a href="enhanced_dashboard.php"><i class="fas fa-home"></i> Dashboard</a></li>
                <li><a href="enhanced_browse_books.php"><i class="fas fa-search"></i> Browse Books</a></li>
                <li><a href="my_books.php" class="active"><i class="fas fa-book"></i> My Books</a></li>
                <li><a href="fines.php"><i class="fas fa-money-bill-wave"></i> My Fines</a></li>
                <li><a href="history.php"><i class="fas fa-history"></i> History</a></li>
                <li><a href="profile.php"><i class="fas fa-user"></i> Profile</a></li>
                <li><a href="../actions/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </div>
        
        <div class="main-content">
            <div class="page-header">
                <h1 class="page-title">My Books</h1>
                <p class="page-subtitle">Manage your borrowed books and view your reading history</p>
            </div>
            
            <!-- Current Borrowings -->
            <div class="books-section">
                <div class="section-header">
                    <h3>Currently Borrowed</h3>
                </div>
                <div class="section-content">
                    <?php if (empty($currentBorrowings)): ?>
                        <div class="empty-state">
                            <i class="fas fa-book-open"></i>
                            <h3>No Books Borrowed</h3>
                            <p>You haven't borrowed any books yet. Start exploring our collection!</p>
                            <a href="enhanced_browse_books.php" class="btn btn-primary">
                                <i class="fas fa-search"></i> Browse Books
                            </a>
                        </div>
                    <?php else: ?>
                        <?php foreach ($currentBorrowings as $book): ?>
                            <div class="book-item">
                                <div class="book-cover">
                                    <?php if ($book['cover_image']): ?>
                                        <img src="../<?php echo htmlspecialchars($book['cover_image']); ?>" alt="Book Cover">
                                    <?php else: ?>
                                        <i class="fas fa-book"></i>
                                    <?php endif; ?>
                                </div>
                                <div class="book-info">
                                    <h4><?php echo htmlspecialchars($book['title']); ?></h4>
                                    <p>by <?php echo htmlspecialchars($book['author']); ?></p>
                                    <div class="book-details">
                                        <div class="book-detail">
                                            <i class="fas fa-calendar"></i>
                                            <span>Borrowed: <?php echo date('M d, Y', strtotime($book['borrowed_date'])); ?></span>
                                        </div>
                                        <div class="book-detail">
                                            <i class="fas fa-clock"></i>
                                            <span>Due: <?php echo date('M d, Y', strtotime($book['due_date'])); ?></span>
                                        </div>
                                        <div class="book-detail">
                                            <i class="fas fa-barcode"></i>
                                            <span>Code: <?php echo htmlspecialchars($book['book_code']); ?></span>
                                        </div>
                                    </div>
                                </div>
                                <span class="status-badge status-<?php echo $book['status']; ?>">
                                    <?php echo ucfirst($book['status']); ?>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Borrowing History -->
            <div class="books-section">
                <div class="section-header">
                    <h3>Borrowing History</h3>
                </div>
                <div class="section-content">
                    <?php if (empty($borrowingHistory)): ?>
                        <div class="empty-state">
                            <i class="fas fa-history"></i>
                            <h3>No History Yet</h3>
                            <p>Your borrowing history will appear here once you return books.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($borrowingHistory as $book): ?>
                            <div class="book-item">
                                <div class="book-cover">
                                    <i class="fas fa-book"></i>
                                </div>
                                <div class="book-info">
                                    <h4><?php echo htmlspecialchars($book['title']); ?></h4>
                                    <p>by <?php echo htmlspecialchars($book['author']); ?></p>
                                    <div class="book-details">
                                        <div class="book-detail">
                                            <i class="fas fa-calendar"></i>
                                            <span>Borrowed: <?php echo date('M d, Y', strtotime($book['borrowed_date'])); ?></span>
                                        </div>
                                        <div class="book-detail">
                                            <i class="fas fa-check-circle"></i>
                                            <span>Returned: <?php echo date('M d, Y', strtotime($book['returned_date'])); ?></span>
                                        </div>
                                        <div class="book-detail">
                                            <i class="fas fa-barcode"></i>
                                            <span>Code: <?php echo htmlspecialchars($book['book_code']); ?></span>
                                        </div>
                                    </div>
                                </div>
                                <span class="status-badge status-returned">
                                    Returned
                                </span>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</body>
</html> 