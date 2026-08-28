<?php
/** Authentication, lockout, and role gating. */

const AUTH_MAX_ATTEMPTS = 5;      // failed logins before lockout
const AUTH_LOCK_MINUTES = 15;     // lockout duration
const AUTH_IDLE_MINUTES = 60;     // auto-logout after inactivity

/** Attempt login. Returns [ok(bool), error(string)]. */
function auth_login(string $email, string $password): array {
    $email = clean_email($email);
    if ($email === '' || $password === '') return [false, 'Enter your email and password.'];

    $stmt = db()->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    $u = $stmt->fetch();

    // Uniform failure message — never reveal which part was wrong.
    $fail = 'Invalid credentials.';

    if (!$u || (int)$u['is_active'] !== 1) {
        // Spend similar time to reduce user-enumeration timing signals.
        password_verify($password, '$2y$10$usesomesillystringforsalt0000000000000000000000000');
        return [false, $fail];
    }

    if (!empty($u['locked_until']) && strtotime($u['locked_until']) > time()) {
        return [false, 'Account temporarily locked. Try again later.'];
    }

    if (!password_verify($password, $u['password_hash'])) {
        $attempts = (int)$u['failed_attempts'] + 1;
        $lock = null;
        if ($attempts >= AUTH_MAX_ATTEMPTS) {
            $lock = date('Y-m-d H:i:s', time() + AUTH_LOCK_MINUTES * 60);
            $attempts = 0;
        }
        $up = db()->prepare('UPDATE users SET failed_attempts = ?, locked_until = ? WHERE id = ?');
        $up->execute([$attempts, $lock, $u['id']]);
        return [false, $fail];
    }

    // Success — reset counters, refresh hash if needed, start session.
    if (password_needs_rehash($u['password_hash'], PASSWORD_DEFAULT)) {
        $rh = db()->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
        $rh->execute([password_hash($password, PASSWORD_DEFAULT), $u['id']]);
    }
    $up = db()->prepare(
        'UPDATE users SET failed_attempts = 0, locked_until = NULL, last_login_at = ? WHERE id = ?'
    );
    $up->execute([date('Y-m-d H:i:s'), $u['id']]);

    session_regenerate_id(true);
    $_SESSION['uid']       = (int)$u['id'];
    $_SESSION['uname']     = $u['name'];
    $_SESSION['urole']     = $u['role'];
    $_SESSION['must_pw']   = (int)$u['must_change_password'] === 1;
    $_SESSION['last_seen'] = time();
    return [true, ''];
}

function auth_logout(): void {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}

function current_user(): ?array {
    if (empty($_SESSION['uid'])) return null;
    return [
        'id'   => (int)$_SESSION['uid'],
        'name' => $_SESSION['uname'] ?? '',
        'role' => $_SESSION['urole'] ?? 'editor',
    ];
}

function is_admin(): bool {
    return current_user() && current_user()['role'] === 'admin';
}

/** Gate a page: require a logged-in session; enforce idle timeout + forced pw change. */
function require_login(bool $allow_pw_page = false): void {
    if (empty($_SESSION['uid'])) redirect('login.php');

    if (!empty($_SESSION['last_seen']) &&
        (time() - (int)$_SESSION['last_seen']) > AUTH_IDLE_MINUTES * 60) {
        auth_logout();
        redirect('login.php?timeout=1');
    }
    $_SESSION['last_seen'] = time();

    if (!$allow_pw_page && !empty($_SESSION['must_pw'])) {
        redirect('change-password.php');
    }
}

/** Gate an admin-only action. */
function require_admin(): void {
    require_login();
    if (!is_admin()) {
        http_response_code(403);
        exit('Forbidden — administrator access required.');
    }
}
