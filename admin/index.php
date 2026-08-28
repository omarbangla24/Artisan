<?php
require __DIR__ . '/inc/bootstrap.php';
require __DIR__ . '/inc/layout.php';
require_login();

$pdo = db();
$total   = (int)$pdo->query('SELECT COUNT(*) FROM submissions')->fetchColumn();
$unread  = (int)$pdo->query("SELECT COUNT(*) FROM submissions WHERE status = 'new'")->fetchColumn();
$users   = (int)$pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
$recent  = $pdo->query('SELECT id, form_key, name, email, subject, status, created_at
                        FROM submissions ORDER BY id DESC LIMIT 8')->fetchAll();

layout_header('Dashboard', 'index.php');
?>
<h1>Dashboard</h1>
<div class="grid cols-3">
  <div class="stat"><div class="n"><?= $total ?></div><div class="l">Total submissions</div></div>
  <div class="stat"><div class="n"><?= $unread ?></div><div class="l">Unread</div></div>
  <div class="stat"><div class="n"><?= $users ?></div><div class="l">Users</div></div>
</div>

<h2 class="mt">Recent submissions</h2>
<div class="tablecard">
  <table>
    <thead><tr><th>When</th><th>Form</th><th>Name</th><th>Subject</th><th>Status</th><th></th></tr></thead>
    <tbody>
    <?php if (!$recent): ?>
      <tr><td colspan="6" class="muted">No submissions yet.</td></tr>
    <?php else: foreach ($recent as $r): ?>
      <tr>
        <td class="muted"><?= e(date('d M, H:i', strtotime($r['created_at']))) ?></td>
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
<p class="mt"><a class="btn ghost" href="submissions.php">All submissions →</a></p>
<?php layout_footer();
