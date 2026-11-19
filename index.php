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
  .install