<?php
session_start();
require_once '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    // Validate inputs
    if (empty($email) || empty($password)) {
        $_SESSION['error'] = 'Please enter both email and password.';
        header("Location: ../index.php");
        exit;
    }

    // Check if email exists
    $stmt = $conn->prepare("SELECT id, username, password, full_name, email, role, status FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        
        // Check if user is suspended
        if ($user['status'] === 'suspended') {
            $_SESSION['error'] = 'Your account has been suspended. Please contact the administrator.';
            header('Location: ../login.php');
            exit;
        }
        
        // Verify password
        if (password_verify($password, $user['password'])) {
            // Set session variables
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['role'] = $user['role'];
            
            // Redirect based on role
            if ($user['role'] === 'admin') {
                header("Location: ../admin/enhanced_dashboard.php");
            } else {
                header("Location: ../student/enhanced_dashboard.php");
            }
            exit;
        } else {
            // If login fails, set error and redirect to login.php (not index.php)
            $_SESSION['error'] = 'Invalid email or password.';
            header('Location: ../login.php');
            exit;
        }
    } else {
        // If login fails, set error and redirect to login.php (not index.php)
        $_SESSION['error'] = 'Invalid email or password.';
        header('Location: ../login.php');
        exit;
    }
} else {
    // If accessed directly without POST
    header("Location: ../index.php");
    exit;
}
?>
