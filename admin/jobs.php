<?php
require_once __DIR__ . '/inc/auth.php';
require_once __DIR__ . '/inc/functions.php';
requireAuth();
$pageTitle = 'Careers / Jobs';
$db = db();
$msg = '';

// Delete
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['delete_id'])) {
    $db->prepare("DELETE FROM jobs WHERE id=?")->execute([(int)$_POST['delete_id']]);
    logActivity('Deleted job', '#'.$_POST['delete_id']);
    header('Location: /admin/jobs.php?msg=deleted'); exit;
}
// Toggle
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['toggle_id'])) {
    $db->prepare("UPDATE jobs SET published=1-published WHERE id=?")->execute([(int)$_POST['toggle_id']]);
    header('Location: /admin/jobs.php'); exit;
}
// Save
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['save_job'])) {
    $id       = (int)($_POST['job_id']??0);
    $title    = trim($_POST['title']??'');
    $dept     = trim($_POST['department']??'');
    $type     = trim($_POST['job_type']??'');
    $location = trim($_POST['location']??'Bangladesh');
    $desc     = $_POST['description']??'';
    $req      = $_POST['requirements']??'';
    $apply_email = trim($_POST['apply_email']??'');
    $deadline = trim($_POST['deadline']??'');
    $published = isset($_POST['published'])?1:0;
    if ($id) {
        $db->prepare("UPDATE jobs SET title=?,department=?,job_type=?,location=?,description=?,requirements=?,apply_email=?,deadline=?,published=? WHERE id=?")
           ->execute([$title,$dept,$type,$location,$desc,$req,$apply_email,$deadline,$published,$id]);
        logActivity('Updated job', $title); $msg='Job updated.';
    } else {
        $db->prepare("INSERT INTO jobs (title,department,job_type,location,description,requirements,apply_email,deadline,published,created_at) VALUES (?,?,?,?,?,?,?,?,?,datetime('now'))")
           ->execute([$title,$dept,$type,$location,$desc,$req,$apply_email,$deadline,$published]);
        logActivity('Created job', $title); $msg='Job created.';
    }
}

$edit = null;
if (isset($_GET['edit'])) { $s=$db->prepare("SELECT * FROM jobs WHERE id=?"); $s->execute([(int)$_GET['edit']]); $edit=$s->fetch(); }
$jobs = $db->query("SELECT j.*,(SELECT COUNT(*) FROM job_applications a WHERE a.job_id=j.id) AS app_count FROM jobs j ORDER BY created_at DESC")->fetchAll();
include __DIR__ . '/inc/header.php';
?>
<?php if($msg): ?><div class="alert alert-success"><?= e($msg) ?></div><?php endif; ?>
<?php if(isset($_GET['msg'])): ?><div class="alert alert-success">Job deleted.</div><?php endif; ?>
<div style="display:grid;grid-template-columns:1fr 340px;gap:20px;align-items:start">
<div class="card">
  <div class="card-header"><h2>Job Listings</h2></div>
  <div class="table-wrap">
  <?php if(empty($jobs)): ?><div class="empty"><p>No jobs yet.</p></div>
  <?php else: ?>
  <table>
    <thead><tr><th>Title</th><th>Dept</th><th>Type</th><th>Deadline</th><th>Apps</th><th>Status</th><th>Actions</th></tr></thead>
    <tbody>
    <?php foreach($jobs as $j): ?>
      <tr>
        <td><?= e($j['title']) ?></td>
        <td style="color:var(--muted);font-size:12px"><?= e($j['department']) ?></td>
        <td style="font-size:12px"><?= e($j['job_type']) ?></td>
        <td style="color:var(--muted);font-size:12px"><?= $j['deadline']?:'-' ?></td>
        <td><a href="/admin/applications.php?job_id=<?= $j['id'] ?>" class="badge badge-blue"><?= $j['app_count'] ?> apps</a></td>
        <td><?= $j['published']?'<span class="badge badge-green">Live</span>':'<span class="badge badge-gray">Draft</span>' ?></td>
        <td>
          <div class="tb-actions">
            <a href="?edit=<?= $j['id'] ?>" class="btn btn-secondary btn-sm">Edit</a>
            <form method="post"><input type="hidden" name="toggle_id" value="<?= $j['id'] ?>"><button class="btn btn-secondary btn-sm"><?= $j['published']?'Hide':'Publish' ?></button></form>
            <form method="post"><input type="hidden" name="delete_id" value="<?= $j['id'] ?>"><button class="btn btn-danger btn-sm" data-confirm="Delete this job?">Del</button></form>
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
  <div class="card-header"><h2><?= $edit?'Edit Job':'Add Job' ?></h2></div>
  <div class="card-body">
    <form method="post">
      <input type="hidden" name="save_job" value="1">
      <input type="hidden" name="job_id" value="<?= $edit['id']??0 ?>">
      <div class="field"><label>Job Title</label><input type="text" name="title" required value="<?= e($edit['title']??'') ?>"></div>
      <div class="field"><label>Department</label><input type="text" name="department" value="<?= e($edit['department']??'') ?>" placeholder="Audit, Tax, Advisory…"></div>
      <div class="field">
        <label>Job Type</label>
        <select name="job_type">
          <?php foreach(['Full-time','Part-time','Contract','Internship'] as $t): ?>
          <option value="<?= $t ?>" <?= ($edit['job_type']??'')===$t?'selected':'' ?>><?= $t ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="field"><label>Location</label><input type="text" name="location" value="<?= e($edit['location']??'Bangladesh') ?>"></div>
      <div class="field"><label>Application Deadline</label><input type="date" name="deadline" value="<?= e($edit['deadline']??'') ?>"></div>
      <div class="field"><label>Apply Email (for notifications)</label><input type="email" name="apply_email" value="<?= e($edit['apply_email']??'') ?>" placeholder="careers@artisancabd.com"></div>
      <div class="field"><label>Job Description</label><textarea name="description" rows="4"><?= e($edit['description']??'') ?></textarea></div>
      <div class="field"><label>Requirements (one per line)</label><textarea name="requirements" rows="4"><?= e($edit['requirements']??'') ?></textarea></div>
      <div class="field" style="flex-direction:row;align-items:center;gap:10px">
        <input type="checkbox" name="published" id="jpub" <?= ($edit['published']??0)?'checked':'' ?> style="width:auto">
        <label for="jpub" style="margin:0">Published / Live</label>
      </div>
      <div style="display:flex;gap:8px;margin-top:4px">
        <button class="btn btn-primary">Save Job</button>
        <?php if($edit): ?><a href="/admin/jobs.php" class="btn btn-secondary">Cancel</a><?php endif; ?>
      </div>
    </form>
  </div>
</div>
</div>
<?php include __DIR__ . '/inc/footer.php'; ?>
