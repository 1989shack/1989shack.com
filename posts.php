<?php
session_start();
require 'db.php';

// 1. If user tries to post, check if logged in first
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit;
    }

    $content = trim($_POST['content'] ?? '');
    if ($content !== '') {
        $stmt = $pdo->prepare("INSERT INTO shack_posts (user_id, content) VALUES (?, ?)");
        $stmt->execute([$_SESSION['user_id'], $content]);
        header("Location: posts.php");
        exit;
    }
}

// 2. Get all posts, newest first, with username
$stmt = $pdo->query("
    SELECT p.content, p.created_at, u.username 
    FROM shack_posts p 
    JOIN shack_users u ON p.user_id = u.id 
    ORDER BY p.id DESC
");
$posts = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html>
<head><title>SHACK Posts</title></head>
<body style="font-family:Arial; background:#111; color:white; text-align:center; padding:20px;">
<h1 style="color:#FFD700;">SHACK Feed</h1>

<?php if (isset($_SESSION['username'])): ?>
  <p>Posting as <b><?= htmlspecialchars($_SESSION['username']) ?></b> | <a href="logout.php" style="color:#FFD700;">Logout</a></p>
  <form method="post" style="background:#222; padding:20px; display:inline-block; border-radius:10px; width:90%; max-width:500px;">
    <textarea name="content" placeholder="What's happening in SHACK?" required style="width:95%; height:70px; padding:10px;"></textarea><br><br>
    <button style="background:#FFD700; padding:10px 20px; border:none; font-weight:bold; width:100%;">Post to SHACK</button>
  </form>
<?php else: ?>
  <p>You need to <a href="login.php" style="color:#FFD700;">Login</a> to post. You can still read below.</p>
<?php endif; ?>

<hr style="border-color:#333; margin:30px 0;">

<h2>Recent Posts</h2>
<?php foreach ($posts as $post): ?>
  <div style="background:#222; margin:10px auto; padding:15px; border-radius:10px; max-width:500px; text-align:left;">
    <b style="color:#FFD700;"><?= htmlspecialchars($post['username']) ?></b>
    <small style="color:#aaa;"> - <?= htmlspecialchars($post['created_at']) ?></small>
    <p style="margin-top:10px;"><?= htmlspecialchars($post['content']) ?></p>
  </div>
<?php endforeach; ?>

<?php if (empty($posts)): ?>
  <p>No posts yet. Be the first!</p>
<?php endif; ?>

<br><a href="index.php" style="color:#FFD700;">Back to Home</a>
</body>
</html>