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
        db()->prepare("UPDATE users SET last_login=NOW() WHERE id=?")->execute([$user['id']]);
        db()->prepare("INSERT INTO activity_log (user_id,user_name,action,ip,created_at) VALUES (?,?,?,?,NOW())")
           ->execute([$user['id'],$user['name'],'Logged in',$_SERVER['REMOTE_ADDR']??'']);
        header('Location: /admin/'); exit;
    }
    $error = 'Invalid email or password.';
}
?><!doctype html>
<html lang="en">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Sign In — ARTISAN Admin</title>
<link rel="icon" href="/assets/img/logo.png">
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{
  font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Inter,sans-serif;
  background:linear-gradient(135deg,#e8f0fb 0%,#f0f4f8 50%,#e8f0fb 100%);
  min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px;
}
.wrap{width:100%;max-width:400px}
.card{
  background:#fff;border:1px solid #e3e8ef;border-radius:16px;
  padding:40px 36px;box-shadow:0 4px 24px rgba(0,0,0,.08),0 1px 4px rgba(0,0,0,.04);
}
.logo-area{text-align:center;margin-bottom:32px}
.logo-area img{height:40px;margin-bottom:16px;display:inline-block}
.logo-area h1{font-size:18px;font-weight:700;color:#111827;margin-bottom:4px}
.logo-area p{color:#6b7280;font-size:13.5px}
label{display:block;font-size:12.5px;font-weight:500;color:#374151;margin-bottom:5px}
input{
  width:100%;background:#f9fafb;border:1px solid #e3e8ef;border-radius:8px;
  color:#111827;padding:10px 13px;font-size:14px;outline:none;transition:.2s;
  font-family:inherit;
}
input:focus{background:#fff;border-color:#1B61A9;box-shadow:0 0 0 3px rgba(27,97,169,.1)}
.field{margin-bottom:18px}
.err{
  background:#fef2f2;color:#dc2626;border:1px solid #fecaca;
  border-radius:8px;padding:10px 14px;font-size:13px;margin-bottom:20px;
  display:flex;align-items:center;gap:8px;
}
button{
  width:100%;background:#1B61A9;color:#fff;border:none;border-radius:8px;
  padding:11px;font-size:14px;font-weight:600;cursor:pointer;transition:.15s;
  font-family:inherit;letter-spacing:.01em;
}
button:hover{background:#154e8a}
.back{text-align:center;margin-top:20px;font-size:12.5px;color:#9ca3af}
.back a{color:#1B61A9}
</style>
</head>
<body>
<div class="wrap">
  <div class="card">
    <div class="logo-area">
      <img src="/assets/img/logo.png" alt="ARTISAN">
      <h1>Admin Panel</h1>
      <p>Sign in to manage your website</p>
    </div>
    <?php if($error): ?>
    <div class="err">
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
      <?= htmlspecialchars($error) ?>
    </div>
    <?php endif; ?>
    <form method="post">
      <div class="field">
        <label>Email Address</label>
        <input type="email" name="email" required autofocus placeholder="admin@artisancabd.com" value="<?= htmlspecialchars($_POST['email']??'') ?>">
      </div>
      <div class="field">
        <label>Password</label>
        <input type="password" name="password" required placeholder="••••••••">
      </div>
      <button type="submit">Sign In &rarr;</button>
    </form>
    <div class="back"><a href="/">← Back to website</a></div>
  </div>
</div>
</body></html>
