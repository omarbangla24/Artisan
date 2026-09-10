<?php
require_once __DIR__ . '/inc/auth.php';
require_once __DIR__ . '/inc/functions.php';
requireAuth();
$pageTitle = 'Partners';
$db = db();
$msg = '';

// Delete
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['delete_id'])) {
    $tid = (int)$_POST['delete_id'];
    $s=$db->prepare("SELECT photo FROM team_members WHERE id=?"); $s->execute([$tid]); $row=$s->fetch();
    if ($row && $row['photo'] && file_exists(__DIR__.'/uploads/team/'.$row['photo'])) unlink(__DIR__.'/uploads/team/'.$row['photo']);
    $db->prepare("DELETE FROM team_members WHERE id=?")->execute([$tid]);
    logActivity('Deleted team member','#'.$tid);
    header('Location: /admin/team.php?msg=deleted'); exit;
}
// Save
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['save_member'])) {
    $id    = (int)($_POST['member_id']??0);
    $name  = trim($_POST['name']??'');
    $role  = trim($_POST['role']??'');
    $bio   = trim($_POST['bio']??'');
    $email = trim($_POST['email']??'');
    $linkedin = trim($_POST['linkedin']??'');
    $order = (int)($_POST['sort_order']??0);
    $active = isset($_POST['active'])?1:0;
    $photo = '';
    if ($id) { $s=$db->prepare("SELECT photo FROM team_members WHERE id=?"); $s->execute([$id]); $row=$s->fetch(); $photo=$row['photo']??''; }
    if (!empty($_FILES['photo']['name'])) {
        $up = uploadFile($_FILES['photo'], 'team');
        if ($up) { if ($photo && file_exists(__DIR__.'/uploads/team/'.$photo)) unlink(__DIR__.'/uploads/team/'.$photo); $photo=$up; }
    }
    if ($id) {
        $db->prepare("UPDATE team_members SET name=?,role=?,bio=?,email=?,linkedin=?,photo=?,sort_order=?,active=? WHERE id=?")
           ->execute([$name,$role,$bio,$email,$linkedin,$photo,$order,$active,$id]);
        logActivity('Updated team member',$name); $msg='Member updated.';
    } else {
        $db->prepare("INSERT INTO team_members (name,role,bio,email,linkedin,photo,sort_order,active,created_at) VALUES (?,?,?,?,?,?,?,?,NOW())")
           ->execute([$name,$role,$bio,$email,$linkedin,$photo,$order,$active]);
        logActivity('Created team member',$name); $msg='Member added.';
    }
}

$edit=null;
if(isset($_GET['edit'])){ $s=$db->prepare("SELECT * FROM team_members WHERE id=?"); $s->execute([(int)$_GET['edit']]); $edit=$s->fetch(); }
$members=$db->query("SELECT * FROM team_members ORDER BY sort_order,name")->fetchAll();
include __DIR__ . '/inc/header.php';
?>
<?php if($msg): ?><div class="alert alert-success"><?= e($msg) ?></div><?php endif; ?>
<?php if(isset($_GET['msg'])): ?><div class="alert alert-success">Member deleted.</div><?php endif; ?>
<div style="display:grid;grid-template-columns:1fr 320px;gap:20px;align-items:start">
<div class="card">
  <div class="card-header"><h2>Partners (<?= count($members) ?>)</h2></div>
  <div class="table-wrap">
  <?php if(empty($members)): ?><div class="empty"><p>No team members yet.</p></div>
  <?php else: ?>
  <table>
    <thead><tr><th>Photo</th><th>Name</th><th>Role</th><th>Order</th><th>Status</th><th>Actions</th></tr></thead>
    <tbody>
    <?php foreach($members as $m): ?>
      <tr>
        <td><img src="<?= $m['photo']?'/admin/uploads/team/'.e($m['photo']):'/assets/img/placeholder.jpg' ?>" style="width:40px;height:40px;object-fit:cover;border-radius:50%"></td>
        <td><?= e($m['name']) ?></td>
        <td style="color:var(--muted);font-size:12px"><?= e($m['role']) ?></td>
        <td style="color:var(--muted)"><?= $m['sort_order'] ?></td>
        <td><?= $m['active']?'<span class="badge badge-green">Active</span>':'<span class="badge badge-gray">Hidden</span>' ?></td>
        <td>
          <div class="tb-actions">
            <a href="?edit=<?= $m['id'] ?>" class="btn btn-secondary btn-sm">Edit</a>
            <form method="post"><input type="hidden" name="delete_id" value="<?= $m['id'] ?>"><button class="btn btn-danger btn-sm" data-confirm="Delete?">Del</button></form>
          </div>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  <?php endif; ?>
  </div>
</div>
<div class="card">
  <div class="card-header"><h2><?= $edit?'Edit Member':'Add Member' ?></h2></div>
  <div class="card-body">
    <form method="post" enctype="multipart/form-data">
      <input type="hidden" name="save_member" value="1">
      <input type="hidden" name="member_id" value="<?= $edit['id']??0 ?>">
      <div class="field"><label>Full Name</label><input type="text" name="name" required value="<?= e($edit['name']??'') ?>"></div>
      <div class="field"><label>Role / Designation</label><input type="text" name="role" value="<?= e($edit['role']??'') ?>" placeholder="Managing Partner, ACA"></div>
      <div class="field"><label>Photo</label>
        <?php if(!empty($edit['photo'])): ?><img src="/admin/uploads/team/<?= e($edit['photo']) ?>" style="width:64px;height:64px;object-fit:cover;border-radius:50%;margin-bottom:8px;display:block"><?php endif; ?>
        <input type="file" name="photo" accept="image/*">
      </div>
      <div class="field"><label>Short Bio</label><textarea name="bio" rows="3"><?= e($edit['bio']??'') ?></textarea></div>
      <div class="field"><label>Email</label><input type="email" name="email" value="<?= e($edit['email']??'') ?>"></div>
      <div class="field"><label>LinkedIn URL</label><input type="url" name="linkedin" value="<?= e($edit['linkedin']??'') ?>"></div>
      <div class="field"><label>Display Order</label><input type="number" name="sort_order" value="<?= $edit['sort_order']??0 ?>" min="0"></div>
      <div class="field" style="flex-direction:row;align-items:center;gap:10px">
        <input type="checkbox" name="active" id="mact" <?= ($edit['active']??1)?'checked':'' ?> style="width:auto">
        <label for="mact" style="margin:0">Visible on site</label>
      </div>
      <div style="display:flex;gap:8px;margin-top:4px">
        <button class="btn btn-primary">Save</button>
        <?php if($edit): ?><a href="/admin/team.php" class="btn btn-secondary">Cancel</a><?php endif; ?>
      </div>
    </form>
  </div>
</div>
</div>
<?php include __DIR__ . '/inc/footer.php'; ?>
