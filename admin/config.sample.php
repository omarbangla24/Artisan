<?php
/**
 * ARTISAN Admin — configuration template.
 *
 * 1. Copy this file to  admin/config.php   (on the server, via cPanel File Manager).
 * 2. Fill in your real MySQL credentials from cPanel → "MySQL Databases".
 * 3. Generate a unique APP_KEY (used to encrypt SMTP password at rest):
 *        php -r "echo bin2hex(random_bytes(32));"
 *
 * config.php is NOT committed to git and is NOT deleted by the FTP deploy,
 * so your live secrets stay on the server only.
 */

// ---- Database (cPanel → MySQL Databases) --------------------------------
define('DB_DRIVER', 'mysql');                 // 'mysql' on the server
define('DB_HOST',   'localhost');
define('DB_NAME',   'cpanelusr_artisan');     // <-- your database name
define('DB_USER',   'cpanelusr_admin');       // <-- your database user
define('DB_PASS',   'CHANGE_ME');             // <-- your database password
define('DB_CHARSET','utf8mb4');

// ---- App secret (32-byte hex) -------------------------------------------
// Generate once and keep it stable:  php -r "echo bin2hex(random_bytes(32));"
define('APP_KEY', 'PUT_A_64_CHAR_HEX_STRING_HERE');

// ---- First admin (seeded automatically on first run) --------------------
// You will be forced to change this password the first time you log in.
define('SEED_ADMIN_NAME',     'Administrator');
define('SEED_ADMIN_EMAIL',    'omarbangla24@gmail.com');
define('SEED_ADMIN_PASSWORD', 'Artisan@Admin2026');

// ---- Environment --------------------------------------------------------
// Set false on the live server so errors are logged, not shown.
define('APP_DEBUG', false);
