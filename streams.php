<?php
session_start();
require 'db.php';

// 1. Create the streams table if it doesn't exist yet
$pdo->exec("CREATE TABLE IF NOT EXISTS shack_streams (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER,
    title TEXT,
    status TEXT DEFAULT 'LIVE',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
)");

// 2. If logged in user starts a stream
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['user_id'])) {
    $title = trim($_POST['title'] ?? '');
    if ($title !== '') {
        $stmt = $pdo->prepare("INSERT INTO shack_streams (user_id, title, status) VALUES (?, ?, 'LIVE')");
        $stmt->execute([$_SESSION['user_id'], $title]);
        header("Location: streams.php");
        exit;
    }
}

// 3. If host ends a stream - only host can end his own
if (isset($_GET['end']) && isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("UPDATE shack_streams SET status = 'ENDED' WHERE id = ? AND user_id = ?");
    $stmt->execute([$_GET['end'], $_SESSION['user_id']]);
    header("Location: streams.php");
    exit;
}

// 4. Get all streams, LIVE first
$streams = $pdo->query("
    SELECT s.id, s.title, s.status, s.created_at, u.username, s.user_id
    FROM shack_streams s
    JOIN shack_users u ON s.user_id = u.id
    ORDER BY s.id DESC
")->fetchAll();
?>
<!DOCTYPE html>
<html>
<head><title>SHACK Streams</title></head>
<body style="font-family:Arial; background:#111; color:white; text-align:center; padding:20px;">
<h1 style="color:#FFD700;">SHACK Live Streams</h1>

<?php if (isset($_SESSION['username'])): ?>
  <p>Live as <b><?= htmlspecialchars($_SESSION['username']) ?></b> | <a href="logout.php" style="color:#FFD700;">Logout</a></p>
  <form method="post" style="background:#222; padding:20px; display:inline-block; border-radius:10px; width:90%; max-width:500px;">
    <input name="title" placeholder="What are you streaming? e.g. SHACK Talk from Monrovia" required style="width:95%; padding:10px;">
    <br><br><button style="background:#FF0000; color:white; padding:10px 20px; border:none; font-weight:bold; width:100%;">🔴 GO LIVE</button>
  </form>
<?php else: ?>
  <p><a href="login.php" style="color:#FFD700;">Login</a> to go live.</p>
<?php endif; ?>

<hr style="border-color:#333; margin:30px 0;">

<?php foreach ($streams as $s): ?>
  <div style="background:#222; margin:10px auto; padding:15px; border-radius:10px; max-width:500px; text-align:left; border-left: 5px solid <?= $s['status'] === 'LIVE' ? '#FF0000' : '#555' ?>;">
    <b><?= htmlspecialchars($s['title']) ?></b><br>
    <small>Host: <?= htmlspecialchars($s['username']) ?> | <?= htmlspecialchars($s['created_at']) ?></small><br>
    <span style="color:<?= $s['status'] === 'LIVE' ? '#FF0000' : '#aaa' ?>; font-weight:bold;"><?= $s['status'] === 'LIVE' ? '● LIVE NOW' : '○ ENDED' ?></span>
    <?php if ($s['status'] === 'LIVE' && isset($_SESSION['user_id']) && $_SESSION['user_id'] == $s['user_id']): ?>
      <br><a href="streams.php?end=<;?= $s['id'] ?>" style="color:#FFD700;">End My Stream</a>
    <?php endif; ?>
  </div>
<?php endforeach; ?>

<?php if (empty($streams)): ?>
  <p>No streams yet. Be the first to go live!</p>
<?php endif; ?>

<br><a href="index.php" style="color:#FFD700;">Back to Home</a> | <a href="posts.php" style="color:#FFD700;">Posts</a>
</body>
</html>