<?php
/** Front-of-every-request bootstrap: config, security, session, DB, helpers. */

declare(strict_types=1);

// ---- Load configuration --------------------------------------------------
$cfg = __DIR__ . '/../config.php';
if (!is_file($cfg)) {
    http_response_code(500);
    exit('Setup required: copy admin/config.sample.php to admin/config.php and fill in your details.');
}
require $cfg;

if (!defined('APP_DEBUG')) define('APP_DEBUG', false);
error_reporting(E_ALL);
ini_set('display_errors', APP_DEBUG ? '1' : '0');
ini_set('log_errors', '1');

// ---- Detect HTTPS (respects a single trusted proxy hop) ------------------
$https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https')
    || (($_SERVER['SERVER_PORT'] ?? '') === '443');

// ---- Security headers ----------------------------------------------------
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('X-XSS-Protection: 0');
header("Content-Security-Policy: default-src 'self'; img-src 'self' data:; "
     . "style-src 'self' 'unsafe-inline'; script-src 'self'; form-action 'self'; "
     . "base-uri 'self'; frame-ancestors 'none'");
if ($https) header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
header('X-Robots-Tag: noindex, nofollow');   // keep the admin out of search results

// ---- Hardened session ----------------------------------------------------
session_name('ARTADMSID');
session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/admin/',
    'httponly' => true,
    'secure'   => $https,
    'samesite' => 'Lax',
]);
ini_set('session.use_strict_mode', '1');
ini_set('session.use_only_cookies', '1');
session_start();

// ---- Includes + migration ------------------------------------------------
require __DIR__ . '/helpers.php';
require __DIR__ . '/db.php';
require __DIR__ . '/csrf.php';
require __DIR__ . '/auth.php';
require __DIR__ . '/mailer.php';

try {
    db_migrate();
} catch (Throwable $ex) {
    http_response_code(500);
    error_log('DB error: ' . $ex->getMessage());
    exit(APP_DEBUG ? ('DB error: ' . e($ex->getMessage())) : 'Database is not reachable. Check admin/config.php.');
}
