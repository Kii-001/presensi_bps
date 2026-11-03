<?php
require __DIR__ . '/../includes/functions.php';
require_role('admin');
$tg = preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['tanggal'] ?? '') ? $_GET['tanggal'] : date('Y-m-d');
$rows = q('SELECT p.*, u.nama FROM presensi p JOIN users u ON u.id=p.user_id WHERE p.tanggal=? AND p.lat_masuk IS NOT NULL ORDER BY p.jam_masuk', [$tg])->fetchAll();
$office = ['lat' => (float)setting('kantor_lat'), 'lng' => (float)setting('kantor_lng'), 'r' => (float)setting('radius', 100)];
$pts = array_map(fn($p) => ['lat' => (float)$p['lat_masuk'], 'lng' => (float)$p['lng_masuk'], 'l' => $p['nama'] . ' - masuk ' . jam($p['jam_masuk']) . ' (' . round($p['jarak_masuk']) . ' m)', 'late' => $p['status'] === 'terlambat'], $rows);
page_start('Peta Presensi', 'peta', '<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">');
?>
<div class="card"><form class="filter" method="get"><div><label for="t">Tanggal</label><input id="t" type="date" name="tanggal" value="<?= e($tg) ?>"></div><button class="btn">Tampilkan</button></form>
<div id="map" class="map" style="height:460px"></div>
<p class="muted small" style="margin:10px 0 0"><?= count($pts) ?> titik Clock In pada <?= e(tgl($tg, true)) ?>. Hijau: tepat waktu, oranye: terlambat.</p></div>
<?php page_end('<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script><script>
var o=' . json_encode($office) . ',pts=' . json_encode($pts) . ',m=L.map("map").setView([o.lat,o.lng],17);
L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png",{maxZoom:19,attribution:"&copy; OpenStreetMap"}).addTo(m);
L.circle([o.lat,o.lng],{radius:o.r,color:"#1B66B3",fillOpacity:.08}).addTo(m);L.marker([o.lat,o.lng]).addTo(m).bindPopup("Kantor BPS Kota Medan");
var b=[[o.lat,o.lng]];pts.forEach(function(p){L.circleMarker([p.lat,p.lng],{radius:9,color:"#fff",weight:2,fillColor:p.late?"#F28C28":"#3E9B3B",fillOpacity:1}).addTo(m).bindPopup(p.l);b.push([p.lat,p.lng])});
if(b.length>1)m.fitBounds(b,{padding:[40,40],maxZoom:18});</script>');
