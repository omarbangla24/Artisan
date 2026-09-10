<?php
require_once __DIR__ . '/inc/auth.php';
require_once __DIR__ . '/inc/functions.php';
requireAuth();
$pageTitle = 'Gallery';
$db = db();
$msg = '';

if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['delete_id'])) {
    $gid=(int)$_POST['delete_id'];
    $s=$db->prepare("SELECT filename FROM gallery WHERE id=?"); $s->execute([$gid]); $row=$s->fetch();
    if ($row && file_exists(__DIR__.'/uploads/gallery/'.$row['filename'])) unlink(__DIR__.'/uploads/gallery/'.$row['filename']);
    $db->prepare("DELETE FROM gallery WHERE id=?")->execute([$gid]);
    logActivity('Deleted gallery image','#'.$gid);
    header('Location: /admin/gallery.php?msg=deleted'); exit;
}
// Upload multiple
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['upload'])) {
    $category = trim($_POST['category']??'');
    $uploaded = 0;
    if (!empty($_FILES['images']['name'][0])) {
        $files = $_FILES['images'];
        $count = count($files['name']);
        for ($i=0; $i<$count; $i++) {
            $single = ['name'=>$files['name'][$i],'tmp_name'=>$files['tmp_name'][$i],'size'=>$files['size'][$i],'type'=>$files['type'][$i],'error'=>$files['error'][$i]];
            $up = uploadFile($single, 'gallery');
            if ($up) {
                $db->prepare("INSERT INTO gallery (filename,category,sort_order,created_at) VALUES (?,?,?,NOW())")
                   ->execute([$up, $category, 0]);
                $uploaded++;
            }
        }
    }
    logActivity('Uploaded gallery images', "$uploaded file(s)");
    $msg = "$uploaded image(s) uploaded.";
}
// Update caption/category
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['update_caption'])) {
    $db->prepare("UPDATE gallery SET caption=?,category=?,sort_order=? WHERE id=?")
       ->execute([trim($_POST['caption']??''), trim($_POST['category']??''), (int)($_POST['sort_order']??0), (int)$_POST['gid']]);
    $msg='Image updated.';
}

$page=max(1,(int)($_GET['page']??1)); $perPage=24;
$cat=$_GET['cat']??'';
$where=$cat?"WHERE category=?":"";
$params=$cat?[$cat]:[];
$sc=$db->prepare("SELECT COUNT(*) FROM gallery $where"); $sc->execute($params); $total=(int)$sc->fetchColumn();
$sg=$db->prepare("SELECT * FROM gallery $where ORDER BY sort_order,created_at DESC LIMIT $perPage OFFSET ".(($page-1)*$perPage)); $sg->execute($params); $images=$sg->fetchAll();
$cats=$db->query("SELECT DISTINCT category FROM gallery WHERE category!='' ORDER BY category")->fetchAll(PDO::FETCH_COLUMN);
include __DIR__ . '/inc/header.php';
?>
<?php if($msg): ?><div class="alert alert-success"><?= e($msg) ?></div><?php endif; ?>
<?php if(isset($_GET['msg'])): ?><div class="alert alert-success">Image deleted.</div><?php endif; ?>

<div class="card" style="margin-bottom:16px">
  <div class="card-header"><h2>Upload Images</h2></div>
  <div class="card-body">
    <form method="post" enctype="multipart/form-data" style="display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end">
      <input type="hidden" name="upload" value="1">
      <div class="field" style="flex:1;min-width:180px;margin:0">
        <label>Category (optional)</label>
        <input type="text" name="category" placeholder="Office, Events, Team…">
      </div>
      <div class="field" style="flex:2;min-width:200px;margin:0">
        <label>Images (multiple allowed)</label>
        <input type="file" name="images[]" accept="image/*" multiple required>
      </div>
      <button class="btn btn-primary">Upload</button>
    </form>
  </div>
</div>

<div class="card">
  <div class="card-header">
    <h2>Gallery (<?= $total ?>)</h2>
    <div style="display:flex;gap:6px;flex-wrap:wrap">
      <a href="?" class="btn <?= !$cat?'btn-primary':'btn-secondary' ?> btn-sm">All</a>
      <?php foreach($cats as $c): ?><a href="?cat=<?= urlencode($c) ?>" class="btn <?= $cat===$c?'btn-primary':'btn-secondary' ?> btn-sm"><?= e($c) ?></a><?php endforeach; ?>
    </div>
  </div>
  <div class="card-body">
  <?php if(empty($images)): ?><div class="empty"><p>No images uploaded yet.</p></div>
  <?php else: ?>
  <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:12px">
    <?php foreach($images as $img): ?>
    <div style="background:var(--surface2);border-radius:8px;overflow:hidden">
      <img src="/admin/uploads/gallery/<?= e($img['filename']) ?>" style="width:100%;height:110px;object-fit:cover;display:block">
      <div style="padding:8px">
        <form method="post" style="display:flex;flex-direction:column;gap:4px">
          <input type="hidden" name="gid" value="<?= $img['id'] ?>">
          <input type="text" name="caption" value="<?= e($img['caption']??'') ?>" placeholder="Caption…" style="font-size:11px;padding:3px 6px">
          <input type="text" name="category" value="<?= e($img['category']??'') ?>" placeholder="Category…" style="font-size:11px;padding:3px 6px">
          <input type="number" name="sort_order" value="<?= $img['sort_order'] ?>" placeholder="Order" style="font-size:11px;padding:3px 6px">
          <div style="display:flex;gap:4px;margin-top:2px">
            <button name="update_caption" class="btn btn-secondary btn-sm" style="flex:1;font-size:11px">Save</button>
            <button name="delete_id" value="<?= $img['id'] ?>" class="btn btn-danger btn-sm" data-confirm="Delete image?" style="font-size:11px">Del</button>
          </div>
        </form>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
  </div>
  <div style="padding:12px 18px"><?= paginate($total,$page,$perPage,'?cat='.urlencode($cat)) ?></div>
</div>
<?php include __DIR__ . '/inc/footer.php'; ?>
