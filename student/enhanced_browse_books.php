<?php
session_start();
require_once '../config/db.php';
require_once '../config/enhanced_functions.php';

// Check if user is logged in and is student
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: ../login.php");
    exit;
}

// Get search parameters
$search = $_GET['search'] ?? '';
$category = $_GET['category'] ?? '';
$sort = $_GET['sort'] ?? 'title';

// Build query
$query = "SELECT b.*, c.name as category_name 
          FROM books b 
          LEFT JOIN categories c ON b.category_id = c.id 
          WHERE b.status = 'available' AND b.available_copies > 0";

$params = [];
$types = '';

if ($search) {
    $query .= " AND (b.title LIKE ? OR b.author LIKE ? OR b.isbn LIKE ?)";
    $searchParam = "%$search%";
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
    $types .= 'sss';
}

if ($category) {
    $query .= " AND c.name = ?";
    $params[] = $category;
    $types .= 's';
}

$query .= " ORDER BY b.$sort ASC";

$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$books = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get categories for filter
$categories = $conn->query("SELECT * FROM categories ORDER BY name")->fetch_all(MYSQLI_ASSOC);

// Get user's current borrowings count
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM borrowings WHERE user_id = ? AND status = 'borrowed'");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$currentBorrowings = $stmt->get_result()->fetch_assoc()['count'];
$maxBooks = (int)getSystemSetting('max_books_per_student', 2);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Browse Books - Tamaroar Library</title>
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
        
        .borrowing-status {
            background: var(--accent);
            color: var(--primary);
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.9rem;
        }
        
        .search-filters {
            background: var(--white);
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        
        .search-filters h3 {
            color: var(--primary);
            margin: 0 0 1rem 0;
            display: flex;
            align-items: center;
        }
        
        .search-filters h3 i {
            margin-right: 0.5rem;
            color: var(--accent);
        }
        
        .filter-row {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr auto;
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
            font-size: 0.9rem;
        }
        
        .filter-group input,
        .filter-group select {
            padding: 0.75rem;
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
        
        .search-btn {
            background: var(--accent);
            color: var(--primary);
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 5px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .search-btn:hover {
            background: #e6c200;
            transform: translateY(-2px);
        }
        
        .books-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
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
        
        .book-info {
            padding: 1.5rem;
        }
        
        .book-title {
            font-size: 1.1rem;
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
        
        .borrow-btn {
            width: 100%;
            background: var(--accent);
            color: var(--primary);
            padding: 0.75rem;
            border: none;
            border-radius: 5px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .borrow-btn:hover {
            background: #e6c200;
            transform: translateY(-2px);
        }
        
        .borrow-btn:disabled {
            background: var(--gray);
            color: #999;
            cursor: not-allowed;
            transform: none;
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
        
        .results-info {
            background: var(--white);
            padding: 1rem 1.5rem;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 1rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .results-count {
            color: var(--primary);
            font-weight: 600;
        }
        
        .sort-options {
            display: flex;
            gap: 1rem;
            align-items: center;
        }
        
        .sort-options label {
            color: var(--primary);
            font-weight: 600;
            font-size: 0.9rem;
        }
        
        .sort-options select {
            padding: 0.5rem;
            border: 1px solid var(--gray);
            border-radius: 5px;
            font-size: 0.9rem;
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
            
            .results-info {
                flex-direction: column;
                gap: 1rem;
                text-align: center;
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
                <li><a href="enhanced_dashboard.php"><i class="fas fa-home"></i> Dashboard</a></li>
                <li><a href="enhanced_browse_books.php" class="active"><i class="fas fa-search"></i> Browse Books</a></li>
                <li><a href="my_books.php"><i class="fas fa-book"></i> My Books</a></li>
                <li><a href="fines.php"><i class="fas fa-money-bill-wave"></i> My Fines</a></li>
                <li><a href="history.php"><i class="fas fa-history"></i> History</a></li>
                <li><a href="profile.php"><i class="fas fa-user"></i> Profile</a></li>
                <li><a href="../actions/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <div class="page-header">
                <div class="page-title">
                    <h1>Browse Books</h1>
                    <p>Discover and borrow from our collection</p>
                </div>
                <div class="borrowing-status">
                    <i class="fas fa-book"></i>
                    <?php echo $currentBorrowings; ?> / <?php echo $maxBooks; ?> books borrowed
                </div>
            </div>

            <!-- Search and Filters -->
            <div class="search-filters">
                <h3><i class="fas fa-search"></i> Search & Filter</h3>
                <form method="GET" action="">
                    <div class="filter-row">
                        <div class="filter-group">
                            <label for="search">Search Books</label>
                            <input type="text" id="search" name="search" 
                                   value="<?php echo htmlspecialchars($search); ?>" 
                                   placeholder="Search by title, author, or ISBN...">
                        </div>
                        <div class="filter-group">
                            <label for="category">Category</label>
                            <select id="category" name="category">
                                <option value="">All Categories</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?php echo htmlspecialchars($cat['name']); ?>" 
                                            <?php echo $category === $cat['name'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($cat['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="filter-group">
                            <label for="sort">Sort By</label>
                            <select id="sort" name="sort">
                                <option value="title" <?php echo $sort === 'title' ? 'selected' : ''; ?>>Title</option>
                                <option value="author" <?php echo $sort === 'author' ? 'selected' : ''; ?>>Author</option>
                                <option value="published_date" <?php echo $sort === 'published_date' ? 'selected' : ''; ?>>Published Date</option>
                            </select>
                        </div>
                        <div class="filter-group">
                            <label>&nbsp;</label>
                            <button type="submit" class="search-btn">
                                <i class="fas fa-search"></i> Search
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Results Info -->
            <div class="results-info">
                <div class="results-count">
                    Found <?php echo count($books); ?> available book(s)
                </div>
                <div class="sort-options">
                    <label for="sort-results">Sort:</label>
                    <select id="sort-results" onchange="window.location.href='?search=<?php echo urlencode($search); ?>&category=<?php echo urlencode($category); ?>&sort=' + this.value">
                        <option value="title" <?php echo $sort === 'title' ? 'selected' : ''; ?>>Title</option>
                        <option value="author" <?php echo $sort === 'author' ? 'selected' : ''; ?>>Author</option>
                        <option value="published_date" <?php echo $sort === 'published_date' ? 'selected' : ''; ?>>Published Date</option>
                    </select>
                </div>
            </div>

            <!-- Books Grid -->
            <div class="books-grid">
                <?php if (empty($books)): ?>
                    <div class="no-books">
                        <i class="fas fa-search"></i>
                        <h3>No Books Found</h3>
                        <p>Try adjusting your search criteria or browse all available books.</p>
                        <a href="enhanced_browse_books.php" class="search-btn">View All Books</a>
                    </div>
                <?php else: ?>
                    <?php foreach ($books as $book): ?>
                        <div class="book-card">
                            <div class="book-cover">
                                <?php if ($book['cover_image']): ?>
                                    <img src="../<?php echo htmlspecialchars($book['cover_image']); ?>" alt="Book Cover">
                                <?php else: ?>
                                    <i class="fas fa-book"></i>
                                <?php endif; ?>
                            </div>
                            <div class="book-info">
                                <h3 class="book-title"><?php echo htmlspecialchars($book['title']); ?></h3>
                                <p class="book-author">by <?php echo htmlspecialchars($book['author']); ?></p>
                                <?php if ($book['category_name']): ?>
                                    <span class="book-category"><?php echo htmlspecialchars($book['category_name']); ?></span>
                                <?php endif; ?>
                                <div class="book-details">
                                    <div class="book-detail">
                                        <strong>Available:</strong> <?php echo $book['available_copies']; ?>
                                    </div>
                                    <div class="book-detail">
                                        <strong>ISBN:</strong> <?php echo htmlspecialchars($book['isbn'] ?? 'N/A'); ?>
                                    </div>
                                    <div class="book-detail">
                                        <strong>Published:</strong> <?php echo date('Y', strtotime($book['published_date'])); ?>
                                    </div>
                                    <div class="book-detail">
                                        <strong>Total Copies:</strong> <?php echo $book['copies']; ?>
                                    </div>
                                </div>
                                <button class="borrow-btn" 
                                        onclick="borrowBook(<?php echo $book['id']; ?>, '<?php echo htmlspecialchars($book['title']); ?>')"
                                        <?php echo $currentBorrowings >= $maxBooks ? 'disabled' : ''; ?>>
                                    <?php if ($currentBorrowings >= $maxBooks): ?>
                                        <i class="fas fa-ban"></i> Borrowing Limit Reached
                                    <?php else: ?>
                                        <i class="fas fa-handshake"></i> Borrow Book
                                    <?php endif; ?>
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        function borrowBook(bookId, bookTitle) {
            if (confirm(`Are you sure you want to borrow "${bookTitle}"?`)) {
                window.location.href = `borrow_book.php?id=${bookId}`;
            }
        }
    </script>
</body>
</html> 