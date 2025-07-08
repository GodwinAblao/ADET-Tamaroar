<?php
session_start();
require_once '../config/db.php';
require_once '../config/enhanced_functions.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

// Get admin dashboard statistics
$stats = getAdminDashboardStats();

// Get recent activities (limit to 3 for dashboard)
$recentActivities = getRecentActivities(3);

// Update overdue books status
updateOverdueBooks();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Tamaroar Library</title>
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

        .admin-container {
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
            transition: background 0.3s;
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
        }
        
        .dashboard-title h1 {
            color: var(--primary);
            margin: 0;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
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
            float: right;
            font-size: 2rem;
            color: var(--accent);
            opacity: 0.7;
        }
        
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .action-card {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
            transition: all 0.3s;
            text-decoration: none;
            color: inherit;
            display: block;
        }
        
        .action-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 20px rgba(0,0,0,0.15);
            background: var(--accent);
            color: var(--primary);
        }
        
        .action-card:hover i {
            color: var(--primary);
        }
        
        .action-card i {
            font-size: 2rem;
            color: var(--accent);
            margin-bottom: 1rem;
        }
        
        .action-card h3 {
            margin: 0 0 0.5rem 0;
            color: var(--primary);
        }
        
        .action-card p {
            margin: 0;
            color: #666;
            font-size: 0.9rem;
        }
        
        .recent-activity {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .section-header-with-action {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }
        
        .section-header-with-action h2 {
            color: var(--primary);
            margin: 0;
            display: flex;
            align-items: center;
        }
        
        .section-header-with-action h2 i {
            margin-right: 0.5rem;
            color: var(--accent);
        }
        
        .see-more-btn {
            background: var(--accent);
            color: var(--primary);
            padding: 0.5rem 1rem;
            border-radius: 5px;
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 600;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .see-more-btn:hover {
            background: #e6c200;
            transform: translateY(-2px);
        }
        
        .activity-item {
            display: flex;
            align-items: center;
            padding: 0.75rem 0;
            border-bottom: 1px solid #eee;
        }
        
        .activity-item:last-child {
            border-bottom: none;
        }
        
        .activity-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--accent);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 1rem;
            color: white;
            font-size: 1.1rem;
        }

        .activity-icon i {
            color: inherit !important;
        }
        
        .activity-content h4 {
            margin: 0 0 0.25rem 0;
            color: var(--primary);
        }
        
        .activity-content p {
            margin: 0;
            color: #666;
            font-size: 0.9rem;
        }
        
        .logout-btn {
            background: rgba(255,255,255,0.1);
            border: 1px solid rgba(255,255,255,0.2);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 5px;
            text-decoration: none;
            transition: background 0.3s;
        }
        
        .logout-btn:hover {
            background: rgba(255,255,255,0.2);
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
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <!-- Sidebar -->
        <div class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <h3><i class="fas fa-user-shield"></i> Admin</h3>
            </div>
            <ul class="nav-menu">
                <li><a href="enhanced_dashboard.php" class="active"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a href="enhanced_manage_books.php"><i class="fas fa-book"></i> Manage Books</a></li>
                <li><a href="manage_students.php"><i class="fas fa-users"></i> Manage Students</a></li>
                <li><a href="transactions.php"><i class="fas fa-exchange-alt"></i> Transactions</a></li>
                <li><a href="fines_reports.php"><i class="fas fa-chart-bar"></i> Fines & Reports</a></li>
                <li><a href="activity_log.php"><i class="fas fa-clock"></i> Activity Log</a></li>
                <li><a href="settings.php"><i class="fas fa-cog"></i> Settings</a></li>
                <li><a href="../actions/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <div class="dashboard-header">
                <div class="dashboard-title">
                    <h1>Admin Dashboard</h1>
                    <p>Welcome back, <?php echo htmlspecialchars($_SESSION['full_name']); ?>!</p>
                </div>
                <div class="user-info">
                    <span>Admin</span>
                    <a href="../actions/logout.php" class="logout-btn">Logout</a>
                </div>
            </div>

            <!-- Statistics Grid -->
            <div class="stats-grid">
                <div class="stat-card">
                    <i class="fas fa-book stat-card icon"></i>
                    <h3>Total Books</h3>
                    <div class="number"><?php echo $stats['total_books']; ?></div>
                </div>
                <div class="stat-card">
                    <i class="fas fa-check-circle stat-card icon"></i>
                    <h3>Available Books</h3>
                    <div class="number"><?php echo $stats['available_books']; ?></div>
                </div>
                <div class="stat-card">
                    <i class="fas fa-users stat-card icon"></i>
                    <h3>Active Students</h3>
                    <div class="number"><?php echo $stats['total_students']; ?></div>
                </div>
                <div class="stat-card">
                    <i class="fas fa-handshake stat-card icon"></i>
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
            </div>

            <!-- Quick Actions -->
            <div class="quick-actions">
                <a href="add_book.php" class="action-card">
                    <i class="fas fa-plus"></i>
                    <h3>Add New Book</h3>
                    <p>Add a new book to the library collection</p>
                </a>
                <a href="manage_students.php" class="action-card">
                    <i class="fas fa-user-plus"></i>
                    <h3>Manage Students</h3>
                    <p>View and manage student accounts</p>
                </a>
                <a href="transactions.php" class="action-card">
                    <i class="fas fa-list"></i>
                    <h3>View Transactions</h3>
                    <p>Monitor borrowing and return activities</p>
                </a>
                <a href="fines_reports.php" class="action-card">
                    <i class="fas fa-chart-line"></i>
                    <h3>Generate Reports</h3>
                    <p>Create reports and view analytics</p>
                </a>
            </div>

            <!-- Recent Activity -->
            <div class="recent-activity">
                <div class="section-header-with-action">
                    <h2><i class="fas fa-clock"></i> Recent Activity</h2>
                    <a href="activity_log.php" class="see-more-btn">
                        <i class="fas fa-external-link-alt"></i> See More
                    </a>
                </div>
                <?php if (empty($recentActivities)): ?>
                    <div class="activity-item">
                        <div class="activity-icon">
                            <i class="fas fa-info-circle"></i>
                        </div>
                        <div class="activity-content">
                            <h4>No Recent Activity</h4>
                            <p>No activities recorded in the last 7 days</p>
                        </div>
                    </div>
                <?php else: ?>
                    <?php foreach ($recentActivities as $activity): ?>
                        <div class="activity-item">
                            <div class="activity-icon">
                                <?php 
                                $icon = 'fas fa-info-circle';
                                $color = 'var(--accent)';
                                
                                switch($activity['type']) {
                                    case 'borrow':
                                        $icon = 'fas fa-book';
                                        $color = '#28a745';
                                        break;
                                    case 'return':
                                        $icon = 'fas fa-undo';
                                        $color = '#17a2b8';
                                        break;
                                    case 'registration':
                                        $icon = 'fas fa-user-plus';
                                        $color = '#ffc107';
                                        break;
                                    case 'book_added':
                                        $icon = 'fas fa-plus-circle';
                                        $color = '#6f42c1';
                                        break;
                                }
                                ?>
                                <i class="<?php echo $icon; ?>" style="color: <?php echo $color; ?>;"></i>
                            </div>
                            <div class="activity-content">
                                <h4>
                                    <?php 
                                    switch($activity['type']) {
                                        case 'borrow':
                                            echo htmlspecialchars($activity['user_name']) . ' borrowed a book';
                                            break;
                                        case 'return':
                                            echo htmlspecialchars($activity['user_name']) . ' returned a book';
                                            break;
                                        case 'registration':
                                            echo 'New student registered';
                                            break;
                                        case 'book_added':
                                            echo 'New book added to collection';
                                            break;
                                    }
                                    ?>
                                </h4>
                                <p>
                                    <?php 
                                    if ($activity['book_title']) {
                                        echo '"' . htmlspecialchars($activity['book_title']) . '"';
                                    } else {
                                        echo htmlspecialchars($activity['user_name']);
                                    }
                                    ?>
                                    • <?php echo date('M d, Y H:i', strtotime($activity['activity_date'])); ?>
                                </p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        // Mobile sidebar toggle
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            sidebar.classList.toggle('open');
        }
        
        // Auto-refresh statistics every 30 seconds
        setInterval(function() {
            location.reload();
        }, 30000);
    </script>
</body>
</html> 