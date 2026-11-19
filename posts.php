<?php
session_start();
require_once 'db.php';

// Must be logged in to post, but anyone can read
$user_id = $_SESSION['user_id'] ?? null;
$username = $_SESSION['username'] ?? 'Guest';

// --- CREATE POST ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['content']) && $user_id) {
    $content = trim($_POST['content']);
    $image = null;

    // Simple photo upload
    if (!empty($_FILES['photo']['name'])) {
        $targetDir = "uploads/";
        if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);
        $fileName = time() . "_" . basename($_FILES['photo']['name']);
        $target = $targetDir . $fileName;
        if (move_uploaded_file($_FILES['photo']['tmp_name'], $target)) {
            $image = $target;
        }
    }

    if ($content !== '' || $image) {
        $stmt = $pdo->prepare("INSERT INTO posts (user_id, content, image, created_at) VALUES (?, ?, ?, NOW())");
        $stmt->execute([$user_id, $content, $image]);
        header("Location: posts.php");
        exit;
    }
}

// --- LIKE ---
if (isset($_GET['like']) && $user_id) {
    $post_id = (int)$_GET['like'];
    $pdo->prepare("INSERT IGNORE INTO post_likes (post_id, user_id) VALUES (?, ?)")->execute([$post_id, $user_id]);
    header("Location: posts.php");
    exit;
}

// --- COMMENT ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['comment'], $_POST['post_id']) && $user_id) {
    $stmt = $pdo->prepare("INSERT INTO comments (post_id, user_id, content, created_at) VALUES (?, ?, ?, NOW())");
    $stmt->execute([(int)$_POST['post_id'], $user_id, trim($_POST['comment'])]);
    header("Location: posts.php");
    exit;
}

// --- GET POSTS ---
$posts = $pdo->query("
    SELECT p.*, u.username, u.avatar,
    (SELECT COUNT(*) FROM post_likes l WHERE l.post_id = p.id) as like_count,
    (SELECT COUNT(*) FROM comments c WHERE c.post_id = p.id) as comment_count
    FROM posts p
    JOIN users u ON u.id = p.user_id
    ORDER BY p.created_at DESC
    LIMIT 50
")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>The Shack - Posts</title>
<style>
  body { background:#f0f2f5; font-family: Arial, sans-serif; margin:0; }
  .wrap { max-width:600px; margin:0 auto; padding:10px; }
  .card { background:#fff; border-radius:12px; padding:14px; margin-bottom:12px; box-shadow:0 1px 3px rgba(0,0,0,.1); }
  .top { display:flex; gap:10px; align-items:center; }
  .avatar { width:42px; height:42px; border-radius:50%; object-fit:cover; background:#ddd; }
  .name { font-weight:bold; } .time { font-size:12px; color:#65676b; }
  textarea { width:100%; border:none; outline:none; resize:none; font-size:16px; min-height:60px; }
  .post-text { font-size:16px; line-height:1.5; white-space:pre-wrap; margin:10px 0; }
  .post-img { width:100%; border-radius:10px; margin-top:8px; }
  .actions { display:flex; justify-content:space-around; border-top:1px solid #eee; margin-top:10px; padding-top:8px; }
  .actions a, .actions button { text-decoration:none; color:#65676b; font-weight:bold; background:none; border:none; cursor:pointer; font-size:14px; }
  .comment-box { display:flex; gap:8px; margin-top:10px; }
  .comment-box input { flex:1; border-radius:20px; border:1px solid #ddd; padding:8px 12px; }
  .btn { background:#1877f2; color:#fff; border:none; padding:10px 18px; border-radius:20px; font-weight:bold; cursor:pointer; }
  .comment { display:flex; gap:8px; margin-top:8px; font-size:14px; }
  .comment-bubble { background:#f0f2f5; padding:8px 12px; border-radius:16px; }
</style>
</head>
<body>
<div class="wrap">

  <h2>🏠 1989 Shack — Community</h2>

  <!-- CREATE POST -->
  <?php if ($user_id): ?>
  <div class="card">
    <form method="POST" enctype="multipart/form-data">
      <div class="top">
        <div class="avatar"></div>
        <strong><?= htmlspecialchars($username) ?></strong>
      </div>
      <textarea name="content" placeholder="What's happening in The Shack, <?= htmlspecialchars($username) ?>?"></textarea>
      <div style="display:flex; justify-content:space-between; align-items:center;">
        <label style="cursor:pointer; color:#1877f2; font-weight:bold;">📷 Photo
          <input type="file" name="photo" accept="image/*" style="display:none;">
        </label>
        <button class="btn" type="submit">Post</button>
      </div>
    </form>
  </div>
  <?php else: ?>
    <div class="card">Please <a href="login.php">login</a> to post in The Shack.</div>
  <?php endif; ?>

  <!-- FEED -->
  <?php foreach ($posts as $p): ?>
  <div class="card">
    <div class="top">
      <img class="avatar" src="<?= htmlspecialchars($p['avatar'] ?: 'default.png') ?>">
      <div>
        <div class="name"><?= htmlspecialchars($p['username']) ?></div>
        <div class="time"><?= htmlspecialchars($p['created_at']) ?></div>
      </div>
    </div>

    <div class="post-text"><?= nl2br(htmlspecialchars($p['content'])) ?></div>
    <?php if ($p['image']): ?>
      <img class="post-img" src="<?= htmlspecialchars($p['image']) ?>">
    <?php endif; ?>

    <div style="font-size:13px; color:#65676b; margin-top:6px;">
      ❤️ <?= $p['like_count'] ?> Likes • 💬 <?= $p['comment_count'] ?> Comments
    </div>

    <div class="actions">
      <a href="posts.php?like=<;?= $p['id'] ?>">👍 Like</a>
      <a href="#c-<?= $p['id'] ?>">💬 Comment</a>
      <a href="#">↗️ Share</a>
    </div>

    <!-- COMMENTS -->
    <?php
      $cs = $pdo->prepare("SELECT c.*, u.username FROM comments c JOIN users u ON u.id=c.user_id WHERE c.post_id=? ORDER BY c.created_at ASC");
      $cs->execute([$p['id']]);
      foreach ($cs->fetchAll(PDO::FETCH_ASSOC) as $c):
    ?>
      <div class="comment">
        <div class="comment-bubble"><b><?= htmlspecialchars($c['username']) ?></b> <?= htmlspecialchars($c['content']) ?></div>
      </div>
    <?php endforeach; ?>

    <?php if ($user_id): ?>
    <form class="comment-box" id="c-<?= $p['id'] ?>" method="POST">
      <input type="hidden" name="post_id" value="<?= $p['id'] ?>">
      <input type="text" name="comment" placeholder="Write a comment..." required>
      <button class="btn" type="submit">Send</button>
    </form>
    <?php endif; ?>
  </div>
  <?php endforeach; ?>

</div>
</body>
</html>