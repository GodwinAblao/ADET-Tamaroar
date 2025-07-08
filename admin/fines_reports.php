<?php
session_start();
require_once '../config/db.php';
require_once '../config/functions.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

// Get fines statistics
$stmt = $pdo->prepare("SELECT SUM(fine_amount) as total_fines FROM borrowings WHERE fine_amount > 0");
$stmt->execute();
$totalFines = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) as overdue_count FROM borrowings WHERE status = 'overdue'");
$stmt->execute();
$overdueCount = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) as paid_fines FROM borrowings WHERE fine_amount > 0 AND fine_paid = 1");
$stmt->execute();
$paidFines = $stmt->fetchColumn();

// Get recent fines
$stmt = $pdo->prepare("
    SELECT b.*, u.full_name as student_name, u.email as student_email, bk.title as book_title
    FROM borrowings b
    JOIN users u ON b.user_id = u.id
    JOIN books bk ON b.book_id = bk.id
    WHERE b.fine_amount > 0
    ORDER BY b.borrowed_date DESC
    LIMIT 20
");
$stmt->execute();
$recentFines = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fines & Reports - Admin Panel</title>
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
        
        .page-header {
            background: white;
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
        
        .fines-table {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .table-header {
            background: var(--primary);
            color: var(--dark);
            padding: 1rem 2rem;
            font-weight: bold;
        }
        
        .table-content {
            padding: 0;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th, td {
            padding: 1rem 2rem;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        
        th {
            background: #f8f9fa;
            font-weight: 600;
            color: var(--dark);
        }
        
        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        
        .status-paid {
            background: #d4edda;
            color: #155724;
        }
        
        .status-unpaid {
            background: #f8d7da;
            color: #721c24;
        }
        
        .fine-amount {
            font-weight: bold;
            color: var(--danger);
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <div class="sidebar">
            <div class="sidebar-header">
                <h3><i class="fas fa-user-shield"></i> Admin</h3>
            </div>
            <ul class="nav-menu">
                <li><a href="enhanced_dashboard.php"><i class="fas fa-home"></i> Dashboard</a></li>
                <li><a href="enhanced_manage_books.php"><i class="fas fa-book"></i> Manage Books</a></li>
                <li><a href="manage_students.php"><i class="fas fa-users"></i> Manage Students</a></li>
                <li><a href="transactions.php"><i class="fas fa-exchange-alt"></i> Transactions</a></li>
                <li><a href="fines_reports.php" class="active"><i class="fas fa-chart-bar"></i> Fines & Reports</a></li>
                <li><a href="activity_log.php"><i class="fas fa-clock"></i> Activity Log</a></li>
                <li><a href="settings.php"><i class="fas fa-cog"></i> Settings</a></li>
                <li><a href="../actions/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </div>
        
        <div class="main-content">
            <div class="page-header">
                <h1 class="page-title">Fines & Reports</h1>
                <p class="page-subtitle">Monitor fines and generate reports</p>
            </div>
            
            <div class="stats-grid">
                <div class="stat-card">
                    <h3>Total Fines</h3>
                    <div class="number">₱<?php echo number_format($totalFines, 2); ?></div>
                    <i class="fas fa-money-bill-wave icon"></i>
                </div>
                <div class="stat-card">
                    <h3>Overdue Books</h3>
                    <div class="number"><?php echo $overdueCount; ?></div>
                    <i class="fas fa-exclamation-triangle icon"></i>
                </div>
                <div class="stat-card">
                    <h3>Paid Fines</h3>
                    <div class="number"><?php echo $paidFines; ?></div>
                    <i class="fas fa-check-circle icon"></i>
                </div>
            </div>
            
            <div class="fines-table">
                <div class="table-header">
                    <h3>Recent Fines</h3>
                </div>
                <div class="table-content">
                    <table>
                        <thead>
                            <tr>
                                <th>Student</th>
                                <th>Book</th>
                                <th>Due Date</th>
                                <th>Returned Date</th>
                                <th>Fine Amount</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentFines as $fine): ?>
                            <tr>
                                <td>
                                    <div>
                                        <strong><?php echo htmlspecialchars($fine['student_name']); ?></strong><br>
                                        <small><?php echo htmlspecialchars($fine['student_email']); ?></small>
                                    </div>
                                </td>
                                <td><?php echo htmlspecialchars($fine['book_title']); ?></td>
                                <td><?php echo date('M d, Y', strtotime($fine['due_date'])); ?></td>
                                <td>
                                    <?php 
                                    if ($fine['returned_date']) {
                                        echo date('M d, Y', strtotime($fine['returned_date']));
                                    } else {
                                        echo '-';
                                    }
                                    ?>
                                </td>
                                <td class="fine-amount">₱<?php echo number_format($fine['fine_amount'], 2); ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo $fine['fine_paid'] ? 'paid' : 'unpaid'; ?>">
                                        <?php echo $fine['fine_paid'] ? 'Paid' : 'Unpaid'; ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</body>
</html> 