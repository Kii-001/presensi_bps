<?php
require __DIR__ . '/../includes/functions.php';
$u = require_role('mahasiswa');
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    if (isset($_POST['hapus'])) { q('DELETE FROM kegiatan WHERE id=? AND user_id=?', [(int)$_POST['hapus'], $u['id']]); flash('Kegiatan dihapus.'); }
    else {
        $t = $_POST['tanggal'] ?? ''; $ur = trim($_POST['uraian'] ?? '');
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $t) || $t > date('Y-m-d') || mb_strlen($ur) < 5) flash('Isi tanggal (tidak melewati hari ini) dan uraian kegiatan minimal 5 karakter.', 'err');
        else { q('INSERT INTO kegiatan (user_id,tanggal,uraian) VALUES (?,?,?)', [$u['id'], $t, $ur]); flash('Kegiatan harian tersimpan.'); }
    }
    redirect('user/kegiatan.php');
}
$rows = q('SELECT * FROM kegiatan WHERE user_id=? ORDER BY tanggal DESC, id DESC LIMIT 60', [$u['id']])->fetchAll();
page_start('Kegiatan Harian', 'kegiatan');
?>
<div class="grid g-side">
<div class="card"><h2>Riwayat kegiatan</h2><div class="tw"><table><thead><tr><th style="width:150px">Tanggal</th><th>Uraian kegiatan</th><th></th></tr></thead><tbody>
<?php foreach ($rows as $k): ?><tr><td><?= e(tgl($k['tanggal'])) ?></td><td><?= nl2br(e($k['uraian'])) ?></td>
<td><form method="post"><?= csrf_field() ?><button class="btn btn-line btn-sm" name="hapus" value="<?= $k['id'] ?>" data-confirm="Hapus kegiatan ini?">Hapus</button></form></td></tr>
<?php endforeach; if (!$rows): ?><tr><td colspan="3" class="empty">Belum ada kegiatan. Catat apa yang Anda kerjakan hari ini.</td></tr><?php endif; ?></tbody></table></div></div>
<form class="card" method="post"><h2>Tambah kegiatan</h2><?= csrf_field() ?>
<div class="fg"><label for="t">Tanggal</label><input id="t" type="date" name="tanggal" value="<?= date('Y-m-d') ?>" max="<?= date('Y-m-d') ?>" required></div>
<div class="fg"><label for="ur">Uraian kegiatan</label><textarea id="ur" name="uraian" required placeholder="Contoh: Merekap data survei ke dalam spreadsheet."></textarea></div>
<button class="btn">Simpan kegiatan</button></form>
</div>
<?php page_end();
