<?php
session_start();
require_once '../config/db.php';
require_once '../config/functions.php';

// Check if user is admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = intval($_POST['user_id']);
    $reason = isset($_POST['reason']) ? sanitizeInput($_POST['reason']) : '';

    // Validate user ID
    if ($user_id <= 0) {
        $_SESSION['error'] = 'Invalid user selection.';
        header('Location: ../admin/manage_users.php');
        exit;
    }

    // Check if user exists and is not already suspended
    $stmt = $conn->prepare("SELECT id, username, status FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        $_SESSION['error'] = 'User not found.';
        header('Location: ../admin/manage_users.php');
        exit;
    }

    $user = $result->fetch_assoc();
    if ($user['status'] === 'suspended') {
        $_SESSION['error'] = 'User is already suspended.';
        header('Location: ../admin/manage_users.php');
        exit;
    }

    // Suspend the user
    if (suspendUser($user_id, $reason)) {
        $_SESSION['success'] = "User '{$user['username']}' has been suspended successfully.";
    } else {
        $_SESSION['error'] = 'Error suspending user.';
    }

    header('Location: ../admin/manage_users.php');
    exit;
} else {
    header('Location: ../admin/manage_users.php');
    exit;
}
?> 