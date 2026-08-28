<?php
require __DIR__ . '/inc/bootstrap.php';
require __DIR__ . '/inc/layout.php';

if (current_user()) redirect('index.php');

$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    [$ok, $err] = auth_login(post_str('email', 190), post_str('password', 200));
    if ($ok) redirect('index.php');
}

layout_header('Sign in');
?>
<div class="authwrap">
  <div class="brand">ARTISAN <span>Admin</span></div>
  <div class="card">
    <?php if (isset($_GET['timeout'])): ?>
      <div class="flash flash-warn">You were signed out after inactivity.</div>
    <?php endif; ?>
    <?php if ($err): ?><div class="flash flash-error"><?= e($err) ?></div><?php endif; ?>
    <form method="post" autocomplete="off">
      <?= csrf_field() ?>
      <div class="field">
        <label for="email">Email</label>
        <input type="email" id="email" name="email" required autofocus
               value="<?= e($_POST['email'] ?? '') ?>">
      </div>
      <div class="field">
        <label for="password">Password</label>
        <input type="password" id="password" name="password" required>
      </div>
      <button class="btn" style="width:100%" type="submit">Sign in</button>
    </form>
  </div>
</div>
<?php layout_footer();
