<?php
session_start();
require_once 'db.php';

$user_id = $_SESSION['user_id'] ?? null;
$username = $_SESSION['username'] ?? null;

// --- TABLES ---
$pdo->exec("
CREATE TABLE IF NOT EXISTS orders (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    buyer_id INTEGER,
    buyer_name VARCHAR(100) NOT NULL,
    phone VARCHAR(50) NOT NULL,
    location VARCHAR(150) NOT NULL,
    payment_method VARCHAR(50) DEFAULT 'cash',
    total DECIMAL(10,2) DEFAULT 0,
    status VARCHAR(20) DEFAULT 'pending',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (buyer_id) REFERENCES users(id) ON DELETE SET NULL
);
");

$pdo->exec("
CREATE TABLE IF NOT EXISTS order_items (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    order_id INTEGER NOT NULL,
    product_id INTEGER NOT NULL,
    product_name VARCHAR(255) NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    quantity INTEGER DEFAULT 1,
    supplier_id INTEGER,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
);
");

if (!isset($_SESSION['cart'])) $_SESSION['cart'] = []; // [product_id => qty]

// --- ADD TO CART ---
if (isset($_GET['add'])) {
    $pid = (int)$_GET['add'];
    $_SESSION['cart'][$pid] = ($_SESSION['cart'][$pid] ?? 0) + 1;
    header("Location: shop.php");
    exit;
}

// --- REMOVE FROM CART ---
if (isset($_GET['remove'])) {
    unset($_SESSION['cart'][(int)$_GET['remove']]);
    header("Location: shop.php?view=cart");
    exit;
}

// --- CLEAR CART ---
if (isset($_GET['clear'])) {
    $_SESSION['cart'] = [];
    header("Location: shop.php");
    exit;
}

// --- CHECKOUT ---
$orderSuccess = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['checkout'])) {
    $buyerName = trim($_POST['buyer_name'] ?? $username ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $pay = $_POST['payment_method'] ?? 'cash';
    $total = 0;
    $items = [];
    foreach ($_SESSION['cart'] as $pid => $qty) {
        $st = $pdo->prepare("SELECT * FROM origin_products WHERE id=?");
        $st->execute([$pid]);
        $p = $st->fetch();
        if ($p) {
            $total += $p['price'] * $qty;
            $items[] = ['product'=>$p, 'qty'=>$qty];
        }
    }
    if (!empty($items) && $buyerName && $phone && $location) {
        $pdo->prepare("INSERT INTO orders (buyer_id, buyer_name, phone, location, payment_method, total, status) VALUES (?,?,?,?,?,?,?)")
            ->execute([$user_id, $buyerName, $phone, $location, $pay, $total, 'pending']);
        $orderId = $pdo->lastInsertId();
        foreach ($items as $it) {
            $pdo->prepare("INSERT INTO order_items (order_id, product_id, product_name, price, quantity, supplier_id) VALUES (?,?,?,?,?,?)")
                ->execute([$orderId, $it['product']['id'], $it['product']['name'], $it['product']['price'], $it['qty'], $it['product']['supplier_id']]);
            // Reduce stock
            $pdo->prepare("UPDATE origin_products SET stock = MAX(0, stock - ?) WHERE id=?")->execute([$it['qty'], $it['product']['id']]);
        }
        $_SESSION['cart'] = [];
        $orderSuccess = $orderId;
    }
}

// --- GET PRODUCTS (from Origin Core warehouse) ---
$filter = $_GET['cat'] ?? 'all';
$allowed = ['all','fashion','food','electronics','beauty','music','auto','general'];
if (!in_array($filter, $allowed)) $filter = 'all';

if ($filter === 'all') {
    $products = $pdo->query("
        SELECT p.*, s.business_name FROM origin_products p
        JOIN suppliers s ON s.id = p.supplier_id
        WHERE p.is_approved=1 AND p.stock > 0
        ORDER BY p.created_at DESC
    ")->fetchAll(PDO::FETCH_ASSOC);
} else {
    $st = $pdo->prepare("
        SELECT p.*, s.business_name FROM origin_products p
        JOIN suppliers s ON s.id = p.supplier_id
        WHERE p.is_approved=1 AND p.stock > 0 AND p.category=?
        ORDER BY p.created_at DESC
    ");
    $st->execute([$filter]);
    $products = $st->fetchAll(PDO::FETCH_ASSOC);
}

// --- CART DETAILS ---
$cartItems = [];
$cartTotal = 0;
foreach ($_SESSION['cart'] as $pid => $qty) {
    $st = $pdo->prepare("SELECT p.*, s.business_name FROM origin_products p JOIN suppliers s ON s.id=p.supplier_id WHERE p.id=?");
    $st->execute([$pid]);
    $p = $st->fetch();
    if ($p) {
        $cartItems[] = ['product'=>$p, 'qty'=>$qty, 'subtotal'=>$p['price']*$qty];
        $cartTotal += $p['price']*$qty;
    }
}

// --- MY ORDERS ---
$myOrders = [];
if ($user_id) {
    $st = $pdo->prepare("SELECT * FROM orders WHERE buyer_id=? ORDER BY created_at DESC LIMIT 10");
    $st->execute([$user_id]);
    $myOrders = $st->fetchAll(PDO::FETCH_ASSOC);
}

$view = $_GET['view'] ?? 'shop';
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Shack Shop — Buy from Origin Core | 1989 Shack</title>
<style>
  body { background:#0f0f0f; color:#fff; font-family:Arial,sans-serif; margin:0; }
  a { color:#fff; text-decoration:none; }
  .wrap { max-width:1000px; margin:0 auto; padding:12px; }
  .topbar { background:#000; border-bottom:1px solid #333; padding:10px 12px; display:flex; justify-content:space-between; align-items:center; }
  .hero { background:linear-gradient(135deg,#6c5ce7,#00cec9); padding:20px; border-radius:16px; margin:12px 0; }
  .hero h1 { margin:0; font-size:26px; }
  .btn { display:inline-block; background:#fff; color:#000; padding:11px 20px; border-radius:20px; font-weight:bold; border:none; cursor:pointer; }
  .btn-purple { background:#6c5ce7; color:#fff; }
  .btn-dark { background:#272727; color:#fff; border:1px solid #444; }
  .cats { display:flex; gap:8px; flex-wrap:wrap; margin:12px 0; }
  .cats a { background:#272727; padding:8px 14px; border-radius:20px; font-size:14px; font-weight:bold; }
  .cats a.active { background:#6c5ce7; color:#fff; }
  .grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(240px,1fr)); gap:14px; }
  .card { background:#1f1f1f; border-radius:12px; overflow:hidden; }
  .card img { width:100%; height:180px; object-fit:cover; background:#333; }
  .card-body { padding:12px; }
  .price { color:#00cec9; font-weight:bold; font-size:18px; }
  .badge { display:inline-block; background:#6c5ce7; color:#fff; padding:3px 8px; border-radius:10px; font-size:11px; font-weight:bold; }
  input, select, textarea { width:100%; padding:11px; border-radius:10px; border:1px solid #444; background:#272727; color:#fff; margin:6px 0; }
  .box { background:#1f1f1f; padding:14px; border-radius:12px; margin:12px 0; }
  .cart-row { display:flex; justify-content:space-between; align-items:center; gap:10px; padding:10px; background:#272727; border-radius:10px; margin-bottom:8px; }
  .success { background:#00b894; color:#fff; padding:20px; border-radius:12px; text-align:center; margin:12px 0; }
</style>
</head>
<body>
<div class="topbar">
  <a href="index.php" style="font-weight:900; font-size:20px;">1989 <span style="color:#ff0000;">SHACK</span></a>
  <div>
    <a href="index.php" style="margin-right:10px;">Home</a>
    <a href="origin.php" style="margin-right:10px;">Origin Core</a>
    <a href="academy.php" style="margin-right:10px;">Academy</a>
    <a href="shop.php?view=cart" style="background:#6c5ce7; padding:8px 14px; border-radius:20px; font-weight:bold;">🛒 Cart (<?= array_sum($_SESSION['cart']) ?>) — $<?= number_format($cartTotal,2) ?></a>
  </div>
</div>

<div class="wrap">
  <div class="hero">
    <h1>🛍️ Shack Shop — Buy Real. Buy Origin.</h1>
    <p>Every product here comes from Origin Core warehouse. Supplied in Liberia. Sold in The Shack. Delivered to you.</p>
    <a class="btn" href="shop.php">Shop Now ↓</a>
    <a class="btn btn-dark" href="origin.php" style="margin-left:6px;">Become a Supplier</a>
  </div>

  <?php if ($orderSuccess): ?>
    <div class="success">
      <h2>✅ Order Placed! Order #<?= $orderSuccess ?></h2>
      <p>Thank you! Supplier will contact you on your phone to confirm delivery.</p>
      <p style="font-size:13px;">Pay with Mobile Money / Orange Money / Cash on Delivery.</p>
      <a href="shop.php" class="btn" style="margin-top:10px;">Continue Shopping</a>
    </div>
  <?php endif; ?>

  <?php if ($view === 'cart'): ?>
    <!-- CART VIEW -->
    <h2>🛒 Your Cart</h2>
    <a href="shop.php" class="btn btn-dark" style="font-size:13px;">← Back to Shop</a>
    <?php if (empty($cartItems)): ?>
      <div class="box" style="text-align:center; color:#aaa;">Cart is empty. Go shop!<br><br><a href="shop.php" class="btn btn-purple">Shop Products →</a></div>
    <?php else: ?>
      <?php foreach ($cartItems as $it): ?>
      <div class="cart-row">
        <div><b><?= htmlspecialchars($it['product']['name']) ?></b><br><span style="color:#aaa; font-size:13px;"><?= htmlspecialchars($it['product']['business_name']) ?> • $<?= number_format($it['product']['price'],2) ?> x <?= $it['qty'] ?></span></div>
        <div style="text-align:right;"><b>$<?= number_format($it['subtotal'],2) ?></b><br><a href="shop.php?view=cart&remove=<;?= $it['product']['id'] ?>" style="color:#ff4444; font-size:13px;">Remove ✕</a></div>
      </div>
      <?php endforeach; ?>
      <div style="display:flex; justify-content:space-between; font-size:18px; font-weight:bold; margin:12px 0;"><span>Total:</span><span>$<?= number_format($cartTotal,2) ?></span></div>
      <a href="shop.php?clear=1" style="color:#ff4444; font-size:13px;">Clear cart</a>

      <div class="box">
        <h3>Checkout — Delivery Info</h3>
        <form method="POST">
          <input type="text" name="buyer_name" placeholder="Full Name" value="<?= htmlspecialchars($username ?? '') ?>" required>
          <input type="text" name="phone" placeholder="Phone / WhatsApp — e.g. +231 77 123 4567" required>
          <input type="text" name="location" placeholder="Delivery Location — e.g. Sinkor, Monrovia" required>
          <label style="font-size:13px; color:#aaa;">Payment Method</label>
          <select name="payment_method">
            <option value="cash">💵 Cash on Delivery</option>
            <option value="orange">🟠 Orange Money</option>
            <option value="mtn">🟡 MTN MoMo / Mobile Money</option>
            <option value="bank">🏦 Bank Transfer</option>
          </select>
          <button class="btn btn-purple" type="submit" name="checkout" style="width:100%; margin-top:10px;">Place Order — $<?= number_format($cartTotal,2) ?> →</button>
          <p style="color:#aaa; font-size:12px; text-align:center;">Supplier will call you to confirm. No payment taken online yet — pay on delivery or via Mobile Money.</p>
        </form>
      </div>
    <?php endif; ?>

    <!-- MY ORDERS -->
    <?php if (!empty($myOrders)): ?>
      <h3>📦 My Orders</h3>
      <?php foreach ($myOrders as $o): ?>
        <div class="box" style="display:flex; justify-content:space-between;">
          <div><b>Order #<?= $o['id'] ?></b> • <?= $o['location'] ?><br><span style="color:#aaa; font-size:13px;"><?= $o['created_at'] ?> • <?= ucfirst($o['payment_method']) ?></span></div>
          <div style="text-align:right;"><b>$<?= number_format($o['total'],2) ?></b><br><span style="background:#ff8c00; padding:3px 8px; border-radius:10px; font-size:11px;"><?= strtoupper($o['status']) ?></span></div>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>

  <?php else: ?>
    <!-- SHOP VIEW -->
    <div class="cats">
      <a href="shop.php?cat=all" class="<?= $filter==='all'?'active':'' ?>">All</a>
      <a href="shop.php?cat=fashion" class="<?= $filter==='fashion'?'active':'' ?>">👕 Fashion</a>
      <a href="shop.php?cat=food" class="<?= $filter==='food'?'active':'' ?>">🍲 Food</a>
      <a href="shop.php?cat=electronics" class="<?= $filter==='electronics'?'active':'' ?>">📱 Electronics</a>
      <a href="shop.php?cat=beauty" class="<?= $filter==='beauty'?'active':'' ?>">💄 Beauty</a>
      <a href="shop.php?cat=music" class="<?= $filter==='music'?'active':'' ?>">🎵 Music</a>
      <a href="shop.php?cat=auto" class="<?= $filter==='auto'?'active':'' ?>">🚗 Auto</a>
    </div>

    <h2>🛍️ Shop — <?= ucfirst($filter) ?> (<?= count($products) ?>)</h2>
    <?php if (empty($products)): ?>
      <div style="background:#1f1f1f; padding:30px; border-radius:12px; text-align:center; color:#aaa;">
        No products in stock yet.<br>Suppliers: Add products in <a href="origin.php" style="color:#6c5ce7;"><b>Origin Core →</b></a><br>They will appear here automatically.
      </div>
    <?php endif; ?>

    <div class="grid">
      <?php foreach ($products as $p): ?>
      <div class="card">
        <img src="<?= htmlspecialchars($p['image'] ?: 'https://via.placeholder.com/400x300?text=SHACK+SHOP') ?>">
        <div class="card-body">
          <span class="badge">✅ ORIGIN CORE</span>
          <div style="font-weight:bold; margin-top:6px;"><?= htmlspecialchars($p['name']) ?></div>
          <div style="color:#aaa; font-size:13px;">by <?= htmlspecialchars($p['business_name']) ?> • Stock: <?= $p['stock'] ?></div>
          <div class="price">$<?= number_format($p['price'],2) ?></div>
          <div style="display:flex; gap:8px; margin-top:10px;">
            <a href="shop.php?add=<;?= $p['id'] ?>" class="btn btn-purple" style="font-size:13px; padding:8px 12px; flex:1; text-align:center;">🛒 Add to Cart</a>
            <a href="streams.php" class="btn btn-dark" style="font-size:13px; padding:8px 12px;">🎥 Sell Live</a>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <p style="text-align:center; color:#666; font-size:12px; margin-top:30px;">
    Shack Shop — Powered by Origin Core.<br>
    Supplier → Academy → Shop → Live Selling. One Platform. One Loop.
  </p>
</div>
</body>
</html>