<?php
session_start();
require_once 'db.php';
$user_id = $_SESSION['user_id'] ?? null;
$username = $_SESSION['username'] ?? null;
// Simple admin check: first user OR username admin
if (!$user_id || !($user_id==1 || strtolower($username)==='admin')) {
  die('<div style="background:#0f0f0f;color:#fff;font-family:Arial;padding:30px;text-align:center;"><h1>⛔ Admin Only</h1><p>Login as admin account to see dashboard.</p><a href="login.php" style="color:#ff4444;">Login →</a></div>');
}
function countTable($pdo,$t){ try{ return $pdo->query("SELECT COUNT(*) as c FROM $t")->fetch()['c']; }catch(Exception $e){ return 0; } }
$users=countTable($pdo,'users'); $posts=countTable($pdo,'posts'); $streams=countTable($pdo,'streams');
$products=countTable($pdo,'origin_products'); $courses=countTable($pdo,'courses'); $orders=countTable($pdo,'orders'); $motors=countTable($pdo,'motors');
try{ $revenue=$pdo->query("SELECT COALESCE(SUM(total),0) as s FROM orders")->fetch()['s']; }catch(Exception $e){ $revenue=0; }
try{ $live=$pdo->query("SELECT COUNT(*) as c FROM streams WHERE is_live=1")->fetch()['c']; }catch(Exception $e){ $live=0; }
try{ $latestOrders=$pdo->query("SELECT * FROM orders ORDER BY created_at DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC); }catch(Exception $e){ $latestOrders=[]; }
try{ $latestUsers=$pdo->query("SELECT * FROM users ORDER BY created_at DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC); }catch(Exception $e){ $latestUsers=[]; }
try{ $latestProducts=$pdo->query("SELECT * FROM origin_products ORDER BY created_at DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC); }catch(Exception $e){ $latestProducts=[]; }
?>
<!DOCTYPE html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Admin — 1989 Shack</title>
<style>body{background:#0f0f0f;color:#fff;font-family:Arial;margin:0} a{color:#fff;text-decoration:none}.wrap{max-width:1100px;margin:0 auto;padding:12px}.grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:12px}.stat{background:#1f1f1f;padding:16px;border-radius:12px}.stat b{font-size:28px}.box{background:#1f1f1f;padding:14px;border-radius:12px;margin:12px 0}.row{display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid #333;font-size:14px}</style></head>
<body><div style="background:#000;padding:12px;display:flex;justify-content:space-between;"><b>1989 SHACK — ADMIN</b><span>Welcome, <?= htmlspecialchars($username) ?> • <a href="index.php" style="color:#ff4444;">Back to Shack →</a></span></div>
<div class="wrap"><h1>📊 Shack Command Center</h1><p style="color:#aaa;">One view. All businesses. All money.</p>
<div class="grid">
<div class="stat">👥 Users<br><b><?= $users ?></b></div><div class="stat">🏠 Posts<br><b><?= $posts ?></b></div><div class="stat">🔴 Live Now<br><b style="color:#ff4444;"><?= $live ?></b> / <?= $streams ?> streams</div><div class="stat">🌍 Products<br><b style="color:#ff8c00;"><?= $products ?></b></div><div class="stat">🎓 Courses<br><b style="color:#00b894;"><?= $courses ?></b></div><div class="stat">🛍️ Orders<br><b style="color:#6c5ce7;"><?= $orders ?></b></div><div class="stat">💰 Total Sales<br><b style="color:#00b894;">$<?= number_format($revenue,2) ?></b></div><div class="stat">🚗 Motors<br><b style="color:#ff4444;"><?= $motors ?></b></div>
</div>
<div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">@media
<div class="box"><h3>🛍️ Latest Orders</h3><?php foreach($latestOrders as $o): ?><div class="row"><span>#<?= $o['id'] ?> • <?= htmlspecialchars($o['buyer_name']) ?> • <?= htmlspecialchars($o['location']) ?></span><b>$<?= number_format($o['total'],2) ?> • <?= strtoupper($o['status']) ?></b></div><?php endforeach; ?><?php if(empty($latestOrders)): ?><p style="color:#aaa;">No orders yet.</p><?php endif; ?><a href="shop.php" style="color:#6c5ce7;">View Shop →</a></div>
<div class="box"><h3>👥 New Users</h3><?php foreach($latestUsers as $u): ?><div class="row"><span><?= htmlspecialchars($u['username']) ?></span><span style="color:#aaa;"><?= $u['created_at'] ?? '' ?></span></div><?php endforeach; ?><a href="index.php" style="color:#ff4444;">View Shack →</a></div>
</div>
<div class="box"><h3>🌍 Latest Products (Origin Core)</h3><?php foreach($latestProducts as $p): ?><div class="row"><span><?= htmlspecialchars($p['name']) ?> • $<?= number_format($p['price'],2) ?></span><span>Stock: <?= $p['stock'] ?></span></div><?php endforeach; ?><?php if(empty($latestProducts)): ?><p style="color:#aaa;">No products yet.</p><?php endif; ?></div>
<div class="box"><h3>Quick Links</h3><p><a href="origin.php" style="color:#ff8c00;">🌍 Origin Core</a> • <a href="academy.php" style="color:#00b894;">🎓 Academy</a> • <a href="shop.php" style="color:#a29bfe;">🛍️ Shop</a> • <a href="streams.php" style="color:#ff4444;">🔴 Streams</a> • <a href="motors.php" style="color:#ff4444;">🚗 Motors</a></p></div>
<p style="text-align:center;color:#666;font-size:12px;">Admin = User ID 1 or username 'admin'. Change this later for real security.</p>
</div></body></html>