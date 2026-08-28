<?php
require __DIR__ . '/inc/bootstrap.php';
require __DIR__ . '/inc/layout.php';
require_admin();

$pdo = db();
$me  = current_user();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $id = (int)($_POST['id'] ?? 0);
    $action = $_POST['action'] ?? '';
    if ($id === $me['id']) {
        flash('You cannot change your own account here.', 'error');
    } elseif ($id > 0 && $action === 'delete') {
        $pdo->prepare('DELETE FROM users WHERE id = ?')->execute([$id]);
        flash('User deleted.', 'ok');
    } elseif ($id > 0 && $action === 'toggle') {
        $pdo->prepare('UPDATE users SET is_active = 1 - is_active WHERE id = ?')->execute([$id]);
        flash('User status changed.', 'ok');
    }
    redirect('users.php');
}

$rows = $pdo->query('SELECT id, name, email, role, is_active, last_login_at, created_at
                     FROM users ORDER BY id')->fetchAll();

layout_header('Users', 'users.php');
?>
<div class="row" style="justify-content:space-between;align-items:center">
  <h1 style="margin:0">Users</h1>
  <a class="btn" href="user-edit.php">+ New user</a>
</div>

<div class="tablecard mt">
  <table>
    <thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Status</th><th>Last login</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($rows as $r): ?>
      <tr>
        <td><?= e($r['name']) ?><?= $r['id']===$me['id'] ? ' <span class="muted">(you)</span>' : '' ?></td>
        <td><?= e($r['email']) ?></td>
        <td><span class="badge role"><?= e($r['role']) ?></span></td>
        <td><?= ((int)$r['is_active']===1) ? 'Active' : '<span class="muted">Disabled</span>' ?></td>
        <td class="muted"><?= $r['last_login_at'] ? e(date('d M Y, H:i', strtotime($r['last_login_at']))) : '—' ?></td>
        <td class="right">
          <a class="btn sm ghost" href="user-edit.php?id=<?= (int)$r['id'] ?>">Edit</a>
          <?php if ($r['id'] !== $me['id']): ?>
            <form method="post" style="display:inline">
              <?= csrf_field() ?>
              <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
              <input type="hidden" name="action" value="toggle">
              <button class="btn sm ghost" type="submit"><?= (int)$r['is_active']===1?'Disable':'Enable' ?></button>
            </form>
            <form method="post" style="display:inline" onsubmit="return confirm('Delete this user?');">
              <?= csrf_field() ?>
              <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
              <input type="hidden" name="action" value="delete">
              <button class="btn sm danger" type="submit">Delete</button>
            </form>
          <?php endif; ?>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php layout_footer();
