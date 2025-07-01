<?php
require_once '../config/session.php';
require_once '../config/db.php';
require_once '../config/functions.php';

if ($_SESSION['role'] !== 'student') {
    header("Location: ../index.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$total_fine = getUserTotalFine($user_id);
$overdue_books = getOverdueBooks($user_id);

// Get all borrowings with fines
$stmt = $conn->prepare("
    SELECT b.*, bk.title, bk.author, bk.book_id as book_code
    FROM borrowings b
    JOIN books bk ON b.book_id = bk.id
    WHERE b.user_id = ? AND b.fine_amount > 0
    ORDER BY b.return_date DESC
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$fines_history = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<div class="fines-page">
    <h2>Fines & Payments</h2>
    
    <!-- Summary Cards -->
    <div class="summary-cards">
        <div class="summary-card">
            <h3>Total Outstanding Fines</h3>
            <p class="amount"><?= formatCurrency($total_fine) ?></p>
        </div>
        <div class="summary-card">
            <h3>Overdue Books</h3>
            <p class="count"><?= count($overdue_books) ?></p>
        </div>
        <div class="summary-card">
            <h3>Fine Rate</h3>
            <p class="rate">₱10.00 per day</p>
        </div>
    </div>

    <!-- Current Overdue Books -->
    <?php if (!empty($overdue_books)): ?>
        <div class="overdue-section">
            <h3>Currently Overdue Books</h3>
            <div class="overdue-grid">
                <?php foreach ($overdue_books as $book): ?>
                    <?php 
                    $days_overdue = ceil((time() - strtotime($book['due_date'])) / (60 * 60 * 24));
                    $current_fine = calculateFine($book['due_date']);
                    ?>
                    <div class="overdue-card">
                        <div class="book-info">
                            <h4><?= htmlspecialchars($book['title']) ?></h4>
                            <p class="author">by <?= htmlspecialchars($book['author']) ?></p>
                            <p class="book-id">ID: <?= htmlspecialchars($book['book_code']) ?></p>
                            <p class="due-date">Due: <?= date('F j, Y', strtotime($book['due_date'])) ?></p>
                            <p class="days-overdue">Days Overdue: <?= $days_overdue ?></p>
                            <p class="current-fine">Current Fine: <?= formatCurrency($current_fine) ?></p>
                        </div>
                        <div class="overdue-actions">
                            <form method="POST" action="../actions/return_book.php">
                                <input type="hidden" name="borrowing_id" value="<?= $book['id'] ?>">
                                <button type="submit" class="return-btn">Return Book</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Fine History -->
    <div class="fines-history">
        <h3>Fine History</h3>
        <?php if (empty($fines_history)): ?>
            <div class="no-fines">
                <p>No fine history found.</p>
            </div>
        <?php else: ?>
            <div class="fines-table">
                <table>
                    <thead>
                        <tr>
                            <th>Book</th>
                            <th>Due Date</th>
                            <th>Return Date</th>
                            <th>Days Overdue</th>
                            <th>Fine Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($fines_history as $fine): ?>
                            <?php 
                            $days_overdue = ceil((strtotime($fine['return_date']) - strtotime($fine['due_date'])) / (60 * 60 * 24));
                            ?>
                            <tr>
                                <td>
                                    <strong><?= htmlspecialchars($fine['title']) ?></strong><br>
                                    <small>by <?= htmlspecialchars($fine['author']) ?></small><br>
                                    <small class="book-id"><?= htmlspecialchars($fine['book_code']) ?></small>
                                </td>
                                <td><?= date('M j, Y', strtotime($fine['due_date'])) ?></td>
                                <td><?= date('M j, Y', strtotime($fine['return_date'])) ?></td>
                                <td class="days-overdue"><?= $days_overdue ?> days</td>
                                <td class="fine-amount"><?= formatCurrency($fine['fine_amount']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <!-- Fine Policy Information -->
    <div class="policy-section">
        <h3>Fine Policy</h3>
        <div class="policy-content">
            <div class="policy-item">
                <h4>📚 Borrowing Limit</h4>
                <p>Students can borrow a maximum of 2 books at a time.</p>
            </div>
            <div class="policy-item">
                <h4>⏰ Loan Period</h4>
                <p>Books can be borrowed for 7 days, including weekends.</p>
            </div>
            <div class="policy-item">
                <h4>💰 Fine Rate</h4>
                <p>Fine of ₱10.00 per day per book for overdue items.</p>
            </div>
            <div class="policy-item">
                <h4>📖 Book Status</h4>
                <p>Archived books cannot be borrowed. Books cannot be deleted from the system.</p>
            </div>
        </div>
    </div>
</div>

<style>
.fines-page {
    padding: 1rem;
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

.summary-card .amount {
    font-size: 2rem;
    font-weight: 700;
    margin: 0;
    color: #ff9800;
}

.summary-card .count {
    font-size: 2rem;
    font-weight: 700;
    margin: 0;
    color: #f44336;
}

.summary-card .rate {
    font-size: 1.5rem;
    font-weight: 700;
    margin: 0;
    color: #4CAF50;
}

.overdue-section, .fines-history, .policy-section {
    margin-bottom: 2rem;
}

.overdue-section h3, .fines-history h3, .policy-section h3 {
    color: #ffdd57;
    margin-bottom: 1rem;
}

.overdue-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
    gap: 1.5rem;
}

.overdue-card {
    background: rgba(244, 67, 54, 0.1);
    padding: 1.5rem;
    border-radius: 10px;
    backdrop-filter: blur(8.5px);
    border: 1px solid #f44336;
    transition: transform 0.3s ease;
}

.overdue-card:hover {
    transform: translateY(-2px);
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

.due-date {
    color: #ccc;
}

.days-overdue {
    color: #f44336;
    font-weight: 700;
}

.current-fine {
    color: #ff9800;
    font-weight: 700;
    font-size: 1.1rem;
}

.overdue-actions {
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

.no-fines {
    text-align: center;
    padding: 3rem;
    background: rgba(255, 255, 255, 0.1);
    border-radius: 10px;
}

.fines-table {
    background: rgba(255, 255, 255, 0.1);
    border-radius: 10px;
    overflow: hidden;
}

.fines-table table {
    width: 100%;
    border-collapse: collapse;
}

.fines-table th,
.fines-table td {
    padding: 1rem;
    text-align: left;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.fines-table th {
    background: rgba(255, 221, 87, 0.2);
    color: #ffdd57;
    font-weight: 700;
}

.fines-table td {
    color: #fff;
}

.fines-table tr:hover {
    background: rgba(255, 255, 255, 0.05);
}

.days-overdue {
    color: #f44336;
    font-weight: 700;
}

.fine-amount {
    color: #ff9800;
    font-weight: 700;
}

.policy-content {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1.5rem;
}

.policy-item {
    background: rgba(255, 255, 255, 0.1);
    padding: 1.5rem;
    border-radius: 10px;
    backdrop-filter: blur(8.5px);
    border: 1px solid rgba(255, 255, 255, 0.18);
}

.policy-item h4 {
    margin: 0 0 1rem 0;
    color: #ffdd57;
    font-size: 1.1rem;
}

.policy-item p {
    margin: 0;
    color: #fff;
    line-height: 1.5;
}

@media (max-width: 768px) {
    .summary-cards {
        grid-template-columns: 1fr;
    }
    
    .overdue-grid {
        grid-template-columns: 1fr;
    }
    
    .policy-content {
        grid-template-columns: 1fr;
    }
    
    .fines-table {
        overflow-x: auto;
    }
}
</style>
