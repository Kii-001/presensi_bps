<?php
require __DIR__ . '/includes/functions.php';
if (me()) redirect('index.php');
$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    if (($_SESSION['lock'] ?? 0) > time()) {
        $err = 'Terlalu banyak percobaan. Coba lagi dalam ' . ($_SESSION['lock'] - time()) . ' detik.';
    } else {
        $u = q('SELECT * FROM users WHERE username=? LIMIT 1', [trim($_POST['username'] ?? '')])->fetch();
        if ($u && $u['status'] === 'aktif' && password_verify($_POST['password'] ?? '', $u['password'])) {
            session_regenerate_id(true);
            unset($u['password']); $_SESSION['user'] = $u; $_SESSION['fail'] = 0;
            redirect('index.php');
        }
        $_SESSION['fail'] = ($_SESSION['fail'] ?? 0) + 1;
        if ($_SESSION['fail'] >= 5) { $_SESSION['lock'] = time() + 30; $_SESSION['fail'] = 0; }
        $err = 'Username atau password salah, atau akun tidak aktif.';
    }
}
?><!doctype html>
<html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Masuk | <?= e(APP_NAME) ?></title><link rel="icon" href="<?= e(logo_url()) ?>">
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= e(url('assets/css/style.css')) ?>"></head>
<body class="login">
  <section class="login-art">
    <div class="lg"><img src="<?= e(logo_url()) ?>" alt="Logo BPS"><div><b>Badan Pusat Statistik</b><span>Kota Medan</span></div></div>
    <h1> Presensi Mahasiswa Magang Badan Pusat Statistik Kota Medan</h1>
    <p>.</p>
    <p>.</p>s
  </section>
  <section class="login-form">
    <form method="post" autocomplete="on">
      <h2>Masuk ke akun</h2><div class="sub">Gunakan akun yang diberikan oleh pengawas magang.</div>
      <?php if ($err): ?><div class="alert a-err"><?= e($err) ?></div><?php endif; ?>
      <?= csrf_field() ?>
      <div class="fg"><label for="u">Username</label><input id="u" name="username" required autofocus autocomplete="username"></div>
      <div class="fg"><label for="p">Password</label><input id="p" type="password" name="password" required autocomplete="current-password"></div>
      <button class="btn">Masuk</button>
    </form>
  </section>
</body></html>
