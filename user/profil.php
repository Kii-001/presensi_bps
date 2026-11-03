<?php
require __DIR__ . '/../includes/functions.php';
$u = require_role('mahasiswa');
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    if (isset($_POST['simpan'])) {
        $n = trim($_POST['nama'] ?? '');
        if ($n === '') flash('Nama tidak boleh kosong.', 'err');
        else { q('UPDATE users SET nama=?, no_hp=? WHERE id=?', [$n, trim($_POST['no_hp'] ?? ''), $u['id']]);
               $_SESSION['user'] = array_merge($_SESSION['user'], ['nama' => $n, 'no_hp' => trim($_POST['no_hp'] ?? '')]); flash('Profil diperbarui.'); }
    } else {
        $h = q('SELECT password FROM users WHERE id=?', [$u['id']])->fetchColumn();
        if (!password_verify($_POST['lama'] ?? '', $h)) flash('Password lama salah.', 'err');
        elseif (strlen($_POST['baru'] ?? '') < 6 || $_POST['baru'] !== ($_POST['ulang'] ?? '')) flash('Password baru minimal 6 karakter dan harus sama dengan konfirmasi.', 'err');
        else { q('UPDATE users SET password=? WHERE id=?', [password_hash($_POST['baru'], PASSWORD_DEFAULT), $u['id']]); flash('Password berhasil diganti.'); }
    }
    redirect('user/profil.php');
}
$u = q('SELECT * FROM users WHERE id=?', [$u['id']])->fetch();
page_start('Profil', 'profil');
?>
<div class="grid g2">
<form class="card" method="post"><h2>Data diri</h2><?= csrf_field() ?>
<div class="fg"><label for="n">Nama lengkap</label><input id="n" name="nama" value="<?= e($u['nama']) ?>" required></div>
<div class="fg"><label for="h">No. HP</label><input id="h" name="no_hp" value="<?= e($u['no_hp']) ?>"></div>
<dl class="kv" style="margin-bottom:16px"><dt>NIM</dt><dd><?= e($u['nim']) ?></dd><dt>Universitas</dt><dd><?= e($u['universitas']) ?></dd><dt>Jurusan</dt><dd><?= e($u['jurusan']) ?></dd><dt>Bidang</dt><dd><?= e($u['bidang']) ?></dd><dt>Periode magang</dt><dd><?= e(tgl($u['mulai_magang'])) ?> s.d. <?= e(tgl($u['selesai_magang'])) ?></dd></dl>
<button class="btn" name="simpan" value="1">Simpan profil</button></form>
<form class="card" method="post"><h2>Ganti password</h2><?= csrf_field() ?>
<div class="fg"><label for="l">Password lama</label><input id="l" type="password" name="lama" required autocomplete="current-password"></div>
<div class="fg"><label for="b">Password baru</label><input id="b" type="password" name="baru" required minlength="6" autocomplete="new-password"></div>
<div class="fg"><label for="r">Ulangi password baru</label><input id="r" type="password" name="ulang" required autocomplete="new-password"></div>
<button class="btn">Ganti password</button></form>
</div>
<?php page_end();
