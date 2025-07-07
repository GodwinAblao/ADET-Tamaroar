<?php
$host = 'localhost';
$user = 'root';
$pass = '';
$dbname = 'tamaroar_library';

try {
    // Create PDO connection
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
    // Also create MySQLi connection for backward compatibility
    $conn = new mysqli($host, $user, $pass, $dbname);
    
    // Check MySQLi connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>
