<?php
require_once __DIR__ . '/../../db/config.php';

function db(): PDO {
    static $pdo = null;
    if ($pdo) return $pdo;
    $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);
    dbSetup($pdo);
    return $pdo;
}

function dbSetup(PDO $db): void {
    $db->exec("
    CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(150) NOT NULL,
        email VARCHAR(191) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        role VARCHAR(30) NOT NULL DEFAULT 'admin',
        active TINYINT NOT NULL DEFAULT 1,
        created_at DATETIME DEFAULT NOW(),
        last_login DATETIME NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

    CREATE TABLE IF NOT EXISTS settings (
        `key` VARCHAR(100) PRIMARY KEY,
        value TEXT
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

    CREATE TABLE IF NOT EXISTS form_entries (
        id INT AUTO_INCREMENT PRIMARY KEY,
        form_type VARCHAR(60) NOT NULL,
        data LONGTEXT NOT NULL,
        ip VARCHAR(45),
        read_at DATETIME NULL,
        created_at DATETIME DEFAULT NOW()
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

    CREATE TABLE IF NOT EXISTS jobs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(200) NOT NULL,
        department VARCHAR(100),
        job_type VARCHAR(60) DEFAULT 'Full-time',
        location VARCHAR(150) DEFAULT 'Bangladesh',
        description TEXT,
        requirements TEXT,
        apply_email VARCHAR(191),
        deadline DATE NULL,
        published TINYINT DEFAULT 0,
        sort_order INT DEFAULT 0,
        created_at DATETIME DEFAULT NOW()
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

    CREATE TABLE IF NOT EXISTS job_applications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        job_id INT NULL,
        applicant_name VARCHAR(150) NOT NULL,
        email VARCHAR(191) NOT NULL,
        phone VARCHAR(30),
        cover_letter TEXT,
        resume VARCHAR(255),
        status VARCHAR(30) DEFAULT 'new',
        created_at DATETIME DEFAULT NOW(),
        FOREIGN KEY(job_id) REFERENCES jobs(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

    CREATE TABLE IF NOT EXISTS articles (
        id INT AUTO_INCREMENT PRIMARY KEY,
        slug VARCHAR(220) UNIQUE NOT NULL,
        title VARCHAR(255) NOT NULL,
        category VARCHAR(100),
        excerpt TEXT,
        body LONGTEXT,
        thumb VARCHAR(255),
        author_id INT NULL,
        meta_title VARCHAR(255),
        meta_desc TEXT,
        published TINYINT DEFAULT 0,
        created_at DATETIME DEFAULT NOW(),
        updated_at DATETIME DEFAULT NOW()
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

    CREATE TABLE IF NOT EXISTS team_members (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(150) NOT NULL,
        role VARCHAR(150),
        bio TEXT,
        photo VARCHAR(255),
        email VARCHAR(191),
        linkedin VARCHAR(255),
        sort_order INT DEFAULT 0,
        active TINYINT DEFAULT 1,
        created_at DATETIME DEFAULT NOW()
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

    CREATE TABLE IF NOT EXISTS partners (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(150) NOT NULL,
        logo VARCHAR(255),
        url VARCHAR(255),
        category VARCHAR(100),
        sort_order INT DEFAULT 0,
        active TINYINT DEFAULT 1,
        created_at DATETIME DEFAULT NOW()
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

    CREATE TABLE IF NOT EXISTS clients (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(150) NOT NULL,
        logo VARCHAR(255),
        url VARCHAR(255),
        sector VARCHAR(100),
        sort_order INT DEFAULT 0,
        active TINYINT DEFAULT 1,
        created_at DATETIME DEFAULT NOW()
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

    CREATE TABLE IF NOT EXISTS gallery (
        id INT AUTO_INCREMENT PRIMARY KEY,
        filename VARCHAR(255) NOT NULL,
        caption VARCHAR(255),
        category VARCHAR(100),
        sort_order INT DEFAULT 0,
        created_at DATETIME DEFAULT NOW()
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

    CREATE TABLE IF NOT EXISTS activity_log (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NULL,
        user_name VARCHAR(150),
        action VARCHAR(255) NOT NULL,
        target VARCHAR(255),
        ip VARCHAR(45),
        created_at DATETIME DEFAULT NOW()
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

    CREATE TABLE IF NOT EXISTS seo_pages (
        id INT AUTO_INCREMENT PRIMARY KEY,
        slug VARCHAR(100) UNIQUE NOT NULL,
        title VARCHAR(255),
        description TEXT,
        keywords VARCHAR(255),
        og_image VARCHAR(255),
        updated_at DATETIME DEFAULT NOW()
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

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
    $ins = $db->prepare("INSERT IGNORE INTO settings (`key`,value) VALUES (?,?)");
    foreach ($defaults as $k => $v) $ins->execute([$k,$v]);
}
