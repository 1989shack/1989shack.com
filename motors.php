<?php
session_start();
require_once 'db.php';
$user_id = $_SESSION['user_id'] ?? null;
$username = $_SESSION['username'] ?? null;

$pdo->exec("CREATE TABLE IF NOT EXISTS motors (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    title VARCHAR(255) NOT NULL,
    type VARCHAR(50) DEFAULT 'car',
    price DECIMAL(10,2) NOT NULL,
    year VARCHAR(10),
    condition_text VARCHAR(50) DEFAULT 'used',
    image VARCHAR(500),
    location VARCHAR(100),
    phone VARCHAR(50),
    description TEXT,
    status VARCHAR(20) DEFAULT 'available',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);");

$pdo->exec("CREATE TABLE IF NOT EXISTS motor_repairs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER,
    name VARCHAR(100) NOT NULL,
    phone VARCHAR(50) NOT NULL,
    vehicle VARCHAR(100) NOT NULL,
    issue TEXT NOT NULL,
    location VARCHAR(100),
    status VARCHAR(20) DEFAULT 'pending',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);");

if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['motor_title']) && $user_id) {
    $pdo->prepare("INSERT INTO motors (user_id,title,type,price,year,condition_text,image,location,phone,description) VALUES (?,?,?,?,?,?,?,?,?,?)")
    ->execute([$user_id, trim($_POST['motor_title']), $_POST['type']??'car', (float)$_POST['price'], trim($_POST['year']??''), $_POST['condition_text']??'used', trim($_POST['image']??''), trim($_POST['location']??''), trim($_POST['phone']??''), trim($_POST['description']??'')]);
    header("Location: motors.php"); exit;
}
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['repair_vehicle'])) {
    $pdo->prepare("INSERT INTO motor_repairs (user_id,name,phone,vehicle,issue,location) VALUES (?,?,?,?,?,?)")
    ->execute([$user_id, trim($_POST['repair_name']), trim($_POST['repair_phone']), trim($_POST['repair_vehicle']), trim($_POST['repair_issue']), trim($_POST['repair_location'])]);
    header("Location: motors.php?tab=repair&booked=1"); exit;
}

$filter = $_GET['type'] ?? 'all';
$allowed=['all','car','keke','bike','truck','parts'];
if(!in_array($filter,$allowed)) $filter='all';
if($filter==='all') $motors=$pdo->query("SELECT m.*, u.username FROM motors m JOIN users u ON u.id=m.user_id ORDER BY m.created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
else { $s=$pdo->prepare("SELECT m.*, u.username FROM motors m JOIN users u ON u.id=m.user_id WHERE m.type=? ORDER BY m.created_at DESC"); $s->execute([$filter]); $motors=$s->fetchAll(PDO::FETCH_ASSOC); }

// Auto parts from Origin Core
try { $parts=$pdo->query("SELECT p.*, s.business_name FROM origin_products p JOIN suppliers s ON s.id=p.supplier_id WHERE p.category='auto' ORDER BY p.created_at DESC LIMIT 6")->fetchAll(PDO::FETCH_ASSOC); } catch(Exception $e){ $parts=[]; }
$tab = $_GET['tab'] ?? 'market';
?>
<!DOCTYPE html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Shack Motors — Cars, Keke, Parts | 1989 Shack</title>
<style>body{background:#0f0f0f;color:#fff;font-family:Arial,sans-serif;margin:0} a{color:#fff;text-decoration:none}.wrap{max-width:1000px;margin:0 auto;padding:12px}.topbar{background:#000;border-bottom:1px solid #333;padding:10px 12px;display:flex;justify-content:space-between}.hero{background:linear-gradient(135deg,#d63031,#2d3436);padding:20px;border-radius:16px;margin:12px 0}.btn{display:inline-block;background:#fff;color:#000;padding:11px 18px;border-radius:20px;font-weight:bold;border:none;cursor:pointer}.btn-red{background:#ff0000;color:#fff}.btn-dark{background:#272727;color:#fff;border:1px solid #444}.grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(240px,1fr));gap:14px}.card{background:#1f1f1f;border-radius:12px;overflow:hidden}.card img{width:100%;height:170px;object-fit:cover;background:#333}.card-body{padding:12px}.tabs{display:flex;gap:8px;margin:12px 0}.tabs a{background:#272727;padding:10px 16px;border-radius:20px;font-weight:bold}.tabs a.active{background:#ff0000} input,select,textarea{width:100%;padding:11px;border-radius:10px;border:1px solid #444;background:#272727;color:#fff;margin:6px 0}.box{background:#1f1f1f;padding:14px;border-radius:12px;margin:12px 0}</style></head>
<body><div class="topbar"><a href="index.php" style="font-weight:900;font-size:20px;">1989 <span style="color:#ff0000;">SHACK</span></a><div><a href="index.php">Home</a> <a href="shop.php" style="margin-left:10px;">Shop</a> <a href="origin.php" style="margin-left:10px;">Origin Core</a></div></div>
<div class="wrap"><div class="hero"><h1 style="margin:0;">🚗 Shack Motors — Cars Factory</h1><p>Buy. Sell. Repair. Build. From Monrovia to the World.</p><p><b>Phase 1:</b> Marketplace + Parts &nbsp; <b>Phase 2:</b> Repair Garage &nbsp; <b>Phase 3:</b> Assembly & Factory</p><?php if($user_id): ?><a class="btn" href="#sell">+ List a Vehicle</a><?php else: ?><a class="btn" href="login.php">Login to Sell</a><?php endif; ?></div>
<div class="tabs"><a href="motors.php?tab=market" class="<?= $tab==='market'?'active':'' ?>">🚗 Marketplace</a><a href="motors.php?tab=parts" class="<?= $tab==='parts'?'active':'' ?>">🔧 Parts</a><a href="motors.php?tab=repair" class="<?= $tab==='repair'?'active':'' ?>">🛠️ Repair</a><a href="motors.php?tab=factory" class="<?= $tab==='factory'?'active':'' ?>">🏭 Factory</a></div>

<?php if($tab==='repair'): ?>
  <?php if(isset($_GET['booked'])): ?><div style="background:#00b894;padding:16px;border-radius:12px;text-align:center;"><b>✅ Repair Booked!</b> Mechanic will call you to confirm.</div><?php endif; ?>
  <div class="box"><h3>🛠️ Book a Repair — Shack Garage</h3><form method="POST"><input name="repair_name" placeholder="Your Name" value="<?= htmlspecialchars($username??'') ?>" required><input name="repair_phone" placeholder="Phone / WhatsApp" required><input name="repair_vehicle" placeholder="Vehicle — e.g. Toyota Corolla 2015 / Keke" required><textarea name="repair_issue" placeholder="What's wrong? e.g. Engine noise, brakes, oil..." required rows="2"></textarea><input name="repair_location" placeholder="Location — e.g. Sinkor, Monrovia"><button class="btn btn-red" type="submit">Book Repair →</button></form><p style="color:#aaa;font-size:13px;">Your repair can be streamed LIVE in The Shack. People watch, learn, trust.</p></div>
<?php elseif($tab==='parts'): ?>
  <h2>🔧 Auto Parts — From Origin Core</h2><div class="grid"><?php foreach($parts as $p): ?><div class="card"><img src="<?= htmlspecialchars($p['image']?:'https://via.placeholder.com/400x300?text=AUTO+PART') ?>"><div class="card-body"><b><?= htmlspecialchars($p['name']) ?></b><div style="color:#aaa;font-size:13px;">by <?= htmlspecialchars($p['business_name']) ?></div><div style="color:#ff8c00;font-weight:bold;">$<?= number_format($p['price'],2) ?> • Stock: <?= $p['stock'] ?></div><a href="shop.php?add=<;?= $p['id'] ?>" class="btn btn-red" style="font-size:13px;padding:8px 12px;margin-top:8px;display:inline-block;">🛒 Add to Cart</a></div></div><?php endforeach; ?></div><?php if(empty($parts)): ?><div class="box" style="text-align:center;color:#aaa;">No auto parts yet. Suppliers: Add product with category <b>Auto</b> in <a href="origin.php" style="color:#ff8c00;">Origin Core →</a></div><?php endif; ?>
<?php elseif($tab==='factory'): ?>
  <div class="box"><h2>🏭 Shack Factory — The Vision</h2><p><b>Phase 1 (NOW):</b> Marketplace — People buy/sell cars in The Shack.</p><p><b>Phase 2 (NEXT):</b> Garage — Repair + Parts, streamed live.</p><p><b>Phase 3 (FUTURE):</b> Assembly — Import parts, assemble Keke / Bikes in Liberia with Origin Core label.</p><p><b>Phase 4 (GLOBAL):</b> Factory — Origin Core vehicles, made in Liberia.</p><p style="color:#aaa;">Every car sold today funds the factory of tomorrow. Watch it build live in The Shack.</p><a class="btn btn-red" href="academy.php">Learn Auto in Academy →</a></div>
<?php else: ?>
  <div style="display:flex;gap:8px;flex-wrap:wrap;margin:10px 0;"><?php foreach(['all'=>'All','car'=>'🚗 Cars','keke'=>'🛺 Keke','bike'=>'🏍️ Bikes','truck'=>'🚚 Trucks'] as $k=>$v): ?><a href="motors.php?type=<;?= $k ?>" style="background:<?= $filter===$k?'#ff0000':'#272727' ?>;padding:8px 14px;border-radius:20px;font-weight:bold;font-size:14px;"><?= $v ?></a><?php endforeach; ?></div>
  <?php if($user_id): ?><details class="box" id="sell"><summary style="cursor:pointer;font-weight:bold;">+ List Your Vehicle for Sale</summary><form method="POST" style="margin-top:10px;"><input name="motor_title" placeholder="Title — e.g. Toyota Corolla 2018 Clean" required><select name="type"><option value="car">🚗 Car</option><option value="keke">🛺 Keke</option><option value="bike">🏍️ Bike</option><option value="truck">🚚 Truck</option></select><input type="number" step="0.01" name="price" placeholder="Price USD" required><input name="year" placeholder="Year — e.g. 2018"><select name="condition_text"><option value="new">✨ New</option><option value="used">Used — Good</option><option value="needs_work">Needs Work</option></select><input name="image" placeholder="Photo link"><input name="phone" placeholder="Phone / WhatsApp"><input name="location" placeholder="Location — e.g. Monrovia"><textarea name="description" placeholder="Describe — mileage, condition, papers..." rows="2"></textarea><button class="btn btn-red" type="submit">List Vehicle</button></form></details><?php endif; ?>
  <h2>🚗 Marketplace (<?= count($motors) ?>)</h2><div class="grid"><?php foreach($motors as $m): ?><div class="card"><img src="<?= htmlspecialchars($m['image']?:'https://via.placeholder.com/400x300?text=SHACK+MOTORS') ?>"><div class="card-body"><span style="background:#ff0000;padding:3px 8px;border-radius:10px;font-size:11px;font-weight:bold;"><?= strtoupper($m['type']) ?> • <?= strtoupper($m['condition_text']) ?></span><div style="font-weight:bold;margin-top:6px;"><?= htmlspecialchars($m['title']) ?></div><div style="color:#aaa;font-size:13px;"><?= htmlspecialchars($m['year']) ?> • <?= htmlspecialchars($m['location']) ?> • by <?= htmlspecialchars($m['username']) ?></div><div style="color:#ff4444;font-weight:bold;font-size:18px;">$<?= number_format($m['price'],2) ?></div><a href="tel:<?= htmlspecialchars($m['phone']) ?>" class="btn btn-red" style="font-size:13px;padding:8px 12px;margin-top:8px;display:inline-block;">📞 Call Seller</a></div></div><?php endforeach; ?></div><?php if(empty($motors)): ?><div class="box" style="text-align:center;color:#aaa;">No vehicles yet. Be the first to list!</div><?php endif; ?>
<?php endif; ?>
<p style="text-align:center;color:#666;font-size:12px;margin-top:20px;">Shack Motors — Part of 1989 Shack Global Platform • Origin Core • Liberia 🇱🇷</p></div></body></html>