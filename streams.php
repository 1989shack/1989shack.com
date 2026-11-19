<?php
session_start();
require_once 'db.php';

$user_id = $_SESSION['user_id'] ?? null;
$username = $_SESSION['username'] ?? 'Guest';

// --- FILTER ---
$allowed = ['all','music','talk','gaming','selling'];
$filter = $_GET['cat'] ?? 'all';
if (!in_array($filter, $allowed)) $filter = 'all';
$liveOnly = isset($_GET['live']);

// --- GO LIVE (create a stream) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['title']) && $user_id) {
    $title = trim($_POST['title']);
    $category = $_POST['category'] ?? 'talk';
    $stream_url = trim($_POST['stream_url'] ?? ''); // YouTube / Twitch / RTMP link for now
    $thumbnail = 'default-stream.jpg';

    if (!empty($_FILES['thumbnail']['name'])) {
        $dir = "uploads/";
        if (!is_dir($dir)) mkdir($dir, 0777, true);
        $fn = time() . "_" . basename($_FILES['thumbnail']['name']);
        if (move_uploaded_file($_FILES['thumbnail']['tmp_name'], $dir.$fn)) $thumbnail = $dir.$fn;
    }

    if ($title !== '') {
        $stmt = $pdo->prepare("INSERT INTO streams (user_id, title, category, stream_url, thumbnail, is_live, viewers, created_at) VALUES (?, ?, ?, ?, ?, 1, 0, NOW())");
        $stmt->execute([$user_id, $title, $category, $stream_url, $thumbnail]);
        header("Location: streams.php");
        exit;
    }
}

// --- END STREAM ---
if (isset($_GET['end']) && $user_id) {
    $pdo->prepare("UPDATE streams SET is_live=0 WHERE id=? AND user_id=?")->execute([(int)$_GET['end'], $user_id]);
    header("Location: streams.php");
    exit;
}

// --- CHAT: send message ---
$watch_id = isset($_GET['watch']) ? (int)$_GET['watch'] : 0;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['chat'], $_POST['stream_id']) && $user_id) {
    $stmt = $pdo->prepare("INSERT INTO stream_chat (stream_id, user_id, message, created_at) VALUES (?, ?, ?, NOW())");
    $stmt->execute([(int)$_POST['stream_id'], $user_id, trim($_POST['chat'])]);
    header("Location: streams.php?watch=" . (int)$_POST['stream_id']);
    exit;
}

// --- GET STREAMS ---
$sql = "SELECT s.*, u.username FROM streams s JOIN users u ON u.id=s.user_id WHERE 1=1";
$params = [];
if ($filter !== 'all') { $sql .= " AND s.category=?"; $params[] = $filter; }
if ($liveOnly) { $sql .= " AND s.is_live=1"; }
$sql .= " ORDER BY s.is_live DESC, s.viewers DESC, s.created_at DESC LIMIT 50";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$streams = $stmt->fetchAll(PDO::FETCH_ASSOC);

// --- GET WATCHING STREAM ---
$watching = null; $chat = [];
if ($watch_id) {
    $s = $pdo->prepare("SELECT s.*, u.username FROM streams s JOIN users u ON u.id=s.user_id WHERE s.id=?");
    $s->execute([$watch_id]);
    $watching = $s->fetch(PDO::FETCH_ASSOC);
    if ($watching) {
        // bump viewers
        $pdo->prepare("UPDATE streams SET viewers = viewers + 1 WHERE id=?")->execute([$watch_id]);
        $c = $pdo->prepare("SELECT ch.*, u.username FROM stream_chat ch JOIN users u ON u.id=ch.user_id WHERE ch.stream_id=? ORDER BY ch.created_at ASC LIMIT 100");
        $c->execute([$watch_id]);
        $chat = $c->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>The Shack - Streams</title>
<style>
  body { background:#0f0f0f; color:#fff; font-family:Arial,sans-serif; margin:0; }
  .wrap { max-width:1000px; margin:0 auto; padding:12px; }
  a { color:#fff; text-decoration:none; }
  .tabs { display:flex; gap:8px; flex-wrap:wrap; margin:12px 0; }
  .tabs a { background:#272727; padding:8px 14px; border-radius:20px; font-size:14px; font-weight:bold; }
  .tabs a.active { background:#e62117; }
  .grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(280px,1fr)); gap:14px; }
  .card { background:#1f1f1f; border-radius:12px; overflow:hidden; }
  .thumb { position:relative; width:100%; height:160px; object-fit:cover; background:#333; display:block; }
  .live { position:absolute; top:8px; left:8px; background:#e62117; padding:4px 8px; border-radius:6px; font-size:12px; font-weight:bold; }
  .offline { position:absolute; top:8px; left:8px; background:#555; padding:4px 8px; border-radius:6px; font-size:12px; }
  .viewers { position:absolute; bottom:8px; left:8px; background:rgba(0,0,0,.7); padding:4px 8px; border-radius:6px; font-size:12px; }
  .info { padding:10px; }
  .title { font-weight:bold; font-size:15px; }
  .meta { font-size:13px; color:#aaa; margin-top:4px; }
  .tag { display:inline-block; background:#333; padding:2px 8px; border-radius:10px; font-size:12px; margin-top:6px; }
  .btn { background:#e62117; color:#fff; border:none; padding:11px 20px; border-radius:20px; font-weight:bold; cursor:pointer; }
  .btn2 { background:#272727; color:#fff; border:1px solid #444; padding:8px 14px; border-radius:20px; cursor:pointer; }
  input, select { width:100%; padding:11px; border-radius:10px; border:1px solid #444; background:#1f1f1f; color:#fff; margin-bottom:8px; }
  .watch { display:grid; grid-template-columns:1fr 320px; gap:14px; }
  .chatbox { background:#1f1f1f; border-radius:12px; padding:10px; height:420px; display:flex; flex-direction:column; }
  .msgs { flex:1; overflow-y:auto; }
  .msg { margin-bottom:8px; font-size:14px; } .msg b { color:#ffcc00; }
  @media(max-width:700px){ .watch{grid-template-columns:1fr;} }
</style>
</head>
<body>
<div class="wrap">
  <h2>🔴 1989 Shack — Streams</h2>
  <p style="color:#aaa;">Music • Talk • Gaming • Selling — All in The Shack</p>

  <!-- TABS -->
  <div class="tabs">
    <a href="streams.php?cat=all" class="<?= $filter=='all'?'active':'' ?>">All</a>
    <a href="streams.php?cat=music" class="<?= $filter=='music'?'active':'' ?>">🎵 Music</a>
    <a href="streams.php?cat=talk" class="<?= $filter=='talk'?'active':'' ?>">🎙️ Talk</a>
    <a href="streams.php?cat=gaming" class="<?= $filter=='gaming'?'active':'' ?>">🎮 Gaming</a>
    <a href="streams.php?cat=selling" class="<?= $filter=='selling'?'active':'' ?>">🛍️ Selling</a>
    <a href="streams.php?cat=all&live=1">🔴 Live Now</a>
  </div>

  <!-- GO LIVE -->
  <?php if ($user_id): ?>
  <details style="background:#1f1f1f; padding:12px; border-radius:12px; margin-bottom:14px;">
    <summary style="cursor:pointer; font-weight:bold;">🎥 Go Live — Start Your Stream</summary>
    <form method="POST" enctype="multipart/form-data" style="margin-top:10px;">
      <input type="text" name="title" placeholder="Stream title — e.g. Live from Monrovia: New Music" required>
      <select name="category">
        <option value="music">🎵 Music</option>
        <option value="talk">🎙️ Talk / Podcast</option>
        <option value="gaming">🎮 Gaming</option>
        <option value="selling">🛍️ Selling</option>
      </select>
      <input type="text" name="stream_url" placeholder="Paste YouTube / Twitch link (for now)">
      <label style="font-size:13px; color:#aaa;">Thumbnail photo: <input type="file" name="thumbnail" accept="image/*"></label>
      <button class="btn" type="submit">Start Live</button>
      <p style="font-size:12px; color:#aaa;">Phase 1: We use YouTube/Twitch link to play. Phase 2: Direct live from phone inside The Shack.</p>
    </form>
  </details>
  <?php else: ?>
    <p><a href="login.php" style="color:#ffcc00; font-weight:bold;">Login to Go Live</a></p>
  <?php endif; ?>

  <!-- WATCHING -->
  <?php if ($watching): ?>
  <div class="watch" style="margin-bottom:16px;">
    <div style="background:#000; border-radius:12px; padding:10px;">
      <h3><?= htmlspecialchars($watching['title']) ?> <?= $watching['is_live'] ? '🔴 LIVE' : '(Ended)' ?></h3>
      <p style="color:#aaa;">by <?= htmlspecialchars($watching['username']) ?> • <?= htmlspecialchars($watching['category']) ?> • 👁️ <?= $watching['viewers'] ?> watching</p>
      <?php if ($watching['stream_url']): ?>
        <p>Watch link: <a href="<?= htmlspecialchars($watching['stream_url']) ?>" target="_blank" style="color:#4da6ff;">Open Stream ↗</a></p>
        <!-- If YouTube embed, you can paste embed here later -->
      <?php else: ?>
        <img src="<?= htmlspecialchars($watching['thumbnail']) ?>" style="width:100%; border-radius:10px;">
      <?php endif; ?>
      <div style="margin-top:10px;"><a class="btn2" href="streams.php">← Back to all streams</a>
      <?php if ($watching['user_id']==$user_id && $watching['is_live']): ?>
        <a class="btn2" href="streams.php?end=<;?= $watching['id'] ?>">⏹ End Stream</a>
      <?php endif; ?></div>
    </div>
    <div class="chatbox">
      <b>💬 Live Chat</b>
      <div class="msgs">
        <?php foreach ($chat as $m): ?>
          <div class="msg"><b><?= htmlspecialchars($m['username']) ?>:</b> <?= htmlspecialchars($m['message']) ?></div>
        <?php endforeach; ?>
        <?php if (empty($chat)): ?><p style="color:#aaa; font-size:13px;">No messages yet. Say hello! 👋</p><?php endif; ?>
      </div>
      <?php if ($user_id): ?>
      <form method="POST" style="display:flex; gap:6px;">
        <input type="hidden" name="stream_id" value="<?= $watching['id'] ?>">
        <input type="text" name="chat" placeholder="Say something..." required style="margin:0;">
        <button class="btn" type="submit">Send</button>
      </form>
      <?php else: ?>
        <p><a href="login.php">Login to chat</a></p>
      <?php endif; ?>
    </div>
  </div>
  <?php endif; ?>

  <!-- GRID -->
  <div class="grid">
    <?php foreach ($streams as $s): ?>
    <a class="card" href="streams.php?watch=<;?= $s['id'] ?>">
      <div style="position:relative;">
        <img class="thumb" src="<?= htmlspecialchars($s['thumbnail']) ?>">
        <span class="<?= $s['is_live'] ? 'live' : 'offline' ?>"><?= $s['is_live'] ? '● LIVE' : 'OFFLINE' ?></span>
        <span class="viewers">👁️ <?= $s['viewers'] ?></span>
      </div>
      <div class="info">
        <div class="title"><?= htmlspecialchars($s['title']) ?></div>
        <div class="meta"><?= htmlspecialchars($s['username']) ?> • <?= htmlspecialchars($s['created_at']) ?></div>
        <span class="tag">
          <?= $s['category']=='music'?'🎵 Music':($s['category']=='gaming'?'🎮 Gaming':($s['category']=='selling'?'🛍️ Selling':'🎙️ Talk')) ?>
        </span>
      </div>
    </a>
    <?php endforeach; ?>
  </div>
  <?php if (empty($streams)): ?>
    <p style="color:#aaa; margin-top:20px;">No streams in this category yet. Be the first to go live! 🎥</p>
  <?php endif; ?>

</div>
</body>
</html>