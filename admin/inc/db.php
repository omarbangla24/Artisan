<?php
define('DB_PATH', __DIR__ . '/../../db/artisan.db');

function db(): PDO {
    static $pdo = null;
    if ($pdo) return $pdo;
    $pdo = new PDO('sqlite:' . DB_PATH);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->exec('PRAGMA journal_mode=WAL; PRAGMA foreign_keys=ON;');
    dbSetup($pdo);
    return $pdo;
}

function dbSetup(PDO $db): void {
    $db->exec("
    CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL,
        email TEXT UNIQUE NOT NULL,
        password TEXT NOT NULL,
        role TEXT NOT NULL DEFAULT 'admin',
        active INTEGER NOT NULL DEFAULT 1,
        created_at TEXT DEFAULT (datetime('now')),
        last_login TEXT
    );
    CREATE TABLE IF NOT EXISTS settings (
        key TEXT PRIMARY KEY,
        value TEXT
    );
    CREATE TABLE IF NOT EXISTS form_entries (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        form_type TEXT NOT NULL,
        data TEXT NOT NULL,
        ip TEXT,
        read_at TEXT,
        created_at TEXT DEFAULT (datetime('now'))
    );
    CREATE TABLE IF NOT EXISTS jobs (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        title TEXT NOT NULL,
        department TEXT,
        job_type TEXT DEFAULT 'Full-time',
        location TEXT DEFAULT 'Bangladesh',
        description TEXT,
        requirements TEXT,
        apply_email TEXT,
        deadline TEXT,
        published INTEGER DEFAULT 0,
        sort_order INTEGER DEFAULT 0,
        created_at TEXT DEFAULT (datetime('now'))
    );
    CREATE TABLE IF NOT EXISTS job_applications (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        job_id INTEGER,
        applicant_name TEXT NOT NULL,
        email TEXT NOT NULL,
        phone TEXT,
        cover_letter TEXT,
        resume TEXT,
        status TEXT DEFAULT 'new',
        created_at TEXT DEFAULT (datetime('now')),
        FOREIGN KEY(job_id) REFERENCES jobs(id) ON DELETE SET NULL
    );
    CREATE TABLE IF NOT EXISTS articles (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        slug TEXT UNIQUE NOT NULL,
        title TEXT NOT NULL,
        category TEXT,
        excerpt TEXT,
        body TEXT,
        thumb TEXT,
        author_id INTEGER,
        meta_title TEXT,
        meta_desc TEXT,
        published INTEGER DEFAULT 0,
        created_at TEXT DEFAULT (datetime('now')),
        updated_at TEXT DEFAULT (datetime('now')),
        FOREIGN KEY(author_id) REFERENCES users(id) ON DELETE SET NULL
    );
    CREATE TABLE IF NOT EXISTS team_members (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL,
        role TEXT,
        bio TEXT,
        photo TEXT,
        email TEXT,
        linkedin TEXT,
        sort_order INTEGER DEFAULT 0,
        active INTEGER DEFAULT 1,
        created_at TEXT DEFAULT (datetime('now'))
    );
    CREATE TABLE IF NOT EXISTS partners (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL,
        logo TEXT,
        url TEXT,
        category TEXT,
        sort_order INTEGER DEFAULT 0,
        active INTEGER DEFAULT 1,
        created_at TEXT DEFAULT (datetime('now'))
    );
    CREATE TABLE IF NOT EXISTS clients (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL,
        logo TEXT,
        url TEXT,
        sector TEXT,
        sort_order INTEGER DEFAULT 0,
        active INTEGER DEFAULT 1,
        created_at TEXT DEFAULT (datetime('now'))
    );
    CREATE TABLE IF NOT EXISTS gallery (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        filename TEXT NOT NULL,
        caption TEXT,
        category TEXT,
        sort_order INTEGER DEFAULT 0,
        created_at TEXT DEFAULT (datetime('now'))
    );
    CREATE TABLE IF NOT EXISTS activity_log (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER,
        user_name TEXT,
        action TEXT NOT NULL,
        target TEXT,
        ip TEXT,
        created_at TEXT DEFAULT (datetime('now'))
    );
    CREATE TABLE IF NOT EXISTS seo_pages (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        slug TEXT UNIQUE NOT NULL,
        title TEXT,
        description TEXT,
        keywords TEXT,
        og_image TEXT,
        updated_at TEXT DEFAULT (datetime('now'))
    );
    ");

    // Run lightweight migrations for columns added after initial deploy
    $migrations = [
        "ALTER TABLE jobs ADD COLUMN apply_email TEXT",
        "ALTER TABLE jobs ADD COLUMN job_type TEXT DEFAULT 'Full-time'",
        "ALTER TABLE articles ADD COLUMN category TEXT",
        "ALTER TABLE articles ADD COLUMN thumb TEXT",
        "ALTER TABLE articles ADD COLUMN author_id INTEGER",
        "ALTER TABLE articles ADD COLUMN meta_title TEXT",
        "ALTER TABLE articles ADD COLUMN meta_desc TEXT",
        "ALTER TABLE team_members ADD COLUMN role TEXT",
        "ALTER TABLE team_members ADD COLUMN active INTEGER DEFAULT 1",
        "ALTER TABLE partners ADD COLUMN logo TEXT",
        "ALTER TABLE partners ADD COLUMN url TEXT",
        "ALTER TABLE partners ADD COLUMN category TEXT",
        "ALTER TABLE partners ADD COLUMN active INTEGER DEFAULT 1",
        "ALTER TABLE clients ADD COLUMN url TEXT",
        "ALTER TABLE clients ADD COLUMN active INTEGER DEFAULT 1",
        "ALTER TABLE gallery ADD COLUMN filename TEXT",
        "ALTER TABLE gallery ADD COLUMN caption TEXT",
        "ALTER TABLE seo_pages ADD COLUMN slug TEXT",
        "ALTER TABLE seo_pages ADD COLUMN keywords TEXT",
        "ALTER TABLE job_applications ADD COLUMN applicant_name TEXT",
        "ALTER TABLE job_applications ADD COLUMN resume TEXT",
    ];
    foreach ($migrations as $sql) {
        try { $db->exec($sql); } catch (\Throwable $_) { /* column already exists */ }
    }

    // Default admin
    $exists = $db->query("SELECT id FROM users WHERE email='admin@artisancabd.com'")->fetch();
    if (!$exists) {
        $hash = password_hash('Admin@1234', PASSWORD_DEFAULT);
        $db->prepare("INSERT INTO users (name,email,password,role) VALUES (?,?,?,?)")
           ->execute(['Administrator','admin@artisancabd.com',$hash,'superadmin']);
    }
    // Default settings
    $defaults = [
        'smtp_host'=>'','smtp_port'=>'587','smtp_user'=>'','smtp_pass'=>'',
        'smtp_from'=>'info@artisancabd.com','smtp_from_name'=>'ARTISAN Chartered Accountants',
        'smtp_secure'=>'tls','notify_email'=>'info@artisancabd.com',
        'seo_site_name'=>'ARTISAN Chartered Accountants',
        'seo_default_title'=>'%s — ARTISAN Chartered Accountants',
    ];
    $ins = $db->prepare("INSERT OR IGNORE INTO settings (key,value) VALUES (?,?)");
    foreach ($defaults as $k => $v) $ins->execute([$k,$v]);
}
