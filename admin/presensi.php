<?php
require __DIR__ . '/../includes/functions.php';
require_role('admin');
$nama = trim($_GET['nama'] ?? ''); $dari = $_GET['dari'] ?? date('Y-m-01'); $sampai = $_GET['sampai'] ?? date('Y-m-d'); $st = $_GET['status'] ?? '';
$namaLike = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\%', '\_'], $nama) . '%';
$sql = 'SELECT p.*, u.nama, u.nim, u.universitas FROM presensi p JOIN users u ON u.id=p.user_id WHERE p.tanggal BETWEEN ? AND ? AND u.nama LIKE ?';
$par = [$dari, $sampai, $namaLike];
if (in_array($st, ['hadir', 'terlambat', 'izin', 'sakit'], true)) { $sql .= ' AND p.status=?'; $par[] = $st; }
$rows = q($sql . ' ORDER BY p.tanggal DESC, p.jam_masuk DESC', $par)->fetchAll();
if (($_GET['export'] ?? '') === 'csv') {
    header('Content-Type: text/csv; charset=utf-8'); header('Content-Disposition: attachment; filename="rekap-presensi-' . $dari . '_' . $sampai . '.csv"');
    echo "\xEF\xBB\xBF"; $o = fopen('php://output', 'w');
    fputcsv($o, ['Tanggal', 'Nama', 'NIM', 'Universitas', 'Clock In', 'Jarak In (m)', 'Clock Out', 'Jarak Out (m)', 'Status'], ';');
    foreach ($rows as $p) fputcsv($o, [$p['tanggal'], $p['nama'], $p['nim'], $p['universitas'], jam($p['jam_masuk']), $p['jarak_masuk'], jam($p['jam_pulang']), $p['jarak_pulang'], $p['status']], ';');
    exit;
}
page_start('Rekap Presensi', 'presensi');
$qs = http_build_query(['nama' => $nama, 'dari' => $dari, 'sampai' => $sampai, 'status' => $st, 'export' => 'csv']);
?>
<div class="card">
<form class="filter" method="get">
  <div><label for="n">Nama mahasiswa</label><input id="n" name="nama" value="<?= e($nama) ?>" placeholder="Cari nama"></div>
  <div><label for="d">Dari tanggal</label><input id="d" type="date" name="dari" value="<?= e($dari) ?>"></div>
  <div><label for="s">Sampai tanggal</label><input id="s" type="date" name="sampai" value="<?= e($sampai) ?>"></div>
  <div><label for="t">Status</label><select id="t" name="status"><option value="">Semua</option><?php foreach (['hadir', 'terlambat', 'izin', 'sakit'] as $s): ?><option value="<?= $s ?>" <?= $st === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option><?php endforeach; ?></select></div>
  <button class="btn">Tampilkan</button>
  <a class="btn btn-line" href="?<?= e($qs) ?>">Export CSV</a><button type="button" class="btn btn-line" onclick="window.print()">Cetak</button>
</form>
<p class="muted small">Periode <?= e(tgl($dari)) ?> s.d. <?= e(tgl($sampai)) ?> &middot; <?= count($rows) ?> data</p>
<div class="tw"><table><thead><tr><th>Tanggal</th><th>Mahasiswa</th><th>Clock In</th><th>Foto</th><th>Clock Out</th><th>Foto</th><th>Jarak</th><th>Status</th><th class="no-print"></th></tr></thead><tbody>
<?php foreach ($rows as $p): ?><tr><td><?= e(tgl($p['tanggal'])) ?></td><td><b><?= e($p['nama']) ?></b><br><span class="muted small"><?= e($p['universitas']) ?></span></td>
<td><?= e(jam($p['jam_masuk'])) ?></td><td><?= foto_tag($p['foto_masuk']) ?></td><td><?= e(jam($p['jam_pulang'])) ?></td><td><?= foto_tag($p['foto_pulang']) ?></td>
<td class="small"><?= $p['jarak_masuk'] !== null ? 'In ' . e(round($p['jarak_masuk'])) . ' m' : '-' ?><?= $p['jarak_pulang'] !== null ? '<br>Out ' . e(round($p['jarak_pulang'])) . ' m' : '' ?></td>
<td><?= badge($p['status']) ?></td><td class="no-print"><?php if ($p['jam_masuk']): ?><a class="btn btn-line btn-sm" href="<?= e(url('admin/presensi_detail.php?id=' . $p['id'])) ?>">Detail</a><?php endif; ?></td></tr>
<?php endforeach; if (!$rows): ?><tr><td colspan="9" class="empty">Tidak ada data presensi pada filter ini.</td></tr><?php endif; ?></tbody></table></div></div>
<?php page_end();
