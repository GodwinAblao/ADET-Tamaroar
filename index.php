<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Library System - Home</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
    <header>
        <div class="logo-nav">
            <img src="assets/logo.png" alt="Library Logo" class="logo">
            <nav aria-label="Main Navigation">
                <button class="nav-toggle" aria-label="Open navigation menu" aria-expanded="false">&#9776;</button>
                <ul class="nav-list">
                    <li><a href="index.php">Home</a></li>
                    <li><a href="#about">About Us</a></li>
                    <li><a href="#contact">Contact</a></li>
                    <li><a href="login.php" class="login-btn">Login</a></li>
                    <li><a href="register.php" class="register-btn">Register</a></li>
                </ul>
            </nav>
        </div>
    </header>
    <main>
        <section class="hero fade-in" id="home">
            <h1>Welcome to Tamaroar Library System</h1>
            <p>Your gateway to knowledge and resources. Manage, borrow, and explore books with ease.</p>
            <div class="cta-buttons">
                <a href="login.php" class="btn btn-login">Login</a>
                <a href="register.php" class="btn btn-register">Register</a>
            </div>
        </section>
        <section id="about" class="about fade-in">
            <div class="about-hero-card">
                <h2>About Tamaroar Library</h2>
                <p>Tamaroar Library is a modern digital library platform designed to empower students, staff, and the academic community. We provide seamless access to a vast collection of books and resources, innovative technology, and a supportive environment for learning and growth.</p>
            </div>
            <div class="about-mv-row">
                <div class="about-mv-card about-vision">
                    <h3>Vision</h3>
                    <p>To be the leading digital library in the region, supporting academic excellence, personal growth, and a culture of lifelong learning through accessible and innovative library services.</p>
                </div>
                <div class="about-mv-card about-mission">
                    <h3>Mission</h3>
                    <p>To empower students and staff with seamless access to knowledge and resources, foster curiosity, and provide tools for success in academics and beyond.</p>
                </div>
            </div>
            <div class="about-details">
                <div class="about-details-card">
                    <h3>Who Can Benefit from Us?</h3>
                    <ul>
                        <li><strong>Students:</strong> Access course materials, recommended readings, and research resources anytime, anywhere.</li>
                        <li><strong>Faculty & Staff:</strong> Manage book collections, track borrowing, and support student learning.</li>
                        <li><strong>Researchers:</strong> Discover a wide range of academic resources and references for your projects.</li>
                        <li><strong>Community Members:</strong> Participate in events, workshops, and lifelong learning opportunities.</li>
                    </ul>
                </div>
                <div class="about-details-card">
                    <h3>How It Works?</h3>
                    <ol>
                        <li><strong>Register:</strong> Create your free account to get started.</li>
                        <li><strong>Browse & Search:</strong> Explore our catalog and find the books or resources you need.</li>
                        <li><strong>Borrow & Return:</strong> Borrow books online and return them with ease.</li>
                        <li><strong>Track & Manage:</strong> Use your dashboard to track borrowed books, due dates, and fines.</li>
                        <li><strong>Get Support:</strong> Reach out to our team for help or recommendations.</li>
                    </ol>
                    <a href="register.php" class="btn btn-register get-started-btn">Get Started</a>
                </div>
            </div>
        </section>
        <section id="contact" class="contact fade-in">
            <h2>Contact</h2>
            <form class="contact-form" aria-label="Contact form" method="post" action="#">
                <label for="name">Name</label>
                <input type="text" id="name" name="name" required>
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required>
                <label for="message">Message</label>
                <textarea id="message" name="message" rows="4" required></textarea>
                <button type="submit" class="btn btn-login">Send Message</button>
            </form>
            <div class="contact-info">
                <p>Email: info@tamaroarlibrary.edu<br>Phone: (123) 456-7890</p>
                <div class="social-icons">
                    <a href="#" aria-label="Facebook"><img src="assets/social_facebook.png" alt="Facebook"></a>
                    <a href="#" aria-label="Twitter"><img src="assets/social_twitter.png" alt="Twitter"></a>
                    <a href="#" aria-label="Instagram"><img src="assets/social_instagram.png" alt="Instagram"></a>
                </div>
            </div>
        </section>
    </main>
    <footer>
        <div class="footer-content">
            <div class="footer-address">
                <p>123 Library Ave, Tamaroar City, 12345</p>
            </div>
            <div class="footer-social">
                <a href="#" aria-label="Facebook"><img src="assets/social_facebook.png" alt="Facebook"></a>
                <a href="#" aria-label="Twitter"><img src="assets/social_twitter.png" alt="Twitter"></a>
                <a href="#" aria-label="Instagram"><img src="assets/social_instagram.png" alt="Instagram"></a>
            </div>
        </div>
        <p>&copy; 2024 Tamaroar Library System. All rights reserved.</p>
    </footer>
    <script>
    // Hamburger menu for mobile
    const navToggle = document.querySelector('.nav-toggle');
    const navList = document.querySelector('.nav-list');
    navToggle.addEventListener('click', function() {
        const expanded = navToggle.getAttribute('aria-expanded') === 'true';
        navToggle.setAttribute('aria-expanded', !expanded);
        navList.classList.toggle('open');
    });
    // Fade-in animation
    window.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('.fade-in').forEach(el => {
            el.classList.add('visible');
        });
    });
    // Active nav highlighting on scroll
    const navLinks = document.querySelectorAll('nav ul li a');
    const sections = [
        {id: 'home', link: navLinks[0]},
        {id: 'about', link: navLinks[1]},
        {id: 'contact', link: navLinks[2]}
    ];
    function setActiveNav() {
        let scrollPos = window.scrollY || window.pageYOffset;
        let offset = 120;
        let found = false;
        for (let i = sections.length - 1; i >= 0; i--) {
            let section = document.getElementById(sections[i].id);
            if (section && scrollPos + offset >= section.offsetTop) {
                sections.forEach(s => s.link.classList.remove('active-nav'));
                sections[i].link.classList.add('active-nav');
                found = true;
                break;
            }
        }
        if (!found) {
            sections.forEach(s => s.link.classList.remove('active-nav'));
        }
    }
    window.addEventListener('scroll', setActiveNav);
    window.addEventListener('DOMContentLoaded', setActiveNav);
    // Sticky header shadow on scroll
    const header = document.querySelector('header');
    window.addEventListener('scroll', function() {
        if (window.scrollY > 10) {
            header.classList.add('sticky');
        } else {
            header.classList.remove('sticky');
        }
    });
    </script>
    <!-- Botpress Chatbot Scripts removed from index.php -->
</body>
</html>
