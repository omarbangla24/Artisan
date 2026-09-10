<?php
require_once __DIR__ . '/inc/auth.php';
require_once __DIR__ . '/inc/functions.php';
requireAuth();
$pageTitle = 'Job Applications';
$db = db();

// Delete
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['delete_id'])) {
    $aid = (int)$_POST['delete_id'];
    $s=$db->prepare("SELECT resume FROM job_applications WHERE id=?"); $s->execute([$aid]); $row=$s->fetch();
    if ($row && $row['resume'] && file_exists(__DIR__.'/uploads/'.$row['resume'])) unlink(__DIR__.'/uploads/'.$row['resume']);
    $db->prepare("DELETE FROM job_applications WHERE id=?")->execute([$aid]);
    logActivity('Deleted job application','#'.$aid);
    header('Location: /admin/applications.php?msg=deleted'); exit;
}
// Update status
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['set_status'])) {
    $db->prepare("UPDATE job_applications SET status=? WHERE id=?")->execute([$_POST['status'],(int)$_POST['app_id']]);
    logActivity('Updated application status','#'.$_POST['app_id'].' → '.$_POST['status']);
    header('Location: /admin/applications.php?job_id='.($_GET['job_id']??'')); exit;
}

$view = isset($_GET['view']) ? (int)$_GET['view'] : 0;
if ($view) {
    $db->prepare("UPDATE job_applications SET status='reviewed' WHERE id=? AND status='new'")->execute([$view]);
    $sv=$db->prepare("SELECT a.*,j.title AS job_title FROM job_applications a LEFT JOIN jobs j ON j.id=a.job_id WHERE a.id=?"); $sv->execute([$view]); $viewApp=$sv->fetch();
}

$jobId = isset($_GET['job_id']) ? (int)$_GET['job_id'] : 0;
$page = max(1,(int)($_GET['page']??1)); $perPage=20;
$where = $jobId ? "WHERE a.job_id=$jobId" : "";
$sc=$db->query("SELECT COUNT(*) FROM job_applications a $where"); $total=(int)$sc->fetchColumn();
$sa=$db->prepare("SELECT a.*,j.title AS job_title FROM job_applications a LEFT JOIN jobs j ON j.id=a.job_id $where ORDER BY a.created_at DESC LIMIT $perPage OFFSET ".(($page-1)*$perPage));
$sa->execute(); $apps=$sa->fetchAll();
$jobs=$db->query("SELECT id,title FROM jobs ORDER BY title")->fetchAll();
include __DIR__ . '/inc/header.php';
?>
<?php if(isset($_GET['msg'])): ?><div class="alert alert-success">Application deleted.</div><?php endif; ?>

<?php if($view && !empty($viewApp)): ?>
<div class="card" style="margin-bottom:20px">
  <div class="card-header">
    <h2><?= e($viewApp['applicant_name']) ?> — <?= e($viewApp['job_title']) ?></h2>
    <div style="display:flex;gap:8px;align-items:center">
      <form method="post" style="display:flex;gap:6px;align-items:center">
        <input type="hidden" name="app_id" value="<?= $viewApp['id'] ?>">
        <select name="status" style="font-size:12px;padding:4px 8px">
          <?php foreach(['new','reviewed','shortlisted','rejected'] as $st): ?>
          <option value="<?= $st ?>" <?= $viewApp['status']===$st?'selected':'' ?>><?= ucfirst($st) ?></option>
          <?php endforeach; ?>
        </select>
        <button name="set_status" class="btn btn-secondary btn-sm">Update</button>
      </form>
      <form method="post"><input type="hidden" name="delete_id" value="<?= $viewApp['id'] ?>"><button class="btn btn-danger btn-sm" data-confirm="Delete?">Delete</button></form>
    </div>
  </div>
  <div class="card-body">
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:16px">
      <div><span style="color:var(--muted);font-size:12px">Email</span><div><?= e($viewApp['email']) ?></div></div>
      <div><span style="color:var(--muted);font-size:12px">Phone</span><div><?= e($viewApp['phone']??'-') ?></div></div>
      <div><span style="color:var(--muted);font-size:12px">Applied</span><div><?= $viewApp['created_at'] ?></div></div>
      <div><span style="color:var(--muted);font-size:12px">Status</span><div><span class="badge badge-blue"><?= $viewApp['status'] ?></span></div></div>
    </div>
    <?php if($viewApp['cover_letter']): ?>
    <div style="margin-bottom:12px"><p style="color:var(--muted);font-size:12px;margin-bottom:4px">Cover Letter</p><p><?= nl2br(e($viewApp['cover_letter'])) ?></p></div>
    <?php endif; ?>
    <?php if($viewApp['resume']): ?>
    <a href="/admin/uploads/<?= e($viewApp['resume']) ?>" target="_blank" class="btn btn-secondary btn-sm">Download Resume</a>
    <?php endif; ?>
  </div>
</div>
<?php endif; ?>

<div class="card">
  <div class="card-header"><h2>Applications (<?= $total ?>)</h2></div>
  <div class="card-body" style="padding-bottom:0">
    <div class="filter-bar">
      <form method="get" style="display:flex;gap:8px;flex:1">
        <select name="job_id" onchange="this.form.submit()">
          <option value="">All Jobs</option>
          <?php foreach($jobs as $j): ?><option value="<?= $j['id'] ?>" <?= $jobId===$j['id']?'selected':'' ?>><?= e($j['title']) ?></option><?php endforeach; ?>
        </select>
      </form>
    </div>
  </div>
  <div class="table-wrap">
  <?php if(empty($apps)): ?><div class="empty"><p>No applications yet.</p></div>
  <?php else: ?>
  <table>
    <thead><tr><th>Applicant</th><th>Email</th><th>Job</th><th>Status</th><th>Date</th><th>Actions</th></tr></thead>
    <tbody>
    <?php foreach($apps as $a): ?>
      <tr>
        <td><?= e($a['applicant_name']) ?></td>
        <td style="color:var(--muted);font-size:12px"><?= e($a['email']) ?></td>
        <td style="font-size:12px"><?= e($a['job_title']??'') ?></td>
        <td>
          <?php $cls=['new'=>'badge-yellow','reviewed'=>'badge-blue','shortlisted'=>'badge-green','rejected'=>'badge-red'][$a['status']]??'badge-gray'; ?>
          <span class="badge <?= $cls ?>"><?= ucfirst($a['status']) ?></span>
        </td>
        <td style="color:var(--muted);font-size:12px;white-space:nowrap"><?= substr($a['created_at'],0,10) ?></td>
        <td>
          <div class="tb-actions">
            <a href="?view=<?= $a['id'] ?>&job_id=<?= $jobId ?>" class="btn btn-secondary btn-sm">View</a>
            <form method="post"><input type="hidden" name="delete_id" value="<?= $a['id'] ?>"><button class="btn btn-danger btn-sm" data-confirm="Delete?">Del</button></form>
          </div>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  <?php endif; ?>
  </div>
  <div style="padding:12px 18px"><?= paginate($total,$page,$perPage,'?job_id='.$jobId) ?></div>
</div>
<?php include __DIR__ . '/inc/footer.php'; ?>
