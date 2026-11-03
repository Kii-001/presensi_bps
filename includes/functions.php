<?php
require_once __DIR__ . '/../config/config.php';
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax']);
    session_start();
}

// Cadangan jika ekstensi mbstring tidak aktif
if (!function_exists('mb_substr')) { function mb_substr($s, $a, $l = null) { return substr($s, $a, $l); } }
if (!function_exists('mb_strlen')) { function mb_strlen($s) { return strlen($s); } }

const BULAN = [1=>'Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
const HARI  = ['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'];

function db(): PDO {
    static $pdo;
    if (!$pdo) {
        try {
            $pdo = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4', DB_USER, DB_PASS,
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
        } catch (PDOException $ex) {
            exit('Koneksi database gagal. Periksa config/config.php dan pastikan database.sql sudah di-import.');
        }
    }
    return $pdo;
}
function q(string $sql, array $p = []): PDOStatement { $s = db()->prepare($sql); $s->execute($p); return $s; }
function setting(string $k, $d = '') {
    static $c;
    if ($c === null) { $c = q('SELECT kunci, nilai FROM pengaturan')->fetchAll(PDO::FETCH_KEY_PAIR); }
    return $c[$k] ?? $d;
}
function e($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function url(string $p = ''): string { return BASE_URL . '/' . ltrim($p, '/'); }
function redirect(string $p): void { header('Location: ' . url($p)); exit; }
function flash(?string $msg = null, string $type = 'ok') {
    if ($msg !== null) { $_SESSION['flash'] = [$msg, $type]; return null; }
    $f = $_SESSION['flash'] ?? null; unset($_SESSION['flash']); return $f;
}
function csrf(): string { return $_SESSION['csrf'] ??= bin2hex(random_bytes(16)); }
function csrf_field(): string { return '<input type="hidden" name="csrf" value="' . csrf() . '">'; }
function csrf_check(): void {
    if (!hash_equals($_SESSION['csrf'] ?? 'x', $_POST['csrf'] ?? '')) { http_response_code(419); exit('Sesi kedaluwarsa. Muat ulang halaman lalu coba lagi.'); }
}
function me(): ?array { return $_SESSION['user'] ?? null; }
function require_role(string $role): array {
    $u = me();
    if (!$u) redirect('login.php');
    if ($u['role'] !== $role) redirect($u['role'] === 'admin' ? 'admin/dashboard.php' : 'user/dashboard.php');
    return $u;
}

// ---------- tanggal & hitung ----------
function tgl(?string $d, bool $hari = false): string {
    if (!$d) return '-';
    $t = strtotime($d); $s = date('j', $t) . ' ' . BULAN[(int)date('n', $t)] . ' ' . date('Y', $t);
    return $hari ? HARI[(int)date('w', $t)] . ', ' . $s : $s;
}
function jam(?string $t): string { return $t ? substr($t, 0, 5) : '-'; }
function hari_kerja(string $from, string $to): int {
    $n = 0;
    for ($t = strtotime($from), $end = strtotime($to); $t <= $end; $t = strtotime('+1 day', $t)) { if (date('N', $t) < 6) $n++; }
    return $n;
}
// Rumus Haversine, hasil dalam meter
function jarak_m(float $lat1, float $lng1, float $lat2, float $lng2): float {
    $R = 6371000; $p1 = deg2rad($lat1); $p2 = deg2rad($lat2);
    $dp = deg2rad($lat2 - $lat1); $dl = deg2rad($lng2 - $lng1);
    $a = sin($dp / 2) ** 2 + cos($p1) * cos($p2) * sin($dl / 2) ** 2;
    return 2 * $R * atan2(sqrt($a), sqrt(1 - $a));
}
function badge(string $s): string {
    $m = ['hadir'=>['Hadir','ok'],'terlambat'=>['Terlambat','warn'],'izin'=>['Izin','info'],'sakit'=>['Sakit','info'],
          'alpa'=>['Alpa','bad'],'belum'=>['Belum absen','mute'],'libur'=>['Libur','mute'],
          'menunggu'=>['Menunggu','warn'],'disetujui'=>['Disetujui','ok'],'ditolak'=>['Ditolak','bad']];
    [$l, $c] = $m[$s] ?? [ucfirst($s), 'mute'];
    return '<span class="badge b-' . $c . '">' . $l . '</span>';
}
function inisial(string $n): string {
    $p = preg_split('/\s+/', trim($n)); $s = strtoupper(mb_substr($p[0], 0, 1));
    if (isset($p[1])) $s .= strtoupper(mb_substr($p[1], 0, 1));
    return $s;
}
function foto_tag(?string $path, string $alt = 'Selfie'): string {
    if ($path && file_exists(__DIR__ . '/../' . $path)) {
        return '<img class="thumb zoomable" src="' . e(url($path)) . '" alt="' . e($alt) . '">';
    }
    return '<span class="thumb thumb-empty">Tidak ada foto</span>';
}
function logo_url(): string {
    $png = __DIR__ . '/../assets/img/logo-bps.png';
    $svg = __DIR__ . '/../assets/img/logo-bps.svg';
    if (file_exists($png)) return url('assets/img/logo-bps.png');
    if (file_exists($svg)) return url('assets/img/logo-bps.svg');
    return url('assets/img/logo-bps.svg'); // fallback
}

// ---------- simpan selfie (base64 dari kamera) ----------
function save_selfie(string $dataUrl, int $uid, string $tipe): ?string {
    if (!preg_match('#^data:image/(jpeg|png);base64,#', $dataUrl, $m)) return null;
    $bin = base64_decode(substr($dataUrl, strpos($dataUrl, ',') + 1), true);
    if (!$bin || strlen($bin) > 3 * 1024 * 1024 || !@getimagesizefromstring($bin)) return null;
    $rel = 'uploads/selfie/' . date('Y/m');
    $dir = __DIR__ . '/../' . $rel;
    if (!is_dir($dir)) mkdir($dir, 0775, true);
    $name = $uid . '_' . date('Ymd_His') . '_' . $tipe . '_' . bin2hex(random_bytes(3)) . '.' . ($m[1] === 'png' ? 'png' : 'jpg');
    return file_put_contents($dir . '/' . $name, $bin) ? $rel . '/' . $name : null;
}

// ---------- tampilan ----------
function icon(string $n): string {
    $i = [
     'home'=>'M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z M9 22V12h6v10',
     'users'=>'M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2 M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8 M23 21v-2a4 4 0 0 0-3-3.87 M16 3.13a4 4 0 0 1 0 7.75',
     'check'=>'M9 11l3 3L22 4 M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11',
     'map'=>'M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z M12 13a3 3 0 1 0 0-6 3 3 0 0 0 0 6',
     'mail'=>'M4 4h16a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2z M22 6l-10 7L2 6',
     'book'=>'M4 19.5A2.5 2.5 0 0 1 6.5 17H20 M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z',
     'file'=>'M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z M14 2v6h6 M16 13H8 M16 17H8',
     'sliders'=>'M4 21v-7 M4 10V3 M12 21v-9 M12 8V3 M20 21v-5 M20 12V3 M1 14h6 M9 8h6 M17 16h6',
     'user'=>'M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2 M12 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8',
     'logout'=>'M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4 M16 17l5-5-5-5 M21 12H9',
     'clock'=>'M12 22a10 10 0 1 0 0-20 10 10 0 0 0 0 20z M12 6v6l4 2',
     'menu'=>'M3 12h18 M3 6h18 M3 18h18',
    ];
    return '<svg class="ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="' . ($i[$n] ?? '') . '"/></svg>';
}
function menu_items(string $role): array {
    if ($role === 'admin') {
        $pending = (int)q("SELECT COUNT(*) FROM izin WHERE status='menunggu'")->fetchColumn();
        return [
            ['dashboard', 'Dashboard', 'admin/dashboard.php', 'home', 0],
            ['mahasiswa', 'Data Mahasiswa', 'admin/mahasiswa.php', 'users', 0],
            ['presensi', 'Rekap Presensi', 'admin/presensi.php', 'check', 0],
            ['peta', 'Peta Presensi', 'admin/peta.php', 'map', 0],
            ['izin', 'Izin & Sakit', 'admin/izin.php', 'mail', $pending],
            ['kegiatan', 'Kegiatan Harian', 'admin/kegiatan.php', 'book', 0],
            ['laporan', 'Laporan', 'admin/laporan.php', 'file', 0],
            ['pengaturan', 'Pengaturan', 'admin/pengaturan.php', 'sliders', 0],
        ];
    }
    return [
        ['dashboard', 'Beranda', 'user/dashboard.php', 'home', 0],
        ['presensi', 'Presensi', 'user/presensi.php', 'clock', 0],
        ['riwayat', 'Riwayat Presensi', 'user/riwayat.php', 'check', 0],
        ['kegiatan', 'Kegiatan Harian', 'user/kegiatan.php', 'book', 0],
        ['izin', 'Izin & Sakit', 'user/izin.php', 'mail', 0],
        ['profil', 'Profil', 'user/profil.php', 'user', 0],
    ];
}
function page_start(string $title, string $active = '', string $head = ''): void {
    $u = me(); $items = menu_items($u['role']); $fl = flash();
    $roleLabel = $u['role'] === 'admin' ? 'Pengawas' : 'Mahasiswa magang';
    ?><!doctype html>
<html lang="id"><head><meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($title) ?> | <?= e(APP_NAME) ?></title>
<link rel="icon" href="<?= e(logo_url()) ?>">
<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= e(url('assets/css/style.css')) ?>">
<?= $head ?></head>
<body>
<div class="app">
  <aside class="side" id="side">
    <div class="brand"><img src="<?= e(logo_url()) ?>" alt="Logo BPS"><div><b>BPS Kota Medan</b><span>Presensi Magang</span></div></div>
    <nav>
      <?php foreach ($items as [$key, $label, $href, $ic, $count]): ?>
        <a href="<?= e(url($href)) ?>" class="<?= $active === $key ? 'on' : '' ?>"><?= icon($ic) ?><span><?= e($label) ?></span><?php if ($count): ?><em><?= $count ?></em><?php endif; ?></a>
      <?php endforeach; ?>
    </nav>
    <div class="who"><div class="av"><?= e(inisial($u['nama'])) ?></div><div><b><?= e($u['nama']) ?></b><span><?= $roleLabel ?></span></div>
      <a href="<?= e(url('logout.php')) ?>" title="Keluar" class="out"><?= icon('logout') ?></a></div>
  </aside>
  <div class="scrim" id="scrim"></div>
  <main class="main">
    <header class="top no-print">
      <button class="burger" id="burger" aria-label="Menu"><?= icon('menu') ?></button>
      <h1><?= e($title) ?></h1>
      <span class="date"><?= e(tgl(date('Y-m-d'), true)) ?></span>
    </header>
    <div class="print-head"><img src="<?= e(logo_url()) ?>" alt=""><div><b>Badan Pusat Statistik Kota Medan</b><span><?= e($title) ?></span></div></div>
    <div class="content">
    <?php if ($fl): ?><div class="alert a-<?= e($fl[1]) ?>"><?= e($fl[0]) ?></div><?php endif; ?>
<?php }
function page_end(string $scripts = ''): void {
    echo '</div></main></div><div class="lightbox" id="lightbox"><img alt=""></div>';
    echo '<script src="' . e(url('assets/js/app.js')) . '"></script>' . $scripts . '</body></html>';
}

// Rekap kehadiran satu mahasiswa pada rentang tanggal
function rekap_user(array $u, string $from, string $to): array {
    $start = max($from, $u['mulai_magang'] ?: $from);
    $end   = min($to, date('Y-m-d', strtotime('-1 day')));
    if ($start > $end) return ['hadir' => 0, 'terlambat' => 0, 'izin' => 0, 'sakit' => 0, 'alpa' => 0];
    $r = q("SELECT status, COUNT(*) n FROM presensi WHERE user_id=? AND tanggal BETWEEN ? AND ? GROUP BY status", [$u['id'], $start, $end])->fetchAll(PDO::FETCH_KEY_PAIR);
    $h = (int)($r['hadir'] ?? 0); $t = (int)($r['terlambat'] ?? 0); $i = (int)($r['izin'] ?? 0); $s = (int)($r['sakit'] ?? 0);
    $alpa = max(0, hari_kerja($start, $end) - ($h + $t + $i + $s));
    return ['hadir' => $h, 'terlambat' => $t, 'izin' => $i, 'sakit' => $s, 'alpa' => $alpa];
}
