<?php
session_start();
require_once '../config/db.php';
require_once '../config/functions.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: ../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Get all borrowing history (both current and past)
$stmt = $pdo->prepare("
    SELECT b.*, bk.title, bk.author, bk.book_id as book_code, bk.cover_image
    FROM borrowings b
    JOIN books bk ON b.book_id = bk.id
    WHERE b.user_id = ?
    ORDER BY b.borrowed_date DESC
");
$stmt->execute([$user_id]);
$history = $stmt->fetchAll();

// Get statistics
$totalBorrowed = count($history);

// Get total fines and paid fines from fines table
$stmt = $pdo->prepare("SELECT SUM(amount) as total_fines FROM fines WHERE user_id = ?");
$stmt->execute([$user_id]);
$totalFines = $stmt->fetchColumn() ?: 0;

$stmt = $pdo->prepare("SELECT SUM(amount) as total_paid FROM fines WHERE user_id = ? AND status = 'paid'");
$stmt->execute([$user_id]);
$totalPaid = $stmt->fetchColumn() ?: 0;

$currentBorrowings = 0;
$returnedBooks = 0;

foreach ($history as $record) {
    if ($record['status'] === 'borrowed' || $record['status'] === 'overdue') {
        $currentBorrowings++;
    } else if ($record['status'] === 'returned') {
        $returnedBooks++;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Borrowing History - Student Panel</title>
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

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: var(--white);
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
            border-left: 4px solid var(--accent);
        }

        .stat-card h3 {
            margin: 0 0 0.5rem 0;
            color: var(--primary);
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .stat-card .number {
            font-size: 2rem;
            font-weight: bold;
            color: var(--primary);
        }

        .stat-card .icon {
            font-size: 2rem;
            color: var(--accent);
            margin-bottom: 1rem;
        }

        .history-section {
            background: var(--white);
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
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

        .history-item {
            display: flex;
            align-items: center;
            padding: 1.5rem;
            border-bottom: 1px solid var(--gray);
            transition: background-color 0.3s;
        }

        .history-item:hover {
            background-color: #f9f9f9;
        }

        .history-item:last-child {
            border-bottom: none;
        }

        .book-cover {
            width: 60px;
            height: 80px;
            background: var(--gray);
            border-radius: 5px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 1.5rem;
            flex-shrink: 0;
        }

        .book-cover img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 5px;
        }

        .book-cover i {
            font-size: 1.5rem;
            color: #999;
        }

        .book-info {
            flex: 1;
        }

        .book-info h4 {
            margin: 0 0 0.5rem 0;
            color: var(--primary);
            font-size: 1.1rem;
        }

        .book-info p {
            margin: 0 0 0.5rem 0;
            color: #666;
            font-size: 0.9rem;
        }

        .book-details {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            margin-top: 0.5rem;
        }

        .book-detail {
            display: flex;
            align-items: center;
            font-size: 0.85rem;
            color: #666;
        }

        .book-detail i {
            margin-right: 0.5rem;
            color: var(--primary);
            width: 14px;
        }

        .status-badge {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .status-borrowed {
            background: #e3f2fd;
            color: #1976d2;
        }

        .status-overdue {
            background: #ffebee;
            color: #d32f2f;
        }

        .status-returned {
            background: #e8f5e8;
            color: #388e3c;
        }

        .empty-state {
            text-align: center;
            padding: 3rem 2rem;
            color: #666;
        }

        .empty-state i {
            font-size: 4rem;
            color: var(--gray);
            margin-bottom: 1rem;
        }

        .empty-state h3 {
            margin: 0 0 0.5rem 0;
            color: var(--primary);
        }

        .empty-state p {
            margin: 0;
            font-size: 0.9rem;
        }

        @media (max-width: 768px) {
            .sidebar {
                width: 100%;
                position: relative;
                height: auto;
            }

            .main-content {
                margin-left: 0;
                padding: 1rem;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .history-item {
                flex-direction: column;
                text-align: center;
            }

            .book-cover {
                margin-right: 0;
                margin-bottom: 1rem;
            }

            .book-details {
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <div class="student-container">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="sidebar-header">
                <h3><i class="fas fa-user-graduate"></i> Student</h3>
            </div>
            <ul class="nav-menu">
                <li><a href="enhanced_dashboard.php"><i class="fas fa-home"></i> Dashboard</a></li>
                <li><a href="enhanced_browse_books.php"><i class="fas fa-search"></i> Browse Books</a></li>
                <li><a href="my_books.php"><i class="fas fa-book"></i> My Books</a></li>
                <li><a href="fines.php"><i class="fas fa-money-bill-wave"></i> My Fines</a></li>
                <li><a href="history.php" class="active"><i class="fas fa-history"></i> History</a></li>
                <li><a href="profile.php"><i class="fas fa-user"></i> Profile</a></li>
                <li><a href="../actions/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <div class="page-header">
                <h1 class="page-title">Borrowing History</h1>
                <p class="page-subtitle">View all your borrowing records and statistics</p>
            </div>

            <!-- Statistics -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="icon">
                        <i class="fas fa-book"></i>
                    </div>
                    <h3>Total Borrowed</h3>
                    <div class="number"><?php echo $totalBorrowed; ?></div>
                </div>
                <div class="stat-card">
                    <div class="icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <h3>Currently Borrowed</h3>
                    <div class="number"><?php echo $currentBorrowings; ?></div>
                </div>
                <div class="stat-card">
                    <div class="icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <h3>Returned</h3>
                    <div class="number"><?php echo $returnedBooks; ?></div>
                </div>
                <div class="stat-card">
                    <div class="icon">
                        <i class="fas fa-money-bill"></i>
                    </div>
                    <h3>Total Fines</h3>
                    <div class="number">₱<?php echo number_format($totalFines, 2); ?></div>
                </div>
                <div class="stat-card">
                    <div class="icon">
                        <i class="fas fa-money-bill-wave"></i>
                    </div>
                    <h3>Paid Fines</h3>
                    <div class="number">₱<?php echo number_format($totalPaid, 2); ?></div>
                </div>
            </div>

            <!-- Borrowing History -->
            <div class="history-section">
                <div class="section-header">
                    <h3><i class="fas fa-history"></i> All Borrowing Records</h3>
                </div>
                <div class="section-content">
                    <?php if (empty($history)): ?>
                        <div class="empty-state">
                            <i class="fas fa-history"></i>
                            <h3>No Borrowing History</h3>
                            <p>You haven't borrowed any books yet. Start by browsing our collection!</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($history as $record): ?>
                            <div class="history-item">
                                <div class="book-cover">
                                    <?php if ($record['cover_image']): ?>
                                        <img src="../uploads/<?php echo htmlspecialchars($record['cover_image']); ?>" alt="Book Cover">
                                    <?php else: ?>
                                        <i class="fas fa-book"></i>
                                    <?php endif; ?>
                                </div>
                                <div class="book-info">
                                    <h4><?php echo htmlspecialchars($record['title']); ?></h4>
                                    <p>by <?php echo htmlspecialchars($record['author']); ?></p>
                                    <div class="book-details">
                                        <div class="book-detail">
                                            <i class="fas fa-calendar"></i>
                                            <span>Borrowed: <?php echo date('M d, Y', strtotime($record['borrowed_date'])); ?></span>
                                        </div>
                                        <div class="book-detail">
                                            <i class="fas fa-calendar-check"></i>
                                            <span>Due: <?php echo date('M d, Y', strtotime($record['due_date'])); ?></span>
                                        </div>
                                        <?php if ($record['returned_date']): ?>
                                            <div class="book-detail">
                                                <i class="fas fa-check-circle"></i>
                                                <span>Returned: <?php echo date('M d, Y', strtotime($record['returned_date'])); ?></span>
                                            </div>
                                        <?php endif; ?>
                                        <div class="book-detail">
                                            <i class="fas fa-barcode"></i>
                                            <span>Code: <?php echo htmlspecialchars($record['book_code']); ?></span>
                                        </div>
                                        <?php if ($record['fine_amount'] > 0): ?>
                                            <div class="book-detail">
                                                <i class="fas fa-money-bill"></i>
                                                <span>Fine: ₱<?php echo number_format($record['fine_amount'], 2); ?></span>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="status-badge status-<?php echo $record['status']; ?>">
                                    <?php echo ucfirst($record['status']); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</body>
</html> 