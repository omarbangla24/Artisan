<?php
require __DIR__ . '/inc/bootstrap.php';
require __DIR__ . '/inc/layout.php';
require_login();

$pdo = db();

// ---- bulk / row actions --------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $id     = (int)($_POST['id'] ?? 0);
    $action = $_POST['action'] ?? '';
    if ($id > 0) {
        if ($action === 'read')     { $pdo->prepare("UPDATE submissions SET status='read' WHERE id=?")->execute([$id]); flash('Marked as read.', 'ok'); }
        elseif ($action === 'archive'){ $pdo->prepare("UPDATE submissions SET status='archived' WHERE id=?")->execute([$id]); flash('Archived.', 'ok'); }
        elseif ($action === 'delete') { $pdo->prepare('DELETE FROM submissions WHERE id=?')->execute([$id]); flash('Submission deleted.', 'ok'); }
    }
    redirect('submissions.php' . (!empty($_POST['ret']) ? ('?' . $_POST['ret']) : ''));
}

// ---- filters -------------------------------------------------------------
$status = $_GET['status'] ?? '';
$form   = $_GET['form'] ?? '';
$where  = []; $args = [];
if (in_array($status, ['new','read','archived'], true)) { $where[] = 'status = ?'; $args[] = $status; }
if ($form !== '' && preg_match('/^[a-z_]+$/', $form))   { $where[] = 'form_key = ?'; $args[] = $form; }
$sql = 'SELECT id, form_key, name, email, subject, status, created_at FROM submissions';
if ($where) $sql .= ' WHERE ' . implode(' AND ', $where);
$sql .= ' ORDER BY id DESC LIMIT 200';
$stmt = $pdo->prepare($sql); $stmt->execute($args);
$rows = $stmt->fetchAll();
$forms = $pdo->query('SELECT DISTINCT form_key FROM submissions ORDER BY form_key')->fetchAll(PDO::FETCH_COLUMN);
$ret = http_build_query(array_filter(['status'=>$status,'form'=>$form]));

layout_header('Submissions', 'submissions.php');
?>
<h1>Submissions</h1>
<div class="card" style="padding:14px 18px">
  <form method="get" class="row" style="align-items:flex-end;margin:0">
    <div class="field" style="margin:0">
      <label for="status">Status</label>
      <select id="status" name="status" onchange="this.form.submit()">
        <option value="">All</option>
        <?php foreach (['new','read','archived'] as $s): ?>
          <option value="<?= $s ?>"<?= $status===$s?' selected':'' ?>><?= ucfirst($s) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="field" style="margin:0">
      <label for="form">Form</label>
      <select id="form" name="form" onchange="this.form.submit()">
        <option value="">All</option>
        <?php foreach ($forms as $fk): ?>
          <option value="<?= e($fk) ?>"<?= $form===$fk?' selected':'' ?>><?= e($fk) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <noscript><button class="btn sm">Filter</button></noscript>
  </form>
</div>

<div class="tablecard">
  <table>
    <thead><tr><th>When</th><th>Form</th><th>From</th><th>Subject</th><th>Status</th><th></th></tr></thead>
    <tbody>
    <?php if (!$rows): ?>
      <tr><td colspan="6" class="muted">No submissions match.</td></tr>
    <?php else: foreach ($rows as $r): ?>
      <tr>
        <td class="muted"><?= e(date('d M Y, H:i', strtotime($r['created_at']))) ?></td>
        <td><?= e($r['form_key']) ?></td>
        <td><?= e($r['name'] ?: '—') ?><div class="muted"><?= e($r['email']) ?></div></td>
        <td><?= e($r['subject']) ?></td>
        <td><span class="badge <?= e($r['status']) ?>"><?= e($r['status']) ?></span></td>
        <td class="right"><a class="btn sm ghost" href="submission-view.php?id=<?= (int)$r['id'] ?>">View</a></td>
      </tr>
    <?php endforeach; endif; ?>
    </tbody>
  </table>
</div>
<p class="hint mt">Showing up to 200 most recent.</p>
<?php layout_footer();
