<?php
require __DIR__ . '/../includes/functions.php';
require_role('admin');
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $id = (int)($_POST['id'] ?? 0);
    if (isset($_POST['toggle'])) {
        q("UPDATE users SET status=IF(status='aktif','nonaktif','aktif') WHERE id=? AND role='mahasiswa'", [(int)$_POST['toggle']]); flash('Status akun diubah.');
    } elseif (isset($_POST['reset'])) {
        q("UPDATE users SET password=? WHERE id=? AND role='mahasiswa'", [password_hash('magang123', PASSWORD_DEFAULT), (int)$_POST['reset']]); flash('Password direset menjadi: magang123');
    } elseif (isset($_POST['hapus'])) {
        $hid = (int)$_POST['hapus'];
        q("DELETE FROM presensi WHERE user_id=?", [$hid]);
        q("DELETE FROM izin WHERE user_id=?", [$hid]);
        q("DELETE FROM kegiatan WHERE user_id=?", [$hid]);
        q("DELETE FROM users WHERE id=? AND role='mahasiswa'", [$hid]);
        flash('Data mahasiswa berhasil dihapus.');
    } else {
        $f = array_map(fn($k) => trim($_POST[$k] ?? ''), ['nama', 'username', 'nim', 'universitas', 'jurusan', 'bidang', 'no_hp', 'mulai', 'selesai']);
        [$nama, $user, $nim, $univ, $jur, $bid, $hp, $mulai, $selesai] = $f;
        $dup = q('SELECT COUNT(*) FROM users WHERE username=? AND id<>?', [$user, $id])->fetchColumn();
        if ($nama === '' || !preg_match('/^[A-Za-z0-9_.]{3,30}$/', $user)) flash('Nama wajib diisi. Username 3-30 karakter (huruf, angka, titik, garis bawah).', 'err');
        elseif ($dup) flash('Username sudah dipakai.', 'err');
        else {
            $mulai = $mulai ?: null; $selesai = $selesai ?: null;
            if ($id) { q("UPDATE users SET nama=?,username=?,nim=?,universitas=?,jurusan=?,bidang=?,no_hp=?,mulai_magang=?,selesai_magang=? WHERE id=? AND role='mahasiswa'", [$nama, $user, $nim, $univ, $jur, $bid, $hp, $mulai, $selesai, $id]); flash('Data mahasiswa diperbarui.'); }
            else { q("INSERT INTO users (nama,username,password,role,nim,universitas,jurusan,bidang,no_hp,mulai_magang,selesai_magang) VALUES (?,?,?,'mahasiswa',?,?,?,?,?,?,?)",
                    [$nama, $user, password_hash('magang123', PASSWORD_DEFAULT), $nim, $univ, $jur, $bid, $hp, $mulai, $selesai]); flash('Mahasiswa ditambahkan. Password awal: magang123'); }
        }
    }
    redirect('admin/mahasiswa.php');
}
$edit = isset($_GET['edit']) ? q("SELECT * FROM users WHERE id=? AND role='mahasiswa'", [(int)$_GET['edit']])->fetch() : null;
$cari = trim($_GET['cari'] ?? '');
$cariLike = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\%', '\_'], $cari) . '%';
$rows = q("SELECT * FROM users WHERE role='mahasiswa' AND (nama LIKE ? OR nim LIKE ? OR universitas LIKE ?) ORDER BY status, nama", [$cariLike, $cariLike, $cariLike])->fetchAll();
$v = fn($k) => e($edit[$k] ?? '');
page_start('Data Mahasiswa', 'mahasiswa');
?>
<div class="grid g-side">
<div class="card"><div class="card-h"><h2>Daftar mahasiswa magang</h2>
  <form method="get" style="display:flex;gap:8px"><input name="cari" placeholder="Cari nama, NIM, kampus" value="<?= e($cari) ?>"><button class="btn btn-line">Cari</button></form></div>
<div class="tw"><table><thead><tr><th>Mahasiswa</th><th>Universitas</th><th>Periode</th><th>Status</th><th></th></tr></thead><tbody>
<?php foreach ($rows as $m): ?><tr><td><b><?= e($m['nama']) ?></b><br><span class="muted small"><?= e($m['nim']) ?> &middot; @<?= e($m['username']) ?></span></td>
<td><?= e($m['universitas']) ?><br><span class="muted small"><?= e($m['jurusan']) ?></span></td>
<td class="small"><?= e(tgl($m['mulai_magang'])) ?><br>s.d. <?= e(tgl($m['selesai_magang'])) ?></td>
<td><?= $m['status'] === 'aktif' ? '<span class="badge b-ok">Aktif</span>' : '<span class="badge b-mute">Nonaktif</span>' ?></td>
<td><form method="post" style="display:flex;gap:6px;flex-wrap:wrap"><?= csrf_field() ?>
<a class="btn btn-line btn-sm" href="?edit=<?= $m['id'] ?>">Edit</a>
<button class="btn btn-line btn-sm" name="toggle" value="<?= $m['id'] ?>" data-confirm="Ubah status akun ini?"><?= $m['status'] === 'aktif' ? 'Nonaktifkan' : 'Aktifkan' ?></button>
<button class="btn btn-line btn-sm" name="reset" value="<?= $m['id'] ?>" data-confirm="Reset password menjadi magang123?">Reset password</button>
<button class="btn btn-bad btn-sm" name="hapus" value="<?= $m['id'] ?>" data-confirm="Hapus mahasiswa <?= e($m['nama']) ?>? Semua data presensi, izin, dan kegiatan akan ikut terhapus dan tidak dapat dikembalikan.">Hapus</button></form></td></tr>
<?php endforeach; if (!$rows): ?><tr><td colspan="5" class="empty">Belum ada data mahasiswa.</td></tr><?php endif; ?></tbody></table></div></div>
<form class="card" method="post"><h2><?= $edit ? 'Edit mahasiswa' : 'Tambah mahasiswa' ?></h2><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)($edit['id'] ?? 0) ?>">
<div class="fg"><label for="a">Nama lengkap</label><input id="a" name="nama" value="<?= $v('nama') ?>" required></div>
<div class="frow"><div class="fg"><label for="b">Username</label><input id="b" name="username" value="<?= $v('username') ?>" required></div><div class="fg"><label for="c">NIM</label><input id="c" name="nim" value="<?= $v('nim') ?>"></div></div>
<div class="fg"><label for="d">Universitas</label><input id="d" name="universitas" value="<?= $v('universitas') ?>"></div>
<div class="frow"><div class="fg"><label for="e">Jurusan</label><input id="e" name="jurusan" value="<?= $v('jurusan') ?>"></div><div class="fg"><label for="f">Bidang penempatan</label><input id="f" name="bidang" value="<?= $v('bidang') ?>"></div></div>
<div class="fg"><label for="g">No. HP</label><input id="g" name="no_hp" value="<?= $v('no_hp') ?>"></div>
<div class="frow"><div class="fg"><label for="h">Mulai magang</label><input id="h" type="date" name="mulai" value="<?= $v('mulai_magang') ?>"></div><div class="fg"><label for="i">Selesai magang</label><input id="i" type="date" name="selesai" value="<?= $v('selesai_magang') ?>"></div></div>
<?php if (!$edit): ?><p class="muted small">Password awal: <b>magang123</b>. Mahasiswa bisa menggantinya di menu Profil.</p><?php endif; ?>
<div class="actions"><button class="btn"><?= $edit ? 'Simpan perubahan' : 'Tambah mahasiswa' ?></button><?php if ($edit): ?><a class="btn btn-line" href="<?= e(url('admin/mahasiswa.php')) ?>">Batal</a><?php endif; ?></div></form>
</div>
<?php page_end();
