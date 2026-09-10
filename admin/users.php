<?php
require_once __DIR__ . '/inc/auth.php';
require_once __DIR__ . '/inc/functions.php';
requireAuth();
requireRole('superadmin');
$pageTitle = 'Users';
$db = db();
$msg = '';

// Delete
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['delete_id'])) {
    $uid = (int)$_POST['delete_id'];
    if ($uid !== currentUser()['id']) {
        $db->prepare("DELETE FROM users WHERE id=?")->execute([$uid]);
        logActivity('Deleted user', '#'.$uid);
        header('Location: /admin/users.php?msg=deleted'); exit;
    }
}
// Save (add/edit)
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['save_user'])) {
    $id    = (int)($_POST['user_id']??0);
    $name  = trim($_POST['name']??'');
    $email = trim($_POST['email']??'');
    $role  = in_array($_POST['role'],['admin','superadmin']) ? $_POST['role'] : 'admin';
    $active= isset($_POST['active']) ? 1 : 0;
    $pass  = trim($_POST['password']??'');
    if ($id) {
        $sql = "UPDATE users SET name=?,email=?,role=?,active=? WHERE id=?";
        $params = [$name,$email,$role,$active,$id];
        if ($pass) { $sql = "UPDATE users SET name=?,email=?,role=?,active=?,password=? WHERE id=?"; $params=[$name,$email,$role,$active,password_hash($pass,PASSWORD_DEFAULT),$id]; }
        $db->prepare($sql)->execute($params);
        logActivity('Updated user', $email);
        $msg = 'User updated.';
    } else {
        if (!$pass) { $msg = 'Password required for new user.'; }
        else {
            $db->prepare("INSERT INTO users (name,email,password,role,active,created_at) VALUES (?,?,?,?,?,datetime('now'))")
               ->execute([$name,$email,password_hash($pass,PASSWORD_DEFAULT),$role,$active]);
            logActivity('Created user', $email);
            $msg = 'User created.';
        }
    }
}

$edit = null;
if (isset($_GET['edit'])) {
    $stmt = $db->prepare("SELECT * FROM users WHERE id=?"); $stmt->execute([(int)$_GET['edit']]);
    $edit = $stmt->fetch();
}
$users = $db->query("SELECT * FROM users ORDER BY created_at DESC")->fetchAll();
include __DIR__ . '/inc/header.php';
?>
<?php if($msg): ?><div class="alert alert-success"><?= e($msg) ?></div><?php endif; ?>
<?php if(isset($_GET['msg'])): ?><div class="alert alert-success">User deleted.</div><?php endif; ?>

<div style="display:grid;grid-template-columns:1fr 340px;gap:20px;align-items:start">
<div class="card">
  <div class="card-header"><h2>Admin Users</h2></div>
  <div class="table-wrap">
  <table>
    <thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Status</th><th>Last Login</th><th>Actions</th></tr></thead>
    <tbody>
    <?php foreach($users as $u): ?>
      <tr>
        <td><?= e($u['name']) ?></td>
        <td style="color:var(--muted)"><?= e($u['email']) ?></td>
        <td><span class="badge <?= $u['role']==='superadmin'?'badge-blue':'badge-gray' ?>"><?= e($u['role']) ?></span></td>
        <td><?= $u['active'] ? '<span class="badge badge-green">Active</span>' : '<span class="badge badge-red">Inactive</span>' ?></td>
        <td style="color:var(--muted);font-size:12px"><?= $u['last_login'] ? ago($u['last_login']) : 'Never' ?></td>
        <td>
          <div class="tb-actions">
            <a href="?edit=<?= $u['id'] ?>" class="btn btn-secondary btn-sm">Edit</a>
            <?php if ($u['id'] !== currentUser()['id']): ?>
            <form method="post"><input type="hidden" name="delete_id" value="<?= $u['id'] ?>"><button class="btn btn-danger btn-sm" data-confirm="Delete this user?">Del</button></form>
            <?php endif; ?>
          </div>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  </div>
</div>

<div class="card">
  <div class="card-header"><h2><?= $edit ? 'Edit User' : 'Add User' ?></h2></div>
  <div class="card-body">
    <form method="post">
      <input type="hidden" name="save_user" value="1">
      <input type="hidden" name="user_id" value="<?= $edit['id']??0 ?>">
      <div class="field"><label>Full Name</label><input type="text" name="name" required value="<?= e($edit['name']??'') ?>"></div>
      <div class="field"><label>Email</label><input type="email" name="email" required value="<?= e($edit['email']??'') ?>"></div>
      <div class="field"><label>Password <?= $edit?'(leave blank to keep)':'' ?></label><input type="password" name="password" <?= $edit?'':'required' ?> placeholder="••••••••"></div>
      <div class="field">
        <label>Role</label>
        <select name="role">
          <option value="admin" <?= ($edit['role']??'')==='admin'?'selected':'' ?>>Admin</option>
          <option value="superadmin" <?= ($edit['role']??'')==='superadmin'?'selected':'' ?>>Super Admin</option>
        </select>
      </div>
      <div class="field" style="flex-direction:row;align-items:center;gap:10px">
        <input type="checkbox" name="active" id="active" <?= ($edit['active']??1)?'checked':'' ?> style="width:auto">
        <label for="active" style="margin:0">Active</label>
      </div>
      <div style="display:flex;gap:8px;margin-top:4px">
        <button class="btn btn-primary">Save User</button>
        <?php if($edit): ?><a href="/admin/users.php" class="btn btn-secondary">Cancel</a><?php endif; ?>
      </div>
    </form>
  </div>
</div>
</div>
<?php include __DIR__ . '/inc/footer.php'; ?>
