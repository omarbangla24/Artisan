<?php
/**
 * Minimal, dependency-free SMTP sender.
 * Supports implicit SSL (465), STARTTLS (587), or plain, with AUTH LOGIN/PLAIN.
 * Header values are sanitised to prevent header injection.
 */

/** Strip CR/LF so a value can't inject extra headers. */
function mail_sanitize_header(string $v): string {
    return trim(str_replace(["\r", "\n", "\0"], '', $v));
}

/**
 * Send one message using the stored SMTP settings.
 * Returns [ok(bool), error(string)]. When SMTP is disabled, returns [false, 'disabled'].
 */
function smtp_send(string $toEmail, string $toName, string $subject, string $htmlBody, string $textBody = ''): array {
    if (setting('smtp_enabled') !== '1') return [false, 'SMTP is disabled.'];

    $host = setting('smtp_host');
    $port = (int)(setting('smtp_port') ?: 587);
    $enc  = setting('smtp_encryption', 'tls');
    $user = setting('smtp_username');
    $pass = secret_decrypt(setting('smtp_password_enc'));
    $fromEmail = clean_email(setting('smtp_from_email')) ?: $user;
    $fromName  = setting('smtp_from_name', 'ARTISAN');

    if ($host === '' || $fromEmail === '') return [false, 'SMTP host/from not configured.'];
    $toEmail = clean_email($toEmail);
    if ($toEmail === '') return [false, 'Invalid recipient.'];

    $subject   = mail_sanitize_header($subject);
    $fromName  = mail_sanitize_header($fromName);
    $toName    = mail_sanitize_header($toName);
    if ($textBody === '') $textBody = trim(html_entity_decode(strip_tags($htmlBody), ENT_QUOTES, 'UTF-8'));

    $transport = ($enc === 'ssl') ? 'ssl://' : '';
    $ctx = stream_context_create(['ssl' => [
        'verify_peer'       => true,
        'verify_peer_name'  => true,
        'SNI_enabled'       => true,
    ]]);
    $errno = 0; $errstr = '';
    $fp = @stream_socket_client($transport . $host . ':' . $port, $errno, $errstr, 20,
        STREAM_CLIENT_CONNECT, $ctx);
    if (!$fp) return [false, "Connect failed: $errstr ($errno)"];
    stream_set_timeout($fp, 20);

    $read = function () use ($fp): string {
        $data = '';
        while (($line = fgets($fp, 515)) !== false) {
            $data .= $line;
            if (isset($line[3]) && $line[3] === ' ') break; // last line of reply
        }
        return $data;
    };
    $cmd = function (string $c) use ($fp, $read): string {
        fwrite($fp, $c . "\r\n");
        return $read();
    };
    $code = fn(string $r): int => (int)substr($r, 0, 3);

    $err = function (string $msg) use ($fp): array { @fclose($fp); return [false, $msg]; };

    if ($code($read()) !== 220) return $err('No greeting from server.');

    $ehloHost = mail_sanitize_header($_SERVER['HTTP_HOST'] ?? 'localhost');
    $r = $cmd('EHLO ' . $ehloHost);
    if ($code($r) !== 250) { $r = $cmd('HELO ' . $ehloHost); if ($code($r) !== 250) return $err('EHLO rejected.'); }

    if ($enc === 'tls') {
        if ($code($cmd('STARTTLS')) !== 220) return $err('STARTTLS not accepted.');
        if (!@stream_socket_enable_crypto($fp, true,
            STREAM_CRYPTO_METHOD_TLS_CLIENT | STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT | STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT)) {
            return $err('TLS negotiation failed.');
        }
        $cmd('EHLO ' . $ehloHost);
    }

    if ($user !== '') {
        $r = $cmd('AUTH LOGIN');
        if ($code($r) !== 334) return $err('AUTH LOGIN not supported.');
        if ($code($cmd(base64_encode($user))) !== 334) return $err('Username rejected.');
        if ($code($cmd(base64_encode($pass)))  !== 235) return $err('Authentication failed.');
    }

    if ($code($cmd('MAIL FROM:<' . $fromEmail . '>')) !== 250) return $err('MAIL FROM rejected.');
    if ($code($cmd('RCPT TO:<' . $toEmail . '>')) > 251)       return $err('RCPT TO rejected.');
    if ($code($cmd('DATA')) !== 354) return $err('DATA rejected.');

    $boundary = 'b' . bin2hex(random_bytes(10));
    $fromHdr  = $fromName !== '' ? '=?UTF-8?B?' . base64_encode($fromName) . "?= <$fromEmail>" : $fromEmail;
    $toHdr    = $toName   !== '' ? '=?UTF-8?B?' . base64_encode($toName)   . "?= <$toEmail>"   : $toEmail;
    $subjHdr  = '=?UTF-8?B?' . base64_encode($subject) . '?=';

    $headers  = "From: $fromHdr\r\n";
    $headers .= "To: $toHdr\r\n";
    $headers .= "Subject: $subjHdr\r\n";
    $headers .= 'Date: ' . date('r') . "\r\n";
    $headers .= 'Message-ID: <' . bin2hex(random_bytes(12)) . '@' . $ehloHost . ">\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: multipart/alternative; boundary=\"$boundary\"\r\n";

    $body  = "--$boundary\r\nContent-Type: text/plain; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n";
    $body .= chunk_split(base64_encode($textBody)) . "\r\n";
    $body .= "--$boundary\r\nContent-Type: text/html; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n";
    $body .= chunk_split(base64_encode($htmlBody)) . "\r\n";
    $body .= "--$boundary--\r\n";

    // Dot-stuff any line beginning with '.'
    $data = preg_replace('/^\./m', '..', $headers . "\r\n" . $body);
    fwrite($fp, $data . "\r\n.\r\n");
    if ($code($read()) !== 250) return $err('Message not accepted.');

    $cmd('QUIT');
    @fclose($fp);
    return [true, ''];
}
