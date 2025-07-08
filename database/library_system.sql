-- Enhanced Tamaroar Library System Database
-- Complete database structure with all required tables and improvements

-- Drop existing database if exists and create new one
DROP DATABASE IF EXISTS tamaroar_library;
CREATE DATABASE tamaroar_library;
USE tamaroar_library;

-- Users table (enhanced)
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    role ENUM('admin', 'student') NOT NULL DEFAULT 'student',
    status ENUM('active', 'suspended', 'inactive') NOT NULL DEFAULT 'active',
    phone VARCHAR(20),
    address TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Add user status fields (from add_user_status.sql)
ALTER TABLE users ADD COLUMN suspension_reason TEXT NULL;
ALTER TABLE users ADD COLUMN suspended_at TIMESTAMP NULL;
-- Update existing users to be active
UPDATE users SET status = 'active' WHERE status IS NULL;

-- Categories table (new)
CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Books table (enhanced)
CREATE TABLE books (
    id INT AUTO_INCREMENT PRIMARY KEY,
    book_id VARCHAR(50) NOT NULL UNIQUE,
    title VARCHAR(255) NOT NULL,
    author VARCHAR(255) NOT NULL,
    category_id INT,
    isbn VARCHAR(20),
    published_date DATE NOT NULL,
    description TEXT,
    copies INT NOT NULL DEFAULT 1,
    available_copies INT NOT NULL DEFAULT 1,
    cover_image VARCHAR(255),
    status ENUM('available', 'borrowed', 'archived') NOT NULL DEFAULT 'available',
    location VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
);

-- Borrowings table (enhanced)
CREATE TABLE borrowings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    book_id INT NOT NULL,
    borrowed_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    due_date TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    returned_date TIMESTAMP NULL,
    fine_amount DECIMAL(10,2) DEFAULT 0.00,
    fine_paid BOOLEAN DEFAULT FALSE,
    status ENUM('borrowed', 'returned', 'overdue') NOT NULL DEFAULT 'borrowed',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (book_id) REFERENCES books(id) ON DELETE CASCADE
);

-- Fines table (new)
CREATE TABLE fines (
    id INT AUTO_INCREMENT PRIMARY KEY,
    borrowing_id INT NOT NULL,
    user_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    reason ENUM('overdue', 'damage', 'lost') NOT NULL,
    status ENUM('pending', 'paid', 'waived') NOT NULL DEFAULT 'pending',
    paid_date TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (borrowing_id) REFERENCES borrowings(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Notifications table (new)
CREATE TABLE notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    type ENUM('info', 'warning', 'success', 'error') NOT NULL DEFAULT 'info',
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- System settings table (new)
CREATE TABLE system_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT NOT NULL,
    description TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Activity log table (from create_activity_log_table.sql)
CREATE TABLE IF NOT EXISTS activity_log (
  id int(11) NOT NULL AUTO_INCREMENT,
  user_id int(11) NOT NULL,
  activity_type varchar(50) NOT NULL,
  description text NOT NULL,
  created_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY user_id (user_id),
  KEY activity_type (activity_type),
  KEY created_at (created_at),
  CONSTRAINT activity_log_ibfk_1 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert some sample activity types
INSERT INTO activity_log (user_id, activity_type, description, created_at) VALUES
(1, 'system_init', 'Activity log table created', NOW());

-- Insert default categories FIRST (before books to satisfy foreign key)
INSERT INTO categories (name, description) VALUES
('Fiction', 'Fictional literature and novels'),
('Non-Fiction', 'Non-fictional books and reference materials'),
('Science Fiction', 'Science fiction and fantasy books'),
('Mystery', 'Mystery and thriller books'),
('Romance', 'Romance novels'),
('Biography', 'Biographies and autobiographies'),
('History', 'Historical books and documents'),
('Science', 'Scientific books and research'),
('Technology', 'Technology and computer books'),
('Philosophy', 'Philosophy and religion books');

-- Insert default admin user
INSERT INTO users (username, password, full_name, email, role) VALUES 
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Library Administrator', 'admin@tamaroar.com', 'admin');

-- Insert sample student users
INSERT INTO users (username, password, full_name, email, role) VALUES 
('student1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'John Doe', 'john.doe@student.com', 'student'),
('student2', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Jane Smith', 'jane.smith@student.com', 'student'),
('student3', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Mike Johnson', 'mike.johnson@student.com', 'student');

-- Insert sample books (enhanced with categories) - NOW categories exist
INSERT INTO books (book_id, title, author, category_id, isbn, published_date, copies, available_copies) VALUES
('THJAN102024-FIC00001', 'The Hobbit', 'J.R.R. Tolkien', 3, '9780547928241', '1937-09-21', 3, 3),
('LOTJAN102024-FIC00002', 'The Lord of the Rings', 'J.R.R. Tolkien', 3, '9780547928210', '1954-07-29', 2, 2),
('HARJAN102024-FIC00003', 'Harry Potter and the Philosopher''s Stone', 'J.K. Rowling', 3, '9780439708180', '1997-06-26', 4, 4),
('HARJAN102024-FIC00004', 'Harry Potter and the Chamber of Secrets', 'J.K. Rowling', 3, '9780439064873', '1998-07-02', 3, 3),
('HARJAN102024-FIC00005', 'Harry Potter and the Prisoner of Azkaban', 'J.K. Rowling', 3, '9780439136365', '1999-07-08', 3, 3),
('HARJAN102024-FIC00006', 'Harry Potter and the Goblet of Fire', 'J.K. Rowling', 3, '9780439139601', '2000-07-08', 3, 3),
('HARJAN102024-FIC00007', 'Harry Potter and the Order of the Phoenix', 'J.K. Rowling', 3, '9780439358071', '2003-06-21', 3, 3),
('HARJAN102024-FIC00008', 'Harry Potter and the Half-Blood Prince', 'J.K. Rowling', 3, '9780439785969', '2005-07-16', 3, 3),
('HARJAN102024-FIC00009', 'Harry Potter and the Deathly Hallows', 'J.K. Rowling', 3, '9780545010221', '2007-07-21', 3, 3),
('GAMJAN102024-FIC00010', 'The Hunger Games', 'Suzanne Collins', 3, '9780439023481', '2008-09-14', 4, 4),
('CACJAN102024-FIC00011', 'Catching Fire', 'Suzanne Collins', 3, '9780439023498', '2009-09-01', 3, 3),
('MOCJAN102024-FIC00012', 'Mockingjay', 'Suzanne Collins', 3, '9780439023511', '2010-08-24', 3, 3),
('TWIJAN102024-FIC00013', 'Twilight', 'Stephenie Meyer', 5, '9780316015844', '2005-10-05', 4, 4),
('NEWJAN102024-FIC00014', 'New Moon', 'Stephenie Meyer', 5, '9780316024969', '2006-09-06', 3, 3),
('ECLJAN102024-FIC00015', 'Eclipse', 'Stephenie Meyer', 5, '9780316160209', '2007-08-07', 3, 3),
('BREJAN102024-FIC00016', 'Breaking Dawn', 'Stephenie Meyer', 5, '9780316067928', '2008-08-02', 3, 3),
('PRJAN102024-FIC00017', 'Pride and Prejudice', 'Jane Austen', 5, '9780141439518', '1813-01-28', 2, 2),
('SENJAN102024-FIC00018', 'Sense and Sensibility', 'Jane Austen', 5, '9780141439662', '1811-10-30', 2, 2),
('EMMJAN102024-FIC00019', 'Emma', 'Jane Austen', 5, '9780141439587', '1815-12-23', 2, 2),
('MANJAN102024-FIC00020', 'Mansfield Park', 'Jane Austen', 5, '9780141439808', '1814-07-04', 2, 2),
('PERJAN102024-FIC00021', 'Persuasion', 'Jane Austen', 5, '9780141439686', '1817-12-20', 2, 2),
('NORJAN102024-FIC00022', 'Northanger Abbey', 'Jane Austen', 5, '9780141439792', '1817-12-20', 2, 2),
('GREJAN102024-FIC00023', 'Great Expectations', 'Charles Dickens', 1, '9780141439563', '1861-08-01', 2, 2),
('OLJAN102024-FIC00024', 'Oliver Twist', 'Charles Dickens', 1, '9780141439747', '1838-01-01', 2, 2),
('DAVJAN102024-FIC00025', 'David Copperfield', 'Charles Dickens', 1, '9780141439167', '1850-05-01', 2, 2),
('ATJAN102024-FIC00026', 'A Tale of Two Cities', 'Charles Dickens', 1, '9780141439600', '1859-04-30', 2, 2),
('NICJAN102024-FIC00027', 'Nicholas Nickleby', 'Charles Dickens', 1, '9780141439722', '1839-03-31', 2, 2),
('BLEJAN102024-FIC00028', 'Bleak House', 'Charles Dickens', 1, '9780141439723', '1853-03-01', 2, 2),
('HARJAN102024-FIC00029', 'Hard Times', 'Charles Dickens', 1, '9780141439724', '1854-04-01', 2, 2),
('LITJAN102024-FIC00030', 'Little Dorrit', 'Charles Dickens', 1, '9780141439725', '1857-12-01', 2, 2),
('MUTJAN102024-FIC00031', 'Mutual Friend', 'Charles Dickens', 1, '9780141439726', '1865-05-01', 2, 2),
('EDWJAN102024-FIC00032', 'Edwin Drood', 'Charles Dickens', 1, '9780141439727', '1870-04-01', 2, 2),
('WUTJAN102024-FIC00033', 'Wuthering Heights', 'Emily Brontë', 5, '9780141439728', '1847-12-01', 2, 2),
('JANJAN102024-FIC00034', 'Jane Eyre', 'Charlotte Brontë', 5, '9780141439729', '1847-10-16', 2, 2),
('VILJAN102024-FIC00035', 'Villette', 'Charlotte Brontë', 5, '9780141439730', '1853-01-01', 2, 2),
('SHIJAN102024-FIC00036', 'Shirley', 'Charlotte Brontë', 5, '9780141439731', '1849-10-26', 2, 2),
('PROJAN102024-FIC00037', 'The Professor', 'Charlotte Brontë', 5, '9780141439732', '1857-06-01', 2, 2),
('TENJAN102024-FIC00038', 'Tenant of Wildfell Hall', 'Anne Brontë', 5, '9780141439733', '1848-06-01', 2, 2),
('AGNJAN102024-FIC00039', 'Agnes Grey', 'Anne Brontë', 5, '9780141439734', '1847-12-01', 2, 2),
('MADJAN102024-FIC00040', 'Madame Bovary', 'Gustave Flaubert', 1, '9780141439735', '1857-01-01', 2, 2),
('LESJAN102024-FIC00041', 'Les Misérables', 'Victor Hugo', 1, '9780141439736', '1862-01-01', 2, 2),
('NOTJAN102024-FIC00042', 'Notre-Dame de Paris', 'Victor Hugo', 1, '9780141439737', '1831-01-14', 2, 2),
('COUJAN102024-FIC00043', 'The Count of Monte Cristo', 'Alexandre Dumas', 1, '9780141439738', '1844-08-28', 2, 2),
('THRJAN102024-FIC00044', 'The Three Musketeers', 'Alexandre Dumas', 1, '9780141439739', '1844-03-14', 2, 2),
('MANJAN102024-FIC00045', 'Man in the Iron Mask', 'Alexandre Dumas', 1, '9780141439740', '1850-01-01', 2, 2),
('CRIJAN102024-FIC00046', 'Crime and Punishment', 'Fyodor Dostoevsky', 1, '9780141439741', '1866-01-01', 2, 2),
('IDJAN102024-FIC00047', 'The Idiot', 'Fyodor Dostoevsky', 1, '9780141439742', '1869-01-01', 2, 2),
('DEMJAN102024-FIC00048', 'Demons', 'Fyodor Dostoevsky', 1, '9780141439743', '1872-01-01', 2, 2),
('BROJAN102024-FIC00049', 'The Brothers Karamazov', 'Fyodor Dostoevsky', 1, '9780141439744', '1880-01-01', 2, 2),
('WARJAN102024-FIC00050', 'War and Peace', 'Leo Tolstoy', 1, '9780141439745', '1869-01-01', 2, 2);

-- Insert system settings
INSERT INTO system_settings (setting_key, setting_value, description) VALUES
('max_books_per_student', '2', 'Maximum number of books a student can borrow'),
('borrowing_days', '7', 'Number of days books can be borrowed'),
('fine_per_day', '10', 'Fine amount per day for overdue books'),
('fine_escalation_days', '7', 'Days after which fine rate increases'),
('fine_escalation_rate', '15', 'Increased fine rate after escalation period'),
('max_fine_escalation_days', '14', 'Days after which fine rate increases again'),
('max_fine_rate', '20', 'Maximum fine rate per day'),
('library_name', 'Tamaroar Library System', 'Name of the library system'),
('library_address', '123 Library Ave, Tamaroar City, 12345', 'Library address'),
('library_phone', '(123) 456-7890', 'Library phone number'),
('library_email', 'info@tamaroarlibrary.edu', 'Library email address');

-- Create indexes for better performance
CREATE INDEX idx_books_status ON books(status);
CREATE INDEX idx_books_category_id ON books(category_id);
CREATE INDEX idx_books_available_copies ON books(available_copies);
CREATE INDEX idx_books_title ON books(title);
CREATE INDEX idx_books_author ON books(author);
CREATE INDEX idx_books_isbn ON books(isbn);
CREATE INDEX idx_books_book_id ON books(book_id);

CREATE INDEX idx_borrowings_user_id ON borrowings(user_id);
CREATE INDEX idx_borrowings_book_id ON borrowings(book_id);
CREATE INDEX idx_borrowings_status ON borrowings(status);
CREATE INDEX idx_borrowings_due_date ON borrowings(due_date);
CREATE INDEX idx_borrowings_borrowed_date ON borrowings(borrowed_date);

CREATE INDEX idx_users_email ON users(email);
CREATE INDEX idx_users_username ON users(username);
CREATE INDEX idx_users_role ON users(role);
CREATE INDEX idx_users_status ON users(status);

CREATE INDEX idx_fines_user_id ON fines(user_id);
CREATE INDEX idx_fines_borrowing_id ON fines(borrowing_id);
CREATE INDEX idx_fines_status ON fines(status);

CREATE INDEX idx_notifications_user_id ON notifications(user_id);
CREATE INDEX idx_notifications_is_read ON notifications(is_read); 
CREATE INDEX idx_notifications_type ON notifications(type);

CREATE INDEX idx_categories_name ON categories(name); 