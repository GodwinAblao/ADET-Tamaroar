<?php
session_start();
require_once '../config/db.php';
require_once '../config/functions.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: ../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Get borrowing history
$stmt = $pdo->prepare("
    SELECT b.*, bk.title, bk.author, bk.book_id as book_code, bk.cover_image
    FROM borrowings b
    JOIN books bk ON b.book_id = bk.id
    WHERE b.user_id = ? AND b.status = 'returned'
    ORDER BY b.returned_date DESC
");
$stmt->execute([$user_id]);
$history = $stmt->fetchAll();

// Get statistics
$totalBorrowed = count($history);
$totalFines = 0;
$totalPaid = 0;

foreach ($history as $record) {
    $totalFines += $record['fine_amount'];
    if ($record['fine_paid']) {
        $totalPaid += $record['fine_amount'];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>History - Student Panel</title>
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
            border: 1px solid var(--gray);
            border-radius: 8px;
            margin-bottom: 1rem;
            transition: all 0.3s;
            background: var(--white);
        }

        .history-item:hover {
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

        .history-info {
            flex: 1;
        }

        .history-info h4 {
            color: var(--primary);
            margin-bottom: 0.5rem;
            font-size: 1.1rem;
        }

        .history-info p {
            color: #666;
            margin-bottom: 0.5rem;
        }

        .history-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1rem;
            margin-top: 1rem;
        }

        .history-detail {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .history-detail i {
            color: var(--primary);
            width: 16px;
        }

        .fine-amount {
            font-size: 1.2rem;
            font-weight: bold;
            color: #e74c3c;
            margin-left: auto;
        }

        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
            margin-left: 1rem;
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
                <li><a href="my_books.php"><i class="fas fa-book"></i> My Books</a></li>
                <li><a href="fines.php"><i class="fas fa-money-bill-wave"></i> My Fines</a></li>
                <li><a href="history.php" class="active"><i class="fas fa-history"></i> History</a></li>
                <li><a href="profile.php"><i class="fas fa-user"></i> Profile</a></li>
                <li><a href="../actions/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </div>
        
        <div class="main-content">
            <div class="page-header">
                <h1 class="page-title">Borrowing History</h1>
                <p class="page-subtitle">View your complete borrowing history and statistics</p>
            </div>
            
            <!-- History Statistics -->
            <div class="stats-grid">
                <div class="stat-card">
                    <i class="fas fa-book icon"></i>
                    <h3>Total Books Borrowed</h3>
                    <div class="number"><?php echo $totalBorrowed; ?></div>
                </div>
                <div class="stat-card">
                    <i class="fas fa-exclamation-triangle icon"></i>
                    <h3>Total Fines</h3>
                    <div class="number">₱<?php echo number_format($totalFines, 2); ?></div>
                </div>
                <div class="stat-card">
                    <i class="fas fa-check-circle icon"></i>
                    <h3>Fines Paid</h3>
                    <div class="number">₱<?php echo number_format($totalPaid, 2); ?></div>
                </div>
            </div>
            
            <!-- History List -->
            <div class="history-section">
                <div class="section-header">
                    <h3>Borrowing History</h3>
                </div>
                <div class="section-content">
                    <?php if (empty($history)): ?>
                        <div class="empty-state">
                            <i class="fas fa-history"></i>
                            <h3>No History Yet</h3>
                            <p>Your borrowing history will appear here once you return books.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($history as $record): ?>
                            <div class="history-item">
                                <div class="book-cover">
                                    <?php if ($record['cover_image']): ?>
                                        <img src="../<?php echo htmlspecialchars($record['cover_image']); ?>" alt="Book Cover">
                                    <?php else: ?>
                                        <i class="fas fa-book"></i>
                                    <?php endif; ?>
                                </div>
                                <div class="history-info">
                                    <h4><?php echo htmlspecialchars($record['title']); ?></h4>
                                    <p>by <?php echo htmlspecialchars($record['author']); ?></p>
                                    <div class="history-details">
                                        <div class="history-detail">
                                            <i class="fas fa-calendar"></i>
                                            <span>Borrowed: <?php echo date('M d, Y', strtotime($record['borrowed_date'])); ?></span>
                                        </div>
                                        <div class="history-detail">
                                            <i class="fas fa-clock"></i>
                                            <span>Due: <?php echo date('M d, Y', strtotime($record['due_date'])); ?></span>
                                        </div>
                                        <div class="history-detail">
                                            <i class="fas fa-check-circle"></i>
                                            <span>Returned: <?php echo date('M d, Y', strtotime($record['returned_date'])); ?></span>
                                        </div>
                                        <div class="history-detail">
                                            <i class="fas fa-barcode"></i>
                                            <span>Code: <?php echo htmlspecialchars($record['book_code']); ?></span>
                                        </div>
                                    </div>
                                </div>
                                <?php if ($record['fine_amount'] > 0): ?>
                                    <div class="fine-amount">
                                        ₱<?php echo number_format($record['fine_amount'], 2); ?>
                                    </div>
                                <?php endif; ?>
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