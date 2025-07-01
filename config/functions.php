<?php
require_once 'db.php';

/**
 * Generate Book ID according to the specified format
 * Format: THFEB102022-FIC00001
 * TH - First 2 letters from the Book Title
 * FEB – month (published)
 * 10 - day (added to the system)
 * 2022 - year (published)
 * FIC - category of book ( FIC = Fiction)
 * 00001 - count of books on the library
 */
function generateBookId($title, $published_month, $published_day, $published_year, $category) {
    global $conn;
    
    // Get first 2 letters from title (uppercase)
    $title_prefix = strtoupper(substr(preg_replace('/[^a-zA-Z]/', '', $title), 0, 2));
    
    // Get month abbreviation
    $months = [
        1 => 'JAN', 2 => 'FEB', 3 => 'MAR', 4 => 'APR', 5 => 'MAY', 6 => 'JUN',
        7 => 'JUL', 8 => 'AUG', 9 => 'SEP', 10 => 'OCT', 11 => 'NOV', 12 => 'DEC'
    ];
    $month_abbr = $months[$published_month] ?? 'JAN';
    
    // Get current day when adding to system
    $current_day = date('d');
    $current_year = date('Y');
    
    // Get book count for this category
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM books WHERE category = ?");
    $stmt->bind_param("s", $category);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $book_count = $row['count'] + 1;
    
    // Format book count with leading zeros
    $formatted_count = str_pad($book_count, 5, '0', STR_PAD_LEFT);
    
    // Generate the book ID
    $book_id = $title_prefix . $month_abbr . $current_day . $current_year . '-' . $category . $formatted_count;
    
    return $book_id;
}

/**
 * Calculate fine amount for overdue books
 * Fine is ₱10.00 per day per book
 */
function calculateFine($due_date, $return_date = null) {
    if ($return_date === null) {
        $return_date = date('Y-m-d');
    }
    
    $due = new DateTime($due_date);
    $return = new DateTime($return_date);
    $interval = $due->diff($return);
    
    // If returned before or on due date, no fine
    if ($return <= $due) {
        return 0.00;
    }
    
    // Calculate days overdue
    $days_overdue = $interval->days;
    
    // Fine is ₱10.00 per day
    $fine_amount = $days_overdue * 10.00;
    
    return round($fine_amount, 2);
}

/**
 * Check if user can borrow more books
 * Maximum 2 books per student
 */
function canUserBorrow($user_id) {
    global $conn;
    
    $stmt = $conn->prepare("SELECT COUNT(*) as borrowed_count FROM borrowings WHERE user_id = ? AND status = 'borrowed'");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    return $row['borrowed_count'] < 2;
}

/**
 * Check if book is available for borrowing
 */
function isBookAvailable($book_id) {
    global $conn;
    
    $stmt = $conn->prepare("SELECT available_copies, status FROM books WHERE id = ?");
    $stmt->bind_param("i", $book_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $book = $result->fetch_assoc();
        return $book['available_copies'] > 0 && $book['status'] === 'active';
    }
    
    return false;
}

/**
 * Get user's current borrowings
 */
function getUserBorrowings($user_id) {
    global $conn;
    
    $stmt = $conn->prepare("
        SELECT b.*, bk.title, bk.author, bk.book_id as book_code
        FROM borrowings b
        JOIN books bk ON b.book_id = bk.id
        WHERE b.user_id = ?
        ORDER BY b.borrow_date DESC
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    return $result->fetch_all(MYSQLI_ASSOC);
}

/**
 * Get overdue books for a user
 */
function getOverdueBooks($user_id) {
    global $conn;
    
    $today = date('Y-m-d');
    $stmt = $conn->prepare("
        SELECT b.*, bk.title, bk.author, bk.book_id as book_code
        FROM borrowings b
        JOIN books bk ON b.book_id = bk.id
        WHERE b.user_id = ? AND b.due_date < ? AND b.status = 'borrowed'
        ORDER BY b.due_date ASC
    ");
    $stmt->bind_param("is", $user_id, $today);
    $stmt->execute();
    $result = $stmt->get_result();
    
    return $result->fetch_all(MYSQLI_ASSOC);
}

/**
 * Update book availability when borrowing/returning
 */
function updateBookAvailability($book_id, $action = 'borrow') {
    global $conn;
    
    if ($action === 'borrow') {
        $stmt = $conn->prepare("UPDATE books SET available_copies = available_copies - 1 WHERE id = ? AND available_copies > 0");
    } else {
        $stmt = $conn->prepare("UPDATE books SET available_copies = available_copies + 1 WHERE id = ?");
    }
    
    $stmt->bind_param("i", $book_id);
    return $stmt->execute();
}

/**
 * Get total fine amount for a user
 */
function getUserTotalFine($user_id) {
    global $conn;
    
    $stmt = $conn->prepare("SELECT SUM(fine_amount) as total_fine FROM borrowings WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    return $row['total_fine'] ?? 0.00;
}

/**
 * Format currency for display
 */
function formatCurrency($amount) {
    return '₱' . number_format($amount, 2);
}

/**
 * Get book categories
 */
function getBookCategories() {
    return [
        'FIC' => 'Fiction',
        'NON' => 'Non-Fiction',
        'REF' => 'Reference',
        'TEC' => 'Technology',
        'SCI' => 'Science',
        'HIS' => 'History',
        'BIO' => 'Biography',
        'POE' => 'Poetry',
        'DRA' => 'Drama',
        'CHI' => 'Children'
    ];
}

/**
 * Validate date format
 */
function isValidDate($date) {
    $d = DateTime::createFromFormat('Y-m-d', $date);
    return $d && $d->format('Y-m-d') === $date;
}

/**
 * Sanitize input
 */
function sanitizeInput($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}
?> 