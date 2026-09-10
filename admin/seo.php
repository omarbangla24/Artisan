<?php
require_once __DIR__ . '/inc/auth.php';
require_once __DIR__ . '/inc/functions.php';
requireAuth();
$pageTitle = 'SEO Settings';
$db = db();
$msg = '';

// Site-wide defaults
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['save_global'])) {
    foreach(['seo_site_name','seo_default_title','seo_default_desc','seo_og_image','seo_ga_id','seo_gtm_id','seo_fb_pixel'] as $k) {
        saveSetting($k, trim($_POST[$k]??''));
    }
    logActivity('Updated global SEO settings');
    $msg = 'Global SEO settings saved.';
}
// Per-page row
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['save_page'])) {
    $slug  = trim($_POST['page_slug']??'');
    $title = trim($_POST['page_title']??'');
    $desc  = trim($_POST['page_desc']??'');
    $keywords = trim($_POST['page_keywords']??'');
    $og_image = trim($_POST['page_og_image']??'');
    if ($slug) {
        $stmt=$db->prepare("SELECT id FROM seo_pages WHERE slug=?"); $stmt->execute([$slug]); $row=$stmt->fetch();
        if ($row) {
            $db->prepare("UPDATE seo_pages SET title=?,description=?,keywords=?,og_image=? WHERE slug=?")->execute([$title,$desc,$keywords,$og_image,$slug]);
        } else {
            $db->prepare("INSERT INTO seo_pages (slug,title,description,keywords,og_image) VALUES (?,?,?,?,?)")->execute([$slug,$title,$desc,$keywords,$og_image]);
        }
        logActivity('Updated page SEO',$slug);
        $msg='Page SEO saved.';
    }
}
// Delete page row
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['delete_seo'])) {
    $db->prepare("DELETE FROM seo_pages WHERE id=?")->execute([(int)$_POST['delete_seo']]);
    $msg='SEO entry removed.';
}

$edit_page=null;
if(isset($_GET['edit_page'])){ $s=$db->prepare("SELECT * FROM seo_pages WHERE id=?"); $s->execute([(int)$_GET['edit_page']]); $edit_page=$s->fetch(); }
$pages=$db->query("SELECT * FROM seo_pages ORDER BY slug")->fetchAll();
$s=function($k){return htmlspecialchars(setting($k));};

$knownPages=['index'=>'Home','about'=>'About','services'=>'Services','career'=>'Career','contact'=>'Contact','client'=>'Clients','affiliation'=>'Affiliation & Enlistment','articles'=>'Articles','gallery'=>'Gallery','audit'=>'Audit & Assurance','tax'=>'Tax & Regulatory','corporate-finance'=>'Corporate Finance','bpo'=>'BPO','risk'=>'Risk & Advisory','team'=>'Our Team'];
include __DIR__ . '/inc/header.php';
?>
<?php if($msg): ?><div class="alert alert-success"><?= e($msg) ?></div><?php endif; ?>

<div class="card" style="margin-bottom:20px">
  <div class="card-header"><h2>Global SEO Defaults</h2></div>
  <div class="card-body">
    <form method="post">
      <input type="hidden" name="save_global" value="1">
      <div class="form-grid">
        <div class="field">
          <label>Site Name</label>
          <input type="text" name="seo_site_name" value="<?= $s('seo_site_name') ?: 'ARTISAN Chartered Accountants' ?>">
        </div>
        <div class="field">
          <label>Default Meta Title Pattern</label>
          <input type="text" name="seo_default_title" value="<?= $s('seo_default_title') ?>" placeholder="Page Name — ARTISAN Chartered Accountants">
        </div>
        <div class="field is-full">
          <label>Default Meta Description</label>
          <textarea name="seo_default_desc" rows="2" placeholder="150–160 chars"><?= $s('seo_default_desc') ?></textarea>
        </div>
        <div class="field">
          <label>Default OG Image URL</label>
          <input type="text" name="seo_og_image" value="<?= $s('seo_og_image') ?>" placeholder="/assets/img/og-default.jpg">
        </div>
        <div class="field">
          <label>Google Analytics ID</label>
          <input type="text" name="seo_ga_id" value="<?= $s('seo_ga_id') ?>" placeholder="G-XXXXXXXXXX">
        </div>
        <div class="field">
          <label>Google Tag Manager ID</label>
          <input type="text" name="seo_gtm_id" value="<?= $s('seo_gtm_id') ?>" placeholder="GTM-XXXXXXX">
        </div>
        <div class="field">
          <label>Facebook Pixel ID</label>
          <input type="text" name="seo_fb_pixel" value="<?= $s('seo_fb_pixel') ?>" placeholder="000000000000000">
        </div>
      </div>
      <button class="btn btn-primary" style="margin-top:8px">Save Global Settings</button>
    </form>
  </div>
</div>

<div style="display:grid;grid-template-columns:1fr 320px;gap:20px;align-items:start">
<div class="card">
  <div class="card-header"><h2>Per-Page SEO</h2></div>
  <div class="table-wrap">
  <?php if(empty($pages)): ?><div class="empty"><p>No page overrides set.</p></div>
  <?php else: ?>
  <table>
    <thead><tr><th>Page</th><th>Title</th><th>Description</th><th>Actions</th></tr></thead>
    <tbody>
    <?php foreach($pages as $p): ?>
      <tr>
        <td><code><?= e($p['slug']) ?></code></td>
        <td style="font-size:12px"><?= e(truncate($p['title'],40)) ?></td>
        <td style="color:var(--muted);font-size:12px"><?= e(truncate($p['description'],50)) ?></td>
        <td>
          <div class="tb-actions">
            <a href="?edit_page=<?= $p['id'] ?>" class="btn btn-secondary btn-sm">Edit</a>
            <form method="post"><input type="hidden" name="delete_seo" value="<?= $p['id'] ?>"><button class="btn btn-danger btn-sm" data-confirm="Remove this SEO override?">Del</button></form>
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
  <div class="card-header"><h2><?= $edit_page?'Edit Page SEO':'Add Page SEO' ?></h2></div>
  <div class="card-body">
    <form method="post">
      <input type="hidden" name="save_page" value="1">
      <div class="field">
        <label>Page Slug</label>
        <?php if($edit_page): ?>
        <input type="text" name="page_slug" value="<?= e($edit_page['slug']) ?>" readonly>
        <?php else: ?>
        <select name="page_slug">
          <option value="">-- Choose page --</option>
          <?php foreach($knownPages as $slug=>$label): ?>
          <option value="<?= $slug ?>"><?= $label ?> (<?= $slug ?>)</option>
          <?php endforeach; ?>
          <option value="__custom">Custom slug…</option>
        </select>
        <?php endif; ?>
      </div>
      <div class="field"><label>Meta Title</label><input type="text" name="page_title" value="<?= e($edit_page['title']??'') ?>" placeholder="Page Title — ARTISAN CA"></div>
      <div class="field"><label>Meta Description</label><textarea name="page_desc" rows="2" placeholder="150–160 chars"><?= e($edit_page['description']??'') ?></textarea></div>
      <div class="field"><label>Keywords</label><input type="text" name="page_keywords" value="<?= e($edit_page['keywords']??'') ?>" placeholder="audit, tax, bangladesh…"></div>
      <div class="field"><label>OG Image URL</label><input type="text" name="page_og_image" value="<?= e($edit_page['og_image']??'') ?>" placeholder="/assets/img/og-about.jpg"></div>
      <div style="display:flex;gap:8px;margin-top:4px">
        <button class="btn btn-primary">Save</button>
        <?php if($edit_page): ?><a href="/admin/seo.php" class="btn btn-secondary">Cancel</a><?php endif; ?>
      </div>
    </form>
  </div>
</div>
</div>
<?php include __DIR__ . '/inc/footer.php'; ?>
