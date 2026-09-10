<?php
if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/db.php';

function currentUser(): ?array {
    return $_SESSION['admin_user'] ?? null;
}

function requireAuth(): void {
    if (!currentUser()) {
        header('Location: /admin/login.php');
        exit;
    }
}

function requireRole(string $role): void {
    requireAuth();
    $user = currentUser();
    $hierarchy = ['editor'=>1,'admin'=>2,'manager'=>2,'superadmin'=>3];
    if (($hierarchy[$user['role']] ?? 0) < ($hierarchy[$role] ?? 99)) {
        http_response_code(403);
        exit('Access denied.');
    }
}

function logActivity(string $action, string $target = ''): void {
    $user = currentUser();
    if (!$user) return;
    db()->prepare("INSERT INTO activity_log (user_id,user_name,action,target,ip) VALUES (?,?,?,?,?)")
       ->execute([$user['id'],$user['name'],$action,$target,$_SERVER['REMOTE_ADDR'] ?? '']);
}
