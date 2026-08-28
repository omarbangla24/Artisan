<?php
require __DIR__ . '/inc/bootstrap.php';
require __DIR__ . '/inc/layout.php';
require_login();

$id = (int)($_GET['id'] ?? 0);
$stmt = db()->prepare('SELECT * FROM submissions WHERE id = ?');
$stmt->execute([$id]);
$s = $stmt->fetch();
if (!$s) { http_response_code(404); layout_header('Not found'); echo '<h1>Not found</h1>'; layout_footer(); exit; }

// Mark as read on first open.
if ($s['status'] === 'new') {
    db()->prepare("UPDATE submissions SET status='read' WHERE id=?")->execute([$id]);
    $s['status'] = 'read';
}

$fields = json_decode((string)$s['payload'], true);
if (!is_array($fields)) $fields = [];

layout_header('Submission #' . $id, 'submissions.php');
?>
<p><a href="submissions.php" class="muted">← Back to submissions</a></p>
<h1><?= e($s['subject'] ?: 'Submission') ?> <span class="badge <?= e($s['status']) ?>"><?= e($s['status']) ?></span></h1>

<div class="card">
  <dl class="dl">
    <div><dt>Received</dt><dd><?= e(date('d M Y, H:i', strtotime($s['created_at']))) ?></dd></div>
    <div><dt>Form</dt><dd><?= e($s['form_key']) ?></dd></div>
    <?php if ($s['name']): ?><div><dt>Name</dt><dd><?= e($s['name']) ?></dd></div><?php endif; ?>
    <?php if ($s['email']): ?><div><dt>Email</dt><dd><a href="mailto:<?= e($s['email']) ?>"><?= e($s['email']) ?></a></dd></div><?php endif; ?>
    <?php if ($s['phone']): ?><div><dt>Phone</dt><dd><?= e($s['phone']) ?></dd></div><?php endif; ?>
    <?php foreach ($fields as $k => $v):
        if (in_array($k, ['name','email','phone'], true)) continue;
        if ($v === '' || $v === null) continue; ?>
      <div><dt><?= e(ucwords(str_replace(['_','-'],' ',$k))) ?></dt><dd><?= nl2br(e(is_scalar($v) ? $v : json_encode($v))) ?></dd></div>
    <?php endforeach; ?>
    <div><dt>IP</dt><dd class="muted"><?= e($s['ip']) ?></dd></div>
  </dl>
</div>

<div class="row">
  <form method="post" action="submissions.php">
    <?= csrf_field() ?>
    <input type="hidden" name="id" value="<?= $id ?>">
    <input type="hidden" name="action" value="archive">
    <button class="btn ghost" type="submit">Archive</button>
  </form>
  <form method="post" action="submissions.php" onsubmit="return confirm('Delete this submission permanently?');">
    <?= csrf_field() ?>
    <input type="hidden" name="id" value="<?= $id ?>">
    <input type="hidden" name="action" value="delete">
    <button class="btn danger" type="submit">Delete</button>
  </form>
</div>
<?php layout_footer();
