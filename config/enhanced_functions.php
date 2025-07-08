<?php
/**
 * Enhanced Library System Functions
 * Advanced validation, fine calculation, and utility functions
 */

require_once 'db.php';

/**
 * Get system setting value
 */
function getSystemSetting($key, $default = null) {
    global $conn;
    $stmt = $conn->prepare("SELECT setting_value FROM system_settings WHERE setting_key = ?");
    $stmt->bind_param("s", $key);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        return $row['setting_value'];
    }
    
    return $default;
}

/**
 * Enhanced borrowing validation
 */
function validateBorrowing($userId, $bookId) {
    global $conn;
    
    // Check if user exists and is active
    $stmt = $conn->prepare("SELECT id, status FROM users WHERE id = ? AND role = 'student'");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        return ['valid' => false, 'message' => 'Invalid user account.'];
    }
    
    $user = $result->fetch_assoc();
    if ($user['status'] !== 'active') {
        return ['valid' => false, 'message' => 'Your account is not active. Please contact administrator.'];
    }
    
    // Check borrowing limit
    $maxBooks = (int)getSystemSetting('max_books_per_student', 2);
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM borrowings WHERE user_id = ? AND status = 'borrowed'");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $currentBorrowings = $result->fetch_assoc()['count'];
    
    if ($currentBorrowings >= $maxBooks) {
        return ['valid' => false, 'message' => "You have reached the maximum borrowing limit ({$maxBooks} books)."];
    }
    
    // Check for overdue books
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM borrowings WHERE user_id = ? AND status = 'overdue'");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $overdueBooks = $result->fetch_assoc()['count'];
    
    if ($overdueBooks > 0) {
        return ['valid' => false, 'message' => 'You have overdue books. Please return them first.'];
    }
    
    // Check book availability
    $stmt = $conn->prepare("SELECT id, title, available_copies, status FROM books WHERE id = ?");
    $stmt->bind_param("i", $bookId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        return ['valid' => false, 'message' => 'Book not found.'];
    }
    
    $book = $result->fetch_assoc();
    if ($book['status'] === 'archived') {
        return ['valid' => false, 'message' => 'This book is archived and not available for borrowing.'];
    }
    
    if ($book['available_copies'] <= 0) {
        return ['valid' => false, 'message' => 'This book is currently not available for borrowing.'];
    }
    
    return ['valid' => true, 'book' => $book];
}

/**
 * Enhanced fine calculation with progressive rates
 */
function calculateFine($dueDate, $returnDate = null) {
    if (!$returnDate) {
        $returnDate = date('Y-m-d H:i:s');
    }
    
    $dueTimestamp = strtotime($dueDate);
    $returnTimestamp = strtotime($returnDate);
    
    if ($returnTimestamp <= $dueTimestamp) {
        return 0; // No fine if returned on time
    }
    
    $daysOverdue = ceil(($returnTimestamp - $dueTimestamp) / 86400);
    
    // Get system settings
    $baseRate = (float)getSystemSetting('fine_per_day', 10);
    $escalationDays = (int)getSystemSetting('fine_escalation_days', 7);
    $escalationRate = (float)getSystemSetting('fine_escalation_rate', 15);
    $maxEscalationDays = (int)getSystemSetting('max_fine_escalation_days', 14);
    $maxRate = (float)getSystemSetting('max_fine_rate', 20);
    
    // Progressive fine calculation
    if ($daysOverdue <= $escalationDays) {
        $rate = $baseRate;
    } elseif ($daysOverdue <= $maxEscalationDays) {
        $rate = $escalationRate;
    } else {
        $rate = $maxRate;
    }
    
    return $rate * $daysOverdue;
}

/**
 * Process book borrowing
 */
function processBorrowing($userId, $bookId) {
    global $conn;
    
    // Validate borrowing
    $validation = validateBorrowing($userId, $bookId);
    if (!$validation['valid']) {
        return $validation;
    }
    
    $conn->begin_transaction();
    
    try {
        // Calculate due date
        $borrowingDays = (int)getSystemSetting('borrowing_days', 7);
        $dueDate = date('Y-m-d H:i:s', strtotime("+{$borrowingDays} days"));
        
        // Create borrowing record
        $stmt = $conn->prepare("INSERT INTO borrowings (user_id, book_id, due_date) VALUES (?, ?, ?)");
        $stmt->bind_param("iis", $userId, $bookId, $dueDate);
        $stmt->execute();
        
        // Update book availability
        $stmt = $conn->prepare("UPDATE books SET available_copies = available_copies - 1 WHERE id = ?");
        $stmt->bind_param("i", $bookId);
        $stmt->execute();
        
        // Create notification
        createNotification($userId, 'Book Borrowed', "You have successfully borrowed '{$validation['book']['title']}'. Due date: " . date('M d, Y', strtotime($dueDate)), 'success');
        
        $conn->commit();
        return ['valid' => true, 'message' => 'Book borrowed successfully!', 'due_date' => $dueDate];
        
    } catch (Exception $e) {
        $conn->rollback();
        return ['valid' => false, 'message' => 'Error processing borrowing: ' . $e->getMessage()];
    }
}

/**
 * Process book return
 */
function processReturn($borrowingId) {
    global $conn;
    
    $conn->begin_transaction();
    
    try {
        // Get borrowing details
        $stmt = $conn->prepare("SELECT b.*, bk.title, bk.id as book_id FROM borrowings b 
                               JOIN books bk ON b.book_id = bk.id 
                               WHERE b.id = ? AND b.status = 'borrowed'");
        $stmt->bind_param("i", $borrowingId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            return ['valid' => false, 'message' => 'Borrowing record not found or already returned.'];
        }
        
        $borrowing = $result->fetch_assoc();
        $returnDate = date('Y-m-d H:i:s');
        
        // Calculate fine
        $fineAmount = calculateFine($borrowing['due_date'], $returnDate);
        
        // Update borrowing record
        $stmt = $conn->prepare("UPDATE borrowings SET 
                               returned_date = ?, 
                               fine_amount = ?, 
                               status = 'returned' 
                               WHERE id = ?");
        $stmt->bind_param("sdi", $returnDate, $fineAmount, $borrowingId);
        $stmt->execute();
        
        // Update book availability
        $stmt = $conn->prepare("UPDATE books SET available_copies = available_copies + 1 WHERE id = ?");
        $stmt->bind_param("i", $borrowing['book_id']);
        $stmt->execute();
        
        // Create fine record if applicable
        if ($fineAmount > 0) {
            $stmt = $conn->prepare("INSERT INTO fines (borrowing_id, user_id, amount, reason) VALUES (?, ?, ?, 'overdue')");
            $stmt->bind_param("iid", $borrowingId, $borrowing['user_id'], $fineAmount);
            $stmt->execute();
            
            createNotification($borrowing['user_id'], 'Fine Incurred', "You have a fine of ₱{$fineAmount} for returning '{$borrowing['title']}' late.", 'warning');
        } else {
            createNotification($borrowing['user_id'], 'Book Returned', "You have successfully returned '{$borrowing['title']}'.", 'success');
        }
        
        $conn->commit();
        return ['valid' => true, 'message' => 'Book returned successfully!', 'fine_amount' => $fineAmount];
        
    } catch (Exception $e) {
        $conn->rollback();
        return ['valid' => false, 'message' => 'Error processing return: ' . $e->getMessage()];
    }
}

/**
 * Create notification
 */
function createNotification($userId, $title, $message, $type = 'info') {
    global $conn;
    $stmt = $conn->prepare("INSERT INTO notifications (user_id, title, message, type) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("isss", $userId, $title, $message, $type);
    return $stmt->execute();
}

/**
 * Get user notifications
 */
function getUserNotifications($userId, $limit = 10) {
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT ?");
    $stmt->bind_param("ii", $userId, $limit);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

/**
 * Mark notification as read
 */
function markNotificationAsRead($notificationId, $userId) {
    global $conn;
    $stmt = $conn->prepare("UPDATE notifications SET is_read = TRUE WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $notificationId, $userId);
    return $stmt->execute();
}

/**
 * Get user dashboard statistics
 */
function getUserDashboardStats($userId) {
    global $conn;
    
    // Current borrowings
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM borrowings WHERE user_id = ? AND status = 'borrowed'");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $currentBorrowings = $stmt->get_result()->fetch_assoc()['count'];
    
    // Overdue books
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM borrowings WHERE user_id = ? AND status = 'overdue'");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $overdueBooks = $stmt->get_result()->fetch_assoc()['count'];
    
    // Total fines
    $stmt = $conn->prepare("SELECT SUM(amount) as total FROM fines WHERE user_id = ? AND status = 'pending'");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $totalFines = $stmt->get_result()->fetch_assoc()['total'] ?? 0;
    
    // Unread notifications
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = FALSE");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $unreadNotifications = $stmt->get_result()->fetch_assoc()['count'];
    
    return [
        'current_borrowings' => $currentBorrowings,
        'overdue_books' => $overdueBooks,
        'total_fines' => $totalFines,
        'unread_notifications' => $unreadNotifications
    ];
}

/**
 * Get admin dashboard statistics
 */
function getAdminDashboardStats() {
    global $conn;
    
    // Total books
    $result = $conn->query("SELECT COUNT(*) as count FROM books WHERE status != 'archived'");
    $totalBooks = $result->fetch_assoc()['count'];
    
    // Available books
    $result = $conn->query("SELECT SUM(available_copies) as count FROM books WHERE status != 'archived'");
    $availableBooks = $result->fetch_assoc()['count'] ?? 0;
    
    // Total students
    $result = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'student' AND status = 'active'");
    $totalStudents = $result->fetch_assoc()['count'];
    
    // Current borrowings
    $result = $conn->query("SELECT COUNT(*) as count FROM borrowings WHERE status = 'borrowed'");
    $currentBorrowings = $result->fetch_assoc()['count'];
    
    // Overdue books
    $result = $conn->query("SELECT COUNT(*) as count FROM borrowings WHERE status = 'overdue'");
    $overdueBooks = $result->fetch_assoc()['count'];
    
    // Total fines
    $result = $conn->query("SELECT SUM(amount) as total FROM fines WHERE status = 'pending'");
    $totalFines = $result->fetch_assoc()['total'] ?? 0;
    
    return [
        'total_books' => $totalBooks,
        'available_books' => $availableBooks,
        'total_students' => $totalStudents,
        'current_borrowings' => $currentBorrowings,
        'overdue_books' => $overdueBooks,
        'total_fines' => $totalFines
    ];
}

/**
 * Update overdue books status
 */
function updateOverdueBooks() {
    global $conn;
    
    $stmt = $conn->prepare("UPDATE borrowings SET status = 'overdue' 
                           WHERE status = 'borrowed' AND due_date < NOW()");
    return $stmt->execute();
}

/**
 * Get recent activities for admin dashboard
 */
function getRecentActivities($limit = 10) {
    global $conn;
    
    $activities = [];
    
    // Get recent borrowings
    $stmt = $conn->prepare("
        SELECT 'borrow' as type, b.borrowed_date as activity_date, 
               u.full_name as user_name, bk.title as book_title,
               'borrowed' as action
        FROM borrowings b
        JOIN users u ON b.user_id = u.id
        JOIN books bk ON b.book_id = bk.id
        WHERE b.borrowed_date >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        ORDER BY b.borrowed_date DESC
        LIMIT ?
    ");
    $stmt->bind_param("i", $limit);
    $stmt->execute();
    $borrowings = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    // Get recent returns
    $stmt = $conn->prepare("
        SELECT 'return' as type, b.returned_date as activity_date,
               u.full_name as user_name, bk.title as book_title,
               'returned' as action
        FROM borrowings b
        JOIN users u ON b.user_id = u.id
        JOIN books bk ON b.book_id = bk.id
        WHERE b.returned_date IS NOT NULL 
        AND b.returned_date >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        ORDER BY b.returned_date DESC
        LIMIT ?
    ");
    $stmt->bind_param("i", $limit);
    $stmt->execute();
    $returns = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    // Get recent user registrations
    $stmt = $conn->prepare("
        SELECT 'registration' as type, u.created_at as activity_date,
               u.full_name as user_name, '' as book_title,
               'registered' as action
        FROM users u
        WHERE u.role = 'student' 
        AND u.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        ORDER BY u.created_at DESC
        LIMIT ?
    ");
    $stmt->bind_param("i", $limit);
    $stmt->execute();
    $registrations = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    // Get recent book additions
    $stmt = $conn->prepare("
        SELECT 'book_added' as type, b.created_at as activity_date,
               'Admin' as user_name, b.title as book_title,
               'added' as action
        FROM books b
        WHERE b.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        ORDER BY b.created_at DESC
        LIMIT ?
    ");
    $stmt->bind_param("i", $limit);
    $stmt->execute();
    $bookAdditions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    // Combine all activities and sort by date
    $allActivities = array_merge($borrowings, $returns, $registrations, $bookAdditions);
    
    // Sort by activity date (newest first)
    usort($allActivities, function($a, $b) {
        return strtotime($b['activity_date']) - strtotime($a['activity_date']);
    });
    
    // Return only the requested number of activities
    return array_slice($allActivities, 0, $limit);
}

/**
 * Get filtered activities for activity log
 */
function getFilteredActivities($filter = 'all', $days = 7, $limit = 20, $offset = 0) {
    global $conn;
    
    $activities = [];
    $dateFilter = ">= DATE_SUB(NOW(), INTERVAL {$days} DAY)";
    
    if ($filter === 'all' || $filter === 'borrow') {
        $stmt = $conn->prepare("
            SELECT 'borrow' as type, b.borrowed_date as activity_date, 
                   u.full_name as user_name, bk.title as book_title,
                   'borrowed' as action
            FROM borrowings b
            JOIN users u ON b.user_id = u.id
            JOIN books bk ON b.book_id = bk.id
            WHERE b.borrowed_date {$dateFilter}
            ORDER BY b.borrowed_date DESC
            LIMIT ? OFFSET ?
        ");
        $stmt->bind_param("ii", $limit, $offset);
        $stmt->execute();
        $borrowings = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $activities = array_merge($activities, $borrowings);
    }
    
    if ($filter === 'all' || $filter === 'return') {
        $stmt = $conn->prepare("
            SELECT 'return' as type, b.returned_date as activity_date,
                   u.full_name as user_name, bk.title as book_title,
                   'returned' as action
            FROM borrowings b
            JOIN users u ON b.user_id = u.id
            JOIN books bk ON b.book_id = bk.id
            WHERE b.returned_date IS NOT NULL 
            AND b.returned_date {$dateFilter}
            ORDER BY b.returned_date DESC
            LIMIT ? OFFSET ?
        ");
        $stmt->bind_param("ii", $limit, $offset);
        $stmt->execute();
        $returns = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $activities = array_merge($activities, $returns);
    }
    
    if ($filter === 'all' || $filter === 'registration') {
        $stmt = $conn->prepare("
            SELECT 'registration' as type, u.created_at as activity_date,
                   u.full_name as user_name, '' as book_title,
                   'registered' as action
            FROM users u
            WHERE u.role = 'student' 
            AND u.created_at {$dateFilter}
            ORDER BY u.created_at DESC
            LIMIT ? OFFSET ?
        ");
        $stmt->bind_param("ii", $limit, $offset);
        $stmt->execute();
        $registrations = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $activities = array_merge($activities, $registrations);
    }
    
    if ($filter === 'all' || $filter === 'book_added') {
        $stmt = $conn->prepare("
            SELECT 'book_added' as type, b.created_at as activity_date,
                   'Admin' as user_name, b.title as book_title,
                   'added' as action
            FROM books b
            WHERE b.created_at {$dateFilter}
            ORDER BY b.created_at DESC
            LIMIT ? OFFSET ?
        ");
        $stmt->bind_param("ii", $limit, $offset);
        $stmt->execute();
        $bookAdditions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $activities = array_merge($activities, $bookAdditions);
    }
    
    // Sort by activity date (newest first)
    usort($activities, function($a, $b) {
        return strtotime($b['activity_date']) - strtotime($a['activity_date']);
    });
    
    return array_slice($activities, 0, $limit);
}

/**
 * Get total count of activities for pagination
 */
function getTotalActivities($filter = 'all', $days = 7) {
    global $conn;
    
    $total = 0;
    $dateFilter = ">= DATE_SUB(NOW(), INTERVAL {$days} DAY)";
    
    if ($filter === 'all' || $filter === 'borrow') {
        $result = $conn->query("SELECT COUNT(*) as count FROM borrowings WHERE borrowed_date {$dateFilter}");
        $total += $result->fetch_assoc()['count'];
    }
    
    if ($filter === 'all' || $filter === 'return') {
        $result = $conn->query("SELECT COUNT(*) as count FROM borrowings WHERE returned_date IS NOT NULL AND returned_date {$dateFilter}");
        $total += $result->fetch_assoc()['count'];
    }
    
    if ($filter === 'all' || $filter === 'registration') {
        $result = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'student' AND created_at {$dateFilter}");
        $total += $result->fetch_assoc()['count'];
    }
    
    if ($filter === 'all' || $filter === 'book_added') {
        $result = $conn->query("SELECT COUNT(*) as count FROM books WHERE created_at {$dateFilter}");
        $total += $result->fetch_assoc()['count'];
    }
    
    return $total;
}

/**
 * Get activity statistics
 */
function getActivityStats($days = 7) {
    global $conn;
    
    $dateFilter = ">= DATE_SUB(NOW(), INTERVAL {$days} DAY)";
    
    // Total activities
    $result = $conn->query("SELECT COUNT(*) as count FROM borrowings WHERE borrowed_date {$dateFilter}");
    $borrowings = $result->fetch_assoc()['count'];
    
    $result = $conn->query("SELECT COUNT(*) as count FROM borrowings WHERE returned_date IS NOT NULL AND returned_date {$dateFilter}");
    $returns = $result->fetch_assoc()['count'];
    
    $result = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'student' AND created_at {$dateFilter}");
    $registrations = $result->fetch_assoc()['count'];
    
    $result = $conn->query("SELECT COUNT(*) as count FROM books WHERE created_at {$dateFilter}");
    $bookAdditions = $result->fetch_assoc()['count'];
    
    return [
        'total_activities' => $borrowings + $returns + $registrations + $bookAdditions,
        'borrowings' => $borrowings,
        'returns' => $returns,
        'registrations' => $registrations,
        'book_additions' => $bookAdditions
    ];
}

/**
 * Generate book ID
 */
function generateBookId($title, $author) {
    $prefix = strtoupper(substr($title, 0, 3));
    $authorPrefix = strtoupper(substr($author, 0, 3));
    $month = date('M');
    $year = date('Y');
    $random = strtoupper(substr(md5(uniqid()), 0, 5));
    
    return $prefix . $authorPrefix . $month . $year . '-' . $random;
}

/**
 * Upload book cover
 */
function uploadBookCover($file, $bookId) {
    $uploadDir = '../uploads/covers/';
    
    // Create directory if it doesn't exist
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    // Validate file
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
    if (!in_array($file['type'], $allowedTypes)) {
        return ['success' => false, 'message' => 'Invalid file type. Only JPG, PNG, and GIF are allowed.'];
    }
    
    if ($file['size'] > 5 * 1024 * 1024) { // 5MB limit
        return ['success' => false, 'message' => 'File too large. Maximum size is 5MB.'];
    }
    
    // Generate unique filename
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'book_' . $bookId . '_' . time() . '.' . $extension;
    $filepath = $uploadDir . $filename;
    
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        return ['success' => true, 'filepath' => 'uploads/covers/' . $filename];
    }
    
    return ['success' => false, 'message' => 'Failed to upload file.'];
}

/**
 * Check if a book can be safely deleted
 */
function canDeleteBook($bookId) {
    global $pdo;
    
    // Check for active borrowings
    $stmt = $pdo->prepare("SELECT COUNT(*) as active_count FROM borrowings WHERE book_id = ? AND status IN ('borrowed', 'overdue')");
    $stmt->execute([$bookId]);
    $activeBorrowings = $stmt->fetch()['active_count'];
    
    if ($activeBorrowings > 0) {
        return [
            'can_delete' => false,
            'reason' => "Book has $activeBorrowings active borrowing(s). All copies must be returned first.",
            'active_borrowings' => $activeBorrowings
        ];
    }
    
    // Check for borrowing history
    $stmt = $pdo->prepare("SELECT COUNT(*) as history_count FROM borrowings WHERE book_id = ?");
    $stmt->execute([$bookId]);
    $borrowingHistory = $stmt->fetch()['history_count'];
    
    return [
        'can_delete' => true,
        'reason' => 'Book can be safely deleted.',
        'active_borrowings' => 0,
        'borrowing_history' => $borrowingHistory
    ];
}

/**
 * Safely delete a book with all validations
 */
function safeDeleteBook($bookId, $adminId) {
    global $pdo;
    
    error_log("safeDeleteBook called with bookId: $bookId, adminId: $adminId");
    
    // Check if book can be deleted
    $canDelete = canDeleteBook($bookId);
    error_log("canDelete result: " . json_encode($canDelete));
    
    if (!$canDelete['can_delete']) {
        error_log("Book cannot be deleted: " . $canDelete['reason']);
        return [
            'success' => false,
            'message' => $canDelete['reason']
        ];
    }
    
    // Get book information for logging
    $stmt = $pdo->prepare("SELECT * FROM books WHERE id = ?");
    $stmt->execute([$bookId]);
    $book = $stmt->fetch();
    
    if (!$book) {
        error_log("Book not found with ID: $bookId");
        return [
            'success' => false,
            'message' => 'Book not found.'
        ];
    }
    
    $bookTitle = $book['title'];
    $bookAuthor = $book['author'];
    
    error_log("Attempting to delete book: $bookTitle by $bookAuthor");
    
    $pdo->beginTransaction();
    
    try {
        // Delete the book
        $stmt = $pdo->prepare("DELETE FROM books WHERE id = ?");
        $stmt->execute([$bookId]);
        
        $deletedRows = $stmt->rowCount();
        error_log("DELETE query affected $deletedRows rows");
        
        if ($deletedRows === 0) {
            throw new Exception("No book was deleted. Book may not exist.");
        }
        
        // Log the deletion activity
        $activityDescription = "Book deleted: '$bookTitle' by $bookAuthor (ID: $bookId)";
        if ($canDelete['borrowing_history'] > 0) {
            $activityDescription .= " - Had {$canDelete['borrowing_history']} borrowing records";
        }
        
        try {
            $stmt = $pdo->prepare("INSERT INTO activity_log (user_id, activity_type, description, created_at) VALUES (?, 'book_deleted', ?, NOW())");
            $stmt->execute([$adminId, $activityDescription]);
            error_log("Activity logged successfully");
        } catch (Exception $e) {
            error_log("Warning: Could not log activity - " . $e->getMessage());
            // Continue with deletion even if logging fails
        }
        
        $pdo->commit();
        error_log("Book deletion completed successfully");
        
        return [
            'success' => true,
            'message' => "Book '$bookTitle' has been permanently deleted successfully.",
            'book_title' => $bookTitle
        ];
        
    } catch (Exception $e) {
        $pdo->rollback();
        error_log("Error deleting book: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Error deleting book: ' . $e->getMessage()
        ];
    }
}
?> 