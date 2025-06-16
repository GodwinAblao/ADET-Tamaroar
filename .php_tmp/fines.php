<?php
session_start();
require_once '../config/db.php';
require_once '../includes/header.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    header("Location: ../index.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$fine_per_day = 5;
$today = new DateTime();

// Get all borrowed books
$sql = "SELECT br.*, b.title 
        FROM borrow_records br 
        JOIN books b ON br.book_id = b.book_id 
        WHERE br.user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<h2>📄 Fines Summary</h2>

<table border="1" cellpadding="10">
    <tr>
        <th>Book Title</th>
        <th>Borrow Date</th>
        <th>Return Date</th>
        <th>Due Date</th>
        <th>Fine (₱)</th>
    </tr>

    <?php while ($row = $result->fetch_assoc()): ?>
        <?php
        $borrow_date = new DateTime($row['borrow_date']);
        $due_date = clone $borrow_date;
        $due_date->modify('+7 days');

        $return_date = $row['return_date'] ? new DateTime($row['return_date']) : $today;

        $interval = $due_date->diff($return_date);
        $late_days = ($return_date > $due_date) ? $interval->days : 0;
        $fine = $late_days * $fine_per_day;
        ?>
        <tr>
            <td><?= htmlspecialchars($row['title']) ?></td>
            <td><?= $borrow_date->format('Y-m-d') ?></td>
            <td><?= $row['return_date'] ? $return_date->format('Y-m-d') : 'Not yet returned' ?></td>
            <td><?= $due_date->format('Y-m-d') ?></td>
            <td><?= $fine > 0 ? "₱" . $fine : 'No fine' ?></td>
        </tr>
    <?php endwhile; ?>
</table>

<?php require_once '../includes/footer.php'; ?>
