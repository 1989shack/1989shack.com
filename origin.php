<?php
session_start();
require_once 'db.php';

$user_id = $_SESSION['user_id'] ?? null;
$username = $_SESSION['username'] ?? null;

// --- NEW TABLES FOR ORIGIN CORE (auto-create) ---
$pdo->exec("
CREATE TABLE IF NOT EXISTS suppliers (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    business_name VARCHAR(100) NOT NULL,
    phone VARCHAR(50),
    location VARCHAR(100),
    is_verified INTEGER DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
");

$pdo->exec("
CREATE TABLE IF NOT EXISTS origin_products (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    supplier_id INTEGER NOT NULL,
    user_id INTEGER NOT NULL,
    name VARCHAR(255) NOT NULL,
    category VARCHAR(50) DEFAULT 'general',
    price DECIMAL(10,2) NOT NULL,
    stock INTEGER DEFAULT 0,
    image VARCHAR(500),
    description TEXT,
    is_origin_core INTEGER DEFAULT 1,
    is_approved INTEGER DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (supplier_id) REFERENCES suppliers(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
");

// --- BECOME A SUPPLIER ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['business_name']) && $user_id) {
    $check = $pdo->prepare("SELECT id FROM suppliers WHERE user_id=?");
    $check->execute([$user_id]);
    if (!$check->fetch()) {
        $pdo->prepare("INSERT INTO suppliers (user_id, business_name, phone, location) VALUES (?,?,?,?)")
            ->execute([$user_id, trim($_POST['business_name']), trim($_POST['phone'] ?? ''), trim($_POST['location'] ?? '')]);
    }
    header("Location: origin.php");
    exit;
}

// --- ADD PRODUCT ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['product_name']) && $user_id) {
    $sup = $pdo->prepare("SELECT id FROM suppliers WHERE user_id=?");
    $sup->execute([$user_id]);
    $supplier = $sup->fetch();
    if ($supplier) {
        $pdo->prepare("INSERT INTO origin_products (supplier_id, user_id, name, category, price, stock, image, description) VALUES (?,?,?,?,?,?,?,?)")
            ->execute([
                $supplier['id'], $user_id,
                trim($_POST['product_name']),
                $_POST['product_category'] ?? 'general',
                (float)$_POST['price'],
                (int)$_POST['stock'],
                trim($_POST['image'] ?? ''),
                trim($_POST['description'] ?? '')
            ]);
    }
    header("Location: origin.php");
    exit;
}

// --- GET DATA ---
$filter = $_GET['cat'] ?? 'all';
$allowed = ['all','fashion','food','electronics','beauty','music','auto','general'];
if (!in_array($filter, $allowed)) $filter = 'all';

if ($filter === 'all') {
    $products = $pdo->query("
        SELECT p.*, s.business_name, u.username
        FROM origin_products p
        JOIN suppliers s ON s.id = p.supplier_id
        JOIN users u ON u.id = p.user_id
        WHERE p.is_approved = 1
        ORDER BY p.created_at DESC
    ")->fetchAll(PDO::FETCH_ASSOC);
} else {
    $st = $pdo->prepare("
        SELECT p.*, s.business_name, u.username
        FROM origin_products p
        JOIN suppliers s ON s.id = p.supplier_id
        JOIN users u ON u.id = p.user_id
        WHERE p.is_approved = 1 AND p.category = ?
        ORDER BY p.created_at DESC
    ");
    $st->execute([$filter]);
    $products = $st->fetchAll(PDO::FETCH_ASSOC);
}

$mySupplier = null;
if ($user_id) {
    $st = $pdo->prepare("SELECT * FROM suppliers WHERE user_id=?");
    $st->execute([$user_id]);
    $mySupplier = $st->fetch();
}

$totalProducts = $pdo->query("SELECT COUNT(*) as c FROM origin_products")->fetch()['c'];
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Origin Core — Supplier & Warehouse | 1989 Shack</title>
<style>
  body { background:#0f0f0f; color:#fff; font-family:Arial,sans-serif; margin:0; }
  a { color:#fff; text-decoration:none; }
  .wrap { max-width:1000px; margin:0 auto; padding:12px; }
  .topbar { background:#000; border-bottom:1px solid #333; padding:10px 12px; display:flex; justify-content:space-between; align-items:center; }
  .hero { background:linear-gradient(135deg,#ff8c00,#ff0000); padding:20px; border-radius:16px; margin:12px 0; }
  .hero h1 { margin:0; font-size:26px; }
  .hero p { color:#ffe0b3; }
  .btn { display:inline-block; background:#fff; color:#000; padding:11px 20px; border-radius:20px; font-weight:bold; border:none; cursor:pointer; }
  .btn-red { background:#ff0000; color:#fff; }
  .btn-dark { background:#272727; color:#fff; border:1px solid #444; }
  .cats { display:flex; gap:8px; flex-wrap:wrap; margin:12px 0; }
  .cats a { background:#272727; padding:8px 14px; border-radius:20px; font-size:14px; font-weight:bold; }
  .cats a.active { background:#ff8c00; color:#000; }
  .grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(240px,1fr)); gap:14px; }
  .card { background:#1f1f1f; border-radius:12px; overflow:hidden; }
  .card img { width:100%; height:180px; object-fit:cover; background:#333; }
  .card-body { padding:12px; }
  .price { color:#ff8c00; font-weight:bold; font-size:18px; }
  .badge { display:inline-block; background:#ff8c00; color:#000; padding:3px 8px; border-radius:10px; font-size:11px; font-weight:bold; }
  .stock { color:#aaa; font-size:13px; }
  input, select, textarea { width:100%; padding:11px; border-radius:10px; border:1px solid #444; background:#272727; color:#fff; margin:6px 0; }
  .box { background:#1f1f1f; padding:14px; border-radius:12px; margin:12px 0; }
</style>
</head>
<body>
<div class="topbar">
  <a href="index.php" style="font-weight:900; font-size:20px;">1989 <span style="color:#ff0000;">SHACK</span></a>
  <div>
    <a href="index.php" style="margin-right:10px;">Home</a>
    <a href="origin.php" style="color:#ff8c00; font-weight:bold;">Origin Core</a>
    <a href="streams.php" style="margin-left:10px;">Streams</a>
  </div>
</div>

<div class="wrap">
  <div class="hero">
    <h1>🌍 Origin Core — Supplier Warehouse</h1>
    <p>Made in Liberia. Made in Africa. Made in The Shack. Real products from real suppliers.</p>
    <p><b><?= $totalProducts ?> products</b> in warehouse • Verified suppliers only</p>
    <?php if ($user_id && !$mySupplier): ?>
      <a class="btn" href="#become">Become a Supplier →</a>
    <?php elseif ($user_id && $mySupplier): ?>
      <a class="btn" href="#add">+ Add Product to Warehouse</a>
    <?php else: ?>
      <a class="btn" href="login.php">Login to Supply</a>
    <?php endif; ?>
  </div>

  <!-- CATEGORIES -->
  <div class="cats">
    <a href="origin.php?cat=all" class="<?= $filter==='all'?'active':'' ?>">All</a>
    <a href="origin.php?cat=fashion" class="<?= $filter==='fashion'?'active':'' ?>">👕 Fashion</a>
    <a href="origin.php?cat=food" class="<?= $filter==='food'?'active':'' ?>">🍲 Food</a>
    <a href="origin.php?cat=electronics" class="<?= $filter==='electronics'?'active':'' ?>">📱 Electronics</a>
    <a href="origin.php?cat=beauty" class="<?= $filter==='beauty'?'active':'' ?>">💄 Beauty</a>
    <a href="origin.php?cat=music" class="<?= $filter==='music'?'active':'' ?>">🎵 Music</a>
    <a href="origin.php?cat=auto" class="<?= $filter==='auto'?'active':'' ?>">🚗 Auto</a>
    <a href="origin.php?cat=general" class="<?= $filter==='general'?'active':'' ?>">📦 General</a>
  </div>

  <!-- BECOME SUPPLIER -->
  <?php if ($user_id && !$mySupplier): ?>
  <div class="box" id="become">
    <h3>Become an Origin Core Supplier</h3>
    <form method="POST">
      <input type="text" name="business_name" placeholder="Business Name — e.g. Monrovia Fashion House" required>
      <input type="text" name="phone" placeholder="Phone / WhatsApp — e.g. +231 77 123 4567">
      <input type="text" name="location" placeholder="Location — e.g. Monrovia, Sinkor">
      <button class="btn btn-red" type="submit">Register as Supplier</button>
    </form>
    <p style="color:#aaa; font-size:13px;">After you register, you can add products. You supply, sellers sell live, you ship.</p>
  </div>
  <?php endif; ?>

  <!-- ADD PRODUCT -->
  <?php if ($user_id && $mySupplier): ?>
  <div class="box" id="add">
    <h3>+ Add Product — <?= htmlspecialchars($mySupplier['business_name']) ?></h3>
    <form method="POST">
      <input type="text" name="product_name" placeholder="Product name — e.g. Origin Core T-Shirt Black" required>
      <select name="product_category">
        <option value="fashion">👕 Fashion</option>
        <option value="food">🍲 Food</option>
        <option value="electronics">📱 Electronics</option>
        <option value="beauty">💄 Beauty</option>
        <option value="music">🎵 Music</option>
        <option value="auto">🚗 Auto</option>
        <option value="general">📦 General</option>
      </select>
      <input type="number" step="0.01" name="price" placeholder="Price in USD — e.g. 15.00" required>
      <input type="number" name="stock" placeholder="Stock — How many do you have? e.g. 50" required>
      <input type="text" name="image" placeholder="Product photo link">
      <textarea name="description" placeholder="Describe product — size, color, quality..." rows="2"></textarea>
      <button class="btn btn-red" type="submit">Add to Warehouse</button>
    </form>
  </div>
  <?php endif; ?>

  <!-- WAREHOUSE GRID -->
  <h2>📦 Warehouse — <?= ucfirst($filter) ?> (<?= count($products) ?>)</h2>

  <?php if (empty($products)): ?>
    <div style="background:#1f1f1f; padding:30px; border-radius:12px; text-align:center; color:#aaa;">
      Warehouse is empty in this category.<br>Be the first supplier to add a product!
    </div>
  <?php endif; ?>

  <div class="grid">
    <?php foreach ($products as $p): ?>
    <div class="card">
      <img src="<?= htmlspecialchars($p['image'] ?: 'https://via.placeholder.com/400x300?text=ORIGIN+CORE') ?>">
      <div class="card-body">
        <span class="badge">✅ ORIGIN CORE</span>
        <div style="font-weight:bold; margin-top:6px;"><?= htmlspecialchars($p['name']) ?></div>
        <div style="color:#aaa; font-size:13px;">by <?= htmlspecialchars($p['business_name']) ?> • <?= htmlspecialchars($p['location'] ?? '') ?></div>
        <div class="price">$<?= number_format($p['price'],2) ?></div>
        <div class="stock">Stock: <?= $p['stock'] ?> • <?= htmlspecialchars($p['category']) ?></div>
        <p style="font-size:13px; color:#ccc;"><?= htmlspecialchars(substr($p['description'] ?? '',0,80)) ?></p>
        <div style="display:flex; gap:8px; margin-top:10px;">
          <a href="streams.php" class="btn btn-red" style="font-size:13px; padding:8px 12px;">🎥 Sell This Live</a>
          <a href="index.php" class="btn btn-dark" style="font-size:13px; padding:8px 12px;">View</a>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>

  <p style="text-align:center; color:#666; font-size:12px; margin-top:30px;">
    Origin Core — The Source. Suppliers supply. Sellers sell. Shack hosts.<br>
    Next: Shack Academy (E-learning) → Then Shack Shop (E-commerce)
  </p>
</div>
</body>
</html>