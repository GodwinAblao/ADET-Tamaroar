<?php
session_start();
require_once '../config/db.php';
require_once '../config/functions.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

$id = $_GET['id'] ?? null;

if (!$id) {
    header("Location: enhanced_manage_books.php");
    exit;
}

// Get book data
$stmt = $pdo->prepare("SELECT * FROM books WHERE id = ?");
$stmt->execute([$id]);
$book = $stmt->fetch();

if (!$book) {
    header("Location: enhanced_manage_books.php");
    exit;
}

// Get categories for dropdown
$stmt = $pdo->prepare("SELECT * FROM categories ORDER BY name");
$stmt->execute();
$categories = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Book - Admin Panel</title>
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

        .form-section {
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

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group.full-width {
            grid-column: 1 / -1;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: var(--primary);
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid var(--gray);
            border-radius: 5px;
            font-size: 1rem;
            transition: border-color 0.3s;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: var(--accent);
            box-shadow: 0 0 0 2px rgba(255, 215, 0, 0.2);
        }

        .current-cover {
            margin: 1rem 0;
            padding: 1rem;
            background: var(--background);
            border-radius: 8px;
            text-align: center;
        }

        .current-cover img {
            max-width: 150px;
            max-height: 200px;
            border-radius: 5px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1rem;
            transition: all 0.3s;
            margin-right: 1rem;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-primary {
            background: var(--accent);
            color: var(--primary);
        }

        .btn-primary:hover {
            background: #e6c200;
            transform: translateY(-2px);
        }

        .btn-secondary {
            background: var(--gray);
            color: var(--text);
        }

        .btn-secondary:hover {
            background: #d0d0d0;
        }

        .success-message {
            background: #d4edda;
            color: #155724;
            padding: 1rem;
            border-radius: 5px;
            margin-bottom: 1rem;
        }

        .error-message {
            background: #f8d7da;
            color: #721c24;
            padding: 1rem;
            border-radius: 5px;
            margin-bottom: 1rem;
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
                <li><a href="enhanced_manage_books.php" class="active"><i class="fas fa-book"></i> Manage Books</a></li>
                <li><a href="manage_students.php"><i class="fas fa-users"></i> Manage Students</a></li>
                <li><a href="transactions.php"><i class="fas fa-exchange-alt"></i> Transactions</a></li>
                <li><a href="fines_reports.php"><i class="fas fa-chart-bar"></i> Fines & Reports</a></li>
                <li><a href="settings.php"><i class="fas fa-cog"></i> Settings</a></li>
                <li><a href="../actions/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </div>
        
        <div class="main-content">
            <div class="page-header">
                <h1 class="page-title">Edit Book</h1>
                <p class="page-subtitle">Update book information and details</p>
            </div>
            
            <?php if (isset($_SESSION['success'])): ?>
                <div class="success-message">
                    <?php 
                    echo $_SESSION['success'];
                    unset($_SESSION['success']);
                    ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['error'])): ?>
                <div class="error-message">
                    <?php 
                    echo $_SESSION['error'];
                    unset($_SESSION['error']);
                    ?>
                </div>
            <?php endif; ?>
            
            <div class="form-section">
                <div class="section-header">
                    <h3>Book Information</h3>
                </div>
                <div class="section-content">
                    <form action="../actions/update_book.php" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="id" value="<?php echo $book['id']; ?>">
                        
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="title">Book Title</label>
                                <input type="text" id="title" name="title" 
                                       value="<?php echo htmlspecialchars($book['title']); ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="author">Author</label>
                                <input type="text" id="author" name="author" 
                                       value="<?php echo htmlspecialchars($book['author']); ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="category">Category</label>
                                <select id="category" name="category_id" required>
                                    <option value="">Select Category</option>
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?php echo htmlspecialchars($category['id']); ?>" <?php echo $book['category_id'] == $category['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($category['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="isbn">ISBN</label>
                                <input type="text" id="isbn" name="isbn" 
                                       value="<?php echo htmlspecialchars($book['isbn'] ?? ''); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="published_date">Published Date</label>
                                <input type="date" id="published_date" name="published_date" value="<?php echo $book['published_date']; ?>" required>
                                <input type="hidden" id="published_year" name="published_year">
                                <input type="hidden" id="published_month" name="published_month">
                                <input type="hidden" id="published_day" name="published_day">
                            </div>
                            
                            <div class="form-group">
                                <label for="copies">Number of Copies</label>
                                <input type="number" id="copies" name="copies" min="1" 
                                       value="<?php echo $book['copies']; ?>" required>
                            </div>
                            
                            <div class="form-group full-width">
                                <label for="description">Description</label>
                                <textarea id="description" name="description" rows="4" 
                                          placeholder="Enter book description..."><?php echo htmlspecialchars($book['description'] ?? ''); ?></textarea>
                            </div>
                            
                            <div class="form-group full-width">
                                <label>Current Cover Image</label>
                                <div class="current-cover">
                                    <?php if ($book['cover_image']): ?>
                                        <img src="../uploads/<?php echo htmlspecialchars($book['cover_image']); ?>" alt="Book Cover">
                                        <p style="margin-top: 0.5rem; color: #666;">Current cover image</p>
                                    <?php else: ?>
                                        <i class="fas fa-book" style="font-size: 3rem; color: var(--gray);"></i>
                                        <p style="margin-top: 0.5rem; color: #666;">No cover image uploaded</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="form-group full-width">
                                <label for="cover_image">Change Cover Image (Optional)</label>
                                <input type="file" id="cover_image" name="cover_image" accept="image/*">
                                <small style="color: #666; display: block; margin-top: 0.25rem;">
                                    Leave empty to keep current image. Accepted formats: JPG, PNG, GIF
                                </small>
                            </div>
                        </div>
                        
                        <div style="margin-top: 2rem;">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Update Book
                            </button>
                            <a href="enhanced_manage_books.php" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <script>
// Before form submit, split published_date into year, month, day
const form = document.querySelector('form[action="../actions/update_book.php"]');
form.addEventListener('submit', function(e) {
    const dateInput = document.getElementById('published_date').value;
    if (dateInput) {
        const [year, month, day] = dateInput.split('-');
        document.getElementById('published_year').value = year;
        document.getElementById('published_month').value = month;
        document.getElementById('published_day').value = day;
    }
});
</script>
</body>
</html>
