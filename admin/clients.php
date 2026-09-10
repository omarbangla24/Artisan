<?php
require_once __DIR__ . '/inc/auth.php';
require_once __DIR__ . '/inc/functions.php';
requireAuth();
$pageTitle = 'Clients';
$db = db();
$msg = '';

if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['delete_id'])) {
    $cid=(int)$_POST['delete_id'];
    $s=$db->prepare("SELECT logo FROM clients WHERE id=?"); $s->execute([$cid]); $row=$s->fetch();
    if ($row && $row['logo'] && file_exists(__DIR__.'/uploads/clients/'.$row['logo'])) unlink(__DIR__.'/uploads/clients/'.$row['logo']);
    $db->prepare("DELETE FROM clients WHERE id=?")->execute([$cid]);
    logActivity('Deleted client','#'.$cid);
    header('Location: /admin/clients.php?msg=deleted'); exit;
}
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['save_client'])) {
    $id    = (int)($_POST['client_id']??0);
    $name  = trim($_POST['name']??'');
    $url   = trim($_POST['url']??'');
    $sector= trim($_POST['sector']??'');
    $order = (int)($_POST['sort_order']??0);
    $active= isset($_POST['active'])?1:0;
    $logo='';
    if ($id) { $s=$db->prepare("SELECT logo FROM clients WHERE id=?"); $s->execute([$id]); $row=$s->fetch(); $logo=$row['logo']??''; }
    if (!empty($_FILES['logo']['name'])) {
        $up=uploadFile($_FILES['logo'],'clients');
        if ($up) { if ($logo && file_exists(__DIR__.'/uploads/clients/'.$logo)) unlink(__DIR__.'/uploads/clients/'.$logo); $logo=$up; }
    }
    if ($id) {
        $db->prepare("UPDATE clients SET name=?,url=?,sector=?,logo=?,sort_order=?,active=? WHERE id=?")->execute([$name,$url,$sector,$logo,$order,$active,$id]);
        logActivity('Updated client',$name); $msg='Client updated.';
    } else {
        $db->prepare("INSERT INTO clients (name,url,sector,logo,sort_order,active,created_at) VALUES (?,?,?,?,?,?,NOW())")->execute([$name,$url,$sector,$logo,$order,$active]);
        logActivity('Created client',$name); $msg='Client added.';
    }
}

$edit=null;
if(isset($_GET['edit'])){ $s=$db->prepare("SELECT * FROM clients WHERE id=?"); $s->execute([(int)$_GET['edit']]); $edit=$s->fetch(); }
$clients=$db->query("SELECT * FROM clients ORDER BY sort_order,name")->fetchAll();
include __DIR__ . '/inc/header.php';
?>
<?php if($msg): ?><div class="alert alert-success"><?= e($msg) ?></div><?php endif; ?>
<?php if(isset($_GET['msg'])): ?><div class="alert alert-success">Client deleted.</div><?php endif; ?>
<div style="display:grid;grid-template-columns:1fr 300px;gap:20px;align-items:start">
<div class="card">
  <div class="card-header"><h2>Client Logos (<?= count($clients) ?>)</h2></div>
  <div class="table-wrap">
  <?php if(empty($clients)): ?><div class="empty"><p>No clients yet.</p></div>
  <?php else: ?>
  <table>
    <thead><tr><th>Logo</th><th>Name</th><th>Sector</th><th>Order</th><th>Status</th><th>Actions</th></tr></thead>
    <tbody>
    <?php foreach($clients as $c): ?>
      <tr>
        <td><img src="<?= $c['logo']?'/admin/uploads/clients/'.e($c['logo']):'data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 50 30"><rect fill="%23334155" width="50" height="30" rx="3"/></svg>' ?>" style="height:30px;max-width:80px;object-fit:contain"></td>
        <td><?= e($c['name']) ?></td>
        <td style="color:var(--muted);font-size:12px"><?= e($c['sector']) ?></td>
        <td style="color:var(--muted)"><?= $c['sort_order'] ?></td>
        <td><?= $c['active']?'<span class="badge badge-green">Active</span>':'<span class="badge badge-gray">Hidden</span>' ?></td>
        <td>
          <div class="tb-actions">
            <a href="?edit=<?= $c['id'] ?>" class="btn btn-secondary btn-sm">Edit</a>
            <form method="post"><input type="hidden" name="delete_id" value="<?= $c['id'] ?>"><button class="btn btn-danger btn-sm" data-confirm="Delete?">Del</button></form>
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
  <div class="card-header"><h2><?= $edit?'Edit Client':'Add Client' ?></h2></div>
  <div class="card-body">
    <form method="post" enctype="multipart/form-data">
      <input type="hidden" name="save_client" value="1">
      <input type="hidden" name="client_id" value="<?= $edit['id']??0 ?>">
      <div class="field"><label>Client Name</label><input type="text" name="name" required value="<?= e($edit['name']??'') ?>"></div>
      <div class="field"><label>Sector / Industry</label><input type="text" name="sector" value="<?= e($edit['sector']??'') ?>" placeholder="Banking, Manufacturing, RMG…"></div>
      <div class="field"><label>Logo</label>
        <?php if(!empty($edit['logo'])): ?><img src="/admin/uploads/clients/<?= e($edit['logo']) ?>" style="height:40px;object-fit:contain;margin-bottom:8px;display:block"><?php endif; ?>
        <input type="file" name="logo" accept="image/*"></div>
      <div class="field"><label>Website URL</label><input type="url" name="url" value="<?= e($edit['url']??'') ?>"></div>
      <div class="field"><label>Display Order</label><input type="number" name="sort_order" value="<?= $edit['sort_order']??0 ?>"></div>
      <div class="field" style="flex-direction:row;align-items:center;gap:10px">
        <input type="checkbox" name="active" id="cact" <?= ($edit['active']??1)?'checked':'' ?> style="width:auto">
        <label for="cact" style="margin:0">Visible on site</label>
      </div>
      <div style="display:flex;gap:8px;margin-top:4px">
        <button class="btn btn-primary">Save</button>
        <?php if($edit): ?><a href="/admin/clients.php" class="btn btn-secondary">Cancel</a><?php endif; ?>
      </div>
    </form>
  </div>
</div>
</div>
<?php include __DIR__ . '/inc/footer.php'; ?>
