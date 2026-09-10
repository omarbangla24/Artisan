<?php
require_once __DIR__ . '/inc/auth.php';
require_once __DIR__ . '/inc/functions.php';
requireAuth();
$pageTitle = 'Form Entries';
$db = db();

// Delete
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['delete_id'])) {
    $db->prepare("DELETE FROM form_entries WHERE id=?")->execute([(int)$_POST['delete_id']]);
    logActivity('Deleted form entry', '#'.$_POST['delete_id']);
    header('Location: /admin/entries.php?msg=deleted'); exit;
}
// Mark read
$view = isset($_GET['view']) ? (int)$_GET['view'] : 0;
if ($view) {
    $db->prepare("UPDATE form_entries SET read_at=datetime('now') WHERE id=? AND read_at IS NULL")->execute([$view]);
    $entry = $db->prepare("SELECT * FROM form_entries WHERE id=?")->execute([$view]) ? $db->prepare("SELECT * FROM form_entries WHERE id=?"): null;
    $stmt = $db->prepare("SELECT * FROM form_entries WHERE id=?"); $stmt->execute([$view]);
    $entry = $stmt->fetch();
}

$type   = $_GET['type'] ?? '';
$page   = max(1,(int)($_GET['page']??1));
$perPage= 20;
$where  = $type ? "WHERE form_type=?" : "";
$params = $type ? [$type] : [];
$total  = $db->prepare("SELECT COUNT(*) FROM form_entries $where")->execute($params) ? 0 : 0;
$stmt   = $db->prepare("SELECT COUNT(*) FROM form_entries $where"); $stmt->execute($params);
$total  = (int)$stmt->fetchColumn();
$stmt2  = $db->prepare("SELECT * FROM form_entries $where ORDER BY created_at DESC LIMIT $perPage OFFSET ".(($page-1)*$perPage));
$stmt2->execute($params);
$entries = $stmt2->fetchAll();
$types  = $db->query("SELECT DISTINCT form_type FROM form_entries ORDER BY form_type")->fetchAll(PDO::FETCH_COLUMN);

include __DIR__ . '/inc/header.php';
?>
<?php if(isset($_GET['msg'])): ?><div class="alert alert-success">Entry deleted.</div><?php endif; ?>

<?php if($view && !empty($entry)): $d=json_decode($entry['data'],true)??[]; ?>
<div class="card" style="margin-bottom:20px">
  <div class="card-header">
    <h2>Entry #<?= $entry['id'] ?> — <?= e($entry['form_type']) ?></h2>
    <div style="display:flex;gap:8px">
      <span style="font-size:12px;color:var(--muted)"><?= $entry['created_at'] ?></span>
      <form method="post" style="display:inline"><input type="hidden" name="delete_id" value="<?= $entry['id'] ?>"><button class="btn btn-danger btn-sm" data-confirm="Delete this entry?">Delete</button></form>
    </div>
  </div>
  <div class="card-body">
    <table style="width:auto">
      <?php foreach($d as $k=>$v): if($k==='form_key')continue; ?>
      <tr><td style="color:var(--muted);padding:6px 16px 6px 0;font-size:12px;white-space:nowrap;vertical-align:top"><?= e(ucwords(str_replace(['_','-'],' ',$k))) ?></td><td style="padding:6px 0"><?= e((string)$v) ?></td></tr>
      <?php endforeach; ?>
      <tr><td style="color:var(--muted);padding:6px 16px 6px 0;font-size:12px">IP</td><td><?= e($entry['ip']??'') ?></td></tr>
    </table>
  </div>
</div>
<?php endif; ?>

<div class="card">
  <div class="card-header"><h2>All Entries (<?= $total ?>)</h2></div>
  <div class="card-body" style="padding-bottom:0">
    <div class="filter-bar">
      <form method="get" style="display:flex;gap:8px;flex:1">
        <select name="type" onchange="this.form.submit()">
          <option value="">All Types</option>
          <?php foreach($types as $t): ?><option value="<?= e($t) ?>" <?= $type===$t?'selected':'' ?>><?= e($t) ?></option><?php endforeach; ?>
        </select>
        <button class="btn btn-secondary btn-sm">Filter</button>
      </form>
    </div>
  </div>
  <div class="table-wrap">
  <?php if(empty($entries)): ?>
    <div class="empty"><p>No entries found</p></div>
  <?php else: ?>
  <table>
    <thead><tr><th>#</th><th>Type</th><th>Summary</th><th>IP</th><th>Date</th><th>Status</th><th>Actions</th></tr></thead>
    <tbody>
    <?php foreach($entries as $e): $d=json_decode($e['data'],true)??[]; ?>
      <tr>
        <td style="color:var(--muted)"><?= $e['id'] ?></td>
        <td><span class="badge badge-blue"><?= htmlspecialchars($e['form_type']) ?></span></td>
        <td><?= htmlspecialchars(truncate(implode(' · ', array_filter(array_values($d))),70)) ?></td>
        <td style="color:var(--muted);font-size:12px"><?= e($e['ip']??'') ?></td>
        <td style="color:var(--muted);white-space:nowrap;font-size:12px"><?= $e['created_at'] ?></td>
        <td><?= $e['read_at'] ? '<span class="badge badge-gray">Read</span>' : '<span class="badge badge-yellow">New</span>' ?></td>
        <td>
          <div class="tb-actions">
            <a href="?view=<?= $e['id'] ?>" class="btn btn-secondary btn-sm">View</a>
            <form method="post"><input type="hidden" name="delete_id" value="<?= $e['id'] ?>"><button class="btn btn-danger btn-sm" data-confirm="Delete?">Del</button></form>
          </div>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  <?php endif; ?>
  </div>
  <div style="padding:12px 18px"><?= paginate($total,$page,$perPage,'?type='.urlencode($type)) ?></div>
</div>
<?php include __DIR__ . '/inc/footer.php'; ?>
