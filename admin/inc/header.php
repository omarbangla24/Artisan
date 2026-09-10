<?php
$_page = basename($_SERVER['PHP_SELF'], '.php');
$user  = currentUser();
?><!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= e($pageTitle ?? 'Admin') ?> — ARTISAN Admin</title>
<link rel="icon" href="/assets/img/logo.png">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{
  --bg:#0f172a;--surface:#1e293b;--surface2:#273448;--border:#334155;
  --brand:#1B61A9;--brand-l:#2d7dd2;--text:#e2e8f0;--muted:#94a3b8;
  --danger:#ef4444;--success:#22c55e;--warning:#f59e0b;--info:#3b82f6;
  --r:8px;--sidebar:240px;
}
body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;background:var(--bg);color:var(--text);font-size:14px;line-height:1.5;min-height:100vh}
a{color:var(--brand-l);text-decoration:none}
a:hover{color:#fff}
img{max-width:100%}
/* Layout */
.admin-wrap{display:flex;min-height:100vh}
/* Sidebar */
.sidebar{width:var(--sidebar);background:var(--surface);border-right:1px solid var(--border);display:flex;flex-direction:column;position:fixed;top:0;left:0;height:100vh;overflow-y:auto;z-index:50;transition:.3s}
.sidebar-brand{padding:20px 16px;border-bottom:1px solid var(--border)}
.sidebar-brand img{height:32px;filter:brightness(0) invert(1);opacity:.9}
.sidebar-nav{padding:12px 0;flex:1}
.nav-section{padding:6px 16px 4px;font-size:10px;letter-spacing:.1em;text-transform:uppercase;color:var(--muted)}
.nav-link{display:flex;align-items:center;gap:10px;padding:9px 16px;color:var(--muted);border-left:3px solid transparent;transition:.2s;font-size:13.5px}
.nav-link svg{width:16px;height:16px;flex:none;opacity:.8}
.nav-link:hover{color:var(--text);background:var(--surface2)}
.nav-link.active{color:#fff;border-left-color:var(--brand);background:var(--surface2)}
.nav-link .badge{margin-left:auto;background:var(--brand);color:#fff;font-size:10px;padding:1px 6px;border-radius:10px}
.sidebar-footer{padding:12px 16px;border-top:1px solid var(--border);font-size:12px;color:var(--muted)}
.sidebar-footer strong{display:block;color:var(--text);font-size:13px}
.sidebar-footer a{color:var(--muted);font-size:12px}
/* Main */
.main{margin-left:var(--sidebar);flex:1;display:flex;flex-direction:column;min-width:0}
.topbar{background:var(--surface);border-bottom:1px solid var(--border);padding:12px 24px;display:flex;align-items:center;gap:16px;position:sticky;top:0;z-index:40}
.topbar h1{font-size:16px;font-weight:600;flex:1}
.topbar-actions{display:flex;gap:8px;align-items:center}
.content{padding:24px;flex:1}
/* Cards */
.card{background:var(--surface);border:1px solid var(--border);border-radius:var(--r);overflow:hidden}
.card-header{padding:14px 18px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;gap:12px}
.card-header h2{font-size:14px;font-weight:600}
.card-body{padding:18px}
/* Stats */
.stats-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:16px;margin-bottom:24px}
.stat-card{background:var(--surface);border:1px solid var(--border);border-radius:var(--r);padding:18px}
.stat-card .num{font-size:28px;font-weight:700;line-height:1}
.stat-card .lbl{font-size:12px;color:var(--muted);margin-top:4px}
.stat-card .icon{float:right;opacity:.4;margin-top:-4px}
.stat-card .icon svg{width:28px;height:28px}
/* Buttons */
.btn{display:inline-flex;align-items:center;gap:6px;padding:7px 14px;border-radius:var(--r);border:1px solid transparent;cursor:pointer;font-size:13px;font-weight:500;transition:.2s;white-space:nowrap}
.btn-primary{background:var(--brand);color:#fff;border-color:var(--brand)}
.btn-primary:hover{background:var(--brand-l)}
.btn-secondary{background:var(--surface2);color:var(--text);border-color:var(--border)}
.btn-secondary:hover{border-color:var(--muted)}
.btn-danger{background:transparent;color:var(--danger);border-color:var(--danger)}
.btn-danger:hover{background:var(--danger);color:#fff}
.btn-success{background:var(--success);color:#fff}
.btn-sm{padding:4px 10px;font-size:12px}
.btn-icon{padding:6px;border-radius:var(--r);border:1px solid var(--border);background:transparent;color:var(--muted);cursor:pointer;transition:.2s;display:inline-flex;align-items:center}
.btn-icon:hover{color:var(--text);border-color:var(--muted)}
/* Table */
.table-wrap{overflow-x:auto}
table{width:100%;border-collapse:collapse}
th,td{padding:10px 14px;text-align:left;border-bottom:1px solid var(--border)}
th{font-size:11px;text-transform:uppercase;letter-spacing:.05em;color:var(--muted);font-weight:600;white-space:nowrap}
tr:last-child td{border-bottom:0}
tr:hover td{background:var(--surface2)}
.tb-actions{display:flex;gap:6px}
/* Forms */
.form-grid{display:grid;gap:16px;grid-template-columns:1fr 1fr}
.form-grid-2{grid-template-columns:1fr 1fr}
.field.is-full{grid-column:1/-1}
label{display:block;font-size:12px;color:var(--muted);margin-bottom:5px;font-weight:500}
input[type=text],input[type=email],input[type=password],input[type=tel],input[type=url],input[type=date],input[type=number],select,textarea{width:100%;background:var(--bg);border:1px solid var(--border);border-radius:var(--r);color:var(--text);padding:8px 12px;font-size:13px;transition:.2s;outline:none;font-family:inherit}
input:focus,select:focus,textarea:focus{border-color:var(--brand)}
textarea{resize:vertical;min-height:100px}
.field{margin-bottom:0}
/* Alerts */
.alert{padding:10px 14px;border-radius:var(--r);font-size:13px;margin-bottom:16px;border:1px solid}
.alert-success{background:#052e16;color:#86efac;border-color:#166534}
.alert-danger{background:#2d0000;color:#fca5a5;border-color:#7f1d1d}
.alert-info{background:#0c1a2e;color:#93c5fd;border-color:#1e3a5f}
/* Badges */
.badge{display:inline-block;padding:2px 8px;border-radius:20px;font-size:11px;font-weight:600}
.badge-green{background:#052e16;color:#86efac}
.badge-red{background:#2d0000;color:#fca5a5}
.badge-yellow{background:#2d1800;color:#fcd34d}
.badge-blue{background:#0c1a2e;color:#93c5fd}
.badge-gray{background:var(--surface2);color:var(--muted)}
/* Search/Filter bar */
.filter-bar{display:flex;gap:10px;margin-bottom:16px;flex-wrap:wrap}
.filter-bar input,.filter-bar select{width:auto;flex:1;min-width:150px}
/* Modal */
.modal-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.7);z-index:100;align-items:center;justify-content:center}
.modal-overlay.open{display:flex}
.modal{background:var(--surface);border:1px solid var(--border);border-radius:12px;width:90%;max-width:560px;max-height:90vh;overflow-y:auto}
.modal-head{padding:16px 20px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between}
.modal-head h3{font-size:15px;font-weight:600}
.modal-body{padding:20px}
.modal-foot{padding:14px 20px;border-top:1px solid var(--border);display:flex;justify-content:flex-end;gap:8px}
/* Pager */
.pager{display:flex;gap:4px;margin-top:16px}
.pager-btn{padding:5px 10px;border:1px solid var(--border);border-radius:var(--r);font-size:12px;color:var(--muted)}
.pager-btn.active{background:var(--brand);color:#fff;border-color:var(--brand)}
/* Empty state */
.empty{text-align:center;padding:48px;color:var(--muted)}
.empty svg{width:40px;height:40px;margin:0 auto 12px;display:block;opacity:.4}
/* Image preview */
.img-thumb{width:52px;height:40px;object-fit:cover;border-radius:4px;border:1px solid var(--border)}
/* Toggle */
.toggle{position:relative;display:inline-block;width:36px;height:20px}
.toggle input{opacity:0;width:0;height:0}
.toggle-slider{position:absolute;inset:0;background:var(--border);border-radius:20px;cursor:pointer;transition:.3s}
.toggle-slider::before{content:'';position:absolute;width:14px;height:14px;left:3px;top:3px;background:#fff;border-radius:50%;transition:.3s}
.toggle input:checked + .toggle-slider{background:var(--brand)}
.toggle input:checked + .toggle-slider::before{transform:translateX(16px)}
/* Rich editor */
#editor{background:var(--bg);border:1px solid var(--border);border-radius:var(--r);color:var(--text);padding:12px;min-height:300px;outline:none;font-size:13px;line-height:1.7}
.editor-toolbar{display:flex;gap:4px;padding:8px;border:1px solid var(--border);border-bottom:0;border-radius:var(--r) var(--r) 0 0;background:var(--surface2);flex-wrap:wrap}
.editor-toolbar button{padding:4px 8px;background:transparent;border:1px solid transparent;border-radius:4px;color:var(--text);cursor:pointer;font-size:12px}
.editor-toolbar button:hover{background:var(--border)}
@media(max-width:768px){
  .sidebar{transform:translateX(-100%)}
  .sidebar.open{transform:translateX(0)}
  .main{margin-left:0}
  .form-grid-2{grid-template-columns:1fr}
}
</style>
</head>
<body>
<div class="admin-wrap">
<aside class="sidebar" id="sidebar">
  <div class="sidebar-brand">
    <img src="/assets/img/logo.png" alt="ARTISAN">
  </div>
  <nav class="sidebar-nav">
    <div class="nav-section">Main</div>
    <a href="/admin/" class="nav-link <?= $_page==='index'?'active':'' ?>">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg>Dashboard</a>
    <a href="/admin/entries.php" class="nav-link <?= $_page==='entries'?'active':'' ?>">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>Form Entries
      <?php $uc=db()->query("SELECT COUNT(*) FROM form_entries WHERE read_at IS NULL")->fetchColumn(); if($uc>0) echo "<span class='badge'>$uc</span>"; ?></a>
    <div class="nav-section">Content</div>
    <a href="/admin/articles.php" class="nav-link <?= $_page==='articles'||$_page==='article-edit'?'active':'' ?>">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>Articles</a>
    <a href="/admin/jobs.php" class="nav-link <?= $_page==='jobs'||$_page==='applications'?'active':'' ?>">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 7V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v2"/></svg>Careers</a>
    <a href="/admin/gallery.php" class="nav-link <?= $_page==='gallery'?'active':'' ?>">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>Gallery</a>
    <div class="nav-section">People</div>
    <a href="/admin/partners.php" class="nav-link <?= $_page==='partners'?'active':'' ?>">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>Partners</a>
    <a href="/admin/team.php" class="nav-link <?= $_page==='team'?'active':'' ?>">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>Team</a>
    <a href="/admin/clients.php" class="nav-link <?= $_page==='clients'?'active':'' ?>">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>Clients</a>
    <div class="nav-section">System</div>
    <a href="/admin/seo.php" class="nav-link <?= $_page==='seo'?'active':'' ?>">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>SEO</a>
    <a href="/admin/smtp.php" class="nav-link <?= $_page==='smtp'?'active':'' ?>">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-10 6L2 7"/></svg>SMTP / Email</a>
    <?php if(($user['role']??'')==='superadmin'): ?>
    <a href="/admin/users.php" class="nav-link <?= $_page==='users'?'active':'' ?>">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><line x1="23" y1="11" x2="17" y2="11"/><line x1="20" y1="8" x2="20" y2="14"/></svg>Users</a>
    <?php endif; ?>
    <a href="/admin/activity.php" class="nav-link <?= $_page==='activity'?'active':'' ?>">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>Activity Log</a>
  </nav>
  <div class="sidebar-footer">
    <strong><?= e($user['name']) ?></strong>
    <span><?= e($user['role']) ?></span><br>
    <a href="/admin/logout.php">Sign out</a>
    <a href="/" target="_blank" style="float:right">↗ Site</a>
  </div>
</aside>
<div class="main">
<div class="topbar">
  <button onclick="document.getElementById('sidebar').classList.toggle('open')" style="background:none;border:none;color:var(--text);cursor:pointer;display:none" id="menu-btn">
    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
  </button>
  <h1><?= e($pageTitle ?? 'Dashboard') ?></h1>
  <div class="topbar-actions">
    <a href="/" target="_blank" class="btn btn-secondary btn-sm">↗ View Site</a>
  </div>
</div>
<div class="content">
