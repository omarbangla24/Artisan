<?php
session_start();
require_once __DIR__ . '/inc/db.php';
require_once __DIR__ . '/inc/auth.php';
if ($u = currentUser()) {
    db()->prepare("INSERT INTO activity_log (user_id,user_name,action,ip) VALUES (?,?,?,?)")
       ->execute([$u['id'],$u['name'],'Logged out',$_SERVER['REMOTE_ADDR']??'']);
}
session_destroy();
header('Location: /admin/login.php');
