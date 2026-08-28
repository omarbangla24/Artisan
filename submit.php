<?php
/**
 * Public form endpoint. Receives the site's enquiry forms, stores them for the
 * admin panel, and (optionally) emails a notification. No auth; protected by
 * honeypot + same-origin check + per-IP rate limiting.
 */
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

function reply(bool $ok, string $msg, int $code = 200): never {
    http_response_code($code);
    echo json_encode(['ok' => $ok, 'message' => $msg]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') reply(false, 'Method not allowed.', 405);

// ---- load backend (reuse admin core, no session) -------------------------
$cfg = __DIR__ . '/admin/config.php';
if (!is_file($cfg)) reply(false, 'Server not configured.', 500);
require $cfg;
require __DIR__ . '/admin/inc/helpers.php';
require __DIR__ . '/admin/inc/db.php';
require __DIR__ . '/admin/inc/mailer.php';

// ---- same-origin check (compare host names only, ignoring port) ----------
$host   = (string)(parse_url('http://' . ($_SERVER['HTTP_HOST'] ?? ''), PHP_URL_HOST) ?: '');
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
$ref    = $_SERVER['HTTP_REFERER'] ?? '';
$srcHost = $origin !== '' ? parse_url($origin, PHP_URL_HOST)
        : ($ref !== '' ? parse_url($ref, PHP_URL_HOST) : $host);
if ($srcHost && $host !== '' && strcasecmp((string)$srcHost, $host) !== 0) {
    reply(false, 'Bad origin.', 403);
}

// ---- honeypot ------------------------------------------------------------
if (!empty($_POST['website'])) reply(true, 'Thank you.'); // silently accept bots

try {
    db(); db_migrate();
} catch (Throwable $e) {
    error_log('submit db: ' . $e->getMessage());
    reply(false, 'Temporarily unavailable. Please email us directly.', 500);
}

// ---- rate limit: max 8 per IP per hour -----------------------------------
$ip = client_ip();
$rl = db()->prepare("SELECT COUNT(*) FROM submissions WHERE ip = ? AND created_at >= ?");
$rl->execute([$ip, date('Y-m-d H:i:s', time() - 3600)]);
if ((int)$rl->fetchColumn() >= 8) reply(false, 'Too many submissions. Please try again later.', 429);

// ---- form identity -------------------------------------------------------
$allowed = ['contact','consultation','newsletter'];
$form = preg_replace('/[^a-z_]/', '', strtolower((string)($_POST['form_key'] ?? '')));
if (!in_array($form, $allowed, true)) $form = 'contact';
$subject = post_str('subject', 200) ?: 'Website enquiry';

// ---- gather fields (skip control + sensitive keys) -----------------------
$skip = ['_csrf','website','form_key','subject','password'];
$fields = [];
foreach ($_POST as $k => $v) {
    if (in_array($k, $skip, true) || !is_string($v)) continue;
    $key = preg_replace('/[^a-z0-9_\-]/i', '', $k);
    if ($key === '') continue;
    $val = trim($v);
    if (function_exists('mb_substr')) $val = mb_substr($val, 0, 5000);
    if ($val !== '') $fields[$key] = $val;
    if (count($fields) >= 40) break;
}

$name  = $fields['name']  ?? ($fields['contact_name'] ?? '');
$email = clean_email($fields['email'] ?? '');
$phone = $fields['phone'] ?? ($fields['contact_number'] ?? '');

if ($email === '') reply(false, 'A valid email address is required.', 422);
if ($form !== 'newsletter' && $name === '') reply(false, 'Your name is required.', 422);

// ---- store ---------------------------------------------------------------
$ins = db()->prepare(
    'INSERT INTO submissions (form_key, subject, name, email, phone, payload, ip, user_agent, status)
     VALUES (?,?,?,?,?,?,?,?,?)'
);
$ins->execute([
    $form, $subject,
    mb_substr($name, 0, 190), $email, mb_substr($phone, 0, 60),
    json_encode($fields, JSON_UNESCAPED_UNICODE),
    $ip, mb_substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255),
    'new',
]);

// ---- notify (best-effort; never blocks the user) -------------------------
if (setting('notify_enabled') === '1' && setting('smtp_enabled') === '1') {
    $to = setting('notify_to') ?: setting('smtp_from_email');
    $rows = '';
    foreach ($fields as $k => $v) {
        $rows .= '<tr><td style="padding:4px 10px;color:#61728a">' . e(ucwords(str_replace(['_','-'],' ',$k)))
               . '</td><td style="padding:4px 10px">' . nl2br(e($v)) . '</td></tr>';
    }
    $html = '<h2>New ' . e($form) . ' submission</h2>'
          . '<p style="color:#61728a">' . e($subject) . '</p>'
          . '<table style="border-collapse:collapse">' . $rows . '</table>'
          . '<p style="color:#61728a;font-size:12px">IP: ' . e($ip) . '</p>';
    [$ok, $err] = smtp_send($to, 'Admin', 'New enquiry: ' . $subject, $html);
    if (!$ok) error_log('notify email failed: ' . $err);
}

reply(true, 'Thank you — your message has been received. We will get back to you shortly.');
