<?php
require_once __DIR__ . '/inc/auth.php';
require_once __DIR__ . '/inc/functions.php';
requireAuth();
$pageTitle = 'Dashboard';

$db = db();
$stats = [
    'entries'  => $db->query("SELECT COUNT(*) FROM form_entries")->fetchColumn(),
    'unread'   => $db->query("SELECT COUNT(*) FROM form_entries WHERE read_at IS NULL")->fetchColumn(),
    'articles' => $db->query("SELECT COUNT(*) FROM articles WHERE published=1")->fetchColumn(),
    'jobs'     => $db->query("SELECT COUNT(*) FROM jobs WHERE published=1")->fetchColumn(),
    'apps'     => $db->query("SELECT COUNT(*) FROM job_applications WHERE status='new'")->fetchColumn(),
    'clients'  => $db->query("SELECT COUNT(*) FROM clients")->fetchColumn(),
    'gallery'  => $db->query("SELECT COUNT(*) FROM gallery")->fetchColumn(),
    'users'    => $db->query("SELECT COUNT(*) FROM users WHERE active=1")->fetchColumn(),
];
$recent_entries = $db->query("SELECT * FROM form_entries ORDER BY created_at DESC LIMIT 8")->fetchAll();
$recent_activity = $db->query("SELECT * FROM activity_log ORDER BY created_at DESC LIMIT 10")->fetchAll();

include __DIR__ . '/inc/header.php';
?>
<div class="stats-grid">
  <div class="stat-card">
    <div class="icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/></svg></div>
    <div class="num"><?= $stats['entries'] ?></div>
    <div class="lbl">Total Entries <?php if($stats['unread']>0): ?><span class="badge badge-yellow"><?= $stats['unread'] ?> new</span><?php endif; ?></div>
  </div>
  <div class="stat-card">
    <div class="icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg></div>
    <div class="num"><?= $stats['articles'] ?></div>
    <div class="lbl">Published Articles</div>
  </div>
  <div class="stat-card">
    <div class="icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 7V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v2"/></svg></div>
    <div class="num"><?= $stats['jobs'] ?></div>
    <div class="lbl">Active Jobs <?php if($stats['apps']>0): ?><span class="badge badge-blue"><?= $stats['apps'] ?> new apps</span><?php endif; ?></div>
  </div>
  <div class="stat-card">
    <div class="icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg></div>
    <div class="num"><?= $stats['gallery'] ?></div>
    <div class="lbl">Gallery Images</div>
  </div>
  <div class="stat-card">
    <div class="icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/></svg></div>
    <div class="num"><?= $stats['clients'] ?></div>
    <div class="lbl">Clients</div>
  </div>
  <div class="stat-card">
    <div class="icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg></div>
    <div class="num"><?= $stats['users'] ?></div>
    <div class="lbl">Admin Users</div>
  </div>
</div>

<div style="display:grid;grid-template-columns:1fr 340px;gap:20px">
<div class="card">
  <div class="card-header"><h2>Recent Form Entries</h2><a href="/admin/entries.php" class="btn btn-secondary btn-sm">View all</a></div>
  <div class="table-wrap">
  <?php if(empty($recent_entries)): ?>
    <div class="empty"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/></svg><p>No entries yet</p></div>
  <?php else: ?>
  <table>
    <thead><tr><th>Type</th><th>Data</th><th>Time</th><th></th></tr></thead>
    <tbody>
    <?php foreach($recent_entries as $e): $d=json_decode($e['data'],true)??[]; ?>
      <tr>
        <td><span class="badge badge-blue"><?= htmlspecialchars($e['form_type']) ?></span></td>
        <td><?= htmlspecialchars(truncate(implode(', ', array_filter(array_values($d))), 60)) ?></td>
        <td style="color:var(--muted);white-space:nowrap"><?= ago($e['created_at']) ?></td>
        <td><a href="/admin/entries.php?view=<?= $e['id'] ?>" class="btn btn-secondary btn-sm">View</a></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  <?php endif; ?>
  </div>
</div>
<div class="card">
  <div class="card-header"><h2>Activity Log</h2><a href="/admin/activity.php" class="btn btn-secondary btn-sm">All</a></div>
  <div class="card-body" style="padding:0">
  <?php foreach($recent_activity as $a): ?>
    <div style="padding:10px 16px;border-bottom:1px solid var(--border);display:flex;gap:10px;align-items:flex-start">
      <div style="width:28px;height:28px;border-radius:50%;background:var(--surface2);display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:700;flex:none;color:var(--muted)"><?= strtoupper(substr($a['user_name']??'?',0,1)) ?></div>
      <div style="min-width:0">
        <div style="font-size:12.5px"><?= e($a['user_name']) ?> <span style="color:var(--muted)"><?= e($a['action']) ?></span><?php if($a['target']): ?> <em style="color:var(--muted);font-size:11px"><?= e(truncate($a['target'],30)) ?></em><?php endif; ?></div>
        <div style="font-size:11px;color:var(--muted)"><?= ago($a['created_at']) ?></div>
      </div>
    </div>
  <?php endforeach; ?>
  <?php if(empty($recent_activity)): ?><div class="empty" style="padding:24px"><p>No activity yet</p></div><?php endif; ?>
  </div>
</div>
</div>
<?php include __DIR__ . '/inc/footer.php'; ?>
