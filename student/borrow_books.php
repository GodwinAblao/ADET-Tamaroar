<?php
require_once '../config/session.php';
require_once '../config/db.php';
require_once '../config/functions.php';

if ($_SESSION['role'] !== 'student') {
    header("Location: ../index.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$borrowings = getUserBorrowings($user_id);
$overdue_books = getOverdueBooks($user_id);
$total_fine = getUserTotalFine($user_id);
?>

<div class="borrow-books">
    <h2>My Borrowed Books</h2>
    
    <?php if (isset($_SESSION['success'])): ?>
        <div class="success-message"><?= htmlspecialchars($_SESSION['success']) ?></div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['error'])): ?>
        <div class="error-message"><?= htmlspecialchars($_SESSION['error']) ?></div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <!-- Summary Cards -->
    <div class="summary-cards">
        <div class="summary-card">
            <h3>Currently Borrowed</h3>
            <p class="count"><?= count(array_filter($borrowings, function($b) { return $b['status'] === 'borrowed'; })) ?></p>
        </div>
        <div class="summary-card">
            <h3>Overdue Books</h3>
            <p class="count overdue"><?= count($overdue_books) ?></p>
        </div>
        <div class="summary-card">
            <h3>Total Fines</h3>
            <p class="count fine"><?= formatCurrency($total_fine) ?></p>
        </div>
    </div>

    <!-- Current Borrowings -->
    <div class="borrowings-section">
        <h3>Current Borrowings</h3>
        <?php 
        $current_borrowings = array_filter($borrowings, function($b) { 
            return $b['status'] === 'borrowed'; 
        });
        ?>
        
        <?php if (empty($current_borrowings)): ?>
            <div class="no-books">
                <p>You haven't borrowed any books yet.</p>
                <a href="browse_books.php" class="browse-link">Browse Available Books</a>
            </div>
        <?php else: ?>
            <div class="borrowings-grid">
                <?php foreach ($current_borrowings as $borrowing): ?>
                    <?php 
                    $is_overdue = strtotime($borrowing['due_date']) < time();
                    $days_remaining = ceil((strtotime($borrowing['due_date']) - time()) / (60 * 60 * 24));
                    ?>
                    <div class="borrowing-card <?= $is_overdue ? 'overdue' : '' ?>">
                        <div class="book-info">
                            <h4><?= htmlspecialchars($borrowing['title']) ?></h4>
                            <p class="author">by <?= htmlspecialchars($borrowing['author']) ?></p>
                            <p class="book-id">ID: <?= htmlspecialchars($borrowing['book_code']) ?></p>
                            <p class="borrow-date">Borrowed: <?= date('F j, Y', strtotime($borrowing['borrow_date'])) ?></p>
                            <p class="due-date <?= $is_overdue ? 'overdue' : '' ?>">
                                Due: <?= date('F j, Y', strtotime($borrowing['due_date'])) ?>
                                <?php if ($is_overdue): ?>
                                    <span class="overdue-badge">OVERDUE</span>
                                <?php elseif ($days_remaining <= 2): ?>
                                    <span class="warning-badge">DUE SOON</span>
                                <?php endif; ?>
                            </p>
                            <?php if ($is_overdue): ?>
                                <p class="fine-amount">
                                    Fine: <?= formatCurrency(calculateFine($borrowing['due_date'])) ?>
                                </p>
                            <?php endif; ?>
                        </div>
                        <div class="borrowing-actions">
                            <form method="POST" action="../actions/return_book.php">
                                <input type="hidden" name="borrowing_id" value="<?= $borrowing['id'] ?>">
                                <button type="submit" class="return-btn">Return Book</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Borrowing History -->
    <div class="history-section">
        <h3>Borrowing History</h3>
        <?php 
        $returned_borrowings = array_filter($borrowings, function($b) { 
            return $b['status'] === 'returned'; 
        });
        ?>
        
        <?php if (empty($returned_borrowings)): ?>
            <div class="no-history">
                <p>No borrowing history yet.</p>
            </div>
        <?php else: ?>
            <div class="history-table">
                <table>
                    <thead>
                        <tr>
                            <th>Book</th>
                            <th>Borrowed</th>
                            <th>Returned</th>
                            <th>Fine</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (array_slice($returned_borrowings, 0, 10) as $borrowing): ?>
                            <tr>
                                <td>
                                    <strong><?= htmlspecialchars($borrowing['title']) ?></strong><br>
                                    <small>by <?= htmlspecialchars($borrowing['author']) ?></small>
                                </td>
                                <td><?= date('M j, Y', strtotime($borrowing['borrow_date'])) ?></td>
                                <td><?= date('M j, Y', strtotime($borrowing['return_date'])) ?></td>
                                <td class="fine-amount"><?= formatCurrency($borrowing['fine_amount']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
.borrow-books {
    padding: 1rem;
}

.success-message, .error-message {
    padding: 1rem;
    border-radius: 5px;
    margin-bottom: 1.5rem;
    font-weight: 700;
}

.success-message {
    background: rgba(76, 175, 80, 0.2);
    color: #4CAF50;
    border: 1px solid #4CAF50;
}

.error-message {
    background: rgba(244, 67, 54, 0.2);
    color: #f44336;
    border: 1px solid #f44336;
}

.summary-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.summary-card {
    background: rgba(255, 255, 255, 0.1);
    padding: 1.5rem;
    border-radius: 10px;
    text-align: center;
    backdrop-filter: blur(8.5px);
    border: 1px solid rgba(255, 255, 255, 0.18);
}

.summary-card h3 {
    margin: 0 0 1rem 0;
    color: #ffdd57;
    font-size: 1rem;
}

.summary-card .count {
    font-size: 2rem;
    font-weight: 700;
    margin: 0;
}

.summary-card .count.overdue {
    color: #f44336;
}

.summary-card .count.fine {
    color: #ff9800;
}

.borrowings-section, .history-section {
    margin-bottom: 2rem;
}

.borrowings-section h3, .history-section h3 {
    color: #ffdd57;
    margin-bottom: 1rem;
}

.borrowings-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
    gap: 1.5rem;
}

.borrowing-card {
    background: rgba(255, 255, 255, 0.1);
    padding: 1.5rem;
    border-radius: 10px;
    backdrop-filter: blur(8.5px);
    border: 1px solid rgba(255, 255, 255, 0.18);
    transition: transform 0.3s ease;
}

.borrowing-card:hover {
    transform: translateY(-2px);
}

.borrowing-card.overdue {
    border-color: #f44336;
    background: rgba(244, 67, 54, 0.1);
}

.book-info h4 {
    margin: 0 0 0.5rem 0;
    color: #ffdd57;
    font-size: 1.1rem;
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

.borrow-date, .due-date {
    color: #ccc;
}

.due-date.overdue {
    color: #f44336;
    font-weight: 700;
}

.overdue-badge, .warning-badge {
    background: #f44336;
    color: white;
    padding: 0.25rem 0.5rem;
    border-radius: 3px;
    font-size: 0.7rem;
    font-weight: 700;
    margin-left: 0.5rem;
}

.warning-badge {
    background: #ff9800;
}

.fine-amount {
    color: #ff9800;
    font-weight: 700;
}

.borrowing-actions {
    margin-top: 1rem;
    text-align: center;
}

.return-btn {
    background: #ffdd57;
    color: #333;
    border: none;
    padding: 0.75rem 1.5rem;
    border-radius: 5px;
    font-weight: 700;
    cursor: pointer;
    transition: background-color 0.3s ease;
}

.return-btn:hover {
    background: #f0c419;
}

.no-books, .no-history {
    text-align: center;
    padding: 3rem;
    background: rgba(255, 255, 255, 0.1);
    border-radius: 10px;
}

.browse-link {
    display: inline-block;
    background: #ffdd57;
    color: #333;
    padding: 0.75rem 1.5rem;
    border-radius: 5px;
    text-decoration: none;
    font-weight: 700;
    margin-top: 1rem;
    transition: background-color 0.3s ease;
}

.browse-link:hover {
    background: #f0c419;
}

.history-table {
    background: rgba(255, 255, 255, 0.1);
    border-radius: 10px;
    overflow: hidden;
}

.history-table table {
    width: 100%;
    border-collapse: collapse;
}

.history-table th,
.history-table td {
    padding: 1rem;
    text-align: left;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.history-table th {
    background: rgba(255, 221, 87, 0.2);
    color: #ffdd57;
    font-weight: 700;
}

.history-table td {
    color: #fff;
}

.history-table tr:hover {
    background: rgba(255, 255, 255, 0.05);
}

@media (max-width: 768px) {
    .summary-cards {
        grid-template-columns: 1fr;
    }
    
    .borrowings-grid {
        grid-template-columns: 1fr;
    }
    
    .history-table {
        overflow-x: auto;
    }
}
</style>
