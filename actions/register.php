<?php
session_start();
require_once '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Validate inputs
    if (empty($full_name) || empty($email) || empty($password) || empty($confirm_password)) {
        $_SESSION['error'] = 'Please fill in all fields.';
        header("Location: ../register.php");
        exit;
    }

    if ($password !== $confirm_password) {
        $_SESSION['error'] = 'Passwords do not match.';
        header("Location: ../register.php");
        exit;
    }

    if (strlen($password) < 6) {
        $_SESSION['error'] = 'Password must be at least 6 characters long.';
        header("Location: ../register.php");
        exit;
    }

    // Check if email already exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $_SESSION['error'] = 'Email already exists. Please use a different email.';
        header("Location: ../register.php");
        exit;
    }

    // Generate username from email
    $username = explode('@', $email)[0];
    
    // Check if username exists and make it unique
    $original_username = $username;
    $counter = 1;
    while (true) {
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            break;
        }
        $username = $original_username . $counter;
        $counter++;
    }

    // Hash password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    $role = 'student';

    // Insert new user
    $stmt = $conn->prepare("INSERT INTO users (username, password, full_name, email, role) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssss", $username, $hashed_password, $full_name, $email, $role);

    if ($stmt->execute()) {
        header("Location: ../login.php");
        exit;
    } else {
        $_SESSION['error'] = 'Registration failed. Please try again.';
        header("Location: ../register.php");
        exit;
    }
} else {
    // If accessed directly without POST
    header("Location: ../register.php");
    exit;
}
?>
