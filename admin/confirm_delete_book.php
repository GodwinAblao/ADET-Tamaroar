<?php
session_start();
require_once '../config/db.php';
require_once '../config/enhanced_functions.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

// Check if book ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error'] = "Invalid book ID provided.";
    header("Location: enhanced_manage_books.php");
    exit;
}

$book_id = (int)$_GET['id'];

// Get book information
$stmt = $pdo->prepare("SELECT b.*, c.name as category_name FROM books b LEFT JOIN categories c ON b.category_id = c.id WHERE b.id = ?");
$stmt->execute([$book_id]);
$book = $stmt->fetch();

if (!$book) {
    $_SESSION['error'] = "Book not found.";
    header("Location: enhanced_manage_books.php");
    exit;
}

// Check for active borrowings
$stmt = $pdo->prepare("SELECT COUNT(*) as active_count FROM borrowings WHERE book_id = ? AND status IN ('borrowed', 'overdue')");
$stmt->execute([$book_id]);
$active_borrowings = $stmt->fetch();

// Check for borrowing history
$stmt = $pdo->prepare("SELECT COUNT(*) as history_count FROM borrowings WHERE book_id = ?");
$stmt->execute([$book_id]);
$borrowing_history = $stmt->fetch();

// Handle password confirmation
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['admin_password'];
    
    // Verify admin password
    $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ? AND role = 'admin'");
    $stmt->execute([$_SESSION['user_id']]);
    $admin = $stmt->fetch();
    
    if ($admin && password_verify($password, $admin['password'])) {
        // Password verified, proceed with deletion
        header("Location: ../actions/safe_delete_book.php?id=$book_id");
        exit;
    } else {
        $error_message = "Incorrect password. Please try again.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirm Book Deletion - Admin Panel</title>
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
            --danger: #dc3545;
            --warning: #ffc107;
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
        
        .confirmation-card {
            background: var(--white);
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            max-width: 600px;
            margin: 0 auto;
        }
        
        .card-header {
            background: var(--danger);
            color: white;
            padding: 1.5rem;
            border-radius: 10px 10px 0 0;
            text-align: center;
        }
        
        .card-header h1 {
            margin: 0;
            font-size: 1.5rem;
        }
        
        .card-body {
            padding: 2rem;
        }
        
        .book-info {
            background: var(--background);
            padding: 1.5rem;
            border-radius: 8px;
            margin-bottom: 2rem;
            border-left: 4px solid var(--danger);
        }
        
        .book-title {
            font-size: 1.3rem;
            font-weight: bold;
            color: var(--primary);
            margin-bottom: 0.5rem;
        }
        
        .book-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-top: 1rem;
        }
        
        .book-detail {
            display: flex;
            justify-content: space-between;
            padding: 0.5rem 0;
            border-bottom: 1px solid var(--gray);
        }
        
        .book-detail:last-child {
            border-bottom: none;
        }
        
        .warning-section {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .warning-section h3 {
            color: #856404;
            margin: 0 0 1rem 0;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .warning-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .warning-list li {
            color: #856404;
            margin: 0.5rem 0;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: var(--primary);
        }
        
        .form-group input {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid var(--gray);
            border-radius: 5px;
            font-size: 1rem;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: var(--danger);
            box-shadow: 0 0 5px rgba(220, 53, 69, 0.3);
        }
        
        .btn-group {
            display: flex;
            gap: 1rem;
            justify-content: center;
        }
        
        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1rem;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .btn-danger {
            background: var(--danger);
            color: white;
        }
        
        .btn-danger:hover {
            background: #c82333;
            transform: translateY(-2px);
        }
        
        .btn-secondary {
            background: var(--gray);
            color: var(--text);
        }
        
        .btn-secondary:hover {
            background: #d0d0d0;
        }
        
        .error-message {
            background: #f8d7da;
            color: #721c24;
            padding: 1rem;
            border-radius: 5px;
            margin-bottom: 1rem;
        }
        
        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        
        .status-available {
            background: #d4edda;
            color: #155724;
        }
        
        .status-archived {
            background: #f8d7da;
            color: #721c24;
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
                <li><a href="enhanced_manage_books.php" class="active"><i class="fas fa-book"></i> Manage Books</a></li>
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
            <div class="confirmation-card">
                <div class="card-header">
                    <h1><i class="fas fa-exclamation-triangle"></i> Confirm Book Deletion</h1>
                </div>
                
                <div class="card-body">
                    <?php if (isset($error_message)): ?>
                        <div class="error-message">
                            <?php echo $error_message; ?>
                        </div>
                    <?php endif; ?>
                    
                    <div class="book-info">
                        <div class="book-title"><?php echo htmlspecialchars($book['title']); ?></div>
                        <div style="color: #666; margin-bottom: 1rem;">by <?php echo htmlspecialchars($book['author']); ?></div>
                        
                        <div class="book-details">
                            <div class="book-detail">
                                <span><strong>Category:</strong></span>
                                <span><?php echo htmlspecialchars($book['category_name'] ?? 'N/A'); ?></span>
                            </div>
                            <div class="book-detail">
                                <span><strong>Status:</strong></span>
                                <span class="status-badge status-<?php echo $book['status']; ?>">
                                    <?php echo ucfirst($book['status']); ?>
                                </span>
                            </div>
                            <div class="book-detail">
                                <span><strong>Total Copies:</strong></span>
                                <span><?php echo $book['copies']; ?></span>
                            </div>
                            <div class="book-detail">
                                <span><strong>Available Copies:</strong></span>
                                <span><?php echo $book['available_copies']; ?></span>
                            </div>
                            <div class="book-detail">
                                <span><strong>ISBN:</strong></span>
                                <span><?php echo htmlspecialchars($book['isbn'] ?? 'N/A'); ?></span>
                            </div>
                            <div class="book-detail">
                                <span><strong>Published:</strong></span>
                                <span><?php echo date('Y', strtotime($book['published_date'])); ?></span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="warning-section">
                        <h3><i class="fas fa-exclamation-triangle"></i> Important Warnings</h3>
                        <ul class="warning-list">
                            <li><i class="fas fa-times-circle"></i> This action is <strong>PERMANENT</strong> and cannot be undone</li>
                            <li><i class="fas fa-database"></i> The book will be completely removed from the database</li>
                            <?php if ($borrowing_history['history_count'] > 0): ?>
                                <li><i class="fas fa-history"></i> This book has <strong><?php echo $borrowing_history['history_count']; ?> borrowing records</strong> in history</li>
                            <?php endif; ?>
                            <?php if ($active_borrowings['active_count'] > 0): ?>
                                <li><i class="fas fa-ban"></i> <strong>Cannot delete:</strong> Book has <?php echo $active_borrowings['active_count']; ?> active borrowing(s)</li>
                            <?php endif; ?>
                            <li><i class="fas fa-user-shield"></i> This action will be logged for audit purposes</li>
                        </ul>
                    </div>
                    
                    <?php if ($active_borrowings['active_count'] > 0): ?>
                        <div style="text-align: center; margin: 2rem 0;">
                            <div class="error-message">
                                <strong>Deletion Blocked:</strong> This book cannot be deleted because it has active borrowings.
                            </div>
                            <a href="enhanced_manage_books.php" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Back to Manage Books
                            </a>
                        </div>
                    <?php else: ?>
                        <form method="POST">
                            <div class="form-group">
                                <label for="admin_password">
                                    <i class="fas fa-lock"></i> Enter Admin Password to Confirm
                                </label>
                                <input type="password" id="admin_password" name="admin_password" required 
                                       placeholder="Enter your admin password">
                                <small style="color: #666; display: block; margin-top: 0.25rem;">
                                    This is required to confirm the permanent deletion
                                </small>
                            </div>
                            
                            <div class="btn-group">
                                <button type="submit" class="btn btn-danger">
                                    <i class="fas fa-trash"></i> Permanently Delete Book
                                </button>
                                <a href="enhanced_manage_books.php" class="btn btn-secondary">
                                    <i class="fas fa-times"></i> Cancel
                                </a>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</body>
</html> 