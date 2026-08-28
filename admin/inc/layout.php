<?php
/** Shared admin chrome. Call layout_header() / layout_footer() around page body. */

function layout_header(string $title, string $active = ''): void {
    $u = current_user();
    $nav = [
        'index.php'       => 'Dashboard',
        'submissions.php' => 'Submissions',
        'users.php'       => 'Users',
        'settings.php'    => 'Settings',
    ];
    ?><!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title><?= e($title) ?> · ARTISAN Admin</title>
<link rel="stylesheet" href="assets/admin.css">
</head>
<body>
<header class="topbar">
  <div class="brand">ARTISAN <span>Admin</span></div>
  <?php if ($u): ?>
  <nav class="mainnav">
    <?php foreach ($nav as $href => $label):
        if ($href === 'users.php' && !is_admin()) continue; ?>
      <a href="<?= e($href) ?>"<?= $active === $href ? ' class="active"' : '' ?>><?= e($label) ?></a>
    <?php endforeach; ?>
  </nav>
  <div class="userbox">
    <span><?= e($u['name']) ?><?= is_admin() ? ' · admin' : '' ?></span>
    <a class="btn-link" href="change-password.php">Password</a>
    <a class="btn-link" href="logout.php">Log out</a>
  </div>
  <?php endif; ?>
</header>
<main class="wrap">
<?php
    if ($f = flash()) {
        echo '<div class="flash flash-' . e($f['type']) . '">' . e($f['msg']) . '</div>';
    }
}

function layout_footer(): void {
    ?>
</main>
<footer class="foot">ARTISAN Chartered Accountants — admin panel</footer>
</body>
</html><?php
}
