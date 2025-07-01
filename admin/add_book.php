<?php
require_once '../config/session.php';
require_once '../config/db.php';
require_once '../config/functions.php';

if ($_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit;
}

$categories = getBookCategories();
?>

<div class="add-book">
    <h2>Add New Book</h2>
    
    <?php if (isset($_SESSION['error'])): ?>
        <div class="error-message"><?= htmlspecialchars($_SESSION['error']) ?></div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['success'])): ?>
        <div class="success-message"><?= htmlspecialchars($_SESSION['success']) ?></div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>

    <form method="POST" action="../actions/add_book.php" enctype="multipart/form-data" class="add-book-form">
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
                <select id="category" name="category" required>
                    <option value="">Select Category</option>
                    <?php foreach ($categories as $code => $name): ?>
                        <option value="<?= $code ?>"><?= $name ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="copies">Number of Copies *</label>
                <input type="number" id="copies" name="copies" min="1" value="1" required>
            </div>

            <div class="form-group">
                <label for="published_year">Published Year *</label>
                <input type="number" id="published_year" name="published_year" min="1000" max="<?= date('Y') ?>" value="<?= date('Y') ?>" required>
            </div>

            <div class="form-group">
                <label for="published_month">Published Month *</label>
                <select id="published_month" name="published_month" required>
                    <option value="">Select Month</option>
                    <option value="1">January</option>
                    <option value="2">February</option>
                    <option value="3">March</option>
                    <option value="4">April</option>
                    <option value="5">May</option>
                    <option value="6">June</option>
                    <option value="7">July</option>
                    <option value="8">August</option>
                    <option value="9">September</option>
                    <option value="10">October</option>
                    <option value="11">November</option>
                    <option value="12">December</option>
                </select>
            </div>

            <div class="form-group">
                <label for="published_day">Published Day *</label>
                <input type="number" id="published_day" name="published_day" min="1" max="31" value="1" required>
            </div>

            <div class="form-group full-width">
                <label for="cover_image">Cover Image</label>
                <input type="file" id="cover_image" name="cover_image" accept="image/*">
                <small>Allowed formats: JPG, JPEG, PNG, GIF. Max size: 5MB</small>
            </div>
        </div>

        <div class="form-actions">
            <button type="submit" class="submit-btn">Add Book</button>
            <button type="reset" class="reset-btn">Reset Form</button>
        </div>
    </form>

    <!-- Book ID Preview -->
    <div class="book-id-preview">
        <h3>Book ID Format</h3>
        <p>The book ID will be automatically generated in the following format:</p>
        <div class="id-format">
            <code>THFEB102024-FIC00001</code>
        </div>
        <div class="id-explanation">
            <ul>
                <li><strong>TH</strong> - First 2 letters from the Book Title</li>
                <li><strong>FEB</strong> - Month abbreviation (published)</li>
                <li><strong>10</strong> - Day when added to system</li>
                <li><strong>2024</strong> - Current year</li>
                <li><strong>FIC</strong> - Category code</li>
                <li><strong>00001</strong> - Sequential number for this category</li>
            </ul>
        </div>
    </div>
</div>

<style>
.add-book {
    padding: 1rem;
}

.error-message, .success-message {
    padding: 1rem;
    border-radius: 5px;
    margin-bottom: 1.5rem;
    font-weight: 700;
}

.error-message {
    background: rgba(244, 67, 54, 0.2);
    color: #f44336;
    border: 1px solid #f44336;
}

.success-message {
    background: rgba(76, 175, 80, 0.2);
    color: #4CAF50;
    border: 1px solid #4CAF50;
}

.add-book-form {
    background: rgba(255, 255, 255, 0.1);
    padding: 2rem;
    border-radius: 15px;
    backdrop-filter: blur(8.5px);
    border: 1px solid rgba(255, 255, 255, 0.18);
    margin-bottom: 2rem;
}

.form-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1.5rem;
}

.form-group {
    display: flex;
    flex-direction: column;
}

.form-group.full-width {
    grid-column: 1 / -1;
}

.form-group label {
    margin-bottom: 0.5rem;
    color: #ffdd57;
    font-weight: 700;
}

.form-group input,
.form-group select {
    padding: 0.75rem;
    border: none;
    border-radius: 5px;
    font-size: 1rem;
    background: rgba(255, 255, 255, 0.9);
    color: #333;
}

.form-group input:focus,
.form-group select:focus {
    outline: none;
    box-shadow: 0 0 0 2px #ffdd57;
}

.form-group small {
    margin-top: 0.25rem;
    color: #ccc;
    font-size: 0.8rem;
}

.form-actions {
    grid-column: 1 / -1;
    display: flex;
    gap: 1rem;
    justify-content: center;
    margin-top: 2rem;
}

.submit-btn, .reset-btn {
    padding: 0.75rem 2rem;
    border: none;
    border-radius: 5px;
    font-weight: 700;
    cursor: pointer;
    transition: background-color 0.3s ease;
}

.submit-btn {
    background: #ffdd57;
    color: #333;
}

.submit-btn:hover {
    background: #f0c419;
}

.reset-btn {
    background: rgba(255, 255, 255, 0.2);
    color: #fff;
}

.reset-btn:hover {
    background: rgba(255, 255, 255, 0.3);
}

.book-id-preview {
    background: rgba(255, 255, 255, 0.1);
    padding: 1.5rem;
    border-radius: 15px;
    backdrop-filter: blur(8.5px);
    border: 1px solid rgba(255, 255, 255, 0.18);
}

.book-id-preview h3 {
    color: #ffdd57;
    margin-bottom: 1rem;
}

.id-format {
    background: rgba(0, 0, 0, 0.3);
    padding: 1rem;
    border-radius: 5px;
    margin: 1rem 0;
    text-align: center;
}

.id-format code {
    font-size: 1.2rem;
    font-weight: 700;
    color: #ffdd57;
    font-family: monospace;
}

.id-explanation ul {
    list-style: none;
    padding: 0;
}

.id-explanation li {
    margin: 0.5rem 0;
    color: #fff;
}

.id-explanation strong {
    color: #ffdd57;
}

@media (max-width: 768px) {
    .form-grid {
        grid-template-columns: 1fr;
    }
    
    .form-actions {
        flex-direction: column;
    }
}
</style>
