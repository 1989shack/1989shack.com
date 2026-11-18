<?php
sessin_start();
require 'db.php';
?>
<!DOCTYPE html>
<html><head><title>SHACK - Liberia</title>
<style>body{font-family:Arial;background:#111;color:#fff;text-align:center;padding:20px}h1{color:#ffcc00}.box{background:#222;padding:15px;margin:10px auto;max-width:500px;border-radius:10px}input{padding:10px;width:80%;margin:5px}button{padding:10px 20px;background:#ffcc00;border:none;font-weight:bold;cursor:pointer}</style>
</head><body>
<h1>SHACK</h1><p>Liberia Home - Music, Streams, Community</p>
<?php if (isset($_SESSION['username'])): ?>
  <p>Welcome back, <b><?= htmlspecialchars($_SESSION['username']) ?></b> | <a href="logout.php" style="color:#FFD700;">Logout</a></p>
<?php else: ?>
  <p><a href="login.php" style="color:#FFD700;">Login</a> | <a href="index.php" style="color:#FFD700;">Sign Up</a></p>
<?php endif; ?>
<div class="box"><h2>Join SHACK</h2>
<form method="post"><input name="username" placeholder="Username" required><br>
<input name="email" type="email" placeholder="Email" required><br>
<input name="password" type="password" placeholder="Password" required><br>
<button>Sign Up</button></form>
<?php
if($_POST){
  $s=$db->prepare("INSERT INTO shack_users (username,email,password) VALUES (?,?,?)");
  try{$s->execute([$_POST['username'],$_POST['email'],password_hash($_POST['password'],PASSWORD_DEFAULT)]); echo "<p style='color:lime'>Welcome to SHACK!</p>";}
  catch(Exception $e){echo "<p style='color:red'>That username/email already exists</p>";}
}
$users=$db->query("SELECT COUNT(*) FROM shack_users")->fetchColumn();
echo "<p>Members: $users</p>";
?>
</div>
<div class="box"><h2>Live Streams</h2><p>No streams yet - be the first!</p></div>
</body></html>
