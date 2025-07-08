<?php
session_start();
require_once '../config/db.php';
require_once '../config/functions.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
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
    <title>Add New Book - Admin Panel</title>
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
            margin-bottom: 2rem;
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

        .form-group small {
            color: #666;
            font-size: 0.8rem;
            margin-top: 0.25rem;
            display: block;
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

        .book-id-preview {
            background: var(--white);
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .preview-header {
            background: var(--primary);
            color: var(--accent);
            padding: 1rem 2rem;
            font-weight: bold;
        }

        .preview-content {
            padding: 2rem;
        }

        .id-format {
            background: var(--background);
            padding: 1.5rem;
            border-radius: 8px;
            margin: 1rem 0;
            text-align: center;
            border: 2px dashed var(--primary);
        }

        .id-format code {
            font-size: 1.2rem;
            font-weight: bold;
            color: var(--primary);
            font-family: 'Courier New', monospace;
        }

        .id-explanation ul {
            list-style: none;
            padding: 0;
        }

        .id-explanation li {
            margin: 0.75rem 0;
            color: var(--text);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .id-explanation strong {
            color: var(--primary);
            background: var(--accent);
            padding: 0.25rem 0.5rem;
            border-radius: 3px;
            font-size: 0.9rem;
        }

        .id-explanation i {
            color: var(--primary);
            width: 16px;
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
                <li><a href="activity_log.php"><i class="fas fa-clock"></i> Activity Log</a></li>
                <li><a href="settings.php"><i class="fas fa-cog"></i> Settings</a></li>
                <li><a href="../actions/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </div>
        
        <div class="main-content">
            <div class="page-header">
                <h1 class="page-title">Add New Book</h1>
                <p class="page-subtitle">Add a new book to the library collection</p>
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
                    <form method="POST" action="../actions/add_book.php" enctype="multipart/form-data">
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="title">Book Title *</label>
                                <input type="text" id="title" name="title" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="author">Author *</label>
                                <input type="text" id="author" name="author" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="category">Category *</label>
                                <select id="category" name="category_id" required>
                                    <option value="">Select Category</option>
                                    <?php foreach (
                                        $categories as $category): ?>
                                        <option value="<?php echo htmlspecialchars($category['id']); ?>">
                                            <?php echo htmlspecialchars($category['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="isbn">ISBN</label>
                                <input type="text" id="isbn" name="isbn" placeholder="Enter ISBN (optional)">
                            </div>
                            
                            <div class="form-group">
                                <label for="published_date">Published Date *</label>
                                <input type="date" id="published_date" name="published_date" required>
                                <input type="hidden" id="published_year" name="published_year">
                                <input type="hidden" id="published_month" name="published_month">
                                <input type="hidden" id="published_day" name="published_day">
                            </div>
                            
                            <div class="form-group">
                                <label for="copies">Number of Copies *</label>
                                <input type="number" id="copies" name="copies" min="1" value="1" required>
                            </div>
                            
                            <div class="form-group full-width">
                                <label for="description">Description</label>
                                <textarea id="description" name="description" rows="4" 
                                          placeholder="Enter book description (optional)"></textarea>
                            </div>
                            
                            <div class="form-group full-width">
                                <label for="cover_image">Cover Image</label>
                                <input type="file" id="cover_image" name="cover_image" accept="image/*">
                                <small>Allowed formats: JPG, JPEG, PNG, GIF. Max size: 5MB</small>
                            </div>
                        </div>
                        
                        <div style="margin-top: 2rem;">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-plus"></i> Add Book
                            </button>
                            <button type="reset" class="btn btn-secondary">
                                <i class="fas fa-undo"></i> Reset Form
                            </button>
                            <a href="enhanced_manage_books.php" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Book ID Preview -->
            <div class="book-id-preview">
                <div class="preview-header">
                    <h3>Book ID Format</h3>
                </div>
                <div class="preview-content">
                    <p>The book ID will be automatically generated in the following format:</p>
                    <div class="id-format">
                        <code>THFEB102024-FIC00001</code>
                    </div>
                    <div class="id-explanation">
                        <ul>
                            <li><i class="fas fa-info-circle"></i><strong>TH</strong> - First 2 letters from the Book Title</li>
                            <li><i class="fas fa-calendar"></i><strong>FEB</strong> - Month abbreviation (published)</li>
                            <li><i class="fas fa-calendar-day"></i><strong>10</strong> - Day when added to system</li>
                            <li><i class="fas fa-calendar-year"></i><strong>2024</strong> - Current year</li>
                            <li><i class="fas fa-tag"></i><strong>FIC</strong> - Category code</li>
                            <li><i class="fas fa-hashtag"></i><strong>00001</strong> - Sequential number for this category</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script>
// Before form submit, split published_date into year, month, day
const form = document.querySelector('form[action="../actions/add_book.php"]');
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
