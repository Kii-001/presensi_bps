<?php
require __DIR__ . '/../includes/functions.php';
require_role('admin');
$today = date('Y-m-d');
$total = (int)q("SELECT COUNT(*) FROM users WHERE role='mahasiswa' AND status='aktif'")->fetchColumn();
$c = q('SELECT status, COUNT(*) FROM presensi WHERE tanggal=? GROUP BY status', [$today])->fetchAll(PDO::FETCH_KEY_PAIR);
$hadir = (int)($c['hadir'] ?? 0); $tl = (int)($c['terlambat'] ?? 0); $iz = (int)($c['izin'] ?? 0) + (int)($c['sakit'] ?? 0);
$belum = max(0, $total - $hadir - $tl - $iz);
$hari = []; // 7 hari kerja terakhir
for ($t = strtotime($today); count($hari) < 7; $t = strtotime('-1 day', $t)) { if (date('N', $t) < 6) $hari[] = date('Y-m-d', $t); }
$hari = array_reverse($hari);
$per = q("SELECT tanggal, COUNT(*) FROM presensi WHERE tanggal BETWEEN ? AND ? AND status IN ('hadir','terlambat') GROUP BY tanggal", [$hari[0], $today])->fetchAll(PDO::FETCH_KEY_PAIR);
$max = max(1, $total, ...array_values($per ?: [0]));
$latest = q('SELECT p.*, u.nama, u.universitas FROM presensi p JOIN users u ON u.id=p.user_id WHERE p.tanggal=? ORDER BY COALESCE(p.jam_pulang,p.jam_masuk) DESC LIMIT 8', [$today])->fetchAll();
page_start('Dashboard', 'dashboard');
?>
<div class="stats">
  <div class="stat"><b><?= $total ?></b><span>Mahasiswa aktif</span></div>
  <div class="stat s-ok"><b><?= $hadir ?></b><span>Hadir hari ini</span></div>
  <div class="stat s-warn"><b><?= $tl ?></b><span>Terlambat</span></div>
  <div class="stat"><b><?= $iz ?></b><span>Izin / sakit</span></div>
  <div class="stat s-mute"><b><?= $belum ?></b><span>Belum presensi</span></div>
</div>
<div class="grid g-side">
  <div class="card"><div class="card-h"><h2>Presensi hari ini</h2><a class="btn btn-line btn-sm" href="<?= e(url('admin/presensi.php')) ?>">Rekap lengkap</a></div>
  <div class="tw"><table><thead><tr><th>Mahasiswa</th><th>Clock In</th><th>Clock Out</th><th>Status</th><th></th></tr></thead><tbody>
  <?php foreach ($latest as $p): ?><tr><td><b><?= e($p['nama']) ?></b><br><span class="muted small"><?= e($p['universitas']) ?></span></td><td><?= e(jam($p['jam_masuk'])) ?></td><td><?= e(jam($p['jam_pulang'])) ?></td><td><?= badge($p['status']) ?></td>
  <td><a class="btn btn-line btn-sm" href="<?= e(url('admin/presensi_detail.php?id=' . $p['id'])) ?>">Detail</a></td></tr>
  <?php endforeach; if (!$latest): ?><tr><td colspan="5" class="empty">Belum ada presensi hari ini.</td></tr><?php endif; ?></tbody></table></div></div>
  <div class="card"><h2>Kehadiran 7 hari kerja</h2><div class="bars">
    <?php foreach ($hari as $d): $n = (int)($per[$d] ?? 0); ?><div><b><?= $n ?></b><i style="height:<?= round($n / $max * 100) ?>px"></i><?= e(substr(HARI[(int)date('w', strtotime($d))], 0, 3)) ?></div><?php endforeach; ?>
  </div><p class="muted small" style="margin:14px 0 0">Jumlah mahasiswa yang hadir atau terlambat per hari.</p></div>
</div>
<?php page_end();
