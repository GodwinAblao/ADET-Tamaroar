<a name="readme-top"></a>

<br/>

<div align="center">
  <a href="#">
    <!-- TODO: Add your logo or banner here if desired -->
    <img src="./assets/logo.png" alt="Tamaroar Library Logo" width="130" height="100">
  </a>
  <h3 align="center">Tamaroar Library System</h3>
</div>

<div align="center">
  A modern PHP & MySQL library management system for schools and organizations.
</div>

<br/>

<!-- Badges (edit as needed) -->
![](https://visit-counter.vercel.app/counter.png?page=GodwinAblao/ADET-Tamaroar)

[![wakatime](https://wakatime.com/badge/user/018dd99a-4985-4f98-8216-6ca6fe2ce0f8/project/63501637-9a31-42f0-960d-4d0ab47977f8.svg)](https://wakatime.com/badge/user/018dd99a-4985-4f98-8216-6ca6fe2ce0f8/project/63501637-9a31-42f0-960d-4d0ab47977f8)

---

<details>
  <summary>Table of Contents</summary>
  <ol>
    <li><a href="#overview">Overview</a>
      <ol>
        <li><a href="#key-components">Key Components</a></li>
        <li><a href="#technology">Technology</a></li>
      </ol>
    </li>
    <li><a href="#rules-practices-and-principles">Rules, Practices and Principles</a></li>
    <li><a href="#file-structure">File Structure</a></li>
    <li><a href="#resources">Resources</a></li>
  </ol>
</details>

---

## Overview

Tamaroar Library System is a robust, user-friendly web application for managing library resources, users, and transactions. It features:
- Modern, unified UI for admins and students
- Secure authentication and role-based access
- Automated book ID generation and fine calculation
- Responsive design for all devices
- Automated critical system testing

### Key Components
- Multi-page web application (Admin & Student interfaces)
- Book management (CRUD, cover upload, category assignment)
- Borrowing and returning with fine calculation
- User registration, login, and role management
- Real-time book availability and borrowing limits
- Automated test script for system health

### Technology
![HTML](https://img.shields.io/badge/HTML-E34F26?style=for-the-badge&logo=html5&logoColor=white)
![CSS](https://img.shields.io/badge/CSS-1572B6?style=for-the-badge&logo=css3&logoColor=white)
![PHP](https://img.shields.io/badge/PHP-777BB4?style=for-the-badge&logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-005C84?style=for-the-badge&logo=mysql&logoColor=white)

---

## Rules, Practices and Principles
1. Use clear, descriptive naming for files and folders (camelCase recommended).
2. Place files in their respective folders (actions, admin, student, config, assets, uploads).
3. Use only external CSS for styling.
4. Do not rename core entry files (`index.php`, `register.php`, `login.php`).
5. All book and user management must go through the provided forms and backend logic.
6. Test all changes using the provided `test_critical_system.php` script before deployment.

## File Structure
```
ADET-Tamaroar/
├── actions/         # Form processing scripts
├── admin/           # Admin interface (dashboard, manage books/users)
├── student/         # Student interface (dashboard, browse, fines)
├── config/          # DB connection, session, functions
├── assets/          # CSS, images
├── uploads/         # Book cover images
├── index.php        # Login page
├── register.php     # Registration page
├── login.php        # Login page (modern design)
├── test_critical_system.php # Automated test script
└── library_system.sql # Database schema
```

---

## Resources
| Title | Purpose | Link |
|-|-|-|
| Github | Project Template | https://github.com/GodwinAblao/AD-FinalProject-Tamaroar |
| Badges4-README.md-Profile | README badges | https://github.com/alexandresanlim/Badges4-README.md-Profile/blob/master/README.md |
| Commit guide | Git commit best practices | https://github.com/zyx-0314/Github-Git-Guide/blob/ef7db7f75870f69828938fd610e478783d1750e9/git/commit.md#L4 |

---

**Tamaroar Library System** - A modern, efficient library management solution built with PHP and MySQL.
