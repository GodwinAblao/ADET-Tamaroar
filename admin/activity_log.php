<?php
session_start();
require_once '../config/db.php';
require_once '../config/enhanced_functions.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

// Get filter parameters
$filter = $_GET['filter'] ?? 'all';
$days = $_GET['days'] ?? '7';
$page = max(1, intval($_GET['page'] ?? 1));
$per_page = 20;
$offset = ($page - 1) * $per_page;

// Get activities based on filters
$activities = getFilteredActivities($filter, $days, $per_page, $offset);
$total_activities = getTotalActivities($filter, $days);
$total_pages = ceil($total_activities / $per_page);

// Get activity statistics
$stats = getActivityStats($days);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Activity Log - Admin Panel</title>
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

        .filters-section {
            background: var(--white);
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }

        .filters-row {
            display: flex;
            gap: 1rem;
            align-items: center;
            flex-wrap: wrap;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .filter-group label {
            font-weight: 600;
            color: var(--primary);
            font-size: 0.9rem;
        }

        .filter-group select {
            padding: 0.5rem;
            border: 1px solid var(--gray);
            border-radius: 5px;
            background: white;
        }

        .filter-btn {
            background: var(--accent);
            color: var(--primary);
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
        }

        .filter-btn:hover {
            background: #e6c200;
            transform: translateY(-2px);
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
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

        .activity-log {
            background: var(--white);
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .log-header {
            background: var(--primary);
            color: var(--accent);
            padding: 1rem 2rem;
            font-weight: bold;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .log-content {
            padding: 0;
        }

        .activity-item {
            display: flex;
            align-items: center;
            padding: 1.5rem 2rem;
            border-bottom: 1px solid var(--gray);
            transition: background-color 0.3s;
        }

        .activity-item:hover {
            background-color: #f9f9f9;
        }

        .activity-item:last-child {
            border-bottom: none;
        }

        .activity-icon {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: var(--accent);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 1.5rem;
            color: white;
            font-size: 1.2rem;
        }

        .activity-content {
            flex: 1;
        }

        .activity-content h4 {
            margin: 0 0 0.5rem 0;
            color: var(--primary);
            font-size: 1.1rem;
        }

        .activity-content p {
            margin: 0;
            color: #666;
            font-size: 0.9rem;
        }

        .activity-time {
            color: #999;
            font-size: 0.8rem;
            margin-left: 1rem;
        }

        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 0.5rem;
            padding: 2rem;
            background: var(--white);
            border-top: 1px solid var(--gray);
        }

        .pagination a,
        .pagination span {
            padding: 0.5rem 1rem;
            border: 1px solid var(--gray);
            border-radius: 5px;
            text-decoration: none;
            color: var(--primary);
            transition: all 0.3s;
        }

        .pagination a:hover {
            background: var(--accent);
            border-color: var(--accent);
        }

        .pagination .current {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
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

            .filters-row {
                flex-direction: column;
                align-items: stretch;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .activity-item {
                flex-direction: column;
                text-align: center;
            }

            .activity-icon {
                margin-right: 0;
                margin-bottom: 1rem;
            }

            .activity-time {
                margin-left: 0;
                margin-top: 0.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="sidebar-header">
                <h3><i class="fas fa-user-shield"></i> Admin</h3>
            </div>
            <ul class="nav-menu">
                <li><a href="enhanced_dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a href="enhanced_manage_books.php"><i class="fas fa-book"></i> Manage Books</a></li>
                <li><a href="manage_students.php"><i class="fas fa-users"></i> Manage Students</a></li>
                <li><a href="transactions.php"><i class="fas fa-exchange-alt"></i> Transactions</a></li>
                <li><a href="fines_reports.php"><i class="fas fa-chart-bar"></i> Fines & Reports</a></li>
                <li><a href="activity_log.php" class="active"><i class="fas fa-clock"></i> Activity Log</a></li>
                <li><a href="settings.php"><i class="fas fa-cog"></i> Settings</a></li>
                <li><a href="../actions/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <div class="page-header">
                <h1 class="page-title">Activity Log</h1>
                <p class="page-subtitle">View detailed activity history and system events</p>
            </div>

            <!-- Filters -->
            <div class="filters-section">
                <form method="GET" class="filters-row">
                    <div class="filter-group">
                        <label for="filter">Activity Type</label>
                        <select name="filter" id="filter">
                            <option value="all" <?php echo $filter === 'all' ? 'selected' : ''; ?>>All Activities</option>
                            <option value="borrow" <?php echo $filter === 'borrow' ? 'selected' : ''; ?>>Book Borrowings</option>
                            <option value="return" <?php echo $filter === 'return' ? 'selected' : ''; ?>>Book Returns</option>
                            <option value="registration" <?php echo $filter === 'registration' ? 'selected' : ''; ?>>Student Registrations</option>
                            <option value="book_added" <?php echo $filter === 'book_added' ? 'selected' : ''; ?>>Book Additions</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label for="days">Time Period</label>
                        <select name="days" id="days">
                            <option value="1" <?php echo $days === '1' ? 'selected' : ''; ?>>Last 24 Hours</option>
                            <option value="7" <?php echo $days === '7' ? 'selected' : ''; ?>>Last 7 Days</option>
                            <option value="30" <?php echo $days === '30' ? 'selected' : ''; ?>>Last 30 Days</option>
                            <option value="90" <?php echo $days === '90' ? 'selected' : ''; ?>>Last 90 Days</option>
                        </select>
                    </div>
                    <button type="submit" class="filter-btn">
                        <i class="fas fa-filter"></i> Apply Filters
                    </button>
                </form>
            </div>

            <!-- Activity Statistics -->
            <div class="stats-grid">
                <div class="stat-card">
                    <h3>Total Activities</h3>
                    <div class="number"><?php echo $stats['total_activities']; ?></div>
                </div>
                <div class="stat-card">
                    <h3>Book Borrowings</h3>
                    <div class="number"><?php echo $stats['borrowings']; ?></div>
                </div>
                <div class="stat-card">
                    <h3>Book Returns</h3>
                    <div class="number"><?php echo $stats['returns']; ?></div>
                </div>
                <div class="stat-card">
                    <h3>New Registrations</h3>
                    <div class="number"><?php echo $stats['registrations']; ?></div>
                </div>
            </div>

            <!-- Activity Log -->
            <div class="activity-log">
                <div class="log-header">
                    <h3><i class="fas fa-list"></i> Activity History</h3>
                    <span>Showing <?php echo count($activities); ?> of <?php echo $total_activities; ?> activities</span>
                </div>
                <div class="log-content">
                    <?php if (empty($activities)): ?>
                        <div class="empty-state">
                            <i class="fas fa-clock"></i>
                            <h3>No Activities Found</h3>
                            <p>No activities match your current filters. Try adjusting the time period or activity type.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($activities as $activity): ?>
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
                                    </p>
                                </div>
                                <div class="activity-time">
                                    <?php echo date('M d, Y H:i', strtotime($activity['activity_date'])); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <div class="pagination">
                        <?php if ($page > 1): ?>
                            <a href="?filter=<?php echo $filter; ?>&days=<?php echo $days; ?>&page=<?php echo $page - 1; ?>">
                                <i class="fas fa-chevron-left"></i> Previous
                            </a>
                        <?php endif; ?>

                        <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                            <?php if ($i == $page): ?>
                                <span class="current"><?php echo $i; ?></span>
                            <?php else: ?>
                                <a href="?filter=<?php echo $filter; ?>&days=<?php echo $days; ?>&page=<?php echo $i; ?>"><?php echo $i; ?></a>
                            <?php endif; ?>
                        <?php endfor; ?>

                        <?php if ($page < $total_pages): ?>
                            <a href="?filter=<?php echo $filter; ?>&days=<?php echo $days; ?>&page=<?php echo $page + 1; ?>">
                                Next <i class="fas fa-chevron-right"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html> 