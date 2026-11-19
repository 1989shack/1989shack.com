<?php
// db.php - 1989 Shack - Works BOTH on localhost AND on Render

$is_local = in_array($_SERVER['HTTP_HOST'] ?? '', ['localhost:8000','localhost','127.0.0.1:8000','127.0.0.1']);

if ($is_local) {
    // ===== LOCALHOST MODE: SQLite file, no password needed =====
    $dbFile = __DIR__ . '/shack.db';
    $pdo = new PDO("sqlite:" . $dbFile);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    // Turn on foreign keys for SQLite
    $pdo->exec("PRAGMA foreign_keys = ON;");
} else {
    // ===== RENDER MODE: MySQL from Environment Variables =====
    $host = $_ENV['DB_HOST'] ?? getenv('DB_HOST') ?? 'localhost';
    $dbname = $_ENV['DB_NAME'] ?? getenv('DB_NAME') ?? 'shack_db';
    $dbuser = $_ENV['DB_USER'] ?? getenv('DB_USER') ?? 'root';
    $dbpass = $_ENV['DB_PASS'] ?? getenv('DB_PASS') ?? '';
    $port = $_ENV['DB_PORT'] ?? getenv('DB_PORT') ?? 3306;

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
            $dbuser, $dbpass,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
        );
    } catch (PDOException $e) {
        die("Database connection failed on Render. Check DATABASE_URL. Error: " . $e->getMessage());
    }
}

// ===== TABLES - Works on BOTH MySQL and SQLite =====
// We use generic SQL that works on both. No ENGINE, no ENUM for SQLite.

$pdo->exec("
CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    avatar VARCHAR(255),
    bio TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);
");

$pdo->exec("
CREATE TABLE IF NOT EXISTS posts (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    content TEXT,
    image VARCHAR(255),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
");

$pdo->exec("
CREATE TABLE IF NOT EXISTS comments (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    post_id INTEGER NOT NULL,
    user_id INTEGER NOT NULL,
    content TEXT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
");

$pdo->exec("
CREATE TABLE IF NOT EXISTS post_likes (
    post_id INTEGER NOT NULL,
    user_id INTEGER NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (post_id, user_id),
    FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
");

$pdo->exec("
CREATE TABLE IF NOT EXISTS streams (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    title VARCHAR(255) NOT NULL,
    category VARCHAR(20) DEFAULT 'talk',
    stream_url VARCHAR(500),
    thumbnail VARCHAR(500),
    is_live INTEGER DEFAULT 1,
    viewers INTEGER DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
");

$pdo->exec("
CREATE TABLE IF NOT EXISTS stream_chat (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    stream_id INTEGER NOT NULL,
    user_id INTEGER NOT NULL,
    message TEXT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (stream_id) REFERENCES streams(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
");

function current_user() {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (!isset($_SESSION['user_id'])) return null;
    global $pdo;
    $st = $pdo->prepare("SELECT id, username, email, avatar, bio FROM users WHERE id=?");
    $st->execute([$_SESSION['user_id']]);
    return $st->fetch();
}