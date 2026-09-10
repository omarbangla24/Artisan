<?php
require_once __DIR__ . '/inc/auth.php';
require_once __DIR__ . '/inc/functions.php';
requireAuth();
$pageTitle = 'SMTP / Email Settings';
$db = db();
$msg = '';

if ($_SERVER['REQUEST_METHOD']==='POST') {
    $fields = ['smtp_host','smtp_port','smtp_user','smtp_pass','smtp_from','smtp_from_name','smtp_secure','notify_email'];
    foreach ($fields as $f) {
        saveSetting($f, trim($_POST[$f]??''));
    }
    logActivity('Updated SMTP settings');
    $msg = 'SMTP settings saved.';

    if (isset($_POST['test_send'])) {
        $to = trim($_POST['test_email']??'');
        if ($to) {
            $ok = sendMail($to, 'ARTISAN Admin — Test Email', "<p>This is a test email from your ARTISAN admin panel.</p>");
            $msg .= $ok ? ' Test email sent to '.$to.'.' : ' Test email FAILED — check your SMTP settings.';
        }
    }
}

$s = function($k){ return htmlspecialchars(setting($k)); };
include __DIR__ . '/inc/header.php';
?>
<?php if($msg): ?><div class="alert alert-success"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
<div class="card">
  <div class="card-header"><h2>SMTP / Email Configuration</h2></div>
  <div class="card-body">
    <form method="post">
      <div class="form-grid">
        <div class="field">
          <label>SMTP Host</label>
          <input type="text" name="smtp_host" value="<?= $s('smtp_host') ?>" placeholder="smtp.gmail.com">
        </div>
        <div class="field">
          <label>SMTP Port</label>
          <input type="number" name="smtp_port" value="<?= $s('smtp_port') ?: '587' ?>" placeholder="587">
        </div>
        <div class="field">
          <label>Username (Email)</label>
          <input type="text" name="smtp_user" value="<?= $s('smtp_user') ?>" placeholder="info@artisancabd.com">
        </div>
        <div class="field">
          <label>Password / App Password</label>
          <input type="password" name="smtp_pass" value="<?= $s('smtp_pass') ?>" placeholder="••••••••">
        </div>
        <div class="field">
          <label>From Email</label>
          <input type="email" name="smtp_from" value="<?= $s('smtp_from') ?>" placeholder="noreply@artisancabd.com">
        </div>
        <div class="field">
          <label>From Name</label>
          <input type="text" name="smtp_from_name" value="<?= $s('smtp_from_name') ?: 'ARTISAN Chartered Accountants' ?>">
        </div>
        <div class="field">
          <label>Security</label>
          <select name="smtp_secure">
            <option value="tls" <?= setting('smtp_secure')==='tls'?'selected':'' ?>>TLS (STARTTLS)</option>
            <option value="ssl" <?= setting('smtp_secure')==='ssl'?'selected':'' ?>>SSL</option>
            <option value="" <?= setting('smtp_secure')===''?'selected':'' ?>>None</option>
          </select>
        </div>
        <div class="field">
          <label>Notification Recipient (receives form submissions)</label>
          <input type="email" name="notify_email" value="<?= $s('notify_email') ?>" placeholder="admin@artisancabd.com">
        </div>
      </div>
      <div style="margin-top:20px;display:flex;gap:10px;align-items:flex-end;flex-wrap:wrap">
        <button class="btn btn-primary" type="submit" name="save_only">Save Settings</button>
        <div style="display:flex;gap:8px;align-items:center">
          <input type="email" name="test_email" placeholder="Test recipient email" style="width:220px">
          <button class="btn btn-secondary" type="submit" name="test_send">Send Test Email</button>
        </div>
      </div>
    </form>
  </div>
</div>

<div class="card" style="margin-top:20px">
  <div class="card-header"><h2>Email Template — Form Notification</h2></div>
  <div class="card-body">
    <p style="color:var(--muted);font-size:13px">When a visitor submits the contact/quote form on the website, an email is sent to the notification recipient above with the form data. The email subject is <code>New [form type] from [name]</code>.</p>
    <br>
    <p style="color:var(--muted);font-size:13px">Career application emails are sent to the email address configured in each job listing.</p>
  </div>
</div>
<?php include __DIR__ . '/inc/footer.php'; ?>
