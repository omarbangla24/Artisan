<?php
/** Small, dependency-free helpers shared across the admin app. */

/** HTML-escape for safe output. */
function e($v): string {
    return htmlspecialchars((string)$v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Redirect and stop. Path is relative to the admin base. */
function redirect(string $path): void {
    header('Location: ' . $path);
    exit;
}

/** Read + clear a one-shot flash message. */
function flash(?string $msg = null, string $type = 'info') {
    if ($msg !== null) {
        $_SESSION['_flash'] = ['msg' => $msg, 'type' => $type];
        return null;
    }
    if (empty($_SESSION['_flash'])) return null;
    $f = $_SESSION['_flash'];
    unset($_SESSION['_flash']);
    return $f;
}

/** Trim + collapse a posted string, capped to a sane length. */
function post_str(string $key, int $max = 2000): string {
    $v = isset($_POST[$key]) && is_string($_POST[$key]) ? $_POST[$key] : '';
    $v = trim($v);
    if (function_exists('mb_substr')) $v = mb_substr($v, 0, $max);
    else $v = substr($v, 0, $max);
    return $v;
}

/** Validate an email, returning normalised form or '' when invalid. */
function clean_email(string $v): string {
    $v = trim($v);
    return filter_var($v, FILTER_VALIDATE_EMAIL) ? $v : '';
}

/** AES-256-GCM encrypt a secret at rest using APP_KEY. Returns base64. */
function secret_encrypt(string $plain): string {
    if ($plain === '') return '';
    $key = hash('sha256', APP_KEY, true);
    $iv  = random_bytes(12);
    $tag = '';
    $ct  = openssl_encrypt($plain, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag);
    if ($ct === false) return '';
    return base64_encode($iv . $tag . $ct);
}

/** Reverse of secret_encrypt(). Returns '' on any failure. */
function secret_decrypt(string $blob): string {
    if ($blob === '') return '';
    $raw = base64_decode($blob, true);
    if ($raw === false || strlen($raw) < 29) return '';
    $key = hash('sha256', APP_KEY, true);
    $iv  = substr($raw, 0, 12);
    $tag = substr($raw, 12, 16);
    $ct  = substr($raw, 28);
    $pt  = openssl_decrypt($ct, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag);
    return $pt === false ? '' : $pt;
}

/** Best-effort client IP (respects a single proxy hop, never trusts blindly). */
function client_ip(): string {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : '0.0.0.0';
}
