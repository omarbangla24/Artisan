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
  --bg:#f0f4f8;
  --surface:#ffffff;
  --surface2:#f7f9fc;
  --border:#e3e8ef;
  --brand:#1B61A9;
  --brand-d:#154e8a;
  --brand-l:#e8f0fb;
  --text:#111827;
  --muted:#6b7280;
  --muted-l:#9ca3af;
  --danger:#dc2626;
  --danger-l:#fef2f2;
  --success:#16a34a;
  --success-l:#f0fdf4;
  --warning:#d97706;
  --warning-l:#fffbeb;
  --info:#2563eb;
  --info-l:#eff6ff;
  --r:10px;
  --r-sm:6px;
  --sidebar:252px;
  --shadow:0 1px 3px rgba(0,0,0,.08),0 1px 2px rgba(0,0,0,.04);
  --shadow-md:0 4px 12px rgba(0,0,0,.08),0 2px 4px rgba(0,0,0,.04);
}
body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Inter,sans-serif;background:var(--bg);color:var(--text);font-size:14px;line-height:1.55;min-height:100vh}
a{color:var(--brand);text-decoration:none}
a:hover{color:var(--brand-d)}
img{max-width:100%}

/* ─── Layout ─── */
.admin-wrap{display:flex;min-height:100vh}

/* ─── Sidebar ─── */
.sidebar{
  width:var(--sidebar);background:var(--brand);
  display:flex;flex-direction:column;
  position:fixed;top:0;left:0;height:100vh;
  overflow-y:auto;z-index:50;transition:transform .25s ease;
  box-shadow:2px 0 8px rgba(0,0,0,.12);
}
.sidebar-brand{
  padding:22px 18px 18px;
  border-bottom:1px solid rgba(255,255,255,.12);
}
.sidebar-brand img{height:34px;filter:brightness(0) invert(1);display:block}
.sidebar-nav{padding:10px 0;flex:1}
.nav-section{
  padding:14px 18px 5px;
  font-size:10px;letter-spacing:.08em;text-transform:uppercase;
  color:rgba(255,255,255,.45);font-weight:600;
}
.nav-link{
  display:flex;align-items:center;gap:10px;
  padding:9px 18px;margin:1px 8px;border-radius:var(--r-sm);
  color:rgba(255,255,255,.75);
  transition:background .15s,color .15s;font-size:13.5px;font-weight:500;
}
.nav-link svg{width:16px;height:16px;flex:none}
.nav-link:hover{color:#fff;background:rgba(255,255,255,.12)}
.nav-link.active{color:#fff;background:rgba(255,255,255,.18);font-weight:600}
.nav-link .nbadge{
  margin-left:auto;background:#ef4444;color:#fff;
  font-size:10px;font-weight:700;padding:1px 6px;border-radius:20px;min-width:18px;text-align:center;
}
.sidebar-footer{
  padding:14px 18px;
  border-top:1px solid rgba(255,255,255,.12);
}
.sidebar-user{display:flex;align-items:center;gap:10px}
.sidebar-avatar{
  width:32px;height:32px;border-radius:50%;
  background:rgba(255,255,255,.2);
  display:flex;align-items:center;justify-content:center;
  font-size:13px;font-weight:700;color:#fff;flex:none;
}
.sidebar-user-info strong{display:block;color:#fff;font-size:12.5px;font-weight:600}
.sidebar-user-info span{font-size:11px;color:rgba(255,255,255,.55);text-transform:capitalize}
.sidebar-links{display:flex;gap:12px;margin-top:10px}
.sidebar-links a{font-size:11.5px;color:rgba(255,255,255,.55)}
.sidebar-links a:hover{color:#fff}

/* ─── Main ─── */
.main{margin-left:var(--sidebar);flex:1;display:flex;flex-direction:column;min-width:0}
.topbar{
  background:var(--surface);border-bottom:1px solid var(--border);
  padding:0 24px;height:58px;
  display:flex;align-items:center;gap:16px;
  position:sticky;top:0;z-index:40;
  box-shadow:0 1px 0 var(--border);
}
.topbar h1{font-size:15px;font-weight:600;flex:1;color:var(--text)}
.topbar-actions{display:flex;gap:8px;align-items:center}
.breadcrumb{font-size:12px;color:var(--muted);display:flex;align-items:center;gap:6px}
.breadcrumb::before{content:'ARTISAN';opacity:.5}
.breadcrumb::after{content:'›  ';opacity:.4}
#menu-btn{background:none;border:none;color:var(--muted);cursor:pointer;padding:6px;border-radius:6px;display:none}
#menu-btn:hover{background:var(--surface2)}
.content{padding:24px;flex:1}

/* ─── Cards ─── */
.card{
  background:var(--surface);border:1px solid var(--border);
  border-radius:var(--r);overflow:hidden;
  box-shadow:var(--shadow);
}
.card-header{
  padding:14px 20px;border-bottom:1px solid var(--border);
  display:flex;align-items:center;justify-content:space-between;gap:12px;
  background:var(--surface);
}
.card-header h2{font-size:14px;font-weight:600;color:var(--text)}
.card-body{padding:20px}

/* ─── Stats ─── */
.stats-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:16px;margin-bottom:24px}
.stat-card{
  background:var(--surface);border:1px solid var(--border);border-radius:var(--r);
  padding:20px;box-shadow:var(--shadow);position:relative;overflow:hidden;
}
.stat-card::before{content:'';position:absolute;top:0;left:0;right:0;height:3px;background:var(--brand);opacity:.7}
.stat-card .num{font-size:30px;font-weight:700;color:var(--text);line-height:1.1;margin-bottom:4px}
.stat-card .lbl{font-size:12px;color:var(--muted);display:flex;align-items:center;gap:6px;flex-wrap:wrap}
.stat-card .icon{position:absolute;right:16px;top:50%;transform:translateY(-50%);opacity:.08}
.stat-card .icon svg{width:44px;height:44px;color:var(--brand)}

/* ─── Buttons ─── */
.btn{
  display:inline-flex;align-items:center;gap:6px;
  padding:8px 16px;border-radius:var(--r-sm);
  border:1px solid transparent;cursor:pointer;
  font-size:13px;font-weight:500;transition:all .15s;
  white-space:nowrap;text-decoration:none;font-family:inherit;
}
.btn-primary{background:var(--brand);color:#fff;border-color:var(--brand)}
.btn-primary:hover{background:var(--brand-d);border-color:var(--brand-d);color:#fff}
.btn-secondary{
  background:var(--surface);color:var(--text);
  border-color:var(--border);box-shadow:0 1px 2px rgba(0,0,0,.05);
}
.btn-secondary:hover{background:var(--surface2);border-color:#c8d0db;color:var(--text)}
.btn-danger{background:var(--danger-l);color:var(--danger);border-color:#fecaca}
.btn-danger:hover{background:var(--danger);color:#fff;border-color:var(--danger)}
.btn-success{background:var(--success-l);color:var(--success);border-color:#bbf7d0}
.btn-success:hover{background:var(--success);color:#fff}
.btn-sm{padding:5px 11px;font-size:12px;border-radius:var(--r-sm)}
.btn-icon{
  padding:7px;border-radius:var(--r-sm);border:1px solid var(--border);
  background:var(--surface);color:var(--muted);cursor:pointer;
  transition:.15s;display:inline-flex;align-items:center;box-shadow:0 1px 2px rgba(0,0,0,.04);
}
.btn-icon:hover{color:var(--text);border-color:#c8d0db}

/* ─── Table ─── */
.table-wrap{overflow-x:auto}
table{width:100%;border-collapse:collapse}
th,td{padding:11px 16px;text-align:left;border-bottom:1px solid var(--border)}
th{
  font-size:11px;text-transform:uppercase;letter-spacing:.06em;
  color:var(--muted);font-weight:600;white-space:nowrap;
  background:var(--surface2);
}
thead th:first-child{border-radius:0}
tr:last-child td{border-bottom:0}
tr:hover td{background:#fafbfd}
.tb-actions{display:flex;gap:6px;align-items:center}

/* ─── Forms ─── */
.form-grid{display:grid;gap:16px;grid-template-columns:1fr 1fr}
.form-grid-2{grid-template-columns:1fr 1fr}
.field{margin-bottom:0;display:flex;flex-direction:column;gap:5px}
.field.is-full{grid-column:1/-1}
label{font-size:12px;color:var(--muted);font-weight:500}
input[type=text],input[type=email],input[type=password],input[type=tel],
input[type=url],input[type=date],input[type=number],select,textarea{
  width:100%;background:var(--surface);
  border:1px solid var(--border);border-radius:var(--r-sm);
  color:var(--text);padding:8px 12px;font-size:13.5px;
  transition:border-color .15s,box-shadow .15s;outline:none;font-family:inherit;
  box-shadow:0 1px 2px rgba(0,0,0,.04);
}
input:focus,select:focus,textarea:focus{
  border-color:var(--brand);
  box-shadow:0 0 0 3px rgba(27,97,169,.12);
}
input[type=file]{padding:6px 8px;background:var(--surface2);cursor:pointer}
textarea{resize:vertical;min-height:100px}
select{appearance:auto}

/* ─── Alerts ─── */
.alert{
  padding:11px 16px;border-radius:var(--r-sm);font-size:13px;
  margin-bottom:16px;border:1px solid;display:flex;align-items:center;gap:8px;
}
.alert-success{background:var(--success-l);color:#15803d;border-color:#bbf7d0}
.alert-danger{background:var(--danger-l);color:var(--danger);border-color:#fecaca}
.alert-info{background:var(--info-l);color:var(--info);border-color:#bfdbfe}
.alert-warn{background:var(--warning-l);color:var(--warning);border-color:#fed7aa}

/* ─── Badges ─── */
.badge{
  display:inline-flex;align-items:center;gap:3px;
  padding:2px 9px;border-radius:20px;font-size:11px;font-weight:600;
  white-space:nowrap;
}
.badge-green{background:var(--success-l);color:#15803d}
.badge-red{background:var(--danger-l);color:var(--danger)}
.badge-yellow{background:var(--warning-l);color:#92400e}
.badge-blue{background:var(--info-l);color:var(--info)}
.badge-gray{background:#f3f4f6;color:#4b5563}
.badge-brand{background:var(--brand-l);color:var(--brand)}

/* ─── Filter bar ─── */
.filter-bar{display:flex;gap:10px;margin-bottom:16px;flex-wrap:wrap;align-items:flex-end}
.filter-bar input,.filter-bar select{width:auto;flex:1;min-width:150px}

/* ─── Pager ─── */
.pager{display:flex;gap:4px;flex-wrap:wrap}
.pager-btn{
  padding:5px 11px;border:1px solid var(--border);border-radius:var(--r-sm);
  font-size:12px;color:var(--muted);background:var(--surface);
  transition:.15s;text-decoration:none;
}
.pager-btn:hover{border-color:#c8d0db;color:var(--text)}
.pager-btn.active{background:var(--brand);color:#fff;border-color:var(--brand)}

/* ─── Empty state ─── */
.empty{text-align:center;padding:56px 24px;color:var(--muted-l)}
.empty svg{width:40px;height:40px;margin:0 auto 14px;display:block;stroke:currentColor;opacity:.5}
.empty p{font-size:14px}

/* ─── Rich editor ─── */
#editor{
  background:var(--surface);border:1px solid var(--border);border-radius:0 0 var(--r-sm) var(--r-sm);
  color:var(--text);padding:14px;min-height:300px;outline:none;font-size:14px;line-height:1.7;
}
#editor:focus{border-color:var(--brand);box-shadow:0 0 0 3px rgba(27,97,169,.1)}

/* ─── Section divider ─── */
.section-head{
  font-size:11px;text-transform:uppercase;letter-spacing:.07em;
  color:var(--muted);font-weight:600;margin-bottom:12px;
}

/* ─── Responsive ─── */
@media(max-width:900px){
  .sidebar{transform:translateX(-100%);box-shadow:none}
  .sidebar.open{transform:translateX(0);box-shadow:var(--shadow-md)}
  .main{margin-left:0}
  .form-grid,.form-grid-2{grid-template-columns:1fr}
  #menu-btn{display:block}
  .content{padding:16px}
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
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>
      Dashboard</a>
    <a href="/admin/entries.php" class="nav-link <?= $_page==='entries'?'active':'' ?>">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
      Form Entries
      <?php try{ $uc=db()->query("SELECT COUNT(*) FROM form_entries WHERE read_at IS NULL")->fetchColumn(); if($uc>0) echo "<span class='nbadge'>$uc</span>"; }catch(\Throwable $e){} ?></a>

    <div class="nav-section">Content</div>
    <a href="/admin/articles.php" class="nav-link <?= $_page==='articles'||$_page==='article-edit'?'active':'' ?>">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
      Articles</a>
    <a href="/admin/jobs.php" class="nav-link <?= $_page==='jobs'||$_page==='applications'?'active':'' ?>">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 7V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v2"/></svg>
      Careers</a>
    <a href="/admin/gallery.php" class="nav-link <?= $_page==='gallery'?'active':'' ?>">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
      Gallery</a>

    <div class="nav-section">People</div>
    <a href="/admin/team.php" class="nav-link <?= $_page==='team'?'active':'' ?>">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
      Partners</a>
    <a href="/admin/partners.php" class="nav-link <?= $_page==='partners'?'active':'' ?>">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="5"/><path d="M20 21a8 8 0 1 0-16 0"/></svg>
      Affiliations</a>
    <a href="/admin/clients.php" class="nav-link <?= $_page==='clients'?'active':'' ?>">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
      Clients</a>

    <div class="nav-section">System</div>
    <a href="/admin/seo.php" class="nav-link <?= $_page==='seo'?'active':'' ?>">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
      SEO</a>
    <a href="/admin/smtp.php" class="nav-link <?= $_page==='smtp'?'active':'' ?>">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-10 6L2 7"/></svg>
      SMTP / Email</a>
    <?php if(($user['role']??'')==='superadmin'): ?>
    <a href="/admin/users.php" class="nav-link <?= $_page==='users'?'active':'' ?>">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><line x1="23" y1="11" x2="17" y2="11"/><line x1="20" y1="8" x2="20" y2="14"/></svg>
      Users</a>
    <?php endif; ?>
    <a href="/admin/activity.php" class="nav-link <?= $_page==='activity'?'active':'' ?>">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
      Activity Log</a>
  </nav>
  <div class="sidebar-footer">
    <div class="sidebar-user">
      <div class="sidebar-avatar"><?= strtoupper(substr($user['name']??'A',0,1)) ?></div>
      <div class="sidebar-user-info">
        <strong><?= e($user['name']) ?></strong>
        <span><?= e($user['role']) ?></span>
      </div>
    </div>
    <div class="sidebar-links">
      <a href="/admin/logout.php">Sign out</a>
      <a href="/" target="_blank">↗ View Site</a>
    </div>
  </div>
</aside>

<div class="main">
<div class="topbar">
  <button onclick="document.getElementById('sidebar').classList.toggle('open')" id="menu-btn" aria-label="Menu">
    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
  </button>
  <h1><?= e($pageTitle ?? 'Dashboard') ?></h1>
  <div class="topbar-actions">
    <a href="/" target="_blank" class="btn btn-secondary btn-sm">
      <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
      View Site</a>
  </div>
</div>
<div class="content">
