<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit;
}

// Always block deletion attempts
header("Location: ../admin/manage_books.php?error=Book+deletion+is+not+allowed+in+this+system");
exit;
?>
