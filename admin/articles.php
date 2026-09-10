<?php
require_once __DIR__ . '/inc/auth.php';
require_once __DIR__ . '/inc/functions.php';
requireAuth();
$pageTitle = 'Articles';
$db = db();

// Delete
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['delete_id'])) {
    $aid = (int)$_POST['delete_id'];
    $row = $db->prepare("SELECT thumb FROM articles WHERE id=?")->execute([$aid]) ? null : null;
    $stmt = $db->prepare("SELECT thumb FROM articles WHERE id=?"); $stmt->execute([$aid]); $row = $stmt->fetch();
    if ($row && $row['thumb'] && file_exists(__DIR__.'/uploads/articles/'.$row['thumb'])) unlink(__DIR__.'/uploads/articles/'.$row['thumb']);
    $db->prepare("DELETE FROM articles WHERE id=?")->execute([$aid]);
    logActivity('Deleted article', '#'.$aid);
    header('Location: /admin/articles.php?msg=deleted'); exit;
}
// Toggle publish
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['toggle_id'])) {
    $aid = (int)$_POST['toggle_id'];
    $db->prepare("UPDATE articles SET published=1-published WHERE id=?")->execute([$aid]);
    logActivity('Toggled article publish', '#'.$aid);
    header('Location: /admin/articles.php'); exit;
}

$page = max(1,(int)($_GET['page']??1)); $perPage=15;
$stmt = $db->prepare("SELECT COUNT(*) FROM articles"); $stmt->execute(); $total=(int)$stmt->fetchColumn();
$stmt2 = $db->prepare("SELECT a.*,u.name AS author_name FROM articles a LEFT JOIN users u ON u.id=a.author_id ORDER BY created_at DESC LIMIT $perPage OFFSET ".(($page-1)*$perPage));
$stmt2->execute();
$articles = $stmt2->fetchAll();
include __DIR__ . '/inc/header.php';
?>
<?php if(isset($_GET['msg'])): ?><div class="alert alert-success">Article deleted.</div><?php endif; ?>
<div class="card">
  <div class="card-header">
    <h2>Articles (<?= $total ?>)</h2>
    <a href="/admin/article-edit.php" class="btn btn-primary btn-sm">+ New Article</a>
  </div>
  <div class="table-wrap">
  <?php if(empty($articles)): ?>
    <div class="empty"><p>No articles yet. <a href="/admin/article-edit.php">Create one</a></p></div>
  <?php else: ?>
  <table>
    <thead><tr><th>Thumb</th><th>Title</th><th>Category</th><th>Author</th><th>Status</th><th>Date</th><th>Actions</th></tr></thead>
    <tbody>
    <?php foreach($articles as $a): ?>
      <tr>
        <td style="width:54px">
          <?php if($a['thumb']): ?>
          <img src="/admin/uploads/articles/<?= e($a['thumb']) ?>" style="width:48px;height:36px;object-fit:cover;border-radius:4px">
          <?php else: ?><div style="width:48px;height:36px;background:var(--surface2);border-radius:4px"></div><?php endif; ?>
        </td>
        <td><?= e(truncate($a['title'],55)) ?></td>
        <td style="color:var(--muted);font-size:12px"><?= e($a['category']??'') ?></td>
        <td style="color:var(--muted);font-size:12px"><?= e($a['author_name']??'') ?></td>
        <td><?= $a['published'] ? '<span class="badge badge-green">Published</span>' : '<span class="badge badge-gray">Draft</span>' ?></td>
        <td style="color:var(--muted);white-space:nowrap;font-size:12px"><?= substr($a['created_at'],0,10) ?></td>
        <td>
          <div class="tb-actions">
            <a href="/admin/article-edit.php?id=<?= $a['id'] ?>" class="btn btn-secondary btn-sm">Edit</a>
            <form method="post"><input type="hidden" name="toggle_id" value="<?= $a['id'] ?>"><button class="btn btn-secondary btn-sm"><?= $a['published']?'Unpublish':'Publish' ?></button></form>
            <form method="post"><input type="hidden" name="delete_id" value="<?= $a['id'] ?>"><button class="btn btn-danger btn-sm" data-confirm="Delete this article?">Del</button></form>
          </div>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  <?php endif; ?>
  </div>
  <div style="padding:12px 18px"><?= paginate($total,$page,$perPage) ?></div>
</div>
<?php include __DIR__ . '/inc/footer.php'; ?>
