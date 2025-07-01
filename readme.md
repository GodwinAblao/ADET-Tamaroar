# Tamaroar Library System

A comprehensive PHP-based library management system with user authentication, book management, borrowing system, and fine calculation.

## Features

### 🔐 User Management
- **Admin Account (Librarian)**: Full system access
- **Student Account**: Browse and borrow books
- Secure login/registration system
- Role-based access control

### 📚 Book Management
- **Automatic Book ID Generation**: Format `THFEB102024-FIC00001`
  - `TH` - First 2 letters from Book Title
  - `FEB` - Month abbreviation (published)
  - `10` - Day when added to system
  - `2024` - Current year
  - `FIC` - Category code
  - `00001` - Sequential number for category
- **Book Categories**: Fiction, Non-Fiction, Reference, Technology, Science, History, Biography, Poetry, Drama, Children
- **Book Status**: Active/Archived (archived books cannot be borrowed)
- **Cover Image Upload**: Support for JPG, JPEG, PNG, GIF formats
- **Minimum 50 Books**: System comes with 50 sample books

### 📖 Borrowing System
- **Borrowing Limit**: Maximum 2 books per student
- **Loan Period**: 7 days (including weekends)
- **Fine System**: ₱10.00 per day per book for overdue items
- **Real-time Availability**: Track available copies
- **Borrowing History**: Complete record of all transactions

### 💰 Fine Management
- **Automatic Calculation**: Fines calculated on return
- **Fine History**: Track all fine payments
- **Overdue Tracking**: Real-time overdue book monitoring
- **Payment Integration**: Ready for payment system integration

### 🎨 User Interface
- **Modern Design**: Glassmorphism UI with gradient backgrounds
- **Responsive Layout**: Works on desktop, tablet, and mobile
- **Intuitive Navigation**: Easy-to-use interface for all users
- **Real-time Updates**: Dynamic content loading

## System Requirements

- **PHP**: 7.4 or higher
- **MySQL**: 5.7 or higher
- **Web Server**: Apache/Nginx
- **PHP Extensions**: mysqli, session, fileinfo

## Installation

### 1. Database Setup

1. Create a MySQL database named `tamaroar_library`
2. Import the database structure:
   ```bash
   mysql -u root -p tamaroar_library < library_system.sql
   ```

### 2. Configuration

1. Update database connection in `config/db.php`:
   ```php
   $host = 'localhost';
   $user = 'your_username';
   $pass = 'your_password';
   $dbname = 'tamaroar_library';
   ```

2. Ensure the `uploads/` directory is writable:
   ```bash
   chmod 755 uploads/
   ```

### 3. Web Server Configuration

1. Place the project in your web server directory
2. Ensure URL rewriting is enabled (for clean URLs)
3. Set proper file permissions

### 4. Default Accounts

**Admin Account:**
- Email: `admin@tamaroar.com`
- Password: `password`

**Sample Student Accounts:**
- Email: `john.doe@student.com` / Password: `password`
- Email: `jane.smith@student.com` / Password: `password`
- Email: `mike.johnson@student.com` / Password: `password`

## File Structure

```
ADET-Tamaroar/
├── actions/                 # Form processing scripts
│   ├── login.php           # User authentication
│   ├── register.php        # User registration
│   ├── add_book.php        # Add new books
│   ├── borrow_book.php     # Borrow books
│   ├── return_book.php     # Return books
│   └── ...
├── admin/                  # Admin interface
│   ├── dashboard.php       # Admin dashboard
│   ├── add_book.php        # Add book form
│   └── ...
├── student/                # Student interface
│   ├── dashboard.php       # Student dashboard
│   ├── browse_books.php    # Browse available books
│   ├── borrow_books.php    # View borrowed books
│   └── fines.php          # View fines
├── config/                 # Configuration files
│   ├── db.php             # Database connection
│   ├── session.php        # Session management
│   └── functions.php      # Helper functions
├── uploads/               # Book cover images
├── assets/                # Static assets
├── index.php              # Login page
├── register.php           # Registration page
└── library_system.sql     # Database structure
```

## Key Features Implementation

### Book ID Generation
The system automatically generates unique book IDs using the specified format:
- Extracts first 2 letters from book title
- Uses month abbreviation from publication date
- Includes current date when added to system
- Appends category code and sequential number

### Fine Calculation
- Calculates fines at ₱10.00 per day per book
- Includes weekends in calculation
- Updates fine amount on book return
- Tracks fine history for reporting

### Borrowing Validation
- Enforces 2-book limit per student
- Prevents borrowing archived books
- Checks book availability in real-time
- Validates user permissions

### Security Features
- Password hashing using PHP's `password_hash()`
- SQL injection prevention with prepared statements
- XSS protection with `htmlspecialchars()`
- Session-based authentication
- Role-based access control

## Usage

### For Administrators
1. **Login** with admin credentials
2. **Add Books**: Use the "Add Book" feature to add new books
3. **Manage Books**: Edit book details, change status, update copies
4. **View Records**: Monitor borrowing records and user activity
5. **User Management**: Manage student accounts

### For Students
1. **Register/Login** with student account
2. **Browse Books**: Search and filter available books
3. **Borrow Books**: Borrow up to 2 books for 7 days
4. **Return Books**: Return books on time to avoid fines
5. **View Fines**: Check fine status and payment history

## Customization

### Adding New Categories
Edit the `getBookCategories()` function in `config/functions.php`:
```php
function getBookCategories() {
    return [
        'FIC' => 'Fiction',
        'NON' => 'Non-Fiction',
        // Add your categories here
    ];
}
```

### Modifying Fine Rate
Update the fine calculation in `config/functions.php`:
```php
function calculateFine($due_date, $return_date = null) {
    // Change the multiplier (currently 10.00)
    $fine_amount = $days_overdue * 10.00;
}
```

### Changing Borrowing Limits
Modify the `canUserBorrow()` function in `config/functions.php`:
```php
function canUserBorrow($user_id) {
    // Change the limit (currently 2)
    return $row['borrowed_count'] < 2;
}
```

## Troubleshooting

### Common Issues

1. **Database Connection Error**
   - Check database credentials in `config/db.php`
   - Ensure MySQL service is running
   - Verify database exists

2. **Upload Errors**
   - Check `uploads/` directory permissions
   - Verify PHP file upload settings
   - Check file size limits

3. **Session Issues**
   - Ensure session directory is writable
   - Check PHP session configuration
   - Clear browser cookies

### Error Logs
Check your web server error logs for detailed error messages.

## Support

For technical support or feature requests, please contact the development team.

## License

This project is licensed under the MIT License - see the LICENSE file for details.

---

**Tamaroar Library System** - A modern, efficient library management solution built with PHP and MySQL.
