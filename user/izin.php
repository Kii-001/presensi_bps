<?php
require __DIR__ . '/../includes/functions.php';
$u = require_role('mahasiswa');
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $jenis = $_POST['jenis'] ?? ''; $m = $_POST['mulai'] ?? ''; $s = $_POST['selesai'] ?? ''; $ket = trim($_POST['keterangan'] ?? '');
    $ok = in_array($jenis, ['izin', 'sakit'], true) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $m) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $s) && $s >= $m && mb_strlen($ket) >= 5;
    $bukti = null;
    if ($ok && !empty($_FILES['bukti']['name'])) {
        $ext = strtolower(pathinfo($_FILES['bukti']['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['jpg', 'jpeg', 'png', 'pdf'], true) || $_FILES['bukti']['size'] > 2 * 1024 * 1024 || $_FILES['bukti']['error'] !== 0) { $ok = false; }
        else {
            $dir = __DIR__ . '/../uploads/bukti/';
            if (!is_dir($dir)) mkdir($dir, 0775, true);
            $bukti = 'uploads/bukti/' . $u['id'] . '_' . bin2hex(random_bytes(5)) . '.' . $ext;
            if (!move_uploaded_file($_FILES['bukti']['tmp_name'], __DIR__ . '/../' . $bukti)) { $ok = false; $bukti = null; }
        }
    }
    if (!$ok) flash('Periksa kembali form. Tanggal selesai tidak boleh sebelum tanggal mulai, keterangan minimal 5 karakter, bukti berupa JPG/PNG/PDF maksimal 2 MB.', 'err');
    else { q('INSERT INTO izin (user_id,jenis,tanggal_mulai,tanggal_selesai,keterangan,bukti) VALUES (?,?,?,?,?,?)', [$u['id'], $jenis, $m, $s, $ket, $bukti]); flash('Pengajuan terkirim. Tunggu persetujuan pengawas.'); }
    redirect('user/izin.php');
}
$rows = q('SELECT * FROM izin WHERE user_id=? ORDER BY id DESC', [$u['id']])->fetchAll();
page_start('Izin & Sakit', 'izin');
?>
<div class="grid g-side">
<div class="card"><h2>Riwayat pengajuan</h2><div class="tw"><table><thead><tr><th>Jenis</th><th>Tanggal</th><th>Keterangan</th><th>Status</th></tr></thead><tbody>
<?php foreach ($rows as $z): ?><tr><td><?= badge($z['jenis']) ?></td><td><?= e(tgl($z['tanggal_mulai'])) ?><?= $z['tanggal_selesai'] !== $z['tanggal_mulai'] ? '<br><span class="muted small">s.d. ' . e(tgl($z['tanggal_selesai'])) . '</span>' : '' ?></td>
<td><?= e($z['keterangan']) ?><?= $z['catatan_admin'] ? '<br><span class="muted small">Catatan pengawas: ' . e($z['catatan_admin']) . '</span>' : '' ?></td><td><?= badge($z['status']) ?></td></tr>
<?php endforeach; if (!$rows): ?><tr><td colspan="4" class="empty">Belum ada pengajuan.</td></tr><?php endif; ?></tbody></table></div></div>
<form class="card" method="post" enctype="multipart/form-data"><h2>Ajukan izin atau sakit</h2><?= csrf_field() ?>
<div class="fg"><label for="j">Jenis</label><select id="j" name="jenis"><option value="izin">Izin</option><option value="sakit">Sakit</option></select></div>
<div class="frow"><div class="fg"><label for="m">Mulai</label><input id="m" type="date" name="mulai" value="<?= date('Y-m-d') ?>" required></div>
<div class="fg"><label for="s">Selesai</label><input id="s" type="date" name="selesai" value="<?= date('Y-m-d') ?>" required></div></div>
<div class="fg"><label for="k">Keterangan</label><textarea id="k" name="keterangan" required></textarea></div>
<div class="fg"><label for="b">Bukti (opsional)</label><input id="b" type="file" name="bukti" accept=".jpg,.jpeg,.png,.pdf"></div>
<button class="btn">Kirim pengajuan</button></form>
</div>
<?php page_end();
