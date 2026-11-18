<?php
session_start();
require 'db.php';

$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare("SELECT * FROM shack_users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        // Success - Give wristband
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        header("Location: index.php");
        exit;
    } else {
        $message = "Wrong username or password.";
    }
}
?>
<!DOCTYPE html>
<html>
<head><title>SHACK Login</title></head>
<body style="font-family:Arial; background:#111; color:white; text-align:center; padding:40px;">
<h1 style="color:#FFD700;">SHACK Login</h1>
<p style="color:red;"><?= htmlspecialchars($message) ?></p>
<form method="post" style="background:#222; padding:20px; display:inline-block; border-radius:10px;">
<input name="username" placeholder="Username" required style="padding:10px; display:block; margin:10px; width:250px;"><br>
<input name="password" type="password" placeholder="Password" required style="padding:10px; display:block; margin:10px; width:250px;"><br>
<button style="background:#FFD700; padding:10px 20px; border:none; font-weight:bold;">Login</button>
</form>
<br><br><a href="index.php" style="color:#FFD700;">Back to Home</a>
</body>
</html>