<?php
require __DIR__ . '/../includes/functions.php';
$u = require_role('mahasiswa');
$today = q('SELECT * FROM presensi WHERE user_id=? AND tanggal=?', [$u['id'], date('Y-m-d')])->fetch();
$r = rekap_user($u, date('Y-m-01'), date('Y-m-t'));
$recent = q('SELECT * FROM presensi WHERE user_id=? ORDER BY tanggal DESC LIMIT 7', [$u['id']])->fetchAll();
page_start('Beranda', 'dashboard');
?>
<div class="card">
  <div class="card-h"><h2>Halo, <?= e(explode(' ', $u['nama'])[0]) ?></h2>
    <a class="btn <?= !$today ? 'btn-g' : ($today['jam_masuk'] && !$today['jam_pulang'] ? 'btn-o' : 'btn-line') ?>" href="<?= e(url('user/presensi.php')) ?>">
      <?= !$today ? 'Clock In sekarang' : ($today['jam_masuk'] && !$today['jam_pulang'] ? 'Clock Out' : 'Lihat presensi hari ini') ?></a></div>
  <?php if (!$today): ?><p class="muted mb0">Anda belum presensi hari ini. Jam masuk <?= e(setting('jam_masuk')) ?>, batas terlambat <?= e(setting('batas_terlambat')) ?>.</p>
  <?php else: ?><div class="done">
    <div><b><?= e(jam($today['jam_masuk'])) ?></b><span>Clock In</span></div>
    <div><b><?= e(jam($today['jam_pulang'])) ?></b><span>Clock Out</span></div>
    <div><b><?= badge($today['status']) ?></b><span>Status hari ini</span></div></div><?php endif; ?>
</div>
<div class="stats">
  <div class="stat s-ok"><b><?= $r['hadir'] ?></b><span>Hadir bulan ini</span></div>
  <div class="stat s-warn"><b><?= $r['terlambat'] ?></b><span>Terlambat</span></div>
  <div class="stat"><b><?= $r['izin'] + $r['sakit'] ?></b><span>Izin / sakit</span></div>
  <div class="stat s-bad"><b><?= $r['alpa'] ?></b><span>Alpa</span></div>
</div>
<div class="card"><div class="card-h"><h2>Presensi terakhir</h2><a class="btn btn-line btn-sm" href="<?= e(url('user/riwayat.php')) ?>">Semua riwayat</a></div>
<div class="tw"><table><thead><tr><th>Tanggal</th><th>Clock In</th><th>Clock Out</th><th>Status</th></tr></thead><tbody>
<?php foreach ($recent as $p): ?><tr><td><?= e(tgl($p['tanggal'], true)) ?></td><td><?= e(jam($p['jam_masuk'])) ?></td><td><?= e(jam($p['jam_pulang'])) ?></td><td><?= badge($p['status']) ?></td></tr>
<?php endforeach; if (!$recent): ?><tr><td colspan="4" class="empty">Belum ada presensi. Mulai dengan Clock In pertama Anda.</td></tr><?php endif; ?>
</tbody></table></div></div>
<?php page_end();
