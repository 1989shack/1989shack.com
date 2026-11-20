<?php
session_start();
require_once 'db.php';
$user_id = $_SESSION['user_id'] ?? null;
$username = $_SESSION['username'] ?? null;
$is_admin = $user_id && ($user_id == 1 || strtolower($username ?? '') === 'admin');

// --- LIVE STREAMS ---
try {
  $liveStreams = $pdo->query("
    SELECT s.*, u.username FROM streams s
    JOIN users u ON u.id = s.user_id
    WHERE s.is_live = 1
    ORDER BY s.viewers DESC, s.created_at DESC
    LIMIT 4
  ")->fetchAll(PDO::FETCH_ASSOC);
} catch(Exception $e) { $liveStreams = []; }

// --- LATEST POSTS ---
try {
  $posts = $pdo->query("
    SELECT p.*, u.username,
    (SELECT COUNT(*) FROM post_likes WHERE post_id = p.id) as like_count,
    (SELECT COUNT(*) FROM comments WHERE post_id = p.id) as comment_count
    FROM posts p JOIN users u ON u.id = p.user_id
    ORDER BY p.created_at DESC LIMIT 6
  ")->fetchAll(PDO::FETCH_ASSOC);
} catch(Exception $e) { $posts = []; }

// --- ORIGIN CORE PRODUCTS ---
try {
  $originProducts = $pdo->query("
    SELECT p.*, s.business_name FROM origin_products p
    JOIN suppliers s ON s.id = p.supplier_id
    WHERE p.is_approved=1
    ORDER BY p.created_at DESC LIMIT 4
  ")->fetchAll(PDO::FETCH_ASSOC);
} catch(Exception $e) { $originProducts = []; }

// --- ACADEMY COURSES ---
try {
  $courses = $pdo->query("
    SELECT c.*, u.username as teacher,
    (SELECT COUNT(*) FROM enrollments WHERE course_id=c.id) as students
    FROM courses c JOIN users u ON u.id=c.teacher_id
    ORDER BY c.created_at DESC LIMIT 4
  ")->fetchAll(PDO::FETCH_ASSOC);
} catch(Exception $e) { $courses = []; }

// --- SHOP PRODUCTS (in stock) ---
try {
  $shopProducts = $pdo->query("
    SELECT p.*, s.business_name FROM origin_products p
    JOIN suppliers s ON s.id = p.supplier_id
    WHERE p.is_approved=1 AND p.stock > 0
    ORDER BY p.created_at DESC LIMIT 4
  ")->fetchAll(PDO::FETCH_ASSOC);
} catch(Exception $e) { $shopProducts = []; }

// --- MOTORS ---
try {
  $motors = $pdo->query("
    SELECT m.*, u.username FROM motors m
    JOIN users u ON u.id=m.user_id
    WHERE m.status='available'
    ORDER BY m.created_at DESC LIMIT 4
  ")->fetchAll(PDO::FETCH_ASSOC);
} catch(Exception $e) { $motors = []; }

// --- STATS FOR HERO ---
try { $totalOrders = $pdo->query("SELECT COUNT(*) as c FROM orders")->fetch()['c']; } catch(Exception $e){ $totalOrders = 0; }
try { $totalProducts = $pdo->query("SELECT COUNT(*) as c FROM origin_products")->fetch()['c']; } catch(Exception $e){ $totalProducts = 0; }
try { $totalUsers = $pdo->query("SELECT COUNT(*) as c FROM users")->fetch()['c']; } catch(Exception $e){ $totalUsers = 0; }

$cartCount = array_sum($_SESSION['cart'] ?? []);

function catEmoji($c) {
  return match($c) {
    'music' => '🎵 Music',
    'talk' => '🎙️ Talk',
    'gaming' => '🎮 Gaming',
    'selling' => '🛍️ Selling',
    default => '📺 Live'
  };
}
function courseLabel($c) {
  return match($c) {
    'selling' => '🛍️ Selling',
    'music' => '🎵 Music',
    'tech' => '💻 Tech',
    'business' => '💼 Business',
    'driving' => '🚗 Driving',
    'school' => '📚 School',
    default => '📚 Course'
  };
}
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>1989 Shack — Music, Talk, Gaming, Selling, Learning, Shopping, Motors</title>
<!-- SHACK APP - PWA -->
<link rel="manifest" href="manifest.json">
<meta name="theme-color" content="#ff0000">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black">
<meta name="apple-mobile-web-app-title" content="1989 Shack">
<style>
    * { box-sizing:border-box; }
  body { background:#0f0f0f; color:#fff; font-family:Arial,sans-serif; margin:0; }
  a { color:#fff; text-decoration:none; }
  .topbar { background:#000; border-bottom:1px solid #333; position:sticky; top:0; z-index:20; }
  .top-inner { max-width:1150px; margin:0 auto; padding:10px 12px; display:flex; align-items:center; justify-content:space-between; gap:10px; flex-wrap:wrap; }
  .logo { font-size:22px; font-weight:900; } .logo span { color:#ff0000; }
  .nav { display:flex; gap:6px; flex-wrap:wrap; }
  .nav a { background:#272727; padding:8px 12px; border-radius:20px; font-size:13px; font-weight:bold; }
  .nav a.active { background:#fff; color:#000; }
  .nav a.orange { background:#ff8c00; color:#000; }
  .nav a.green { background:#00b894; color:#000; }
  .nav a.purple { background:#6c5ce7; color:#fff; }
  .nav a.motors { background:#d63031; color:#fff; }
  .nav a.admin { background:#000; border:1px solid #00b894; color:#00b894; }
  .wrap { max-width:1150px; margin:0 auto; padding:12px; }
  .hero { background:linear-gradient(135deg,#ff0000,#ff8c00,#6c5ce7,#0984e3,#d63031); padding:24px; border-radius:16px; margin:12px 0; }
  .hero h1 { margin:0; font-size:30px; line-height:1.2; }
  .hero p { color:#fff; opacity:.95; margin:8px 0 14px; }
  .hero-stats { display:flex; gap:10px; flex-wrap:wrap; margin:12px 0; }
  .hero-stats span { background:rgba(0,0,0,.35); padding:8px 14px; border-radius:20px; font-size:13px; font-weight:bold; }
  .btn { display:inline-block; background:#fff; color:#000; padding:11px 18px; border-radius:20px; font-weight:bold; border:none; cursor:pointer; font-size:14px; }
  .btn-red { background:#ff0000; color:#fff; }
  .btn-dark { background:#272727; color:#fff; border:1px solid #444; }
  .btn-orange { background:#ff8c00; color:#000; }
  .btn-green { background:#00b894; color:#000; }
  .btn-purple { background:#6c5ce7; color:#fff; }
  .grid4 { display:grid; grid-template-columns:repeat(auto-fill,minmax(240px,1fr)); gap:14px; margin:12px 0; }
  .card { background:#1f1f1f; border-radius:12px; overflow:hidden; display:block; }
  .card img { width:100%; height:160px; object-fit:cover; background:#333; display:block; }
  .card-body { padding:12px; }
  .title { font-weight:bold; font-size:15px; }
  .meta { font-size:13px; color:#aaa; margin-top:4px; }
  .tag { display:inline-block; background:#333; padding:3px 8px; border-radius:10px; font-size:11px; margin-top:6px; }
  .live-badge { position:absolute; top:8px; left:8px; background:#ff0000; padding:4px 8px; border-radius:6px; font-size:12px; font-weight:bold; }
  .viewers { position:absolute; bottom:8px; left:8px; background:rgba(0,0,0,.7); padding:4px 8px; border-radius:6px; font-size:12px; }
  .thumb-wrap { position:relative; }
  .section-head { display:flex; justify-content:space-between; align-items:center; margin:24px 0 6px; }
  .section-head h2 { margin:0; font-size:20px; }
  .section-head a { font-size:14px; font-weight:bold; }
  .worlds { display:grid; grid-template-columns:repeat(auto-fill,minmax(240px,1fr)); gap:12px; margin:14px 0; }
  .world { padding:16px; border-radius:14px; }
  .world h3 { margin:0 0 6px; }
  .world p { margin:0 0 10px; font-size:13px; opacity:.95; }
  .post { background:#1f1f1f; border-radius:12px; padding:14px; margin-bottom:14px; }
  .post img.post-img { width:100%; border-radius:10px; margin-top:10px; max-height:400px; object-fit:cover; }
  .avatar { width:36px; height:36px; border-radius:50%; background:#ff0000; display:inline-flex; align-items:center; justify-content:center; font-weight:bold; vertical-align:middle; }
  input, textarea, select { width:100%; padding:11px; border-radius:10px; border:1px solid #444; background:#272727; color:#fff; margin:6px 0; }
  .post-box { background:#1f1f1f; padding:14px; border-radius:12px; margin-bottom:14px; }
  .actions { display:flex; gap:8px; margin-top:10px; flex-wrap:wrap; }
  .actions a { background:#272727; padding:8px 14px; border-radius:20px; font-size:13px; }
  .install-banner { background:#00b894; color:#000; padding:12px 16px; border-radius:12px; margin:12px 0; display:flex; justify-content:space-between; align-items:center; gap:10px; flex-wrap:wrap; }
  @media(max-width:650px){ .hero h1{ font-size:22px; } .nav a{ padding:7px 10px; font-size:12px; } }
</style>
</head>
<body>

<!-- TOP BAR - FULL PLATFORM INTEGRATION -->
<div class="topbar">
  <div class="top-inner">
    <a href="index.php" class="logo">1989 <span>SHACK</span></a>
    <div class="nav">
      <a href="index.php" class="active">Home</a>
      <a href="posts.php">Posts</a>
      <a href="streams.php">🔴 Streams</a>
      <a href="origin.php" class="orange">🌍 Origin Core</a>
      <a href="academy.php" class="green">🎓 Academy</a>
      <a href="shop.php" class="purple">🛍️ Shop <?= $cartCount ? "($cartCount)" : "" ?></a>
      <a href="motors.php" class="motors">🚗 Motors</a>
      <?php if ($is_admin): ?><a href="admin.php" class="admin">📊 Admin</a><?php endif; ?>
      <?php if ($user_id): ?>
        <a href="logout.php">Logout (<?= htmlspecialchars($username) ?>)</a>
      <?php else: ?>
        <a href="login.php">Login</a>
      <?php endif; ?>
    </div>
  </div>
</div>

<div class="wrap">

  <!-- HERO -->
  <div class="hero">
    <h1>Welcome to The Shack 🏠<br>Music • Talk • Gaming • Selling • Learning • Shopping • Motors</h1>
    <p>One login. One house. All of Liberia, all of the world. Watch live, learn a skill, buy real products, sell live, buy a car.</p>
    <div class="hero-stats">
      <span>👥 <?= $totalUsers ?> Members</span>
      <span>🌍 <?= $totalProducts ?> Products in Warehouse</span>
      <span>🛍️ <?= $totalOrders ?> Orders</span>
      <span>🔴 <?= count($liveStreams) ?> Live Now</span>
    </div>
    <?php if ($user_id): ?>
      <a class="btn" href="streams.php">🔴 Go Live Now</a>
      <a class="btn btn-dark" href="posts.php" style="margin-left:6px;">✍️ Post</a>
      <a class="btn btn-dark" href="shop.php" style="margin-left:6px;">🛍️ Shop</a>
      <?php if ($is_admin): ?><a class="btn" href="admin.php" style="margin-left:6px; background:#00b894;">📊 Admin</a><?php endif; ?>
    <?php else: ?>
      <a class="btn" href="login.php">Join The Shack — Free</a>
      <a class="btn btn-dark" href="streams.php" style="margin-left:6px;">Watch Live 👀</a>
      <a class="btn btn-dark" href="shop.php" style="margin-left:6px;">Shop Now 🛍️</a>
    <?php endif; ?>
  </div>

  <!-- INSTALL APP BANNER -->
  <div class="install-banner">
    <div><b>📱 Get Shack App</b> — Add to Home Screen for full app experience. No App Store needed.</div>
    <a href="shop.php" style="background:#000; color:#fff; padding:8px 14px; border-radius:20px; font-weight:bold; font-size:13px;">How? Chrome → Menu → Add to Home Screen</a>
  </div>

  <!-- 5 WORLDS -->
  <div class="worlds">
    <div class="world" style="background:#ff8c00;">
      <h3>🌍 Origin Core</h3>
      <p>Supplier Warehouse. Real products from Liberia. Verified. <?= $totalProducts ?> products.</p>
      <a class="btn" href="origin.php" style="font-size:13px; padding:8px 14px;">Visit Warehouse →</a>
    </div>
    <div class="world" style="background:#00b894;">
      <h3>🎓 Shack Academy</h3>
      <p>Learn to sell live, produce music, code, drive. Get Shack Certified.</p>
      <a class="btn" href="academy.php" style="font-size:13px; padding:8px 14px;">Start Learning →</a>
    </div>
    <div class="world" style="background:#6c5ce7;">
      <h3>🛍️ Shack Shop</h3>
      <p>Buy from Origin Core. Pay with Mobile Money / Orange Money / Cash.</p>
      <a class="btn" href="shop.php" style="font-size:13px; padding:8px 14px;">Shop Now →</a>
    </div>
    <div class="world" style="background:#d63031;">
      <h3>🚗 Shack Motors</h3>
      <p>Buy cars, keke, bikes. Parts + Repair + Future Factory.</p>
      <a class="btn" href="motors.php" style="font-size:13px; padding:8px 14px;">View Motors →</a>
    </div>
    <div class="world" style="background:#ff0000;">
      <h3>🔴 Live Streams</h3>
      <p>Music, Talk, Gaming, Selling — Live from The Shack.</p>
      <a class="btn" href="streams.php" style="font-size:13px; padding:8px 14px;">Watch Live →</a>
    </div>
  </div>

  <!-- LIVE NOW -->
  <div class="section-head">
    <h2>🔴 Live Now (<?= count($liveStreams) ?>)</h2>
    <a href="streams.php" style="color:#ff4444;">View All →</a>
  </div>
  <?php if (empty($liveStreams)): ?>
    <div style="background:#1f1f1f; padding:20px; border-radius:12px; text-align:center; color:#aaa;">
      No one is live right now. <?php if ($user_id): ?>Be the first! <a href="streams.php" style="color:#ff4444;"><b>Go Live →</b></a><?php else: ?><a href="login.php" style="color:#ff4444;"><b>Login to Go Live</b></a><?php endif; ?>
    </div>
  <?php endif; ?>
  <div class="grid4">
    <?php foreach ($liveStreams as $s): ?>
    <a class="card" href="streams.php?watch=<;?= $s['id'] ?>">
      <div class="thumb-wrap">
        <img src="<?= htmlspecialchars($s['thumbnail'] ?: 'https://via.placeholder.com/600x340?text=LIVE+IN+THE+SHACK') ?>">
        <span class="live-badge">● LIVE</span>
        <span class="viewers">👁️ <?= $s['viewers'] ?> watching</span>
      </div>
      <div class="card-body">
        <div class="title"><?= htmlspecialchars($s['title']) ?></div>
        <div class="meta"><?= htmlspecialchars($s['username']) ?></div>
        <span class="tag"><?= catEmoji($s['category']) ?></span>
      </div>
    </a>
    <?php endforeach; ?>
  </div>

  <!-- ORIGIN CORE PREVIEW -->
  <div class="section-head">
    <h2>🌍 Origin Core — Warehouse</h2>
    <a href="origin.php" style="color:#ff8c00;">Visit Warehouse →</a>
  </div>
  <?php if (empty($originProducts)): ?>
    <div style="background:#1f1f1f; padding:16px; border-radius:12px; color:#aaa; text-align:center;">Warehouse empty. Suppliers: <a href="origin.php" style="color:#ff8c00;"><b>Add first product →</b></a></div>
  <?php endif; ?>
  <div class="grid4">
    <?php foreach ($originProducts as $p): ?>
    <div class="card">
      <img src="<?= htmlspecialchars($p['image'] ?: 'https://via.placeholder.com/400x300?text=ORIGIN+CORE') ?>">
      <div class="card-body">
        <span style="background:#ff8c00; color:#000; padding:3px 8px; border-radius:10px; font-size:11px; font-weight:bold;">✅ ORIGIN CORE</span>
        <div class="title" style="margin-top:6px;"><?= htmlspecialchars($p['name']) ?></div>
        <div class="meta">by <?= htmlspecialchars($p['business_name']) ?> • Stock: <?= $p['stock'] ?></div>
        <div style="color:#ff8c00; font-weight:bold; margin-top:6px;">$<?= number_format($p['price'],2) ?></div>
        <div style="display:flex; gap:6px; margin-top:8px;">
          <a href="shop.php?add=<;?= $p['id'] ?>" style="background:#ff8c00; color:#000; padding:8px 10px; border-radius:20px; font-size:12px; font-weight:bold; flex:1; text-align:center;">🛒 Add to Cart</a>
          <a href="origin.php" style="background:#272727; padding:8px 10px; border-radius:20px; font-size:12px;">View</a>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>

  <!-- ACADEMY PREVIEW -->
  <div class="section-head">
    <h2>🎓 Shack Academy — Learn</h2>
    <a href="academy.php" style="color:#00b894;">All Courses →</a>
  </div>
  <?php if (empty($courses)): ?>
    <div style="background:#1f1f1f; padding:16px; border-radius:12px; color:#aaa; text-align:center;">No courses yet. Teachers: <a href="academy.php" style="color:#00b894;"><b>Create first course →</b></a></div>
  <?php endif; ?>
  <div class="grid4">
    <?php foreach ($courses as $c): ?>
    <a class="card" href="academy.php?course=<;?= $c['id'] ?>">
      <img src="<?= htmlspecialchars($c['image'] ?: 'https://via.placeholder.com/600x340?text=SHACK+ACADEMY') ?>">
      <div class="card-body">
        <span style="background:#00b894; color:#000; padding:3px 8px; border-radius:10px; font-size:11px; font-weight:bold;"><?= courseLabel($c['category']) ?></span>
        <div class="title" style="margin-top:6px;"><?= htmlspecialchars($c['title']) ?></div>
        <div class="meta">Teacher: <?= htmlspecialchars($c['teacher']) ?> • <?= $c['students'] ?> students</div>
        <div style="margin-top:6px;"><b style="color:#00b894;"><?= $c['price']==0 ? 'FREE' : '$'.number_format($c['price'],2) ?></b> • <span style="color:#00b894; font-weight:bold; font-size:13px;">Learn →</span></div>
      </div>
    </a>
    <?php endforeach; ?>
  </div>

  <!-- SHOP PREVIEW -->
  <div class="section-head">
    <h2>🛍️ Shack Shop — Buy Now</h2>
    <a href="shop.php" style="color:#a29bfe;">Visit Shop →</a>
  </div>
  <?php if (empty($shopProducts)): ?>
    <div style="background:#1f1f1f; padding:16px; border-radius:12px; color:#aaa; text-align:center;">Shop empty — Add products in Origin Core first.</div>
  <?php endif; ?>
  <div class="grid4">
    <?php foreach ($shopProducts as $p): ?>
    <div class="card">
      <img src="<?= htmlspecialchars($p['image'] ?: 'https://via.placeholder.com/400x300?text=SHACK+SHOP') ?>">
      <div class="card-body">
        <span style="background:#6c5ce7; color:#fff; padding:3px 8px; border-radius:10px; font-size:11px; font-weight:bold;">🛍️ SHACK SHOP</span>
        <div class="title" style="margin-top:6px;"><?= htmlspecialchars($p['name']) ?></div>
        <div class="meta">by <?= htmlspecialchars($p['business_name']) ?> • Stock: <?= $p['stock'] ?></div>
        <div style="color:#00cec9; font-weight:bold; margin-top:6px;">$<?= number_format($p['price'],2) ?></div>
        <div style="display:flex; gap:6px; margin-top:8px;">
          <a href="shop.php?add=<;?= $p['id'] ?>" style="background:#6c5ce7; color:#fff; padding:8px 10px; border-radius:20px; font-size:12px; font-weight:bold; flex:1; text-align:center;">🛒 Add to Cart</a>
          <a href="shop.php?view=cart" style="background:#272727; padding:8px 10px; border-radius:20px; font-size:12px;">Cart</a>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>

  <!-- MOTORS PREVIEW -->
  <div class="section-head">
    <h2>🚗 Shack Motors — Cars Factory</h2>
    <a href="motors.php" style="color:#ff6b6b;">View Motors →</a>
  </div>
  <?php if (empty($motors)): ?>
    <div style="background:#1f1f1f; padding:16px; border-radius:12px; color:#aaa; text-align:center;">No vehicles yet. <a href="motors.php" style="color:#ff6b6b;"><b>List first vehicle →</b></a> • Book repair • Buy parts</div>
  <?php endif; ?>
  <div class="grid4">
    <?php foreach ($motors as $m): ?>
    <a class="card" href="motors.php">
      <img src="<?= htmlspecialchars($m['image'] ?: 'https://via.placeholder.com/400x300?text=SHACK+MOTORS') ?>">
      <div class="card-body">
        <span style="background:#d63031; color:#fff; padding:3px 8px; border-radius:10px; font-size:11px; font-weight:bold;"><?= strtoupper($m['type']) ?> • <?= strtoupper($m['condition_text']) ?></span>
        <div class="title" style="margin-top:6px;"><?= htmlspecialchars($m['title']) ?></div>
        <div class="meta"><?= htmlspecialchars($m['year']) ?> • <?= htmlspecialchars($m['location']) ?> • by <?= htmlspecialchars($m['username']) ?></div>
        <div style="color:#ff6b6b; font-weight:bold; margin-top:6px;">$<?= number_format($m['price'],2) ?></div>
        <div style="margin-top:6px;"><span style="color:#ff6b6b; font-weight:bold; font-size:13px;">View →</span></div>
      </div>
    </a>
    <?php endforeach; ?>
  </div>

  <!-- QUICK POST -->
  <?php if ($user_id): ?>
  <div class="section-head"><h2>🏠 Community</h2><a href="posts.php">All Posts →</a></div>
  <div class="post-box">
    <form action="posts.php" method="POST" enctype="multipart/form-data">
      <div style="display:flex; gap:10px; align-items:center;">
        <span class="avatar"><?= strtoupper(substr($username,0,1)) ?></span>
        <b>What's happening, <?= htmlspecialchars($username) ?>?</b>
      </div>
      <textarea name="content" placeholder="Share music, talk, gaming, selling..." required rows="2"></textarea>
      <input type="file" name="image" accept="image/*">
      <button class="btn btn-red" type="submit">Post to Shack</button>
      <a class="btn btn-dark" href="streams.php" style="margin-left:6px;">🎥 Go Live</a>
    </form>
  </div>
  <?php endif; ?>

  <!-- COMMUNITY FEED -->
  <?php if (empty($posts)): ?>
    <div style="background:#1f1f1f; padding:30px; border-radius:12px; text-align:center; color:#aaa;">No posts yet. Be the first to post in The Shack! 🏠</div>
  <?php endif; ?>
  <?php foreach ($posts as $p): ?>
  <div class="post">
    <div><span class="avatar"><?= strtoupper(substr($p['username'],0,1)) ?></span> <b style="margin-left:6px;"><?= htmlspecialchars($p['username']) ?></b> <span style="color:#aaa; font-size:12px;">• <?= htmlspecialchars($p['created_at']) ?></span></div>
    <p style="margin:10px 0; line-height:1.5; white-space:pre-wrap;"><?= htmlspecialchars($p['content']) ?></p>
    <?php if ($p['image']): ?><img class="post-img" src="<?= htmlspecialchars($p['image']) ?>"><?php endif; ?>
    <div class="actions"><a href="posts.php">❤️ <?= $p['like_count'] ?> Likes</a><a href="posts.php">💬 <?= $p['comment_count'] ?> Comments</a><a href="posts.php">View Post →</a></div>
  </div>
  <?php endforeach; ?>

  <div style="text-align:center; margin:20px 0;"><a class="btn btn-dark" href="posts.php">Load More Posts →</a></div>

  <div style="background:#1f1f1f; padding:18px; border-radius:12px; text-align:center; margin:20px 0;">
    <h3 style="margin:0;">The Loop: Supplier → Academy → Shop → Live → Motors</h3>
    <p style="color:#aaa; font-size:13px; margin:8px 0 0;">1. Supplier adds product in Origin Core 🌍 &nbsp; 2. Student learns in Academy 🎓 &nbsp; 3. Buyer buys in Shop 🛍️ &nbsp; 4. Seller sells LIVE in Streams 🔴 &nbsp; 5. Driver buys car in Motors 🚗</p>
    <?php if ($is_admin): ?><a class="btn" href="admin.php" style="margin-top:12px; background:#00b894;">📊 Open Admin Command Center →</a><?php endif; ?>
  </div>

  <p style="text-align:center; color:#666; font-size:12px; margin-top:20px;">
    1989 Shack — Global Architecture Infrastructure Hosting Platform<br>
    Built in Monrovia, Liberia 🇱🇷 • For the World 🌍<br>
    Origin Core • Academy • Shop • Streams • Posts • Motors • Admin • App
  </p>

</div>
</body>
</html>