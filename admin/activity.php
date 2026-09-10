<?php
require_once __DIR__ . '/inc/auth.php';
require_once __DIR__ . '/inc/functions.php';
requireAuth();
$pageTitle = 'Activity Log';
$db = db();

if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['clear_log'])) {
    requireRole('superadmin');
    $db->exec("DELETE FROM activity_log WHERE created_at < NOW() - INTERVAL 30 DAY");
    logActivity('Cleared activity log (>30 days)');
    header('Location: /admin/activity.php?msg=cleared'); exit;
}

$user_filter = (int)($_GET['user_id']??0);
$page = max(1,(int)($_GET['page']??1)); $perPage=40;
$where = $user_filter ? "WHERE user_id=$user_filter" : "";
$sc=$db->query("SELECT COUNT(*) FROM activity_log $where"); $total=(int)$sc->fetchColumn();
$sa=$db->prepare("SELECT * FROM activity_log $where ORDER BY created_at DESC LIMIT $perPage OFFSET ".(($page-1)*$perPage));
$sa->execute(); $logs=$sa->fetchAll();
$users=$db->query("SELECT DISTINCT user_id,user_name FROM activity_log ORDER BY user_name")->fetchAll();
include __DIR__ . '/inc/header.php';
?>
<?php if(isset($_GET['msg'])): ?><div class="alert alert-success">Old entries cleared.</div><?php endif; ?>
<div class="card">
  <div class="card-header">
    <h2>Activity Log (<?= $total ?>)</h2>
    <div style="display:flex;gap:8px;align-items:center">
      <form method="get" style="display:flex;gap:6px">
        <select name="user_id" onchange="this.form.submit()">
          <option value="">All Users</option>
          <?php foreach($users as $u): ?><option value="<?= $u['user_id'] ?>" <?= $user_filter===$u['user_id']?'selected':'' ?>><?= e($u['user_name']) ?></option><?php endforeach; ?>
        </select>
      </form>
      <?php if(currentUser()['role']==='superadmin'): ?>
      <form method="post"><button name="clear_log" class="btn btn-danger btn-sm" data-confirm="Delete all activity older than 30 days?">Clear Old</button></form>
      <?php endif; ?>
    </div>
  </div>
  <div class="table-wrap">
  <?php if(empty($logs)): ?><div class="empty"><p>No activity recorded.</p></div>
  <?php else: ?>
  <table>
    <thead><tr><th>User</th><th>Action</th><th>Target</th><th>IP</th><th>Time</th></tr></thead>
    <tbody>
    <?php foreach($logs as $l): ?>
      <tr>
        <td>
          <div style="display:flex;align-items:center;gap:8px">
            <div style="width:26px;height:26px;border-radius:50%;background:var(--surface2);display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:700;flex:none;color:var(--muted)"><?= strtoupper(substr($l['user_name']??'?',0,1)) ?></div>
            <?= e($l['user_name']??'System') ?>
          </div>
        </td>
        <td><?= e($l['action']) ?></td>
        <td style="color:var(--muted);font-size:12px"><?= e($l['target']??'') ?></td>
        <td style="color:var(--muted);font-size:11px"><?= e($l['ip']??'') ?></td>
        <td style="color:var(--muted);white-space:nowrap;font-size:12px" title="<?= $l['created_at'] ?>"><?= ago($l['created_at']) ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  <?php endif; ?>
  </div>
  <div style="padding:12px 18px"><?= paginate($total,$page,$perPage,'?user_id='.$user_filter) ?></div>
</div>
<?php include __DIR__ . '/inc/footer.php'; ?>
