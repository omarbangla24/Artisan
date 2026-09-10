<?php
require_once __DIR__ . '/inc/auth.php';
require_once __DIR__ . '/inc/functions.php';
requireAuth();
$pageTitle = 'Affiliations';
$db = db();
$msg = '';

if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['delete_id'])) {
    $pid=(int)$_POST['delete_id'];
    $s=$db->prepare("SELECT logo FROM partners WHERE id=?"); $s->execute([$pid]); $row=$s->fetch();
    if ($row && $row['logo'] && file_exists(__DIR__.'/uploads/partners/'.$row['logo'])) unlink(__DIR__.'/uploads/partners/'.$row['logo']);
    $db->prepare("DELETE FROM partners WHERE id=?")->execute([$pid]);
    logActivity('Deleted partner','#'.$pid);
    header('Location: /admin/partners.php?msg=deleted'); exit;
}
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['save_partner'])) {
    $id    = (int)($_POST['partner_id']??0);
    $name  = trim($_POST['name']??'');
    $url   = trim($_POST['url']??'');
    $category = trim($_POST['category']??'');
    $order = (int)($_POST['sort_order']??0);
    $active = isset($_POST['active'])?1:0;
    $logo='';
    if ($id) { $s=$db->prepare("SELECT logo FROM partners WHERE id=?"); $s->execute([$id]); $row=$s->fetch(); $logo=$row['logo']??''; }
    if (!empty($_FILES['logo']['name'])) {
        $up=uploadFile($_FILES['logo'],'partners');
        if ($up) { if ($logo && file_exists(__DIR__.'/uploads/partners/'.$logo)) unlink(__DIR__.'/uploads/partners/'.$logo); $logo=$up; }
    }
    if ($id) {
        $db->prepare("UPDATE partners SET name=?,url=?,category=?,logo=?,sort_order=?,active=? WHERE id=?")->execute([$name,$url,$category,$logo,$order,$active,$id]);
        logActivity('Updated partner',$name); $msg='Partner updated.';
    } else {
        $db->prepare("INSERT INTO partners (name,url,category,logo,sort_order,active,created_at) VALUES (?,?,?,?,?,?,NOW())")->execute([$name,$url,$category,$logo,$order,$active]);
        logActivity('Created partner',$name); $msg='Partner added.';
    }
}

$edit=null;
if(isset($_GET['edit'])){ $s=$db->prepare("SELECT * FROM partners WHERE id=?"); $s->execute([(int)$_GET['edit']]); $edit=$s->fetch(); }
$partners=$db->query("SELECT * FROM partners ORDER BY sort_order,name")->fetchAll();
include __DIR__ . '/inc/header.php';
?>
<?php if($msg): ?><div class="alert alert-success"><?= e($msg) ?></div><?php endif; ?>
<?php if(isset($_GET['msg'])): ?><div class="alert alert-success">Partner deleted.</div><?php endif; ?>
<div style="display:grid;grid-template-columns:1fr 300px;gap:20px;align-items:start">
<div class="card">
  <div class="card-header"><h2>Affiliations & Enlistments (<?= count($partners) ?>)</h2></div>
  <div class="table-wrap">
  <?php if(empty($partners)): ?><div class="empty"><p>No partners yet.</p></div>
  <?php else: ?>
  <table>
    <thead><tr><th>Logo</th><th>Name</th><th>Category</th><th>Order</th><th>Status</th><th>Actions</th></tr></thead>
    <tbody>
    <?php foreach($partners as $p): ?>
      <tr>
        <td>
          <?php if($p['logo']): ?>
            <img src="/admin/uploads/partners/<?= e($p['logo']) ?>" style="height:30px;max-width:70px;object-fit:contain">
          <?php else: ?>
            <div style="width:48px;height:30px;background:var(--surface2);border:1px solid var(--border);border-radius:4px;display:flex;align-items:center;justify-content:center">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="var(--muted-l)" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
            </div>
          <?php endif; ?>
        </td>
        <td><?= e($p['name']) ?></td>
        <td style="color:var(--muted);font-size:12px"><?= e($p['category']) ?></td>
        <td style="color:var(--muted)"><?= $p['sort_order'] ?></td>
        <td><?= $p['active']?'<span class="badge badge-green">Active</span>':'<span class="badge badge-gray">Hidden</span>' ?></td>
        <td>
          <div class="tb-actions">
            <a href="?edit=<?= $p['id'] ?>" class="btn btn-secondary btn-sm">Edit</a>
            <form method="post"><input type="hidden" name="delete_id" value="<?= $p['id'] ?>"><button class="btn btn-danger btn-sm" data-confirm="Delete?">Del</button></form>
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
  <div class="card-header"><h2><?= $edit?'Edit Affiliation':'Add Affiliation' ?></h2></div>
  <div class="card-body">
    <form method="post" enctype="multipart/form-data">
      <input type="hidden" name="save_partner" value="1">
      <input type="hidden" name="partner_id" value="<?= $edit['id']??0 ?>">
      <div class="field"><label>Body / Organization Name</label><input type="text" name="name" required value="<?= e($edit['name']??'') ?>"></div>
      <div class="field"><label>Category</label><input type="text" name="category" value="<?= e($edit['category']??'') ?>" placeholder="Affiliation, Enlistment, Partner…"></div>
      <div class="field"><label>Logo</label>
        <?php if(!empty($edit['logo'])): ?><img src="/admin/uploads/partners/<?= e($edit['logo']) ?>" style="height:40px;object-fit:contain;margin-bottom:8px;display:block"><?php endif; ?>
        <input type="file" name="logo" accept="image/*"></div>
      <div class="field"><label>Website URL</label><input type="url" name="url" value="<?= e($edit['url']??'') ?>"></div>
      <div class="field"><label>Display Order</label><input type="number" name="sort_order" value="<?= $edit['sort_order']??0 ?>"></div>
      <div class="field" style="flex-direction:row;align-items:center;gap:10px">
        <input type="checkbox" name="active" id="pact" <?= ($edit['active']??1)?'checked':'' ?> style="width:auto">
        <label for="pact" style="margin:0">Visible on site</label>
      </div>
      <div style="display:flex;gap:8px;margin-top:4px">
        <button class="btn btn-primary">Save</button>
        <?php if($edit): ?><a href="/admin/partners.php" class="btn btn-secondary">Cancel</a><?php endif; ?>
      </div>
    </form>
  </div>
</div>
</div>
<?php include __DIR__ . '/inc/footer.php'; ?>
