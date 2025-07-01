-- Tamaroar Library System Database
-- Complete database structure with all required tables

-- Drop existing database if exists and create new one
DROP DATABASE IF EXISTS tamaroar_library;
CREATE DATABASE tamaroar_library;
USE tamaroar_library;

-- Users table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    role ENUM('admin', 'student') NOT NULL DEFAULT 'student',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Books table
CREATE TABLE books (
    id INT AUTO_INCREMENT PRIMARY KEY,
    book_id VARCHAR(50) NOT NULL UNIQUE,
    title VARCHAR(255) NOT NULL,
    author VARCHAR(255) NOT NULL,
    category VARCHAR(50) NOT NULL,
    published_year INT NOT NULL,
    published_month INT NOT NULL,
    published_day INT NOT NULL,
    copies INT NOT NULL DEFAULT 1,
    available_copies INT NOT NULL DEFAULT 1,
    cover_image VARCHAR(255),
    status ENUM('active', 'archived') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Borrowings table
CREATE TABLE borrowings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    book_id INT NOT NULL,
    borrow_date DATE NOT NULL,
    due_date DATE NOT NULL,
    return_date DATE NULL,
    fine_amount DECIMAL(10,2) DEFAULT 0.00,
    status ENUM('borrowed', 'returned', 'overdue') NOT NULL DEFAULT 'borrowed',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (book_id) REFERENCES books(id) ON DELETE CASCADE
);

-- Insert default admin user
INSERT INTO users (username, password, full_name, email, role) VALUES 
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Library Administrator', 'admin@tamaroar.com', 'admin');

-- Insert sample books (minimum 50 books as required)
INSERT INTO books (book_id, title, author, category, published_year, published_month, published_day, copies, available_copies) VALUES
('THJAN102024-FIC00001', 'The Hobbit', 'J.R.R. Tolkien', 'FIC', 1937, 9, 21, 3, 3),
('LOTJAN102024-FIC00002', 'The Lord of the Rings', 'J.R.R. Tolkien', 'FIC', 1954, 7, 29, 2, 2),
('HARJAN102024-FIC00003', 'Harry Potter and the Philosopher''s Stone', 'J.K. Rowling', 'FIC', 1997, 6, 26, 4, 4),
('HARJAN102024-FIC00004', 'Harry Potter and the Chamber of Secrets', 'J.K. Rowling', 'FIC', 1998, 7, 2, 3, 3),
('HARJAN102024-FIC00005', 'Harry Potter and the Prisoner of Azkaban', 'J.K. Rowling', 'FIC', 1999, 7, 8, 3, 3),
('HARJAN102024-FIC00006', 'Harry Potter and the Goblet of Fire', 'J.K. Rowling', 'FIC', 2000, 7, 8, 3, 3),
('HARJAN102024-FIC00007', 'Harry Potter and the Order of the Phoenix', 'J.K. Rowling', 'FIC', 2003, 6, 21, 3, 3),
('HARJAN102024-FIC00008', 'Harry Potter and the Half-Blood Prince', 'J.K. Rowling', 'FIC', 2005, 7, 16, 3, 3),
('HARJAN102024-FIC00009', 'Harry Potter and the Deathly Hallows', 'J.K. Rowling', 'FIC', 2007, 7, 21, 3, 3),
('GAMJAN102024-FIC00010', 'The Hunger Games', 'Suzanne Collins', 'FIC', 2008, 9, 14, 4, 4),
('CACJAN102024-FIC00011', 'Catching Fire', 'Suzanne Collins', 'FIC', 2009, 9, 1, 3, 3),
('MOCJAN102024-FIC00012', 'Mockingjay', 'Suzanne Collins', 'FIC', 2010, 8, 24, 3, 3),
('TWIJAN102024-FIC00013', 'Twilight', 'Stephenie Meyer', 'FIC', 2005, 10, 5, 4, 4),
('NEWJAN102024-FIC00014', 'New Moon', 'Stephenie Meyer', 'FIC', 2006, 9, 6, 3, 3),
('ECLJAN102024-FIC00015', 'Eclipse', 'Stephenie Meyer', 'FIC', 2007, 8, 7, 3, 3),
('BREJAN102024-FIC00016', 'Breaking Dawn', 'Stephenie Meyer', 'FIC', 2008, 8, 2, 3, 3),
('PRJAN102024-FIC00017', 'Pride and Prejudice', 'Jane Austen', 'FIC', 1813, 1, 28, 2, 2),
('SENJAN102024-FIC00018', 'Sense and Sensibility', 'Jane Austen', 'FIC', 1811, 10, 30, 2, 2),
('EMMJAN102024-FIC00019', 'Emma', 'Jane Austen', 'FIC', 1815, 12, 23, 2, 2),
('MANJAN102024-FIC00020', 'Mansfield Park', 'Jane Austen', 'FIC', 1814, 7, 4, 2, 2),
('PERJAN102024-FIC00021', 'Persuasion', 'Jane Austen', 'FIC', 1817, 12, 20, 2, 2),
('NORJAN102024-FIC00022', 'Northanger Abbey', 'Jane Austen', 'FIC', 1817, 12, 20, 2, 2),
('GREJAN102024-FIC00023', 'Great Expectations', 'Charles Dickens', 'FIC', 1861, 8, 1, 2, 2),
('OLJAN102024-FIC00024', 'Oliver Twist', 'Charles Dickens', 'FIC', 1838, 1, 1, 2, 2),
('DAVJAN102024-FIC00025', 'David Copperfield', 'Charles Dickens', 'FIC', 1850, 5, 1, 2, 2),
('ATJAN102024-FIC00026', 'A Tale of Two Cities', 'Charles Dickens', 'FIC', 1859, 4, 30, 2, 2),
('NICJAN102024-FIC00027', 'Nicholas Nickleby', 'Charles Dickens', 'FIC', 1839, 3, 31, 2, 2),
('BLEJAN102024-FIC00028', 'Bleak House', 'Charles Dickens', 'FIC', 1853, 3, 1, 2, 2),
('HARJAN102024-FIC00029', 'Hard Times', 'Charles Dickens', 'FIC', 1854, 4, 1, 2, 2),
('LITJAN102024-FIC00030', 'Little Dorrit', 'Charles Dickens', 'FIC', 1857, 12, 1, 2, 2),
('MUTJAN102024-FIC00031', 'Mutual Friend', 'Charles Dickens', 'FIC', 1865, 5, 1, 2, 2),
('EDWJAN102024-FIC00032', 'Edwin Drood', 'Charles Dickens', 'FIC', 1870, 4, 1, 2, 2),
('WUTJAN102024-FIC00033', 'Wuthering Heights', 'Emily Brontë', 'FIC', 1847, 12, 1, 2, 2),
('JANJAN102024-FIC00034', 'Jane Eyre', 'Charlotte Brontë', 'FIC', 1847, 10, 16, 2, 2),
('VILJAN102024-FIC00035', 'Villette', 'Charlotte Brontë', 'FIC', 1853, 1, 1, 2, 2),
('SHIJAN102024-FIC00036', 'Shirley', 'Charlotte Brontë', 'FIC', 1849, 10, 26, 2, 2),
('PROJAN102024-FIC00037', 'The Professor', 'Charlotte Brontë', 'FIC', 1857, 6, 1, 2, 2),
('TENJAN102024-FIC00038', 'Tenant of Wildfell Hall', 'Anne Brontë', 'FIC', 1848, 6, 1, 2, 2),
('AGNJAN102024-FIC00039', 'Agnes Grey', 'Anne Brontë', 'FIC', 1847, 12, 1, 2, 2),
('MADJAN102024-FIC00040', 'Madame Bovary', 'Gustave Flaubert', 'FIC', 1857, 1, 1, 2, 2),
('LESJAN102024-FIC00041', 'Les Misérables', 'Victor Hugo', 'FIC', 1862, 1, 1, 2, 2),
('NOTJAN102024-FIC00042', 'Notre-Dame de Paris', 'Victor Hugo', 'FIC', 1831, 1, 14, 2, 2),
('COUJAN102024-FIC00043', 'The Count of Monte Cristo', 'Alexandre Dumas', 'FIC', 1844, 8, 28, 2, 2),
('THRJAN102024-FIC00044', 'The Three Musketeers', 'Alexandre Dumas', 'FIC', 1844, 3, 14, 2, 2),
('MANJAN102024-FIC00045', 'Man in the Iron Mask', 'Alexandre Dumas', 'FIC', 1850, 1, 1, 2, 2),
('CRIJAN102024-FIC00046', 'Crime and Punishment', 'Fyodor Dostoevsky', 'FIC', 1866, 1, 1, 2, 2),
('IDJAN102024-FIC00047', 'The Idiot', 'Fyodor Dostoevsky', 'FIC', 1869, 1, 1, 2, 2),
('DEMJAN102024-FIC00048', 'Demons', 'Fyodor Dostoevsky', 'FIC', 1872, 1, 1, 2, 2),
('BROJAN102024-FIC00049', 'The Brothers Karamazov', 'Fyodor Dostoevsky', 'FIC', 1880, 1, 1, 2, 2),
('WARJAN102024-FIC00050', 'War and Peace', 'Leo Tolstoy', 'FIC', 1869, 1, 1, 2, 2);

-- Insert sample student users
INSERT INTO users (username, password, full_name, email, role) VALUES 
('student1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'John Doe', 'john.doe@student.com', 'student'),
('student2', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Jane Smith', 'jane.smith@student.com', 'student'),
('student3', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Mike Johnson', 'mike.johnson@student.com', 'student');

-- Create indexes for better performance
CREATE INDEX idx_books_status ON books(status);
CREATE INDEX idx_books_category ON books(category);
CREATE INDEX idx_borrowings_user_id ON borrowings(user_id);
CREATE INDEX idx_borrowings_book_id ON borrowings(book_id);
CREATE INDEX idx_borrowings_status ON borrowings(status);
CREATE INDEX idx_borrowings_due_date ON borrowings(due_date); 