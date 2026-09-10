<?php
/**
 * Form submission endpoint — handles all contact/quote forms.
 * Saves to DB, sends SMTP notification email.
 */
require_once __DIR__ . '/admin/inc/db.php';
require_once __DIR__ . '/admin/inc/functions.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok'=>false,'error'=>'Method not allowed']);
    exit;
}

$data = [];
$source = 'unknown';

// Accept JSON or form-encoded
$raw = file_get_contents('php://input');
if ($raw && ($json = json_decode($raw, true))) {
    $data   = $json;
    $source = 'json';
} else {
    $data   = $_POST;
    $source = 'form';
}

// Honeypot
if (!empty($data['website'])) {
    http_response_code(200);
    echo json_encode(['ok'=>true]);
    exit;
}

// Sanitize
$clean = [];
foreach ($data as $k => $v) {
    if (is_string($v)) $clean[preg_replace('/[^a-z0-9_\-]/i','',$k)] = htmlspecialchars(trim($v), ENT_QUOTES, 'UTF-8');
}

$formType = $clean['form_key'] ?? $clean['form_type'] ?? 'contact';
$ip       = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '';

// Save to database
try {
    $db = db();
    $db->prepare("INSERT INTO form_entries (form_type, data, ip, created_at) VALUES (?, ?, ?, NOW())")
       ->execute([$formType, json_encode($clean), $ip]);
} catch (\Throwable $e) {
    error_log('DB error saving form: ' . $e->getMessage());
}

// Build email notification
$notifyTo = setting('notify_email') ?: setting('smtp_from') ?: 'info@artisancabd.com';
$siteName  = setting('seo_site_name') ?: 'ARTISAN Chartered Accountants';
$name  = $clean['name'] ?? $clean['full_name'] ?? 'Unknown';
$subject = "New $formType submission from $name";

$rows = '';
foreach ($clean as $k => $v) {
    if ($k === 'form_key' || $k === 'website') continue;
    $label = ucwords(str_replace(['_','-'],' ',$k));
    $rows .= "<tr><td style='padding:6px 12px 6px 0;color:#6b7280;white-space:nowrap;vertical-align:top;font-size:14px'>$label</td><td style='padding:6px 0;font-size:14px'>".nl2br($v)."</td></tr>";
}

$emailBody = "
<div style='font-family:-apple-system,BlinkMacSystemFont,Segoe UI,sans-serif;max-width:560px;margin:0 auto'>
  <div style='background:#1B61A9;padding:20px 24px;border-radius:8px 8px 0 0'>
    <h2 style='color:#fff;margin:0;font-size:18px'>$siteName</h2>
    <p style='color:#93c5fd;margin:4px 0 0;font-size:13px'>New form submission</p>
  </div>
  <div style='background:#f8fafc;border:1px solid #e2e8f0;border-top:none;border-radius:0 0 8px 8px;padding:24px'>
    <p style='margin:0 0 16px;font-size:14px;color:#374151'>A new <strong>$formType</strong> form was submitted on your website.</p>
    <table style='border-collapse:collapse;width:100%'>$rows</table>
    <hr style='border:none;border-top:1px solid #e2e8f0;margin:20px 0'>
    <p style='font-size:12px;color:#9ca3af;margin:0'>Submitted from IP: $ip &middot; <a href='https://artisancabd.com/admin/entries.php' style='color:#1B61A9'>View in Admin</a></p>
  </div>
</div>
";

try {
    sendMail($notifyTo, $subject, $emailBody);
} catch (\Throwable $e) {
    error_log('Mail error: ' . $e->getMessage());
}

echo json_encode(['ok' => true, 'message' => 'Submitted successfully']);
