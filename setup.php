<?php
/**
 * Tamaroar Library System Setup Script
 * Run this file to set up the database and check system requirements
 */

// Check PHP version
if (version_compare(PHP_VERSION, '7.4.0', '<')) {
    die("Error: PHP 7.4 or higher is required. Current version: " . PHP_VERSION);
}

// Check required extensions
$required_extensions = ['mysqli', 'session', 'fileinfo'];
$missing_extensions = [];

foreach ($required_extensions as $ext) {
    if (!extension_loaded($ext)) {
        $missing_extensions[] = $ext;
    }
}

if (!empty($missing_extensions)) {
    die("Error: Missing required PHP extensions: " . implode(', ', $missing_extensions));
}

// Database configuration
$host = 'localhost';
$user = 'root';
$pass = '';
$dbname = 'tamaroar_library';

echo "<h1>Tamaroar Library System Setup</h1>";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    .success { color: green; }
    .error { color: red; }
    .info { color: blue; }
    .step { margin: 20px 0; padding: 10px; border: 1px solid #ccc; }
</style>";

// Step 1: Test database connection
echo "<div class='step'>";
echo "<h2>Step 1: Database Connection Test</h2>";

try {
    $conn = new mysqli($host, $user, $pass);
    
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
    
    echo "<p class='success'>✓ Database connection successful</p>";
    
    // Check if database exists
    $result = $conn->query("SHOW DATABASES LIKE '$dbname'");
    if ($result->num_rows > 0) {
        echo "<p class='info'>⚠ Database '$dbname' already exists</p>";
        echo "<p>Do you want to recreate it? This will delete all existing data.</p>";
        echo "<form method='POST'>";
        echo "<input type='submit' name='recreate_db' value='Recreate Database'>";
        echo "</form>";
        
        if (isset($_POST['recreate_db'])) {
            $conn->query("DROP DATABASE IF EXISTS $dbname");
            echo "<p class='success'>✓ Old database dropped</p>";
        } else {
            echo "<p class='info'>Skipping database creation</p>";
            $conn->close();
            exit;
        }
    }
    
    // Create database
    if ($conn->query("CREATE DATABASE $dbname")) {
        echo "<p class='success'>✓ Database '$dbname' created successfully</p>";
    } else {
        throw new Exception("Error creating database: " . $conn->error);
    }
    
    $conn->select_db($dbname);
    
} catch (Exception $e) {
    echo "<p class='error'>✗ " . $e->getMessage() . "</p>";
    echo "<p>Please check your database configuration in config/db.php</p>";
    exit;
}

// Step 2: Create tables
echo "</div><div class='step'>";
echo "<h2>Step 2: Creating Database Tables</h2>";

$sql_file = 'library_system.sql';
if (!file_exists($sql_file)) {
    echo "<p class='error'>✗ SQL file '$sql_file' not found</p>";
    exit;
}

$sql_content = file_get_contents($sql_file);
$queries = explode(';', $sql_content);

$success_count = 0;
$error_count = 0;

foreach ($queries as $query) {
    $query = trim($query);
    if (empty($query)) continue;
    
    if ($conn->query($query)) {
        $success_count++;
    } else {
        $error_count++;
        echo "<p class='error'>✗ Query failed: " . $conn->error . "</p>";
    }
}

echo "<p class='success'>✓ $success_count queries executed successfully</p>";
if ($error_count > 0) {
    echo "<p class='error'>✗ $error_count queries failed</p>";
}

// Step 3: Check uploads directory
echo "</div><div class='step'>";
echo "<h2>Step 3: Checking Uploads Directory</h2>";

$uploads_dir = 'uploads';
if (!is_dir($uploads_dir)) {
    if (mkdir($uploads_dir, 0755, true)) {
        echo "<p class='success'>✓ Uploads directory created</p>";
    } else {
        echo "<p class='error'>✗ Failed to create uploads directory</p>";
    }
} else {
    echo "<p class='success'>✓ Uploads directory exists</p>";
}

if (is_writable($uploads_dir)) {
    echo "<p class='success'>✓ Uploads directory is writable</p>";
} else {
    echo "<p class='error'>✗ Uploads directory is not writable</p>";
    echo "<p>Please set proper permissions: chmod 755 uploads/</p>";
}

// Step 4: Test configuration
echo "</div><div class='step'>";
echo "<h2>Step 4: Testing Configuration</h2>";

// Test config files
$config_files = ['config/db.php', 'config/session.php', 'config/functions.php'];
foreach ($config_files as $file) {
    if (file_exists($file)) {
        echo "<p class='success'>✓ $file exists</p>";
    } else {
        echo "<p class='error'>✗ $file missing</p>";
    }
}

// Test database tables
$tables = ['users', 'books', 'borrowings'];
foreach ($tables as $table) {
    $result = $conn->query("SHOW TABLES LIKE '$table'");
    if ($result->num_rows > 0) {
        echo "<p class='success'>✓ Table '$table' exists</p>";
    } else {
        echo "<p class='error'>✗ Table '$table' missing</p>";
    }
}

// Check sample data
$result = $conn->query("SELECT COUNT(*) as count FROM books");
$book_count = $result->fetch_assoc()['count'];
echo "<p class='info'>📚 Sample books loaded: $book_count</p>";

$result = $conn->query("SELECT COUNT(*) as count FROM users");
$user_count = $result->fetch_assoc()['count'];
echo "<p class='info'>👥 Sample users loaded: $user_count</p>";

$conn->close();

// Step 5: Setup complete
echo "</div><div class='step'>";
echo "<h2>Step 5: Setup Complete!</h2>";
echo "<p class='success'>🎉 Tamaroar Library System has been set up successfully!</p>";

echo "<h3>Default Login Credentials:</h3>";
echo "<ul>";
echo "<li><strong>Admin:</strong> admin@tamaroar.com / password</li>";
echo "<li><strong>Student:</strong> john.doe@student.com / password</li>";
echo "<li><strong>Student:</strong> jane.smith@student.com / password</li>";
echo "<li><strong>Student:</strong> mike.johnson@student.com / password</li>";
echo "</ul>";

echo "<h3>Next Steps:</h3>";
echo "<ol>";
echo "<li>Delete this setup.php file for security</li>";
echo "<li>Access the system at: <a href='index.php'>index.php</a></li>";
echo "<li>Login with admin account to start managing books</li>";
echo "<li>Register new student accounts as needed</li>";
echo "</ol>";

echo "<p class='info'><strong>Important:</strong> For production use, please change the default passwords!</p>";
echo "</div>";

echo "<div class='step'>";
echo "<h2>System Information</h2>";
echo "<ul>";
echo "<li><strong>PHP Version:</strong> " . PHP_VERSION . "</li>";
echo "<li><strong>MySQL Version:</strong> " . mysqli_get_server_info($conn) . "</li>";
echo "<li><strong>Upload Max Size:</strong> " . ini_get('upload_max_filesize') . "</li>";
echo "<li><strong>Post Max Size:</strong> " . ini_get('post_max_size') . "</li>";
echo "<li><strong>Session Save Path:</strong> " . session_save_path() . "</li>";
echo "</ul>";
echo "</div>";
?> 