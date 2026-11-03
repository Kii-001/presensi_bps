<?php
require __DIR__ . '/../includes/functions.php';
$u = require_role('mahasiswa');
$bln = preg_match('/^\d{4}-\d{2}$/', $_GET['bulan'] ?? '') ? $_GET['bulan'] : date('Y-m');
$rows = q('SELECT * FROM presensi WHERE user_id=? AND DATE_FORMAT(tanggal,"%Y-%m")=? ORDER BY tanggal DESC', [$u['id'], $bln])->fetchAll();
$r = rekap_user($u, $bln . '-01', date('Y-m-t', strtotime($bln . '-01')));
page_start('Riwayat Presensi', 'riwayat');
?>
<form class="filter" method="get"><div><label for="b">Bulan</label><input id="b" type="month" name="bulan" value="<?= e($bln) ?>"></div><button class="btn">Tampilkan</button></form>
<div class="stats">
  <div class="stat s-ok"><b><?= $r['hadir'] ?></b><span>Hadir</span></div><div class="stat s-warn"><b><?= $r['terlambat'] ?></b><span>Terlambat</span></div>
  <div class="stat"><b><?= $r['izin'] + $r['sakit'] ?></b><span>Izin / sakit</span></div><div class="stat s-bad"><b><?= $r['alpa'] ?></b><span>Alpa</span></div>
</div>
<div class="card"><div class="tw"><table><thead><tr><th>Tanggal</th><th>Clock In</th><th>Jarak</th><th>Foto</th><th>Clock Out</th><th>Jarak</th><th>Foto</th><th>Status</th></tr></thead><tbody>
<?php foreach ($rows as $p): ?><tr><td><?= e(tgl($p['tanggal'], true)) ?></td>
<td><?= e(jam($p['jam_masuk'])) ?></td><td><?= $p['jarak_masuk'] !== null ? e(round($p['jarak_masuk'])) . ' m' : '-' ?></td><td><?= foto_tag($p['foto_masuk']) ?></td>
<td><?= e(jam($p['jam_pulang'])) ?></td><td><?= $p['jarak_pulang'] !== null ? e(round($p['jarak_pulang'])) . ' m' : '-' ?></td><td><?= foto_tag($p['foto_pulang']) ?></td>
<td><?= badge($p['status']) ?></td></tr>
<?php endforeach; if (!$rows): ?><tr><td colspan="8" class="empty">Tidak ada data presensi pada bulan ini.</td></tr><?php endif; ?>
</tbody></table></div></div>
<?php page_end();
