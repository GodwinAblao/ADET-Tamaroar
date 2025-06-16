<?php
require_once '../config/db.php';
require_once '../includes/header.php';

$id = $_GET['id'] ?? null;

if (!$id) {
    echo "No book selected.";
    exit;
}

$stmt = $conn->prepare("SELECT * FROM books WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$book = $result->fetch_assoc();

if (!$book) {
    echo "Book not found.";
    exit;
}
?>

<div class="container mt-5">
    <h2>Edit Book</h2>
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger"><?= $_SESSION['error']; unset($_SESSION['error']); ?></div>
    <?php endif; ?>

    <form action="../actions/update_book.php" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="id" value="<?= $book['id']; ?>">

        <div class="mb-3">
            <label>Title</label>
            <input type="text" name="title" class="form-control" value="<?= htmlspecialchars($book['title']); ?>" required>
        </div>

        <div class="mb-3">
            <label>Author</label>
            <input type="text" name="author" class="form-control" value="<?= htmlspecialchars($book['author']); ?>" required>
        </div>

        <div class="mb-3">
            <label>Category</label>
            <input type="text" name="category" class="form-control" value="<?= htmlspecialchars($book['category']); ?>" required>
        </div>

        <div class="mb-3">
            <label>Published Year</label>
            <input type="number" name="published_year" class="form-control" value="<?= htmlspecialchars($book['published_year']); ?>" required>
        </div>

        <div class="mb-3">
            <label>Copies</label>
            <input type="number" name="copies" class="form-control" value="<?= htmlspecialchars($book['copies']); ?>" required>
        </div>

        <div class="mb-3">
            <label>Current Cover</label><br>
            <?php if ($book['cover_image']): ?>
                <img src="../uploads/<?= $book['cover_image']; ?>" width="100"><br><br>
            <?php else: ?>
                <span>No image uploaded</span><br><br>
            <?php endif; ?>
            <label>Change Cover Image (optional)</label>
            <input type="file" name="cover_image" class="form-control">
        </div>

        <button type="submit" class="btn btn-primary">Update Book</button>
        <a href="manage_books.php" class="btn btn-secondary">Cancel</a>
    </form>
</div>

<?php require_once '../includes/footer.php'; ?>
