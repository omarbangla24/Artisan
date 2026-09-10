<?php
require_once __DIR__ . '/inc/auth.php';
require_once __DIR__ . '/inc/functions.php';
requireAuth();
$db = db();
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$art = [];
if ($id) { $stmt=$db->prepare("SELECT * FROM articles WHERE id=?"); $stmt->execute([$id]); $art=$stmt->fetch()||[]; if(!$art) $art=[]; }
$pageTitle = $id ? 'Edit Article' : 'New Article';
$msg = '';

if ($_SERVER['REQUEST_METHOD']==='POST') {
    $title    = trim($_POST['title']??'');
    $slug     = trim($_POST['slug']??'');
    $slug     = $slug ?: preg_replace('/[^a-z0-9]+/','-',strtolower($title));
    $category = trim($_POST['category']??'');
    $excerpt  = trim($_POST['excerpt']??'');
    $body     = $_POST['body']??'';
    $meta_title = trim($_POST['meta_title']??'');
    $meta_desc  = trim($_POST['meta_desc']??'');
    $published  = isset($_POST['published']) ? 1 : 0;
    $thumb = $art['thumb']??'';
    if (!empty($_FILES['thumb']['name'])) {
        $up = uploadFile($_FILES['thumb'], 'articles');
        if ($up) $thumb = $up;
    }
    $user = currentUser();
    if ($id) {
        $db->prepare("UPDATE articles SET title=?,slug=?,category=?,excerpt=?,body=?,thumb=?,meta_title=?,meta_desc=?,published=?,updated_at=NOW() WHERE id=?")
           ->execute([$title,$slug,$category,$excerpt,$body,$thumb,$meta_title,$meta_desc,$published,$id]);
        logActivity('Updated article', $title);
        $msg = 'Article updated.';
    } else {
        $db->prepare("INSERT INTO articles (title,slug,category,excerpt,body,thumb,meta_title,meta_desc,published,author_id,created_at,updated_at) VALUES (?,?,?,?,?,?,?,?,?,?,NOW(),NOW())")
           ->execute([$title,$slug,$category,$excerpt,$body,$thumb,$meta_title,$meta_desc,$published,$user['id']]);
        $id = (int)$db->lastInsertId();
        logActivity('Created article', $title);
        header("Location: /admin/article-edit.php?id=$id&msg=created"); exit;
    }
    $stmt=$db->prepare("SELECT * FROM articles WHERE id=?"); $stmt->execute([$id]); $art=$stmt->fetch();
}

include __DIR__ . '/inc/header.php';
?>
<?php if($msg||isset($_GET['msg'])): ?><div class="alert alert-success"><?= $msg ?: 'Article created.' ?></div><?php endif; ?>
<form method="post" enctype="multipart/form-data">
<div style="display:grid;grid-template-columns:1fr 300px;gap:20px;align-items:start">
<div>
  <div class="card" style="margin-bottom:16px">
    <div class="card-body">
      <div class="field">
        <label>Title</label>
        <input type="text" name="title" required value="<?= e($art['title']??'') ?>" placeholder="Article title…" style="font-size:18px;font-weight:600">
      </div>
      <div class="field">
        <label>URL Slug</label>
        <input type="text" name="slug" value="<?= e($art['slug']??'') ?>" placeholder="auto-generated-from-title">
      </div>
      <div class="field">
        <label>Excerpt</label>
        <textarea name="excerpt" rows="2" placeholder="Short summary shown in article listings…"><?= e($art['excerpt']??'') ?></textarea>
      </div>
    </div>
  </div>
  <div class="card">
    <div class="card-header"><h2>Content</h2></div>
    <div class="card-body">
      <div id="editor-toolbar" style="display:flex;gap:4px;flex-wrap:wrap;padding-bottom:10px;border-bottom:1px solid var(--border);margin-bottom:10px">
        <?php $btns=[['b','bold','<strong>B</strong>'],['i','italic','<em>I</em>'],['u','underline','<u>U</u>'],['h2','heading2','H2'],['h3','heading3','H3'],['ul','insertUnorderedList','• List'],['ol','insertOrderedList','1. List'],['link','createLink','Link']]; ?>
        <?php foreach($btns as [$k,$cmd,$lbl]): ?>
        <button type="button" class="btn btn-secondary btn-sm" onclick="edCmd('<?= $cmd ?>')"><?= $lbl ?></button>
        <?php endforeach; ?>
      </div>
      <div id="editor" contenteditable="true" style="min-height:320px;outline:none;font-size:14px;line-height:1.7;color:var(--text)"><?= $art['body']??'' ?></div>
      <textarea name="body" id="body-input" style="display:none"><?= e($art['body']??'') ?></textarea>
    </div>
  </div>
</div>
<div>
  <div class="card" style="margin-bottom:16px">
    <div class="card-header"><h2>Publish</h2></div>
    <div class="card-body">
      <div class="field" style="flex-direction:row;align-items:center;gap:10px;margin-bottom:16px">
        <input type="checkbox" name="published" id="pub" <?= ($art['published']??0)?'checked':'' ?> style="width:auto">
        <label for="pub" style="margin:0;font-weight:500">Published (visible on site)</label>
      </div>
      <div style="display:flex;gap:8px;flex-wrap:wrap">
        <button class="btn btn-primary" type="submit">Save</button>
        <a href="/admin/articles.php" class="btn btn-secondary">Cancel</a>
      </div>
    </div>
  </div>
  <div class="card" style="margin-bottom:16px">
    <div class="card-header"><h2>Details</h2></div>
    <div class="card-body">
      <div class="field">
        <label>Category</label>
        <input type="text" name="category" value="<?= e($art['category']??'') ?>" placeholder="e.g. Tax, Audit…">
      </div>
      <div class="field">
        <label>Featured Image</label>
        <?php if(!empty($art['thumb'])): ?>
        <img src="/admin/uploads/articles/<?= e($art['thumb']) ?>" style="width:100%;height:120px;object-fit:cover;border-radius:6px;margin-bottom:8px">
        <?php endif; ?>
        <input type="file" name="thumb" accept="image/*">
      </div>
    </div>
  </div>
  <div class="card">
    <div class="card-header"><h2>SEO</h2></div>
    <div class="card-body">
      <div class="field">
        <label>Meta Title</label>
        <input type="text" name="meta_title" value="<?= e($art['meta_title']??'') ?>" placeholder="Defaults to article title">
      </div>
      <div class="field">
        <label>Meta Description</label>
        <textarea name="meta_desc" rows="2" placeholder="150–160 chars recommended"><?= e($art['meta_desc']??'') ?></textarea>
      </div>
    </div>
  </div>
</div>
</div>
</form>
<script>
function edCmd(cmd){
  if(cmd==='createLink'){var url=prompt('Enter URL:');if(url)document.execCommand('createLink',false,url);}
  else if(cmd==='heading2'){document.execCommand('formatBlock',false,'h2');}
  else if(cmd==='heading3'){document.execCommand('formatBlock',false,'h3');}
  else document.execCommand(cmd,false,null);
  document.getElementById('editor').focus();
}
document.querySelector('form').addEventListener('submit',function(){
  document.getElementById('body-input').value=document.getElementById('editor').innerHTML;
});
</script>
<?php include __DIR__ . '/inc/footer.php'; ?>
