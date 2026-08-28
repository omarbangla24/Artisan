# ARTISAN Admin — setup (cPanel)

The admin panel lives at **`/admin`** and stores contact/consultation/newsletter
submissions, manages users, and holds SMTP + Search Console settings.

It needs a MySQL database and a `config.php` that is **created on the server only**
(never committed to git, never overwritten by the FTP deploy).

## 1. Create a MySQL database (cPanel → "MySQL Databases")

1. Create a database, e.g. `cpaneluser_artisan`.
2. Create a database user with a strong password.
3. **Add the user to the database** and grant **ALL PRIVILEGES**.
4. Note the database name, user, and password.

## 2. Create `admin/config.php` on the server (cPanel → File Manager)

Copy `admin/config.sample.php` to `admin/config.php` and fill in:

- `DB_NAME`, `DB_USER`, `DB_PASS` — from step 1.
- `APP_KEY` — a unique 64-char hex string. Generate one with, e.g.
  `php -r "echo bin2hex(random_bytes(32));"` (or any hex generator).
- Leave `SEED_ADMIN_EMAIL` / `SEED_ADMIN_PASSWORD` as-is or change them.
- Keep `APP_DEBUG` set to `false` on the live server.

The database tables are created automatically on the first visit to `/admin`.

## 3. First login

- Go to **https://artisan-ca.net/admin**
- Email: `omarbangla24@gmail.com`  (or whatever `SEED_ADMIN_EMAIL` is)
- Password: `Artisan@Admin2026`  (or your `SEED_ADMIN_PASSWORD`)
- You will be **forced to set a new password** immediately.

## 4. Turn on email (optional but recommended)

**Admin → Settings → Email notifications (SMTP)**

- Enable SMTP, enter host/port/username/password (in cPanel → "Email Accounts"
  create e.g. `no-reply@artisan-ca.net`; typical values: host `mail.artisan-ca.net`,
  port `465` SSL or `587` STARTTLS).
- Set the "From" address to that mailbox.
- Under **Form notifications**, set who gets alerted on each new submission.
- Click **Send test email** to confirm it works.

The SMTP password is stored **encrypted** (AES-256-GCM using `APP_KEY`).

## 5. Search Console / analytics

**Admin → Settings → Search Console & analytics**

- Paste the Google Search Console verification token (the `content` value only).
- Optionally add Bing token, GA4 ID (`G-…`), GTM ID (`GTM-…`), or custom
  `<head>` / `<body>` HTML.
- Saving writes `assets/inc/site-head.php` and `site-body.php`, which every public
  page includes — so the tags appear site-wide with no database calls on page load.

## Security notes

- Passwords hashed with `password_hash` (bcrypt); login lockout after 5 failed
  attempts for 15 minutes; 60-minute idle auto-logout.
- CSRF tokens on every admin form; prepared statements throughout; output escaped.
- Public form endpoint (`/submit`) is protected by honeypot + same-origin check +
  per-IP rate limiting (8/hour).
- `config.php`, `admin/inc/`, and dotfiles are blocked from direct web access;
  the whole `/admin` area sends `noindex`.
- Deploy never uploads or deletes `admin/config.php` or `assets/inc/`, so live
  secrets and generated files are safe.
