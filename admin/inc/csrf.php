<?php
/** Per-session CSRF token with constant-time verification. */

function csrf_token(): string {
    if (empty($_SESSION['_csrf'])) {
        $_SESSION['_csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['_csrf'];
}

/** Hidden input for forms. */
function csrf_field(): string {
    return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
}

/** Verify a submitted token; call on every state-changing POST. */
function csrf_check(): void {
    $sent = $_POST['_csrf'] ?? '';
    if (!is_string($sent) || empty($_SESSION['_csrf']) ||
        !hash_equals($_SESSION['_csrf'], $sent)) {
        http_response_code(400);
        exit('Invalid or expired form token. Please go back and try again.');
    }
}
