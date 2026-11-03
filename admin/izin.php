<?php
require __DIR__ . '/../includes/functions.php';
require_role('admin');
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $z = q("SELECT * FROM izin WHERE id=? AND status='menunggu'", [(int)($_POST['id'] ?? 0)])->fetch();
    if ($z && in_array($_POST['aksi'] ?? '', ['setuju', 'tolak'], true)) {
        $ok = $_POST['aksi'] === 'setuju';
        q('UPDATE izin SET status=?, catatan_admin=? WHERE id=?', [$ok ? 'disetujui' : 'ditolak', trim($_POST['catatan'] ?? '') ?: null, $z['id']]);
        if ($ok) { // catat izin/sakit pada hari kerja di tabel presensi
            for ($t = strtotime($z['tanggal_mulai']), $end = strtotime($z['tanggal_selesai']); $t <= $end; $t = strtotime('+1 day', $t)) {
                if (date('N', $t) < 6) q('INSERT IGNORE INTO presensi (user_id, tanggal, status) VALUES (?,?,?)', [$z['user_id'], date('Y-m-d', $t), $z['jenis']]);
            }
        }
        flash($ok ? 'Pengajuan disetujui dan dicatat pada rekap presensi.' : 'Pengajuan ditolak.');
    }
    redirect('admin/izin.php');
}
$st = $_GET['status'] ?? 'menunggu';
$rows = q('SELECT z.*, u.nama, u.universitas FROM izin z JOIN users u ON u.id=z.user_id' . ($st !== 'semua' ? ' WHERE z.status=?' : '') . ' ORDER BY z.id DESC', $st !== 'semua' ? [$st] : [])->fetchAll();
page_start('Izin & Sakit', 'izin');
?>
<div class="tabs"><?php foreach (['menunggu' => 'Menunggu', 'disetujui' => 'Disetujui', 'ditolak' => 'Ditolak', 'semua' => 'Semua'] as $k => $l): ?><a class="<?= $st === $k ? 'on' : '' ?>" href="?status=<?= $k ?>"><?= $l ?></a><?php endforeach; ?></div>
<?php foreach ($rows as $z): ?>
<div class="card"><div class="card-h"><h2><?= e($z['nama']) ?> <span class="muted small" style="font-weight:500">&middot; <?= e($z['universitas']) ?></span></h2><?= badge($z['jenis']) ?> <?= badge($z['status']) ?></div>
  <p style="margin:0 0 6px"><b><?= e(tgl($z['tanggal_mulai'])) ?></b><?= $z['tanggal_selesai'] !== $z['tanggal_mulai'] ? ' s.d. <b>' . e(tgl($z['tanggal_selesai'])) . '</b>' : '' ?></p>
  <p style="margin:0 0 8px"><?= nl2br(e($z['keterangan'])) ?></p>
  <?php if ($z['bukti']): ?><p class="small"><a target="_blank" href="<?= e(url($z['bukti'])) ?>">Lihat bukti</a></p><?php endif; ?>
  <?php if ($z['status'] === 'menunggu'): ?>
  <form method="post" class="filter mb0"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $z['id'] ?>">
    <div><label for="c<?= $z['id'] ?>">Catatan (opsional)</label><input id="c<?= $z['id'] ?>" name="catatan"></div>
    <button class="btn btn-g" name="aksi" value="setuju">Setujui</button><button class="btn btn-bad" name="aksi" value="tolak" data-confirm="Tolak pengajuan ini?">Tolak</button></form>
  <?php elseif ($z['catatan_admin']): ?><p class="muted small mb0">Catatan: <?= e($z['catatan_admin']) ?></p><?php endif; ?></div>
<?php endforeach; if (!$rows): ?><div class="card empty">Tidak ada pengajuan pada kategori ini.</div><?php endif; ?>
<?php page_end();
