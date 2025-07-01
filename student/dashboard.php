<?php
require_once '../config/session.php';

if ($_SESSION['role'] !== 'student') {
    header("Location: ../index.php");
    exit;
}

$name = htmlspecialchars($_SESSION['full_name']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Student Dashboard ⋅ Tamaroar Library</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;700&display=swap" rel="stylesheet" />
  <style>
    body {
      font-family: 'Poppins', sans-serif;
      background: linear-gradient(135deg, #667eea, #764ba2);
      margin: 0; height: 100vh; display: flex; flex-direction: column; color: #fff;
    }
    .page-container {
      flex: 1; display: flex; gap: 2rem; padding: 2rem; box-sizing: border-box;
    }
    .sidebar, .content-area, footer {
      background: rgba(255, 255, 255, 0.1);
      border-radius: 15px;
      box-shadow: 0 8px 32px rgba(31, 38, 135, 0.37);
      backdrop-filter: blur(8.5px);
      border: 1px solid rgba(255, 255, 255, 0.18);
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
    .nav-links { list-style: none; margin: 0; padding: 0; width: 100%; }
    .nav-links li { margin-bottom: 1rem; }
    .nav-links button {
      width: 100%; background: none; border: none;
      color: #ffdd57; font-weight: 700; font-size: 1.1rem;
      text-align: left; padding: 0.5rem 1rem; border-radius: 10px;
      cursor: pointer; transition: background-color 0.3s ease;
    }
    .nav-links button:hover,
    .nav-links button.active {
      background-color: rgba(255, 221, 87, 0.3);
    }
    .content-area {
      flex: 1; padding: 2rem 3rem; overflow-y: auto;
    }
    footer {
      padding: 1rem 2rem; text-align: center;
      color: #ffdd57; font-weight: 700;
    }
  </style>
</head>
<body>
  <div class="page-container">
    <aside class="sidebar">
      <h2><span class="highlight">Tamaroar</span> Library</h2>
      <ul class="nav-links">
        <li><button class="nav-btn active" data-content="browse_books">Browse Books</button></li>
        <li><button class="nav-btn" data-content="borrow_books">My Borrowed</button></li>
        <li><button class="nav-btn" data-content="fines">Fines</button></li>
        <li><button class="nav-btn" data-content="logout">Logout</button></li>
      </ul>
    </aside>

    <main id="content-area" class="content-area">
      <h3>Welcome, <?= $name ?>!</h3>
      <p>Select an option to continue.</p>
    </main>
  </div>

  <footer>&copy; <?= date("Y") ?> Tamaroar Library.</footer>

  <script>
    const contentArea = document.getElementById('content-area');
    const navBtns = document.querySelectorAll('.nav-btn');

    const clearActive = () => navBtns.forEach(b => b.classList.remove('active'));

    const loadSection = key => {
      if (key === 'logout') {
        window.location.href = '../actions/logout.php';
        return;
        }

        fetch(`${key}.php`) // ✅ Fixed path
          .then(r => {
            if (!r.ok) throw new Error('HTTP ' + r.status);
            return r.text();
            })
            .then(html => contentArea.innerHTML = html)
            .catch(err => {
              console.error('Load error:', err);
              contentArea.innerHTML = `<p style="color: #ffdd57;">Error loading section.</p>`;
            });
        };

        navBtns.forEach(btn => {
        btn.addEventListener('click', () => {
          clearActive();
            btn.classList.add('active');
          loadSection(btn.dataset.content);
        });
      });
    // default load
    loadSection('browse_books');
  </script>
</body>
</html>
