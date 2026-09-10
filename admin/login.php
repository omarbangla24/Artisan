<?php
session_start();
require_once __DIR__ . '/inc/db.php';
require_once __DIR__ . '/inc/functions.php';

if (isset($_SESSION['admin_user'])) { header('Location: /admin/'); exit; }

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $pass  = $_POST['password'] ?? '';
    $stmt  = db()->prepare("SELECT * FROM users WHERE email=? AND active=1");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    if ($user && password_verify($pass, $user['password'])) {
        $_SESSION['admin_user'] = $user;
        db()->prepare("UPDATE users SET last_login=datetime('now') WHERE id=?")->execute([$user['id']]);
        db()->prepare("INSERT INTO activity_log (user_id,user_name,action,ip) VALUES (?,?,?,?)")
           ->execute([$user['id'],$user['name'],'Logged in',$_SERVER['REMOTE_ADDR']??'']);
        header('Location: /admin/'); exit;
    }
    $error = 'Invalid email or password.';
}
?><!doctype html>
<html lang="en">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Admin Login — ARTISAN</title>
<link rel="icon" href="/assets/img/logo.png">
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;background:#0f172a;color:#e2e8f0;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px}
.box{background:#1e293b;border:1px solid #334155;border-radius:12px;padding:36px 40px;width:100%;max-width:380px}
.logo{text-align:center;margin-bottom:28px}
.logo img{height:36px;filter:brightness(0) invert(1);opacity:.9}
h1{text-align:center;font-size:18px;font-weight:600;margin-bottom:8px}
p{text-align:center;color:#94a3b8;font-size:13px;margin-bottom:24px}
label{display:block;font-size:12px;color:#94a3b8;margin-bottom:5px;font-weight:500}
input{width:100%;background:#0f172a;border:1px solid #334155;border-radius:8px;color:#e2e8f0;padding:9px 12px;font-size:14px;outline:none;transition:.2s;margin-bottom:14px}
input:focus{border-color:#1B61A9}
button{width:100%;background:#1B61A9;color:#fff;border:none;border-radius:8px;padding:10px;font-size:14px;font-weight:600;cursor:pointer;margin-top:4px}
button:hover{background:#2d7dd2}
.err{background:#2d0000;color:#fca5a5;border:1px solid #7f1d1d;border-radius:8px;padding:10px 12px;font-size:13px;margin-bottom:16px}
</style>
</head>
<body>
<div class="box">
  <div class="logo"><img src="/assets/img/logo.png" alt="ARTISAN"></div>
  <h1>Admin Panel</h1>
  <p>Sign in to manage your website</p>
  <?php if($error): ?><div class="err"><?= htmlspecialchars($error) ?></div><?php endif; ?>
  <form method="post">
    <label>Email Address</label>
    <input type="email" name="email" required autofocus placeholder="admin@artisancabd.com">
    <label>Password</label>
    <input type="password" name="password" required placeholder="••••••••">
    <button type="submit">Sign In</button>
  </form>
</div>
</body></html>
