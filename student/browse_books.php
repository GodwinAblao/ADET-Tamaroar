<?php
require_once '../config/session.php';
require_once '../config/db.php';
require_once '../config/functions.php';

if ($_SESSION['role'] !== 'student') {
    header("Location: ../index.php");
    exit;
}

// Get search parameters
$search = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';
$category = isset($_GET['category']) ? sanitizeInput($_GET['category']) : '';

// Build query
$query = "SELECT * FROM books WHERE status = 'active'";
$params = [];
$types = "";

if (!empty($search)) {
    $query .= " AND (title LIKE ? OR author LIKE ? OR book_id LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= "sss";
}

if (!empty($category)) {
    $query .= " AND category = ?";
    $params[] = $category;
    $types .= "s";
}

$query .= " ORDER BY title ASC";

$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$books = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$categories = getBookCategories();
?>

<div class="browse-books">
    <h2>Browse Books</h2>
    
    <!-- Search and Filter Form -->
    <form method="GET" class="search-form">
        <div class="search-row">
            <input type="text" name="search" placeholder="Search by title, author, or book ID..." value="<?= htmlspecialchars($search) ?>" class="search-input">
            <select name="category" class="category-select">
                <option value="">All Categories</option>
                <?php foreach ($categories as $code => $name): ?>
                    <option value="<?= $code ?>" <?= $category === $code ? 'selected' : '' ?>><?= $name ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="search-btn">Search</button>
            <a href="?clear=1" class="clear-btn">Clear</a>
        </div>
    </form>

    <!-- Books Grid -->
    <div class="books-grid">
        <?php if (empty($books)): ?>
            <div class="no-books">
                <p>No books found matching your criteria.</p>
            </div>
        <?php else: ?>
            <?php foreach ($books as $book): ?>
                <div class="book-card">
                    <div class="book-cover">
                        <?php if ($book['cover_image']): ?>
                            <img src="../uploads/<?= htmlspecialchars($book['cover_image']) ?>" alt="Book Cover">
                        <?php else: ?>
                            <div class="no-cover">No Cover</div>
                        <?php endif; ?>
                    </div>
                    <div class="book-info">
                        <h3><?= htmlspecialchars($book['title']) ?></h3>
                        <p class="author">by <?= htmlspecialchars($book['author']) ?></p>
                        <p class="book-id">ID: <?= htmlspecialchars($book['book_id']) ?></p>
                        <p class="category"><?= $categories[$book['category']] ?? $book['category'] ?></p>
                        <p class="availability">
                            Available: <?= $book['available_copies'] ?> of <?= $book['copies'] ?> copies
                        </p>
                        <p class="published">
                            Published: <?= date('F j, Y', strtotime($book['published_year'] . '-' . $book['published_month'] . '-' . $book['published_day'])) ?>
                        </p>
                    </div>
                    <div class="book-actions">
                        <?php if ($book['available_copies'] > 0): ?>
                            <form method="POST" action="../actions/borrow_book.php" style="display: inline;">
                                <input type="hidden" name="book_id" value="<?= $book['id'] ?>">
                                <button type="submit" class="borrow-btn">Borrow Book</button>
                            </form>
                        <?php else: ?>
                            <button class="unavailable-btn" disabled>Not Available</button>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<style>
.browse-books {
    padding: 1rem;
}

.search-form {
    margin-bottom: 2rem;
    background: rgba(255, 255, 255, 0.1);
    padding: 1.5rem;
    border-radius: 10px;
}

.search-row {
    display: flex;
    gap: 1rem;
    align-items: center;
    flex-wrap: wrap;
}

.search-input, .category-select {
    padding: 0.75rem;
    border: none;
    border-radius: 5px;
    font-size: 1rem;
    background: rgba(255, 255, 255, 0.9);
    color: #333;
}

.search-input {
    flex: 1;
    min-width: 200px;
}

.category-select {
    min-width: 150px;
}

.search-btn, .clear-btn {
    padding: 0.75rem 1.5rem;
    border: none;
    border-radius: 5px;
    font-weight: 700;
    cursor: pointer;
    text-decoration: none;
    display: inline-block;
    text-align: center;
}

.search-btn {
    background: #ffdd57;
    color: #333;
}

.clear-btn {
    background: rgba(255, 255, 255, 0.2);
    color: #fff;
}

.books-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 2rem;
}

.book-card {
    background: rgba(255, 255, 255, 0.1);
    border-radius: 15px;
    padding: 1.5rem;
    backdrop-filter: blur(8.5px);
    border: 1px solid rgba(255, 255, 255, 0.18);
    transition: transform 0.3s ease;
}

.book-card:hover {
    transform: translateY(-5px);
}

.book-cover {
    text-align: center;
    margin-bottom: 1rem;
}

.book-cover img {
    max-width: 150px;
    max-height: 200px;
    border-radius: 5px;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
}

.no-cover {
    width: 150px;
    height: 200px;
    background: rgba(255, 255, 255, 0.2);
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 5px;
    margin: 0 auto;
    color: #fff;
    font-weight: 700;
}

.book-info h3 {
    margin: 0 0 0.5rem 0;
    color: #ffdd57;
    font-size: 1.2rem;
}

.book-info p {
    margin: 0.25rem 0;
    font-size: 0.9rem;
}

.author {
    font-style: italic;
    color: #ccc;
}

.book-id {
    font-family: monospace;
    color: #ffdd57;
    font-weight: 700;
}

.category {
    background: rgba(255, 221, 87, 0.2);
    padding: 0.25rem 0.5rem;
    border-radius: 3px;
    display: inline-block;
    font-size: 0.8rem;
    font-weight: 700;
}

.availability {
    color: #90EE90;
    font-weight: 700;
}

.published {
    color: #ccc;
    font-size: 0.8rem;
}

.book-actions {
    margin-top: 1rem;
    text-align: center;
}

.borrow-btn, .unavailable-btn {
    padding: 0.75rem 1.5rem;
    border: none;
    border-radius: 5px;
    font-weight: 700;
    cursor: pointer;
    width: 100%;
}

.borrow-btn {
    background: #ffdd57;
    color: #333;
    transition: background-color 0.3s ease;
}

.borrow-btn:hover {
    background: #f0c419;
}

.unavailable-btn {
    background: rgba(255, 255, 255, 0.2);
    color: #ccc;
    cursor: not-allowed;
}

.no-books {
    grid-column: 1 / -1;
    text-align: center;
    padding: 3rem;
    background: rgba(255, 255, 255, 0.1);
    border-radius: 15px;
}

@media (max-width: 768px) {
    .search-row {
        flex-direction: column;
        align-items: stretch;
    }
    
    .books-grid {
        grid-template-columns: 1fr;
    }
}
</style>
