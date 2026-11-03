<?php
require __DIR__ . '/../includes/functions.php';
require_role('admin');
$p = q('SELECT p.*, u.nama, u.nim, u.universitas, u.jurusan, u.bidang FROM presensi p JOIN users u ON u.id=p.user_id WHERE p.id=?', [(int)($_GET['id'] ?? 0)])->fetch();
if (!$p) { flash('Data presensi tidak ditemukan.', 'err'); redirect('admin/presensi.php'); }
$office = ['lat' => (float)setting('kantor_lat'), 'lng' => (float)setting('kantor_lng'), 'r' => (float)setting('radius', 100)];
$pts = [];
if ($p['lat_masuk'] !== null) $pts[] = ['lat' => (float)$p['lat_masuk'], 'lng' => (float)$p['lng_masuk'], 'l' => 'Clock In ' . jam($p['jam_masuk']), 'c' => '#3E9B3B'];
if ($p['lat_pulang'] !== null) $pts[] = ['lat' => (float)$p['lat_pulang'], 'lng' => (float)$p['lng_pulang'], 'l' => 'Clock Out ' . jam($p['jam_pulang']), 'c' => '#F28C28'];
$head = '<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">';
page_start('Detail Presensi', 'presensi', $head);
function box(string $cls, string $t, ?string $jam, $lat, $lng, $jr, ?string $foto): void { ?>
  <div class="pbox <?= $cls ?>"><h3><?= $t ?></h3><dl class="kv">
    <dt>Waktu</dt><dd><?= e(jam($jam)) ?></dd><dt>Latitude</dt><dd><?= $lat !== null ? e($lat) : '-' ?></dd><dt>Longitude</dt><dd><?= $lng !== null ? e($lng) : '-' ?></dd>
    <dt>Jarak dari kantor</dt><dd><?= $jr !== null ? e(number_format((float)$jr, 2, ',', '.')) . ' meter' : '-' ?></dd><dt>Foto selfie</dt><dd><?= foto_tag($foto, $t) ?></dd></dl></div>
<?php } ?>
<p><a class="btn btn-line btn-sm" href="javascript:history.back()">&larr; Kembali</a></p>
<div class="grid g-side" style="grid-template-columns:1fr 2fr">
  <div class="card"><h2>Data mahasiswa</h2><div style="text-align:center;margin-bottom:14px"><div class="av" style="width:56px;height:56px;font-size:19px;margin:0 auto 8px"><?= e(inisial($p['nama'])) ?></div><b><?= e($p['nama']) ?></b></div>
    <dl class="kv" style="grid-template-columns:100px 1fr"><dt>NIM</dt><dd><?= e($p['nim']) ?></dd><dt>Universitas</dt><dd><?= e($p['universitas']) ?></dd><dt>Jurusan</dt><dd><?= e($p['jurusan']) ?></dd><dt>Bidang</dt><dd><?= e($p['bidang']) ?></dd><dt>Tanggal</dt><dd><?= e(tgl($p['tanggal'], true)) ?></dd><dt>Status</dt><dd><?= badge($p['status']) ?></dd></dl></div>
  <div><div class="grid g2" style="margin-bottom:18px">
    <?php box('in', 'Clock In', $p['jam_masuk'], $p['lat_masuk'], $p['lng_masuk'], $p['jarak_masuk'], $p['foto_masuk']);
          box('outb', 'Clock Out', $p['jam_pulang'], $p['lat_pulang'], $p['lng_pulang'], $p['jarak_pulang'], $p['foto_pulang']); ?></div>
    <div class="card"><h2>Lokasi presensi</h2><div id="map" class="map"></div><p class="muted small" style="margin:10px 0 0">Lingkaran biru menandai radius presensi di sekitar kantor.</p></div></div>
</div>
<?php page_end('<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script><script>
var o=' . json_encode($office) . ',pts=' . json_encode($pts) . ',m=L.map("map").setView([o.lat,o.lng],17);
L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png",{maxZoom:19,attribution:"&copy; OpenStreetMap"}).addTo(m);
L.circle([o.lat,o.lng],{radius:o.r,color:"#1B66B3",fillOpacity:.08}).addTo(m);L.marker([o.lat,o.lng]).addTo(m).bindPopup("Kantor BPS Kota Medan");
var b=[[o.lat,o.lng]];pts.forEach(function(p){L.circleMarker([p.lat,p.lng],{radius:9,color:"#fff",weight:2,fillColor:p.c,fillOpacity:1}).addTo(m).bindPopup(p.l);b.push([p.lat,p.lng])});
if(b.length>1)m.fitBounds(b,{padding:[40,40],maxZoom:18});</script>');
