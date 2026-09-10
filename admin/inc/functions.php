<?php
function setting(string $key, string $default = ''): string {
    static $cache = [];
    if (!array_key_exists($key, $cache)) {
        $stmt = db()->prepare("SELECT value FROM settings WHERE key=?");
        $stmt->execute([$key]);
        $row = $stmt->fetch();
        $cache[$key] = $row ? $row['value'] : $default;
    }
    return $cache[$key];
}

function saveSetting(string $key, string $value): void {
    db()->prepare("INSERT INTO settings (`key`,value) VALUES (?,?) ON DUPLICATE KEY UPDATE value=VALUES(value)")->execute([$key,$value]);
}

function uploadFile(array|string $fileOrField, string $subdir, array $allow = ['jpg','jpeg','png','webp','gif']): ?string {
    $file = is_string($fileOrField) ? ($_FILES[$fileOrField] ?? null) : $fileOrField;
    if (!$file || empty($file['tmp_name'])) return null;
    if ($file['error'] !== UPLOAD_ERR_OK) return null;
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allow)) return null;
    if ($file['size'] > 5 * 1024 * 1024) return null;
    $name = uniqid('', true) . '.' . $ext;
    $dir = __DIR__ . '/../../admin/uploads/' . $subdir . '/';
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    move_uploaded_file($file['tmp_name'], $dir . $name);
    return $name;
}

function sendMail(string $to, string $subject, string $body, bool $isHtml = true): bool {
    $host    = setting('smtp_host');
    $port    = (int) setting('smtp_port', '587');
    $user    = setting('smtp_user');
    $pass    = setting('smtp_pass');
    $from    = setting('smtp_from', 'info@artisancabd.com');
    $fromName = setting('smtp_from_name', 'ARTISAN');
    $secure  = setting('smtp_secure', 'tls');

    if (!$host || !$user) {
        // Fallback to PHP mail()
        $headers = "From: $fromName <$from>\r\nContent-Type: text/html; charset=UTF-8";
        return mail($to, $subject, $body, $headers);
    }

    // Native SMTP via socket
    try {
        $errno = 0; $errstr = '';
        $prefix = $secure === 'ssl' ? 'ssl://' : '';
        $sock = fsockopen($prefix . $host, $port, $errno, $errstr, 10);
        if (!$sock) return false;

        $recv = function() use ($sock) { return fgets($sock, 512); };
        $send = function(string $cmd) use ($sock) { fputs($sock, $cmd . "\r\n"); };

        $recv(); // greeting
        $send("EHLO " . $_SERVER['SERVER_NAME'] ?? 'localhost');
        while (($line = $recv()) !== false) { if (substr($line,3,1) === ' ') break; }

        if ($secure === 'tls') {
            $send("STARTTLS");
            $recv();
            stream_socket_enable_crypto($sock, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
            $send("EHLO " . ($_SERVER['SERVER_NAME'] ?? 'localhost'));
            while (($line = $recv()) !== false) { if (substr($line,3,1) === ' ') break; }
        }

        $send("AUTH LOGIN");
        $recv();
        $send(base64_encode($user)); $recv();
        $send(base64_encode($pass)); $recv();
        $send("MAIL FROM:<$from>"); $recv();
        $send("RCPT TO:<$to>"); $recv();
        $send("DATA"); $recv();

        $ct = $isHtml ? 'text/html' : 'text/plain';
        $msg  = "From: $fromName <$from>\r\n";
        $msg .= "To: $to\r\n";
        $msg .= "Subject: $subject\r\n";
        $msg .= "MIME-Version: 1.0\r\nContent-Type: $ct; charset=UTF-8\r\n\r\n";
        $msg .= $body . "\r\n.";
        $send($msg); $recv();
        $send("QUIT"); fclose($sock);
        return true;
    } catch (\Throwable $e) {
        error_log('SMTP error: ' . $e->getMessage());
        return false;
    }
}

function e(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }
function truncate(string $s, int $len = 80): string { return mb_strlen($s) > $len ? mb_substr($s,0,$len).'…' : $s; }
function ago(string $dt): string {
    $diff = time() - strtotime($dt);
    if ($diff < 60) return 'just now';
    if ($diff < 3600) return intval($diff/60) . 'm ago';
    if ($diff < 86400) return intval($diff/3600) . 'h ago';
    return intval($diff/86400) . 'd ago';
}
function paginate(int $total, int $page, int $perPage, string $url): string {
    $pages = ceil($total / $perPage);
    if ($pages <= 1) return '';
    $html = '<div class="pager">';
    for ($i = 1; $i <= $pages; $i++) {
        $active = $i === $page ? ' active' : '';
        $html .= "<a href=\"{$url}&page={$i}\" class=\"pager-btn{$active}\">{$i}</a>";
    }
    return $html . '</div>';
}
