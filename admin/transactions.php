<?php
session_start();
require_once '../config/db.php';
require_once '../config/functions.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

// Get all transactions
$stmt = $pdo->prepare("
    SELECT b.*, u.full_name as student_name, u.email as student_email, bk.title as book_title, bk.author as book_author
    FROM borrowings b
    JOIN users u ON b.user_id = u.id
    JOIN books bk ON b.book_id = bk.id
    ORDER BY b.borrowed_date DESC
");
$stmt->execute();
$transactions = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transactions - Admin Panel</title>
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
        
        .transactions-table {
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
        
        .status-borrowed {
            background: #d1ecf1;
            color: #0c5460;
        }
        
        .status-returned {
            background: #d4edda;
            color: #155724;
        }
        
        .status-overdue {
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
                <li><a href="transactions.php" class="active"><i class="fas fa-exchange-alt"></i> Transactions</a></li>
                <li><a href="fines_reports.php"><i class="fas fa-chart-bar"></i> Fines & Reports</a></li>
                <li><a href="activity_log.php"><i class="fas fa-clock"></i> Activity Log</a></li>
                <li><a href="settings.php"><i class="fas fa-cog"></i> Settings</a></li>
                <li><a href="../actions/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </div>
        
        <div class="main-content">
            <div class="page-header">
                <h1 class="page-title">Transactions</h1>
                <p class="page-subtitle">View all borrowing and returning transactions</p>
            </div>
            
            <div class="transactions-table">
                <div class="table-header">
                    <h3>Transaction History</h3>
                </div>
                <div class="table-content">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Student</th>
                                <th>Book</th>
                                <th>Borrowed Date</th>
                                <th>Due Date</th>
                                <th>Returned Date</th>
                                <th>Status</th>
                                <th>Fine</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($transactions as $transaction): ?>
                            <tr>
                                <td><?php echo $transaction['id']; ?></td>
                                <td>
                                    <div>
                                        <strong><?php echo htmlspecialchars($transaction['student_name']); ?></strong><br>
                                        <small><?php echo htmlspecialchars($transaction['student_email']); ?></small>
                                    </div>
                                </td>
                                <td>
                                    <div>
                                        <strong><?php echo htmlspecialchars($transaction['book_title']); ?></strong><br>
                                        <small>by <?php echo htmlspecialchars($transaction['book_author']); ?></small>
                                    </div>
                                </td>
                                <td><?php echo date('M d, Y', strtotime($transaction['borrowed_date'])); ?></td>
                                <td><?php echo date('M d, Y', strtotime($transaction['due_date'])); ?></td>
                                <td>
                                    <?php 
                                    if ($transaction['returned_date']) {
                                        echo date('M d, Y', strtotime($transaction['returned_date']));
                                    } else {
                                        echo '-';
                                    }
                                    ?>
                                </td>
                                <td>
                                    <span class="status-badge status-<?php echo $transaction['status']; ?>">
                                        <?php echo ucfirst($transaction['status']); ?>
                                    </span>
                                </td>
                                <td class="fine-amount">
                                    ₱<?php echo number_format($transaction['fine_amount'], 2); ?>
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