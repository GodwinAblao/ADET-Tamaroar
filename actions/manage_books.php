<?php
session_start();
require_once '../config/db.php';

// Only admin allowed
if ($_SESSION['role'] !== 'admin') {
    echo '<div style="color:red;">Access denied.</div>';
    exit;
}

// Fetch all books
$sql = "SELECT * FROM books ORDER BY created_at DESC";
$result = $conn->query($sql);
?>

<div class="container mt-5">
    <h2>📚 Manage Books</h2>
    <a href="#" class="btn btn-success mb-3" style="pointer-events:none;opacity:0.6;">➕ Add New Book (use sidebar)</a>

    <table class="table table-bordered table-striped" style="width:100%;background:#fff;color:#333;">
        <thead class="table-dark" style="background:#222;color:#ffdd57;">
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
                        <a href="#" class="btn btn-sm btn-warning" style="pointer-events:none;opacity:0.6;">✏️ Edit (use sidebar)</a>
                        <span class="btn btn-sm btn-danger" style="pointer-events:none;opacity:0.6;">🗑️ Delete (disabled)</span>
                    </td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr><td colspan="8">No books found.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>
