<?php
session_start();
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Login - Tamaroar Library</title>
    <link rel="stylesheet" href="assets/style.css">
    <style>
        .logo-link {
            display: block;
            text-align: center;
            text-decoration: none;
            transition: transform 0.3s ease;
        }
        .logo-link:hover {
            transform: scale(1.05);
        }
        .logo-link img {
            height: 80px;
            width: auto;
        }
        .login-container input[type="email"],
        .login-container input[type="password"] {
            padding: 0.75rem 1rem;
            border: 1px solid #e0e0e0;
            border-radius: 30px;
            font-size: 1rem;
            outline: none;
            transition: box-shadow 0.3s, border 0.3s;
        }
        .login-container input[type="email"]:focus,
        .login-container input[type="password"]:focus {
            border: 1.5px solid #FFD700;
            box-shadow: 0 0 8px 2px rgba(255, 236, 139, 0.2);
        }
    </style>
</head>
<body>
    <main>
        <a href="index.php" class="logo-link">
            <img src="assets/logo.png" alt="Library Logo">
        </a>
        <div class="login-container">
            <h2><span class="highlight">Tamaroar</span> Library</h2>
            <?php
            if (isset($_SESSION['error'])) {
                echo '<div class="error-message">' . htmlspecialchars($_SESSION['error']) . '</div>';
                unset($_SESSION['error']);
            }
            ?>
            <form action="actions/login.php" method="POST" novalidate>
                <input type="email" name="email" placeholder="Email" required autofocus />
                <input type="password" name="password" placeholder="Password" required />
                <button type="submit">Login</button>
            </form>
            <p>No account? <a href="register.php">Register here</a></p>
        </div>
    </main>
</body>
</html> 