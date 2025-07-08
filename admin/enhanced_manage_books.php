<?php
session_start();
require_once '../config/db.php';
require_once '../config/enhanced_functions.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

// Get all books with category information
$query = "SELECT b.*, c.name as category_name 
          FROM books b 
          LEFT JOIN categories c ON b.category_id = c.id 
          ORDER BY b.created_at DESC";
$result = $conn->query($query);
$books = $result->fetch_all(MYSQLI_ASSOC);

// Get categories for filter
$categories = $conn->query("SELECT * FROM categories ORDER BY name")->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Books - Tamaroar Library</title>
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
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            background: var(--white);
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .page-title h1 {
            color: var(--primary);
            margin: 0;
        }
        
        .add-book-btn {
            background: var(--accent);
            color: var(--primary);
            padding: 0.75rem 1.5rem;
            border-radius: 5px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
            display: flex;
            align-items: center;
        }
        
        .add-book-btn:hover {
            background: #e6c200;
            transform: translateY(-2px);
        }
        
        .add-book-btn i {
            margin-right: 0.5rem;
        }
        
        .filters {
            background: var(--white);
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        
        .filters h3 {
            color: var(--primary);
            margin: 0 0 1rem 0;
        }
        
        .filter-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            align-items: end;
        }
        
        .filter-group {
            display: flex;
            flex-direction: column;
        }
        
        .filter-group label {
            margin-bottom: 0.5rem;
            color: var(--primary);
            font-weight: 600;
        }
        
        .filter-group input,
        .filter-group select {
            padding: 0.5rem;
            border: 1px solid var(--gray);
            border-radius: 5px;
            font-size: 0.9rem;
        }
        
        .filter-group input:focus,
        .filter-group select:focus {
            outline: none;
            border-color: var(--accent);
            box-shadow: 0 0 5px rgba(255, 215, 0, 0.3);
        }
        
        .books-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1.5rem;
        }
        
        .book-card {
            background: var(--white);
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
            transition: all 0.3s;
        }
        
        .book-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.15);
        }
        
        .book-cover {
            height: 200px;
            background: var(--gray);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--primary);
            font-size: 3rem;
            position: relative;
        }
        
        .book-cover img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .book-status {
            position: absolute;
            top: 10px;
            right: 10px;
            padding: 0.25rem 0.75rem;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .status-available {
            background: #e8f5e8;
            color: #2e7d32;
        }
        
        .status-archived {
            background: #ffebee;
            color: #c62828;
        }
        
        .book-info {
            padding: 1.5rem;
        }
        
        .book-title {
            font-size: 1.2rem;
            font-weight: 600;
            color: var(--primary);
            margin: 0 0 0.5rem 0;
            line-height: 1.3;
        }
        
        .book-author {
            color: #666;
            margin: 0 0 0.5rem 0;
            font-size: 0.9rem;
        }
        
        .book-category {
            background: var(--accent);
            color: var(--primary);
            padding: 0.25rem 0.75rem;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 600;
            display: inline-block;
            margin-bottom: 1rem;
        }
        
        .book-details {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.5rem;
            margin-bottom: 1rem;
            font-size: 0.9rem;
        }
        
        .book-detail {
            color: #666;
        }
        
        .book-detail strong {
            color: var(--primary);
        }
        
        .book-actions {
            display: flex;
            gap: 0.5rem;
        }
        
        .action-btn {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 0.9rem;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            margin: 0.25rem;
        }
        
        .edit-btn {
            background: var(--accent);
            color: var(--primary);
        }
        
        .edit-btn:hover {
            background: #e6c200;
            transform: translateY(-2px);
        }
        
        .archive-btn {
            background: #ffc107;
            color: #212529;
        }
        
        .archive-btn:hover {
            background: #e0a800;
            transform: translateY(-2px);
        }
        
        .restore-btn {
            background: #28a745;
            color: white;
        }
        
        .restore-btn:hover {
            background: #218838;
            transform: translateY(-2px);
        }
        
        .delete-btn {
            background: #dc3545;
            color: white;
        }
        
        .delete-btn:hover {
            background: #c82333;
            transform: translateY(-2px);
        }
        
        .no-books {
            text-align: center;
            padding: 3rem;
            color: #666;
        }
        
        .no-books i {
            font-size: 4rem;
            color: var(--gray);
            margin-bottom: 1rem;
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
            
            .books-grid {
                grid-template-columns: 1fr;
            }
            
            .filter-row {
                grid-template-columns: 1fr;
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
                <li><a href="enhanced_dashboard.php"><i class="fas fa-home"></i> Dashboard</a></li>
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
            <div class="page-header">
                <div class="page-title">
                    <h1>Manage Books</h1>
                    <p>Total Books: <?php echo count($books); ?></p>
                </div>
                <a href="add_book.php" class="add-book-btn">
                    <i class="fas fa-plus"></i> Add New Book
                </a>
            </div>

            <!-- Filters -->
            <div class="filters">
                <h3><i class="fas fa-filter"></i> Filters</h3>
                <div class="filter-row">
                    <div class="filter-group">
                        <label for="search">Search Books</label>
                        <input type="text" id="search" placeholder="Search by title, author, or ISBN...">
                    </div>
                    <div class="filter-group">
                        <label for="category">Category</label>
                        <select id="category">
                            <option value="">All Categories</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo htmlspecialchars($category['name']); ?>">
                                    <?php echo htmlspecialchars($category['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label for="status">Status</label>
                        <select id="status">
                            <option value="">All Status</option>
                            <option value="available">Available</option>
                            <option value="archived">Archived</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Books Grid -->
            <div class="books-grid">
                <?php if (empty($books)): ?>
                    <div class="no-books">
                        <i class="fas fa-book-open"></i>
                        <h3>No Books Found</h3>
                        <p>Start by adding some books to your library collection.</p>
                        <a href="add_book.php" class="add-book-btn">Add First Book</a>
                    </div>
                <?php else: ?>
                    <?php foreach ($books as $book): ?>
                        <div class="book-card" data-title="<?php echo strtolower($book['title']); ?>" 
                             data-author="<?php echo strtolower($book['author']); ?>"
                             data-category="<?php echo strtolower($book['category_name'] ?? ''); ?>"
                             data-status="<?php echo $book['status']; ?>">
                            <div class="book-cover">
                                <?php if ($book['cover_image']): ?>
                                    <img src="../uploads/<?php echo htmlspecialchars($book['cover_image']); ?>" alt="Book Cover">
                                <?php else: ?>
                                    <i class="fas fa-book"></i>
                                <?php endif; ?>
                                <div class="book-status status-<?php echo $book['status']; ?>">
                                    <?php echo ucfirst($book['status']); ?>
                                </div>
                            </div>
                            <div class="book-info">
                                <h3 class="book-title"><?php echo htmlspecialchars($book['title']); ?></h3>
                                <p class="book-author">by <?php echo htmlspecialchars($book['author']); ?></p>
                                <?php if ($book['category_name']): ?>
                                    <span class="book-category"><?php echo htmlspecialchars($book['category_name']); ?></span>
                                <?php endif; ?>
                                <div class="book-details">
                                    <div class="book-detail">
                                        <strong>Copies:</strong> <?php echo $book['copies']; ?>
                                    </div>
                                    <div class="book-detail">
                                        <strong>Available:</strong> <?php echo $book['available_copies']; ?>
                                    </div>
                                    <div class="book-detail">
                                        <strong>ISBN:</strong> <?php echo htmlspecialchars($book['isbn'] ?? 'N/A'); ?>
                                    </div>
                                    <div class="book-detail">
                                        <strong>Published:</strong> <?php echo date('Y', strtotime($book['published_date'])); ?>
                                    </div>
                                </div>
                                <div class="book-actions">
                                    <a href="edit_book.php?id=<?php echo $book['id']; ?>" class="action-btn edit-btn">
                                        <i class="fas fa-edit"></i> Edit
                                    </a>
                                    <?php if ($book['status'] === 'available'): ?>
                                        <button class="action-btn archive-btn" onclick="archiveBook(<?php echo $book['id']; ?>)">
                                            <i class="fas fa-archive"></i> Archive
                                        </button>
                                    <?php else: ?>
                                        <button class="action-btn restore-btn" onclick="restoreBook(<?php echo $book['id']; ?>)">
                                            <i class="fas fa-undo"></i> Restore
                                        </button>
                                    <?php endif; ?>
                                    <button class="action-btn delete-btn" onclick="confirmDelete(<?php echo $book['id']; ?>, '<?php echo htmlspecialchars($book['title']); ?>')">
                                        <i class="fas fa-trash"></i> Delete
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        // Filter functionality
        document.getElementById('search').addEventListener('input', filterBooks);
        document.getElementById('category').addEventListener('change', filterBooks);
        document.getElementById('status').addEventListener('change', filterBooks);

        function filterBooks() {
            const search = document.getElementById('search').value.toLowerCase();
            const category = document.getElementById('category').value.toLowerCase();
            const status = document.getElementById('status').value.toLowerCase();
            const books = document.querySelectorAll('.book-card');

            books.forEach(book => {
                const title = book.dataset.title;
                const author = book.dataset.author;
                const bookCategory = book.dataset.category;
                const bookStatus = book.dataset.status;

                const matchesSearch = title.includes(search) || author.includes(search);
                const matchesCategory = !category || bookCategory === category;
                const matchesStatus = !status || bookStatus === status;

                if (matchesSearch && matchesCategory && matchesStatus) {
                    book.style.display = 'block';
                } else {
                    book.style.display = 'none';
                }
            });
        }

        // Archive/Restore functionality
        function archiveBook(bookId) {
            if (confirm('Are you sure you want to archive this book?')) {
                window.location.href = `../actions/archive_book.php?id=${bookId}`;
            }
        }

        function restoreBook(bookId) {
            if (confirm('Are you sure you want to restore this book?')) {
                window.location.href = `../actions/restore_book.php?id=${bookId}`;
            }
        }

        // Confirm Delete functionality
        function confirmDelete(bookId, bookTitle) {
            if (confirm(`Are you sure you want to delete the book "${bookTitle}"? This action cannot be undone.`)) {
                window.location.href = `confirm_delete_book.php?id=${bookId}`;
            }
        }
    </script>
</body>
</html> 