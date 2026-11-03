<?php
require __DIR__ . '/../includes/functions.php';
require_role('admin');
$mhs = q("SELECT id, nama FROM users WHERE role='mahasiswa' ORDER BY nama")->fetchAll();
$uid = (int)($_GET['mhs'] ?? 0); $dari = $_GET['dari'] ?? date('Y-m-01'); $sampai = $_GET['sampai'] ?? date('Y-m-d');
$rows = q('SELECT k.*, u.nama FROM kegiatan k JOIN users u ON u.id=k.user_id WHERE k.tanggal BETWEEN ? AND ?' . ($uid ? ' AND k.user_id=?' : '') . ' ORDER BY k.tanggal DESC, u.nama', $uid ? [$dari, $sampai, $uid] : [$dari, $sampai])->fetchAll();
page_start('Kegiatan Harian', 'kegiatan');
?>
<div class="card"><form class="filter" method="get">
<div><label for="m">Mahasiswa</label><select id="m" name="mhs"><option value="0">Semua</option><?php foreach ($mhs as $m): ?><option value="<?= $m['id'] ?>" <?= $uid === (int)$m['id'] ? 'selected' : '' ?>><?= e($m['nama']) ?></option><?php endforeach; ?></select></div>
<div><label for="d">Dari</label><input id="d" type="date" name="dari" value="<?= e($dari) ?>"></div><div><label for="s">Sampai</label><input id="s" type="date" name="sampai" value="<?= e($sampai) ?>"></div>
<button class="btn">Tampilkan</button><button type="button" class="btn btn-line" onclick="window.print()">Cetak</button></form>
<div class="tw"><table><thead><tr><th style="width:140px">Tanggal</th><th style="width:200px">Mahasiswa</th><th>Uraian kegiatan</th></tr></thead><tbody>
<?php foreach ($rows as $k): ?><tr><td><?= e(tgl($k['tanggal'])) ?></td><td><b><?= e($k['nama']) ?></b></td><td><?= nl2br(e($k['uraian'])) ?></td></tr>
<?php endforeach; if (!$rows): ?><tr><td colspan="3" class="empty">Belum ada kegiatan pada filter ini.</td></tr><?php endif; ?></tbody></table></div></div>
<?php page_end();
