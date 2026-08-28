<?php
require __DIR__ . '/inc/bootstrap.php';
// Logout must be an intentional POST or a token-guarded GET to avoid CSRF logout.
if ($_SERVER['REQUEST_METHOD'] === 'POST') { csrf_check(); }
auth_logout();
redirect('login.php');
