<?php
session_start();
require_once '../config/db.php';

// Only admin allowed
if ($_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit;
}

// Fetch all books
$sql = "SELECT * FROM books ORDER BY created_at DESC";
$result = $conn->query($sql);
?>

<?php include '../includes/header.php'; ?>

<div class="container mt-5">
    <h2>📚 Manage Books</h2>
    <a href="add_book.php" class="btn btn-success mb-3">➕ Add New Book</a>

    <table class="table table-bordered table-striped">
        <thead class="table-dark">
            <tr>
                <th>Book ID</th>
                <th>Title</th>
                <th>Author</th>
                <th>Category</th>
                <th>Published</th>
                <th>Copies</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php if ($result->num_rows > 0): ?>
            <?php while($book = $result->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($book['book_id']) ?></td>
                    <td><?= htmlspecialchars($book['title']) ?></td>
                    <td><?= htmlspecialchars($book['author']) ?></td>
                    <td><?= htmlspecialchars($book['category']) ?></td>
                    <td><?= "{$book['published_year']}-{$book['published_month']}-{$book['published_day']}" ?></td>
                    <td><?= $book['copies'] ?></td>
                    <td><?= $book['status'] ?></td>
                    <td>
                        <a href="edit_book.php?book_id=<?= urlencode($book['book_id']) ?>" class="btn btn-sm btn-warning">✏️ Edit</a>
                        <a href="../actions/delete_book.php?book_id=<?= urlencode($book['book_id']) ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">🗑️ Delete</a>
                    </td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr><td colspan="8">No books found.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<?php include '../includes/footer.php'; ?>
