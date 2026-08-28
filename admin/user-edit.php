<?php
require __DIR__ . '/inc/bootstrap.php';
require __DIR__ . '/inc/layout.php';
require_admin();

$pdo = db();
$id  = (int)($_GET['id'] ?? 0);
$editing = $id > 0;
$user = ['name' => '', 'email' => '', 'role' => 'editor', 'is_active' => 1];

if ($editing) {
    $stmt = $pdo->prepare('SELECT id, name, email, role, is_active FROM users WHERE id = ?');
    $stmt->execute([$id]);
    $user = $stmt->fetch();
    if (!$user) { http_response_code(404); layout_header('Not found'); echo '<h1>User not found</h1>'; layout_footer(); exit; }
}

$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $name  = post_str('name', 120);
    $email = clean_email(post_str('email', 190));
    $role  = ($_POST['role'] ?? 'editor') === 'admin' ? 'admin' : 'editor';
    $pass  = post_str('password', 200);
    $active = isset($_POST['is_active']) ? 1 : 0;

    if ($name === '')        $err = 'Name is required.';
    elseif ($email === '')   $err = 'A valid email is required.';
    elseif (!$editing && strlen($pass) < 10) $err = 'Set an initial password of at least 10 characters.';
    elseif ($pass !== '' && strlen($pass) < 10) $err = 'Password must be at least 10 characters.';
    else {
        // Unique email check (excluding self).
        $chk = $pdo->prepare('SELECT id FROM users WHERE email = ? AND id <> ?');
        $chk->execute([$email, $id]);
        if ($chk->fetch()) { $err = 'Another user already uses that email.'; }
    }

    if ($err === '') {
        if ($editing) {
            if ($pass !== '') {
                $up = $pdo->prepare('UPDATE users SET name=?, email=?, role=?, is_active=?, password_hash=?, must_change_password=1 WHERE id=?');
                $up->execute([$name, $email, $role, $active, password_hash($pass, PASSWORD_DEFAULT), $id]);
            } else {
                $up = $pdo->prepare('UPDATE users SET name=?, email=?, role=?, is_active=? WHERE id=?');
                $up->execute([$name, $email, $role, $active, $id]);
            }
            flash('User updated.', 'ok');
        } else {
            $ins = $pdo->prepare('INSERT INTO users (name, email, password_hash, role, must_change_password, is_active) VALUES (?,?,?,?,1,?)');
            $ins->execute([$name, $email, password_hash($pass, PASSWORD_DEFAULT), $role, $active]);
            flash('User created. They must change the password at first login.', 'ok');
        }
        redirect('users.php');
    }
    $user = ['id'=>$id,'name'=>$name,'email'=>$email,'role'=>$role,'is_active'=>$active];
}

layout_header($editing ? 'Edit user' : 'New user', 'users.php');
?>
<p><a href="users.php" class="muted">← Back to users</a></p>
<h1><?= $editing ? 'Edit user' : 'New user' ?></h1>
<div class="card" style="max-width:520px">
  <?php if ($err): ?><div class="flash flash-error"><?= e($err) ?></div><?php endif; ?>
  <form method="post" autocomplete="off">
    <?= csrf_field() ?>
    <div class="field">
      <label for="name">Name</label>
      <input type="text" id="name" name="name" required value="<?= e($user['name']) ?>">
    </div>
    <div class="field">
      <label for="email">Email</label>
      <input type="email" id="email" name="email" required value="<?= e($user['email']) ?>">
    </div>
    <div class="row">
      <div class="field">
        <label for="role">Role</label>
        <select id="role" name="role">
          <option value="editor"<?= $user['role']==='editor'?' selected':'' ?>>Editor</option>
          <option value="admin"<?= $user['role']==='admin'?' selected':'' ?>>Admin</option>
        </select>
        <div class="hint">Admins can manage users and settings.</div>
      </div>
      <div class="field">
        <label for="password"><?= $editing ? 'New password' : 'Initial password' ?></label>
        <input type="password" id="password" name="password" <?= $editing ? '' : 'required' ?>>
        <div class="hint"><?= $editing ? 'Leave blank to keep current.' : 'Min 10 chars.' ?> User must change it at first login.</div>
      </div>
    </div>
    <div class="field check">
      <input type="checkbox" id="is_active" name="is_active" value="1" <?= (int)$user['is_active']===1?'checked':'' ?>>
      <label for="is_active" style="margin:0">Account active</label>
    </div>
    <button class="btn" type="submit"><?= $editing ? 'Save changes' : 'Create user' ?></button>
  </form>
</div>
<?php layout_footer();
