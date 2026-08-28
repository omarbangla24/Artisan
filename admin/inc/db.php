<?php
/** PDO connection + lightweight migrator. MySQL in production; SQLite for local tests. */

function db(): PDO {
    static $pdo = null;
    if ($pdo instanceof PDO) return $pdo;

    $opts = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    if (DB_DRIVER === 'sqlite') {
        $pdo = new PDO('sqlite:' . DB_PATH, null, null, $opts);
        $pdo->exec('PRAGMA foreign_keys = ON');
    } else {
        $dsn = sprintf('mysql:host=%s;dbname=%s;charset=%s', DB_HOST, DB_NAME, DB_CHARSET);
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $opts);
    }
    return $pdo;
}

/** True when running against SQLite (used only to smooth over dialect gaps). */
function db_is_sqlite(): bool { return DB_DRIVER === 'sqlite'; }

/** Create tables if missing, then ensure default settings + seed admin exist. */
function db_migrate(): void {
    $pdo = db();

    if (db_is_sqlite()) {
        $pk  = 'INTEGER PRIMARY KEY AUTOINCREMENT';
        $now = "DATETIME DEFAULT CURRENT_TIMESTAMP";
        $txt = 'TEXT';
        $eng = '';
    } else {
        $pk  = 'INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY';
        $now = "DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP";
        $txt = 'TEXT';
        $eng = ' ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci';
    }

    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id $pk,
        name VARCHAR(120) NOT NULL,
        email VARCHAR(190) NOT NULL UNIQUE,
        password_hash VARCHAR(255) NOT NULL,
        role VARCHAR(20) NOT NULL DEFAULT 'editor',
        must_change_password TINYINT NOT NULL DEFAULT 0,
        is_active TINYINT NOT NULL DEFAULT 1,
        failed_attempts INT NOT NULL DEFAULT 0,
        locked_until DATETIME NULL,
        last_login_at DATETIME NULL,
        created_at $now
    )$eng");

    $pdo->exec("CREATE TABLE IF NOT EXISTS submissions (
        id $pk,
        form_key VARCHAR(40) NOT NULL,
        subject VARCHAR(200) NOT NULL DEFAULT '',
        name VARCHAR(190) NOT NULL DEFAULT '',
        email VARCHAR(190) NOT NULL DEFAULT '',
        phone VARCHAR(60) NOT NULL DEFAULT '',
        payload $txt,
        ip VARCHAR(45) NOT NULL DEFAULT '',
        user_agent VARCHAR(255) NOT NULL DEFAULT '',
        status VARCHAR(20) NOT NULL DEFAULT 'new',
        created_at $now
    )$eng");

    $pdo->exec("CREATE TABLE IF NOT EXISTS settings (
        skey VARCHAR(60) NOT NULL PRIMARY KEY,
        svalue $txt
    )$eng");

    // ---- default settings (only inserted when absent) --------------------
    $defaults = [
        'smtp_enabled'      => '0',
        'smtp_host'         => '',
        'smtp_port'         => '587',
        'smtp_encryption'   => 'tls',      // tls | ssl | none
        'smtp_username'     => '',
        'smtp_password_enc' => '',         // encrypted at rest
        'smtp_from_email'   => '',
        'smtp_from_name'    => 'ARTISAN Chartered Accountants',
        'notify_enabled'    => '1',
        'notify_to'         => 'omarbangla24@gmail.com',
        // Integrations / Search Console & friends
        'gsc_verification'  => '',         // google-site-verification token
        'bing_verification' => '',
        'ga4_id'            => '',         // G-XXXXXXX
        'gtm_id'            => '',         // GTM-XXXXXX
        'head_snippet'      => '',         // raw HTML injected into <head>
        'body_snippet'      => '',         // raw HTML injected before </body>
    ];
    $sel = $pdo->prepare('SELECT 1 FROM settings WHERE skey = ?');
    $ins = $pdo->prepare('INSERT INTO settings (skey, svalue) VALUES (?, ?)');
    foreach ($defaults as $k => $v) {
        $sel->execute([$k]);
        if (!$sel->fetchColumn()) $ins->execute([$k, $v]);
    }

    // ---- seed first admin ------------------------------------------------
    $count = (int)$pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
    if ($count === 0) {
        $email = defined('SEED_ADMIN_EMAIL') ? SEED_ADMIN_EMAIL : 'admin@example.com';
        $name  = defined('SEED_ADMIN_NAME')  ? SEED_ADMIN_NAME  : 'Administrator';
        $pass  = defined('SEED_ADMIN_PASSWORD') ? SEED_ADMIN_PASSWORD : bin2hex(random_bytes(6));
        $hash  = password_hash($pass, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare(
            'INSERT INTO users (name, email, password_hash, role, must_change_password, is_active)
             VALUES (?, ?, ?, ?, 1, 1)'
        );
        $stmt->execute([$name, $email, $hash, 'admin']);
    }
}

/** settings helpers --------------------------------------------------------*/
function settings_all(bool $fresh = false): array {
    static $cache = null;
    if ($fresh) $cache = null;
    if ($cache !== null) return $cache;
    $rows = db()->query('SELECT skey, svalue FROM settings')->fetchAll();
    $cache = [];
    foreach ($rows as $r) $cache[$r['skey']] = $r['svalue'];
    return $cache;
}

function setting(string $key, string $default = ''): string {
    $all = settings_all();
    return array_key_exists($key, $all) ? (string)$all[$key] : $default;
}

function settings_save(array $pairs): void {
    $pdo = db();
    $up  = $pdo->prepare('UPDATE settings SET svalue = ? WHERE skey = ?');
    $ins = $pdo->prepare('INSERT INTO settings (skey, svalue) VALUES (?, ?)');
    $has = $pdo->prepare('SELECT 1 FROM settings WHERE skey = ?');
    foreach ($pairs as $k => $v) {
        $has->execute([$k]);
        if ($has->fetchColumn()) $up->execute([(string)$v, $k]);
        else                     $ins->execute([$k, (string)$v]);
    }
    settings_all(true); // refresh the in-request cache
}
