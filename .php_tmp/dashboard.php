<?php
require_once '../config/session.php';

if ($_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Admin Dashboard - Tamaroar Library</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;700&display=swap" rel="stylesheet" />
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #667eea, #764ba2);
            height: 100vh;
            margin: 0;
            display: flex;
            flex-direction: column;
            color: #fff;
        }
        .page-container {
            flex: 1;
            display: flex;
            gap: 2rem;
            padding: 2rem;
            box-sizing: border-box;
        }
        .sidebar {
            width: 220px;
            background: rgba(255, 255, 255, 0.1);
            padding: 2rem 1.5rem;
            border-radius: 15px;
            backdrop-filter: blur(8.5px);
            border: 1px solid rgba(255, 255, 255, 0.18);
            display: flex;
            flex-direction: column;
        }
        .sidebar h2 {
            font-size: 2rem;
            margin-bottom: 2rem;
            text-align: center;
            font-weight: 700;
        }
        .highlight {
            color: #ffdd57;
            font-size: 2.5rem;
            font-family: 'Segoe Script', cursive;
            text-shadow: 0 3px 8px rgba(255, 221, 87, 0.7);
        }
        .nav-links {
            list-style: none;
            padding: 0;
        }
        .nav-links li {
            margin-bottom: 1rem;
        }
        .nav-links button {
            width: 100%;
            background: none;
            border: none;
            color: #ffdd57;
            font-weight: 700;
            font-size: 1.1rem;
            text-align: left;
            padding: 0.5rem 1rem;
            border-radius: 10px;
            cursor: pointer;
        }
        .nav-links button:hover,
        .nav-links button.active {
            background-color: rgba(255, 221, 87, 0.3);
        }
        .content-area {
            flex: 1;
            background: rgba(255, 255, 255, 0.1);
            padding: 2rem 3rem;
            border-radius: 15px;
            overflow-y: auto;
            border: 1px solid rgba(255, 255, 255, 0.18);
        }
        footer {
            background: rgba(255, 255, 255, 0.1);
            text-align: center;
            color: #ffdd57;
            font-weight: 700;
            padding: 1rem 2rem;
            margin-top: auto;
            border-top: 1px solid rgba(255, 255, 255, 0.18);
        }
    </style>
</head>
<body>
    <div class="page-container">
        <aside class="sidebar">
            <h2><span class="highlight">Tamaroar</span> Library</h2>
            <ul class="nav-links">
                <li><button class="nav-btn active" data-content="add_book">Add Book</button></li>
                <li><button class="nav-btn" data-content="edit_book">Edit Book</button></li>
                <li><button class="nav-btn" data-content="borrow_records">Borrow Records</button></li>
                <li><button class="nav-btn" data-content="manage_books">Manage Books</button></li>
                <li><button class="nav-btn" data-content="manage_users">Manage Users</button></li>
                <li><button class="nav-btn" data-content="logout">Logout</button></li>
            </ul>
        </aside>

        <main class="content-area" id="content-area">
            <h3>Welcome, Admin <?php echo htmlspecialchars($_SESSION['name']); ?>!</h3>
            <p>Select an option from the menu to get started.</p>
        </main>
    </div>

    <footer>
        &copy; <?php echo date("Y"); ?> Tamaroar Library. All rights reserved.
    </footer>

    <script>
        const contentArea = document.getElementById('content-area');
        const navButtons = document.querySelectorAll('.nav-btn');

        function clearActiveButtons() {
            navButtons.forEach(btn => btn.classList.remove('active'));
        }

        function handleNavClick(event) {
            const btn = event.currentTarget;
            const contentKey = btn.dataset.content;

            clearActiveButtons();
            btn.classList.add('active');

            if (contentKey === 'logout') {
                window.location.href = '../actions/logout.php';
                return;
            }

            loadContent(contentKey);
        }

        function loadContent(contentKey) {
            const url = `../actions/${contentKey}.php`; // adjusted for relative path

            fetch(url)
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error ${response.status}`);
                    }
                    return response.text();
                })
                .then(html => {
                    contentArea.innerHTML = html;
                })
                .catch(error => {
                    contentArea.innerHTML = `<p style="color: red;">Error loading content: ${error.message}</p>`;
                    console.error('Fetch error:', error);
                });
        }

        navButtons.forEach(btn => btn.addEventListener('click', handleNavClick));

        // Load initial content
        loadContent('add_book');
    </script>
</body>
</html>
