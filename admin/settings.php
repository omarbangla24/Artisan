<?php
require __DIR__ . '/inc/bootstrap.php';
require __DIR__ . '/inc/layout.php';
require __DIR__ . '/inc/publicgen.php';
require_admin();

$err = ''; $notice = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    if (($_POST['do'] ?? '') === 'test') {
        // Send a test email using the *saved* settings.
        [$ok, $msg] = smtp_send(setting('notify_to') ?: setting('smtp_from_email'),
            'Admin', 'ARTISAN SMTP test',
            '<p>This is a test email from your ARTISAN admin panel. SMTP is working.</p>');
        flash($ok ? 'Test email sent successfully.' : ('Test failed: ' . $msg), $ok ? 'ok' : 'error');
        redirect('settings.php');
    }

    // ---- collect + save --------------------------------------------------
    $pairs = [
        'smtp_enabled'    => isset($_POST['smtp_enabled']) ? '1' : '0',
        'smtp_host'       => post_str('smtp_host', 190),
        'smtp_port'       => (string)max(1, min(65535, (int)($_POST['smtp_port'] ?? 587))),
        'smtp_encryption' => in_array($_POST['smtp_encryption'] ?? 'tls', ['tls','ssl','none'], true) ? $_POST['smtp_encryption'] : 'tls',
        'smtp_username'   => post_str('smtp_username', 190),
        'smtp_from_email' => clean_email(post_str('smtp_from_email', 190)),
        'smtp_from_name'  => post_str('smtp_from_name', 120),
        'notify_enabled'  => isset($_POST['notify_enabled']) ? '1' : '0',
        'notify_to'       => clean_email(post_str('notify_to', 190)),
        'gsc_verification'=> post_str('gsc_verification', 200),
        'bing_verification'=>post_str('bing_verification', 200),
        'ga4_id'          => post_str('ga4_id', 40),
        'gtm_id'          => post_str('gtm_id', 40),
        'head_snippet'    => post_str('head_snippet', 20000),
        'body_snippet'    => post_str('body_snippet', 20000),
    ];
    // Only overwrite the SMTP password when a new one is typed.
    $newpw = (string)($_POST['smtp_password'] ?? '');
    if ($newpw !== '') $pairs['smtp_password_enc'] = secret_encrypt($newpw);

    settings_save($pairs);

    // Refresh cached settings, then regenerate public snippets.
    // (settings_all() is statically cached, so reload from a fresh request path)
    $regen = regenerate_public_snippets();
    flash('Settings saved.' . ($regen ? '' : ' (Could not write public snippet files — check assets/inc permissions.)'),
          $regen ? 'ok' : 'warn');
    redirect('settings.php');
}

$s = settings_all();
$has_pw = setting('smtp_password_enc') !== '';

layout_header('Settings', 'settings.php');
?>
<h1>Settings</h1>

<form method="post">
<?= csrf_field() ?>

<div class="card">
  <h2>Email notifications (SMTP)</h2>
  <div class="field check">
    <input type="checkbox" id="smtp_enabled" name="smtp_enabled" value="1" <?= setting('smtp_enabled')==='1'?'checked':'' ?>>
    <label for="smtp_enabled" style="margin:0">Enable sending email through SMTP</label>
  </div>
  <div class="row">
    <div class="field"><label for="smtp_host">SMTP host</label>
      <input type="text" id="smtp_host" name="smtp_host" value="<?= e($s['smtp_host']) ?>" placeholder="mail.artisan-ca.net"></div>
    <div class="field" style="max-width:120px"><label for="smtp_port">Port</label>
      <input type="number" id="smtp_port" name="smtp_port" value="<?= e($s['smtp_port']) ?>"></div>
    <div class="field" style="max-width:150px"><label for="smtp_encryption">Encryption</label>
      <select id="smtp_encryption" name="smtp_encryption">
        <?php foreach (['tls'=>'STARTTLS','ssl'=>'SSL/TLS','none'=>'None'] as $v=>$l): ?>
          <option value="<?= $v ?>"<?= $s['smtp_encryption']===$v?' selected':'' ?>><?= $l ?></option>
        <?php endforeach; ?>
      </select></div>
  </div>
  <div class="row">
    <div class="field"><label for="smtp_username">Username</label>
      <input type="text" id="smtp_username" name="smtp_username" autocomplete="off" value="<?= e($s['smtp_username']) ?>"></div>
    <div class="field"><label for="smtp_password">Password</label>
      <input type="password" id="smtp_password" name="smtp_password" autocomplete="new-password" placeholder="<?= $has_pw ? '•••••••• (unchanged)' : '' ?>">
      <div class="hint">Stored encrypted. Leave blank to keep the current one.</div></div>
  </div>
  <div class="row">
    <div class="field"><label for="smtp_from_email">From email</label>
      <input type="email" id="smtp_from_email" name="smtp_from_email" value="<?= e($s['smtp_from_email']) ?>" placeholder="no-reply@artisan-ca.net"></div>
    <div class="field"><label for="smtp_from_name">From name</label>
      <input type="text" id="smtp_from_name" name="smtp_from_name" value="<?= e($s['smtp_from_name']) ?>"></div>
  </div>
</div>

<div class="card">
  <h2>Form notifications</h2>
  <div class="field check">
    <input type="checkbox" id="notify_enabled" name="notify_enabled" value="1" <?= setting('notify_enabled')==='1'?'checked':'' ?>>
    <label for="notify_enabled" style="margin:0">Email me when a new form submission arrives</label>
  </div>
  <div class="field"><label for="notify_to">Notification recipient</label>
    <input type="email" id="notify_to" name="notify_to" value="<?= e($s['notify_to']) ?>">
    <div class="hint">Where new submission alerts are sent (requires SMTP enabled).</div>
  </div>
</div>

<div class="card">
  <h2>Search Console &amp; analytics</h2>
  <div class="row">
    <div class="field"><label for="gsc_verification">Google Search Console token</label>
      <input type="text" id="gsc_verification" name="gsc_verification" value="<?= e($s['gsc_verification']) ?>" placeholder="google-site-verification value">
      <div class="hint">Paste only the <code>content</code> token, not the full meta tag.</div></div>
    <div class="field"><label for="bing_verification">Bing verification token</label>
      <input type="text" id="bing_verification" name="bing_verification" value="<?= e($s['bing_verification']) ?>" placeholder="msvalidate.01 value"></div>
  </div>
  <div class="row">
    <div class="field"><label for="ga4_id">Google Analytics 4 ID</label>
      <input type="text" id="ga4_id" name="ga4_id" value="<?= e($s['ga4_id']) ?>" placeholder="G-XXXXXXXXXX"></div>
    <div class="field"><label for="gtm_id">Google Tag Manager ID</label>
      <input type="text" id="gtm_id" name="gtm_id" value="<?= e($s['gtm_id']) ?>" placeholder="GTM-XXXXXXX"></div>
  </div>
</div>

<div class="card">
  <h2>Custom code (advanced)</h2>
  <div class="field"><label for="head_snippet">Extra &lt;head&gt; HTML</label>
    <textarea id="head_snippet" name="head_snippet" rows="4"><?= e($s['head_snippet']) ?></textarea>
    <div class="hint">Injected into every public page's &lt;head&gt;. Admin-only — trusted HTML.</div></div>
  <div class="field"><label for="body_snippet">Extra pre-&lt;/body&gt; HTML</label>
    <textarea id="body_snippet" name="body_snippet" rows="4"><?= e($s['body_snippet']) ?></textarea></div>
</div>

<div class="row">
  <button class="btn" type="submit">Save settings</button>
  <button class="btn ghost" type="submit" name="do" value="test">Send test email</button>
</div>
</form>
<p class="hint mt">Saving regenerates <code>assets/inc/site-head.php</code> and <code>site-body.php</code>, which the public pages include.</p>
<?php layout_footer();
