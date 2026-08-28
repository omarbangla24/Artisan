<?php
require __DIR__ . '/inc/bootstrap.php';
require __DIR__ . '/inc/layout.php';
require_login(true); // allow reaching this page even when a change is forced

$u = current_user();
$err = '';
$forced = !empty($_SESSION['must_pw']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $cur = post_str('current', 200);
    $new = post_str('new', 200);
    $rep = post_str('repeat', 200);

    $row = db()->prepare('SELECT password_hash FROM users WHERE id = ?');
    $row->execute([$u['id']]);
    $hash = $row->fetchColumn();

    if (!password_verify($cur, (string)$hash)) {
        $err = 'Your current password is incorrect.';
    } elseif (strlen($new) < 10) {
        $err = 'New password must be at least 10 characters.';
    } elseif (!preg_match('/[A-Za-z]/', $new) || !preg_match('/\d/', $new)) {
        $err = 'Use a mix of letters and numbers.';
    } elseif ($new !== $rep) {
        $err = 'The new passwords do not match.';
    } elseif (password_verify($new, (string)$hash)) {
        $err = 'Choose a password different from the current one.';
    } else {
        $up = db()->prepare(
            'UPDATE users SET password_hash = ?, must_change_password = 0 WHERE id = ?'
        );
        $up->execute([password_hash($new, PASSWORD_DEFAULT), $u['id']]);
        $_SESSION['must_pw'] = false;
        flash('Password updated.', 'ok');
        redirect('index.php');
    }
}

layout_header('Change password');
?>
<h1>Change password</h1>
<?php if ($forced): ?>
  <div class="flash flash-warn">For security, please set a new password before continuing.</div>
<?php endif; ?>
<div class="card" style="max-width:480px">
  <?php if ($err): ?><div class="flash flash-error"><?= e($err) ?></div><?php endif; ?>
  <form method="post" autocomplete="off">
    <?= csrf_field() ?>
    <div class="field">
      <label for="current">Current password</label>
      <input type="password" id="current" name="current" required autofocus>
    </div>
    <div class="field">
      <label for="new">New password</label>
      <input type="password" id="new" name="new" required>
      <div class="hint">At least 10 characters, with letters and numbers.</div>
    </div>
    <div class="field">
      <label for="repeat">Repeat new password</label>
      <input type="password" id="repeat" name="repeat" required>
    </div>
    <button class="btn" type="submit">Update password</button>
  </form>
</div>
<?php layout_footer();
