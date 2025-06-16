<?php
session_start();

// Retrieve error and success messages if any
$error = $_SESSION['error'] ?? null;
$success = $_SESSION['success'] ?? null;

// Clear messages after displaying
unset($_SESSION['error'], $_SESSION['success']);

// Optional: retain old input values after failed submission
$title = $_SESSION['old']['title'] ?? '';
$author = $_SESSION['old']['author'] ?? '';
$category = $_SESSION['old']['category'] ?? '';
$published_year = $_SESSION['old']['published_year'] ?? '';
$published_month = $_SESSION['old']['published_month'] ?? '';
$published_day = $_SESSION['old']['published_day'] ?? '';
$copies = $_SESSION['old']['copies'] ?? 1;

// Clear old inputs
unset($_SESSION['old']);

include '../includes/header.php';
?>

<div class="container mt-5">
    <h2>Add New Book</h2>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <form method="POST" action="../actions/add_book.php" enctype="multipart/form-data">
        <div class="mb-3">
            <label for="title" class="form-label">Book Title</label>
            <input type="text" class="form-control" id="title" name="title" value="<?= htmlspecialchars($title) ?>" required>
        </div>

        <div class="mb-3">
            <label for="author" class="form-label">Author</label>
            <input type="text" class="form-control" id="author" name="author" value="<?= htmlspecialchars($author) ?>" required>
        </div>

        <div class="mb-3">
            <label for="category" class="form-label">Category (e.g., Fiction, Science)</label>
            <input type="text" class="form-control" id="category" name="category" value="<?= htmlspecialchars($category) ?>" required>
        </div>

        <div class="mb-3">
            <label>Published Date</label>
            <div class="d-flex gap-2">
                <input type="number" name="published_year" placeholder="Year" class="form-control" min="1900" max="<?= date('Y') ?>" value="<?= htmlspecialchars($published_year) ?>" required>
                <input type="number" name="published_month" placeholder="Month (1-12)" class="form-control" min="1" max="12" value="<?= htmlspecialchars($published_month) ?>" required>
                <input type="number" name="published_day" placeholder="Day (1-31)" class="form-control" min="1" max="31" value="<?= htmlspecialchars($published_day) ?>" required>
            </div>
        </div>

        <div class="mb-3">
            <label for="copies" class="form-label">Number of Copies</label>
            <input type="number" class="form-control" id="copies" name="copies" min="1" value="<?= htmlspecialchars($copies) ?>" required>
        </div>

        <div class="mb-3">
            <label for="cover_image">Book Cover Image:</label>
            <input type="file" name="cover_image" id="cover_image" accept="image/*">
        </div>

        <button type="submit" class="btn btn-success">Add Book</button>
        <a href="manage_books.php" class="btn btn-secondary">Back to Manage Books</a>
    </form>
</div>

<?php include '../includes/footer.php'; ?>
