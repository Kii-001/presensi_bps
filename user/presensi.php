<?php
require __DIR__ . '/../includes/functions.php';
$u = require_role('mahasiswa');
$row = q('SELECT * FROM presensi WHERE user_id=? AND tanggal=?', [$u['id'], date('Y-m-d')])->fetch();
$aksi = !$row ? 'masuk' : (($row['jam_masuk'] && !$row['jam_pulang']) ? 'pulang' : '');
$label = $aksi === 'masuk' ? 'Clock In' : 'Clock Out';
$head = '';
page_start('Presensi', 'presensi', $head);
?>
<?php if ($aksi): ?>
<div id="presensi" data-lat="<?= e(setting('kantor_lat')) ?>" data-lng="<?= e(setting('kantor_lng')) ?>" data-radius="<?= e(setting('radius', 100)) ?>"
     data-aksi="<?= $aksi ?>" data-csrf="<?= csrf() ?>" data-endpoint="<?= e(url('api/presensi.php')) ?>" data-back="<?= e(url('user/presensi.php')) ?>" data-label="<?= $label ?>">
<div id="result" class="alert" style="display:none"></div>
<div class="clockbox">
  <section class="card">
    <div class="muted"><?= e(tgl(date('Y-m-d'), true)) ?></div>
    <div class="bigtime" data-clock>--:--:--</div>
    <h2 style="margin:22px 0 4px"><?= $aksi === 'masuk' ? 'Presensi masuk' : 'Presensi pulang' ?></h2>
    <div class="dist"><b id="distVal">--</b><span>meter dari kantor</span></div>
    <div class="meter" id="meter" style="display:none"><i></i></div>
    <div class="locmsg wait" id="locMsg">Menunggu izin lokasi...</div>
    <div class="checks"><span id="chkLoc">Lokasi sesuai radius <?= e(setting('radius', 100)) ?> m</span><span id="chkFoto">Selfie diambil</span></div>
    <div class="actions"><button class="btn <?= $aksi === 'masuk' ? 'btn-g' : 'btn-o' ?>" id="btnSubmit" disabled><?= $label ?></button></div>
    <p class="muted small" style="margin:14px 0 0">Tombol aktif setelah lokasi sesuai dan foto selfie diambil.</p>
  </section>
  <section class="card">
    <h2>Foto selfie</h2>
    <div class="cam"><video id="video" autoplay playsinline muted style="display:none"></video><img id="preview" alt="Hasil selfie" style="display:none"><div class="ph" id="camPh">Meminta akses kamera...</div></div>
    <div class="actions" style="margin-top:12px"><button class="btn btn-line" id="btnShot" type="button">Ambil foto</button></div>
  </section>
</div></div>
<script src="<?= e(url('assets/js/presensi.js')) ?>" defer></script>
<?php elseif ($row && !$row['jam_masuk']): ?>
<div class="card"><h2>Hari ini tercatat <?= badge($row['status']) ?></h2><p class="muted mb0">Tidak perlu presensi karena pengajuan izin/sakit Anda sudah disetujui.</p></div>
<?php else: ?>
<div class="card"><div class="card-h"><h2>Presensi hari ini selesai</h2><?= badge($row['status']) ?></div>
<div class="grid g2">
  <div class="pbox in"><h3>Clock In</h3><dl class="kv"><dt>Waktu</dt><dd><?= e(jam($row['jam_masuk'])) ?></dd><dt>Jarak</dt><dd><?= $row['jarak_masuk'] !== null ? e(round($row['jarak_masuk'])) . ' m dari kantor' : '-' ?></dd><dt>Selfie</dt><dd><?= foto_tag($row['foto_masuk']) ?></dd></dl></div>
  <div class="pbox outb"><h3>Clock Out</h3><dl class="kv"><dt>Waktu</dt><dd><?= e(jam($row['jam_pulang'])) ?></dd><dt>Jarak</dt><dd><?= $row['jarak_pulang'] !== null ? e(round($row['jarak_pulang'])) . ' m dari kantor' : '-' ?></dd><dt>Selfie</dt><dd><?= foto_tag($row['foto_pulang']) ?></dd></dl></div>
</div></div>
<?php endif; ?>
<?php page_end();
