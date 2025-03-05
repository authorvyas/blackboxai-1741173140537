<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'workspace_db');
define('DB_USER', 'root');
define('DB_PASS', '');

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Create tables if they don't exist
$queries = [
    // Admin table
    "CREATE TABLE IF NOT EXISTS admin (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",

    // Apps table
    "CREATE TABLE IF NOT EXISTS apps (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        description TEXT,
        thumbnail VARCHAR(255) NOT NULL,
        link VARCHAR(255) NOT NULL,
        click_count INT DEFAULT 0,
        download_count INT DEFAULT 0,
        is_downloadable TINYINT(1) DEFAULT 0,
        tags VARCHAR(255),
        enabled TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",

    // Pages table
    "CREATE TABLE IF NOT EXISTS pages (
        id INT AUTO_INCREMENT PRIMARY KEY,
        page_name ENUM('about_us', 'contact_us') NOT NULL UNIQUE,
        content TEXT,
        last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )"
];

// Execute each query
foreach ($queries as $query) {
    try {
        $pdo->exec($query);
    } catch(PDOException $e) {
        die("Table creation failed: " . $e->getMessage());
    }
}

// Insert default admin user if not exists (username: admin, password: admin123)
try {
    $stmt = $pdo->prepare("SELECT id FROM admin LIMIT 1");
    $stmt->execute();
    if ($stmt->rowCount() == 0) {
        $default_password = password_hash('admin123', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO admin (username, password) VALUES (?, ?)");
        $stmt->execute(['admin', $default_password]);
    }
} catch(PDOException $e) {
    die("Default admin creation failed: " . $e->getMessage());
}

// Insert default page content if not exists
try {
    $stmt = $pdo->prepare("SELECT id FROM pages WHERE page_name IN ('about_us', 'contact_us')");
    $stmt->execute();
    if ($stmt->rowCount() < 2) {
        $stmt = $pdo->prepare("INSERT IGNORE INTO pages (page_name, content) VALUES 
            ('about_us', 'Welcome to About Us page. Edit this content in admin panel.'),
            ('contact_us', 'Contact Us page content. Edit this content in admin panel.')");
        $stmt->execute();
    }
} catch(PDOException $e) {
    die("Default pages creation failed: " . $e->getMessage());
}
?>
