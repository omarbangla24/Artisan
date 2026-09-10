<?php
/**
 * ONE-TIME DATA SEED — run once, then delete this file.
 * Imports all existing hardcoded data into the MySQL database.
 * URL: https://artisancabd.com/admin/seed.php
 */
require_once __DIR__ . '/inc/auth.php';
require_once __DIR__ . '/inc/functions.php';
requireAuth();
requireRole('superadmin');

$db  = db();
$log = [];
$err = [];

// ── Helper: copy asset to uploads dir, return new filename ──────────────────
function seedCopy(string $src, string $dir): string {
    $base  = __DIR__ . '/uploads/' . $dir . '/';
    if (!is_dir($base)) mkdir($base, 0755, true);
    $file  = basename($src);
    $dest  = $base . $file;
    if (!file_exists($dest) && file_exists(__DIR__ . '/../' . $src)) {
        copy(__DIR__ . '/../' . $src, $dest);
    }
    return $file;
}

// ── CLIENTS ─────────────────────────────────────────────────────────────────
$clients = [
    ['American Life Insurance Company (Alico)',           'assets/img/client/alico-logo-png_seeklogo-427723.png',                         'Insurance'],
    ['Delta Life Insurance',                              'assets/img/client/delta_life_insurance_company_limited_logo.jpeg',              'Insurance'],
    ['Dhaka Electricity Supply Company (DESCO)',          'assets/img/client/Dhaka-Electric-Supply-Company-Logo-Vector.svg-.png',          'Utilities'],
    ['Foodpanda',                                         'assets/img/client/Foodpanda_logo_since_2017.jpeg',                             'Food & Beverage'],
    ['Jamuna Electronics & Automobiles',                  'assets/img/client/Jamuna-Electronics-Logo-Vector.svg-.png',                    'Electronics'],
    ['Global Heavy Chemical',                             'assets/img/client/cropped-Global-Heavy-Chemicals-Logo-Black-300x100.png.webp', 'Chemical'],
    ['Lira Group of Industries',                          'assets/img/client/lira_group_of_industries_logo.jpeg',                        'Manufacturing'],
    ['Peoples Insurance Company',                         'assets/img/client/peoples-logo-1000-x-563.gif',                               'Insurance'],
    ['United Group',                                      'assets/img/client/united-group1.webp',                                        'Conglomerate'],
    ['Uttara Motor Corporation',                          'assets/img/client/uttara-motors-logo-png_seeklogo-447358.png',                 'Automotive'],
    ['Standard Ceramic Bangladesh (Super Stone)',         'assets/img/client/standard-ceramic.jpeg',                                     'Manufacturing'],
    ['Client',                                            'assets/img/client/logo.png',                                                  ''],
    ['Client',                                            'assets/img/client/logo-1.png',                                               ''],
    ['Client',                                            'assets/img/client/logo-2.png',                                               ''],
    ['Client',                                            'assets/img/client/logo2.png',                                                ''],
    ['Client',                                            'assets/img/client/logo-fb.png',                                              ''],
    ['Client',                                            'assets/img/client/images.png',                                               ''],
    ['Client',                                            'assets/img/client/images-2.png',                                             ''],
    ['Client',                                            'assets/img/client/images-3.png',                                             ''],
    ['Client',                                            'assets/img/client/images-3.jpeg',                                            ''],
    ['Client',                                            'assets/img/client/images-4.jpeg',                                            ''],
    ['Client',                                            'assets/img/client/6.jpg',                                                    ''],
    ['Client',                                            'assets/img/client/1751171001116.jpeg',                                       ''],
    ['Client',                                            'assets/img/client/32fa828ba61ea8d3e8dcce2e990a304e251f5819_url.jpeg',         ''],
    ['Client',                                            'assets/img/client/1-20211108071725-583267265-1218872872.png.webp',            ''],
];

$inserted_clients = 0;
foreach ($clients as $i => [$name, $src, $sector]) {
    try {
        $logo = seedCopy($src, 'clients');
        $db->prepare("INSERT IGNORE INTO clients (name,logo,sector,sort_order,active,created_at) VALUES (?,?,?,?,1,NOW())")
           ->execute([$name, $logo, $sector, $i + 1]);
        $inserted_clients++;
    } catch (\Throwable $e) { $err[] = "Client '$name': " . $e->getMessage(); }
}
$log[] = "✓ Clients: $inserted_clients inserted";

// ── TEAM MEMBERS ────────────────────────────────────────────────────────────
$team = [
    ['A F M Alamgir FCA',         'Chief Executive Partner', 'assets/img/partner-alamgir.jpg', 1],
    ['Md. Abdus Salam FCA',       'Senior Partner',          'assets/img/partner-salam.jpg',   2],
    ['Md. A M Khan Lohani FCA',   'Senior Partner',          'assets/img/partner-lohani.jpg',  3],
    ['Md. Selim Reza FCA',        'Senior Partner',          'assets/img/partner-reza.jpg',    4],
    ['Dr. ASM Hossain Taiyab FCA','Senior Partner',          'assets/img/partner-taiyab.jpg',  5],
    ['Md. Harun-Or-Rashid FCA',   'Partner',                 'assets/img/partner-harun.jpg',   6],
];

$inserted_team = 0;
foreach ($team as [$name, $role, $src, $order]) {
    try {
        $photo = seedCopy($src, 'team');
        $db->prepare("INSERT IGNORE INTO team_members (name,role,photo,sort_order,active,created_at) VALUES (?,?,?,?,1,NOW())")
           ->execute([$name, $role, $photo, $order]);
        $inserted_team++;
    } catch (\Throwable $e) { $err[] = "Team '$name': " . $e->getMessage(); }
}
$log[] = "✓ Team: $inserted_team members inserted";

// ── PARTNERS / AFFILIATIONS ──────────────────────────────────────────────────
$partners = [
    ['Institute of Chartered Accountants of Bangladesh', 'ICAB',         'Affiliation & Membership', 1],
    ['Institute of Company Secretaries of Bangladesh',   'ICSB',         'Affiliation & Membership', 2],
    ['Institute of Public Accountants (Australia)',      'IPA Australia', 'Affiliation & Membership', 3],
    ['Financial Reporting Council',                      'FRC',           'Regulatory Enlistment',    4],
    ['Bangladesh Securities and Exchange Commission',    'BSEC',          'Regulatory Enlistment',    5],
    ['Insurance Development & Regulatory Authority',     'IDRA',          'Regulatory Enlistment',    6],
    ['NGO Affairs Bureau',                               'NGOAB',         'Regulatory Enlistment',    7],
    ['Microcredit Regulatory Authority',                 'MRA',           'Regulatory Enlistment',    8],
];

$inserted_partners = 0;
foreach ($partners as [$name, $abbr, $category, $order]) {
    try {
        $db->prepare("INSERT IGNORE INTO partners (name,category,sort_order,active,created_at) VALUES (?,?,?,1,NOW())")
           ->execute(["$name ($abbr)", $category, $order]);
        $inserted_partners++;
    } catch (\Throwable $e) { $err[] = "Partner '$name': " . $e->getMessage(); }
}
$log[] = "✓ Partners/Affiliations: $inserted_partners inserted";

// ── GALLERY ─────────────────────────────────────────────────────────────────
$gallery = [
    ['assets/img/e1.jpg', 'ARTISAN Chartered Accountants Event',        'Events'],
    ['assets/img/e2.jpg', 'ARTISAN Team Moment',                        'Events'],
    ['assets/img/e3.jpg', 'ARTISAN Professional Gathering',             'Events'],
];

$inserted_gallery = 0;
foreach ($gallery as [$src, $caption, $category]) {
    try {
        $filename = seedCopy($src, 'gallery');
        $db->prepare("INSERT IGNORE INTO gallery (filename,caption,category,sort_order,created_at) VALUES (?,?,?,?,NOW())")
           ->execute([$filename, $caption, $category, $inserted_gallery + 1]);
        $inserted_gallery++;
    } catch (\Throwable $e) { $err[] = "Gallery '$src': " . $e->getMessage(); }
}
$log[] = "✓ Gallery: $inserted_gallery images inserted";

$pageTitle = 'Data Seed';
include __DIR__ . '/inc/header.php';
?>
<div class="card" style="max-width:640px">
  <div class="card-header">
    <h2>Data Seed — Results</h2>
    <?php if(empty($err)): ?>
    <span class="badge badge-green">All done</span>
    <?php else: ?>
    <span class="badge badge-yellow"><?= count($err) ?> error(s)</span>
    <?php endif; ?>
  </div>
  <div class="card-body">
    <?php foreach($log as $l): ?>
    <div class="alert alert-success" style="margin-bottom:8px"><?= htmlspecialchars($l) ?></div>
    <?php endforeach; ?>
    <?php foreach($err as $e): ?>
    <div class="alert alert-danger" style="margin-bottom:8px"><?= htmlspecialchars($e) ?></div>
    <?php endforeach; ?>

    <div style="margin-top:20px;padding-top:16px;border-top:1px solid var(--border)">
      <p style="color:var(--muted);font-size:13px;margin-bottom:14px">
        ⚠️ <strong>Delete this file</strong> from the server after seeding — it should not remain accessible.
      </p>
      <div style="display:flex;gap:10px;flex-wrap:wrap">
        <a href="/admin/clients.php" class="btn btn-secondary btn-sm">View Clients →</a>
        <a href="/admin/team.php" class="btn btn-secondary btn-sm">View Team →</a>
        <a href="/admin/partners.php" class="btn btn-secondary btn-sm">View Partners →</a>
        <a href="/admin/gallery.php" class="btn btn-secondary btn-sm">View Gallery →</a>
      </div>
    </div>
  </div>
</div>
<?php include __DIR__ . '/inc/footer.php'; ?>
