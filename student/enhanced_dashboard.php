<?php
session_start();
require_once '../config/db.php';
require_once '../config/enhanced_functions.php';

// Check if user is logged in and is student
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: ../login.php");
    exit;
}

// Get user dashboard statistics
$stats = getUserDashboardStats($_SESSION['user_id']);

// Get user's current borrowings
$stmt = $conn->prepare("SELECT b.*, bk.title, bk.author, bk.cover_image 
                       FROM borrowings b 
                       JOIN books bk ON b.book_id = bk.id 
                       WHERE b.user_id = ? AND b.status IN ('borrowed', 'overdue')
                       ORDER BY b.due_date ASC");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$currentBorrowings = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get recent notifications
$notifications = getUserNotifications($_SESSION['user_id'], 5);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard - Tamaroar Library</title>
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
        
        .dashboard-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            background: var(--white);
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .dashboard-title h1 {
            color: var(--primary);
            margin: 0;
        }
        
        .dashboard-title p {
            color: #666;
            margin: 0.5rem 0 0 0;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .logout-btn {
            background: var(--accent);
            color: var(--primary);
            padding: 0.5rem 1rem;
            border-radius: 5px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .logout-btn:hover {
            background: #e6c200;
            transform: translateY(-2px);
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
            border-left: 4px solid var(--accent);
            transition: transform 0.3s;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
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
            float: right;
            font-size: 2rem;
            color: var(--accent);
            opacity: 0.8;
        }
        
        .content-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 2rem;
        }
        
        .my-books {
            background: var(--white);
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .my-books h2 {
            color: var(--primary);
            margin: 0 0 1rem 0;
            display: flex;
            align-items: center;
        }
        
        .my-books h2 i {
            margin-right: 0.5rem;
            color: var(--accent);
        }
        
        .book-item {
            display: flex;
            align-items: center;
            padding: 1rem;
            border: 1px solid var(--gray);
            border-radius: 8px;
            margin-bottom: 1rem;
            transition: all 0.3s;
        }
        
        .book-item:hover {
            border-color: var(--accent);
            box-shadow: 0 2px 8px rgba(255, 215, 0, 0.2);
        }
        
        .book-cover {
            width: 60px;
            height: 80px;
            background: var(--gray);
            border-radius: 5px;
            margin-right: 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--primary);
            font-size: 1.5rem;
        }
        
        .book-info {
            flex: 1;
        }
        
        .book-info h4 {
            margin: 0 0 0.25rem 0;
            color: var(--primary);
        }
        
        .book-info p {
            margin: 0;
            color: #666;
            font-size: 0.9rem;
        }
        
        .book-status {
            text-align: right;
        }
        
        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .status-borrowed {
            background: #e3f2fd;
            color: #1976d2;
        }
        
        .status-overdue {
            background: #ffebee;
            color: #d32f2f;
        }
        
        .due-date {
            font-size: 0.8rem;
            color: #666;
            margin-top: 0.25rem;
        }
        
        .notifications {
            background: var(--white);
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .notifications h2 {
            color: var(--primary);
            margin: 0 0 1rem 0;
            display: flex;
            align-items: center;
        }
        
        .notifications h2 i {
            margin-right: 0.5rem;
            color: var(--accent);
        }
        
        .notification-item {
            padding: 0.75rem;
            border-left: 3px solid var(--accent);
            margin-bottom: 0.75rem;
            background: #fafafa;
            border-radius: 0 5px 5px 0;
        }
        
        .notification-item h4 {
            margin: 0 0 0.25rem 0;
            color: var(--primary);
            font-size: 0.9rem;
        }
        
        .notification-item p {
            margin: 0;
            color: #666;
            font-size: 0.8rem;
        }
        
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .action-card {
            background: var(--white);
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
            transition: all 0.3s;
            text-decoration: none;
            color: inherit;
        }
        
        .action-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.15);
        }
        
        .action-card i {
            font-size: 2rem;
            color: var(--accent);
            margin-bottom: 1rem;
        }
        
        .action-card h3 {
            margin: 0 0 0.5rem 0;
            color: var(--primary);
            font-size: 1rem;
        }
        
        .action-card p {
            margin: 0;
            color: #666;
            font-size: 0.8rem;
        }
        
        .no-books {
            text-align: center;
            padding: 2rem;
            color: #666;
        }
        
        .no-books i {
            font-size: 3rem;
            color: var(--gray);
            margin-bottom: 1rem;
        }

        .btn-return {
            background: #dc3545;
            color: white;
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 0.8rem;
            transition: all 0.3s;
            margin-left: 1rem;
        }

        .btn-return:hover {
            background: #c82333;
            transform: translateY(-2px);
        }

        .book-actions {
            display: flex;
            align-items: center;
            margin-left: auto;
        }

        .fine-warning {
            color: #dc3545;
            font-size: 0.8rem;
            margin-top: 0.25rem;
            font-weight: 500;
        }
        
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s;
            }
            
            .sidebar.open {
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: 0;
            }
            
            .content-grid {
                grid-template-columns: 1fr;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="student-container">
        <!-- Sidebar -->
        <div class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <h3><i class="fas fa-user-graduate"></i> Student</h3>
            </div>
            <ul class="nav-menu">
                <li><a href="enhanced_dashboard.php" class="active"><i class="fas fa-home"></i> Dashboard</a></li>
                <li><a href="enhanced_browse_books.php"><i class="fas fa-search"></i> Browse Books</a></li>
                <li><a href="my_books.php"><i class="fas fa-book"></i> My Books</a></li>
                <li><a href="fines.php"><i class="fas fa-money-bill-wave"></i> My Fines</a></li>
                <li><a href="history.php"><i class="fas fa-history"></i> History</a></li>
                <li><a href="profile.php"><i class="fas fa-user"></i> Profile</a></li>
                <li><a href="../actions/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <div class="dashboard-header">
                <div class="dashboard-title">
                    <h1>Student Dashboard</h1>
                    <p>Welcome back, <?php echo htmlspecialchars($_SESSION['full_name']); ?>!</p>
                </div>
                <div class="user-info">
                    <span>Student</span>
                    <a href="../actions/logout.php" class="logout-btn">Logout</a>
                </div>
            </div>

            <!-- Statistics Grid -->
            <div class="stats-grid">
                <div class="stat-card">
                    <i class="fas fa-book stat-card icon"></i>
                    <h3>Current Borrowings</h3>
                    <div class="number"><?php echo $stats['current_borrowings']; ?></div>
                </div>
                <div class="stat-card">
                    <i class="fas fa-exclamation-triangle stat-card icon"></i>
                    <h3>Overdue Books</h3>
                    <div class="number"><?php echo $stats['overdue_books']; ?></div>
                </div>
                <div class="stat-card">
                    <i class="fas fa-money-bill-wave stat-card icon"></i>
                    <h3>Total Fines</h3>
                    <div class="number">₱<?php echo number_format($stats['total_fines'], 2); ?></div>
                </div>
                <div class="stat-card">
                    <i class="fas fa-bell stat-card icon"></i>
                    <h3>Notifications</h3>
                    <div class="number"><?php echo $stats['unread_notifications']; ?></div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="quick-actions">
                <a href="enhanced_browse_books.php" class="action-card">
                    <i class="fas fa-search"></i>
                    <h3>Browse Books</h3>
                    <p>Find and borrow new books</p>
                </a>
                <a href="my_books.php" class="action-card">
                    <i class="fas fa-list"></i>
                    <h3>My Books</h3>
                    <p>View your borrowed books</p>
                </a>
                <a href="fines.php" class="action-card">
                    <i class="fas fa-dollar-sign"></i>
                    <h3>Pay Fines</h3>
                    <p>Check and pay your fines</p>
                </a>
                <a href="profile.php" class="action-card">
                    <i class="fas fa-user-edit"></i>
                    <h3>Update Profile</h3>
                    <p>Manage your account</p>
                </a>
            </div>

            <!-- Content Grid -->
            <div class="content-grid">
                <!-- My Books Section -->
                <div class="my-books">
                    <h2><i class="fas fa-book"></i> My Current Books</h2>
                    <?php if (empty($currentBorrowings)): ?>
                        <div class="no-books">
                            <i class="fas fa-book-open"></i>
                            <h3>No Books Borrowed</h3>
                            <p>You haven't borrowed any books yet. Start exploring our collection!</p>
                            <a href="enhanced_browse_books.php" class="btn btn-register">Browse Books</a>
                        </div>
                    <?php else: ?>
                        <?php foreach ($currentBorrowings as $borrowing): ?>
                            <div class="book-item">
                                <div class="book-cover">
                                    <?php if ($borrowing['cover_image']): ?>
                                        <img src="../uploads/<?php echo htmlspecialchars($borrowing['cover_image']); ?>" alt="Book Cover" style="width: 100%; height: 100%; object-fit: cover; border-radius: 5px;">
                                    <?php else: ?>
                                        <i class="fas fa-book"></i>
                                    <?php endif; ?>
                                </div>
                                <div class="book-info">
                                    <h4><?php echo htmlspecialchars($borrowing['title']); ?></h4>
                                    <p>by <?php echo htmlspecialchars($borrowing['author']); ?></p>
                                    <div class="due-date">
                                        Due: <?php echo date('M d, Y', strtotime($borrowing['due_date'])); ?>
                                    </div>
                                    <?php 
                                    // Check if book is overdue and show fine warning
                                    $due_date = new DateTime($borrowing['due_date']);
                                    $today = new DateTime();
                                    if ($due_date < $today): 
                                        $days_overdue = $today->diff($due_date)->days;
                                        $fine_amount = $days_overdue * 10; // ₱10 per day
                                    ?>
                                        <div class="fine-warning">
                                            <i class="fas fa-exclamation-triangle"></i>
                                            Overdue by <?php echo $days_overdue; ?> day(s) - Fine: ₱<?php echo number_format($fine_amount, 2); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="book-actions">
                                    <div class="status-badge <?php echo $borrowing['status'] === 'overdue' ? 'status-overdue' : 'status-borrowed'; ?>">
                                        <?php echo ucfirst($borrowing['status']); ?>
                                    </div>
                                    <button class="btn-return" onclick="returnBook(<?php echo $borrowing['id']; ?>, '<?php echo htmlspecialchars($borrowing['title']); ?>')">
                                        <i class="fas fa-undo"></i> Return
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <!-- Notifications Section -->
                <div class="notifications">
                    <h2><i class="fas fa-bell"></i> Recent Notifications</h2>
                    <?php if (empty($notifications)): ?>
                        <p style="color: #666; text-align: center;">No new notifications</p>
                    <?php else: ?>
                        <?php foreach ($notifications as $notification): ?>
                            <div class="notification-item">
                                <h4><?php echo htmlspecialchars($notification['title']); ?></h4>
                                <p><?php echo htmlspecialchars($notification['message']); ?></p>
                                <small style="color: #999;"><?php echo date('M d, Y H:i', strtotime($notification['created_at'])); ?></small>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Mobile sidebar toggle
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            sidebar.classList.toggle('open');
        }
        
        // Return book function
        function returnBook(borrowingId, bookTitle) {
            if (confirm(`Are you sure you want to return "${bookTitle}"?`)) {
                // Create a form to submit the return request
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '../actions/return_book.php';
                
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'borrowing_id';
                input.value = borrowingId;
                
                form.appendChild(input);
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        // Auto-refresh every 30 seconds
        setInterval(function() {
            location.reload();
        }, 30000);
    </script>
</body>
</html> 