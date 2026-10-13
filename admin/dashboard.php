<?php
require_once '../includes/cek_login.php';
cek_login('admin');

$judul_halaman = 'Dashboard Admin';
require_once '../includes/header.php';
?>

<h4 class="mb-3">Dashboard Admin</h4>
<p>Selamat datang, <strong><?= htmlspecialchars($_SESSION['nama']) ?></strong>.</p>
<p class="text-muted">Fitur dashboard akan dilengkapi pada modul berikutnya.</p>

<?php require_once '../includes/footer.php'; ?>
