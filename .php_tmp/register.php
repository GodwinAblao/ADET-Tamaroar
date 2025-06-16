<?php
session_start();
if (isset($_SESSION['user_id'])) {
    header("Location: public/dashboard.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Register - Tamaroar Library</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;700&display=swap" rel="stylesheet" />
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #667eea, #764ba2);
            height: 100vh;
            margin: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            color: #fff;
        }
        .register-container {
            background: rgba(255, 255, 255, 0.1);
            padding: 2.5rem 3rem;
            border-radius: 15px;
            box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.37);
            backdrop-filter: blur(8.5px);
            -webkit-backdrop-filter: blur(8.5px);
            border: 1px solid rgba(255, 255, 255, 0.18);
            width: 100%;
            max-width: 400px;
            box-sizing: border-box;
            text-align: center;
        }
        h2 {
            font-weight: 700;
            font-size: 2rem;
            margin-bottom: 1.5rem;
            letter-spacing: 2px;
            text-shadow: 0 2px 5px rgba(0,0,0,0.2);
        }
        .highlight {
            color: #ffdd57;
            font-weight: 900;
            font-size: 2.5rem;
            font-family: 'Segoe Script', cursive;
            text-shadow: 0 3px 8px rgba(255, 221, 87, 0.7);
        }
        form {
            display: flex;
            flex-direction: column;
        }
        input[type="text"],
        input[type="email"],
        input[type="password"] {
            padding: 0.75rem 1rem;
            margin-bottom: 1rem;
            border: none;
            border-radius: 30px;
            font-size: 1rem;
            outline: none;
            transition: box-shadow 0.3s ease;
        }
        input[type="text"]:focus,
        input[type="email"]:focus,
        input[type="password"]:focus {
            box-shadow: 0 0 8px 2px #ffdd57;
        }
        button {
            padding: 0.75rem 1rem;
            background-color: #ffdd57;
            border: none;
            border-radius: 30px;
            color: #333;
            font-weight: 700;
            font-size: 1.1rem;
            cursor: pointer;
            transition: background-color 0.3s ease;
            box-shadow: 0 4px 15px rgba(255, 221, 87, 0.6);
        }
        button:hover {
            background-color: #f0c419;
        }
        p {
            margin-top: 1rem;
            color: #eee;
            font-size: 0.9rem;
        }
        p a {
            color: #ffdd57;
            text-decoration: none;
            font-weight: 700;
        }
        p a:hover {
            text-decoration: underline;
        }
        .error-message {
            background-color: rgba(255, 0, 0, 0.7);
            padding: 0.5rem 1rem;
            border-radius: 10px;
            margin-bottom: 1rem;
            font-weight: 600;
            color: #fff;
        }
    </style>
</head>
<body>
    <div class="register-container">
        <h2><span class="highlight">Tamaroar</span> Library</h2>
        <?php
        if (isset($_SESSION['error'])) {
            echo '<div class="error-message">' . htmlspecialchars($_SESSION['error']) . '</div>';
            unset($_SESSION['error']);
        }
        ?>
        <form action="actions/register.php" method="POST" novalidate>
            <input type="text" name="full_name" placeholder="Full Name" required autofocus />
            <input type="email" name="email" placeholder="Email" required />
            <input type="password" name="password" placeholder="Password" required />
            <input type="password" name="confirm_password" placeholder="Confirm Password" required />
            <button type="submit">Register</button>
        </form>
        <p>Already have an account? <a href="index.php">Login here</a></p>
    </div>
</body>
</html>
