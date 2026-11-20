<?php
// db.php - 1989 Shack - Global Architecture Infrastructure Hosting Platform
// One connection for posts.php, streams.php, login.php, index.php

// --- 1. GET DATABASE CREDENTIALS ---
// On Render: Set these in Dashboard > Environment Variables
// Locally: It will fall back to these defaults

$host = $_ENV['DB_HOST'] ?? getenv('DB_HOST') ?? 'localhost';
$dbname = $_ENV['DB_NAME'] ?? getenv('DB_NAME') ?? 'shack_db';
$dbuser = $_ENV['DB_USER'] ?? getenv('DB_USER') ?? 'root';
$dbpass = $_ENV['DB_PASS'] ?? getenv('DB_PASS') ?? '';
$port = $_ENV['DB_PORT'] ?? getenv('DB_PORT') ?? 3306;

// Render sometimes gives you one DATABASE_URL like:
// mysql://user:pass@host:3306/dbname
// If that exists, parse it:
if (!empty($_ENV['DATABASE_URL']) || getenv('DATABASE_URL')) {
    $url = $_ENV['DATABASE_URL'] ?? getenv('DATABASE_URL');
    $parts = parse_url($url);
    if ($parts) {
        $host = $parts['host'] ?? $host;
        $port = $parts['port'] ?? $port;
        $dbuser = $parts['user'] ?? $dbuser;
        $dbpass = $parts['pass'] ?? $dbpass;
        $dbname = ltrim($parts['path'] ?? $dbname, '/');
    }
}

try {
    $pdo = new PDO(
        "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4",
        $dbuser,
        $dbpass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
} catch (PDOException $e) {
    // Don't show password in error on live site
    die("Database connection failed. Check Render Environment Variables: DB_HOST, DB_NAME, DB_USER, DB_PASS. Error: " . $e->getMessage());
}

// --- 2. AUTO-CREATE TABLES (runs once, safe to run every time) ---
$pdo->exec("
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    avatar VARCHAR(255) DEFAULT NULL,
    bio TEXT DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");

$pdo->exec("
CREATE TABLE IF NOT EXISTS posts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    content TEXT,
    image VARCHAR(255) DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");

$pdo->exec("
CREATE TABLE IF NOT EXISTS comments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    post_id INT NOT NULL,
    user_id INT NOT NULL,
    content TEXT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");

$pdo->exec("
CREATE TABLE IF NOT EXISTS post_likes (
    post_id INT NOT NULL,
    user_id INT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (post_id, user_id),
    FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");

$pdo->exec("
CREATE TABLE IF NOT EXISTS streams (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    category ENUM('music','talk','gaming','selling') DEFAULT 'talk',
    stream_url VARCHAR(500) DEFAULT NULL,
    thumbnail VARCHAR(500) DEFAULT NULL,
    is_live TINYINT(1) DEFAULT 1,
    viewers INT DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");

$pdo->exec("
CREATE TABLE IF NOT EXISTS stream_chat (
    id INT AUTO_INCREMENT PRIMARY KEY,
    stream_id INT NOT NULL,
    user_id INT NOT NULL,
    message TEXT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (stream_id) REFERENCES streams(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");

// --- 3. HELPER: Get current user ---
function current_user() {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (!isset($_SESSION['user_id'])) return null;
    global $pdo;
    $st = $pdo->prepare("SELECT id, username, email, avatar, bio FROM users WHERE id=?");
    $st->execute([$_SESSION['user_id']]);
    return $st->fetch();
}

