<?php
require __DIR__ . '/../includes/functions.php';
$a = require_role('admin');
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    if (isset($_POST['simpan'])) {
        $lat = filter_var($_POST['kantor_lat'] ?? '', FILTER_VALIDATE_FLOAT); $lng = filter_var($_POST['kantor_lng'] ?? '', FILTER_VALIDATE_FLOAT);
        $rad = filter_var($_POST['radius'] ?? '', FILTER_VALIDATE_INT, ['options' => ['min_range' => 10, 'max_range' => 5000]]);
        $re = '/^([01]\d|2[0-3]):[0-5]\d$/';
        if ($lat === false || $lng === false || abs($lat) > 90 || abs($lng) > 180 || !$rad || !preg_match($re, $_POST['jam_masuk'] ?? '') || !preg_match($re, $_POST['batas_terlambat'] ?? '') || !preg_match($re, $_POST['jam_pulang'] ?? '')) {
            flash('Periksa kembali isian. Radius 10-5000 meter, koordinat valid, jam format HH:MM.', 'err');
        } else {
            foreach (['nama_kantor' => trim($_POST['nama_kantor']), 'kantor_lat' => $lat, 'kantor_lng' => $lng, 'radius' => $rad, 'jam_masuk' => $_POST['jam_masuk'], 'batas_terlambat' => $_POST['batas_terlambat'], 'jam_pulang' => $_POST['jam_pulang']] as $k => $v)
                q('INSERT INTO pengaturan (kunci, nilai) VALUES (?,?) ON DUPLICATE KEY UPDATE nilai=VALUES(nilai)', [$k, $v]);
            flash('Pengaturan disimpan.');
        }
    } else {
        $h = q('SELECT password FROM users WHERE id=?', [$a['id']])->fetchColumn();
        if (!password_verify($_POST['lama'] ?? '', $h)) flash('Password lama salah.', 'err');
        elseif (strlen($_POST['baru'] ?? '') < 6 || $_POST['baru'] !== ($_POST['ulang'] ?? '')) flash('Password baru minimal 6 karakter dan harus sama dengan konfirmasi.', 'err');
        else { q('UPDATE users SET password=? WHERE id=?', [password_hash($_POST['baru'], PASSWORD_DEFAULT), $a['id']]); flash('Password admin diganti.'); }
    }
    redirect('admin/pengaturan.php');
}
$extraHead = '<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<style>
.map{height:320px;border-radius:10px;border:2px solid var(--orange);z-index:1}
.loc-search{display:flex;gap:8px;margin-bottom:10px}
.loc-search input{flex:1}.loc-search button{flex:none}
.coord-row{display:grid;grid-template-columns:1fr 1fr;gap:12px}
.coord-row input{font-family:monospace;font-size:13px}
.loc-actions{display:flex;gap:8px;flex-wrap:wrap;margin:10px 0}
.gps-badge{display:inline-flex;align-items:center;gap:6px;background:var(--sky);color:var(--blue);font-size:12px;font-weight:600;padding:4px 12px;border-radius:99px}
.gps-badge.err{background:#F8E0DC;color:#9C2B1E}
.search-results{position:absolute;z-index:999;background:#fff;border:1px solid var(--line);border-radius:8px;box-shadow:0 4px 16px rgba(0,0,0,.12);width:100%;max-height:220px;overflow-y:auto;top:calc(100% + 4px);left:0;margin:0;padding:0}
.search-results li{padding:10px 14px;cursor:pointer;font-size:13px;border-bottom:1px solid var(--line);list-style:none}
.search-results li:last-child{border:0}.search-results li:hover{background:var(--sky)}
.search-wrap{position:relative}
</style>';
page_start('Pengaturan', 'pengaturan', $extraHead);
?>
<div class="grid g2">
<form class="card" method="post">
  <h2>Lokasi dan jam presensi</h2><?= csrf_field() ?>
  <div class="fg">
    <label for="nk">Nama kantor</label>
    <input id="nk" name="nama_kantor" value="<?= e(setting('nama_kantor')) ?>" required placeholder="Contoh: BPS Kota Medan">
  </div>
  <div class="fg">
    <label>Cari lokasi di peta</label>
    <div class="search-wrap">
      <div class="loc-search">
        <input id="searchInput" type="text" placeholder="Ketik nama tempat, alamat, kota..." autocomplete="off">
        <button type="button" class="btn btn-line" id="btnSearch">Cari</button>
      </div>
      <ul class="search-results" id="searchResults" style="display:none"></ul>
    </div>
  </div>
  <div class="loc-actions">
    <button type="button" class="btn btn-line btn-sm" id="btnGPS">
      <svg class="ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M12 2v3M12 19v3M2 12h3M19 12h3"/></svg>
      Gunakan lokasi saya (GPS)
    </button>
    <span class="gps-badge" id="gpsStatus" style="display:none"></span>
  </div>
  <div class="fg">
    <div id="map" class="map"></div>
    <p class="muted small" style="margin:8px 0 0"><b>Cara ganti lokasi:</b> klik titik di peta, geser marker (drag), gunakan GPS, atau cari nama tempat di atas.</p>
  </div>
  <div class="fg">
    <label>Koordinat kantor</label>
    <div class="coord-row">
      <div>
        <label for="la" style="font-weight:500;font-size:12px;color:var(--mute);margin-bottom:4px">Latitude</label>
        <input id="la" name="kantor_lat" value="<?= e(setting('kantor_lat')) ?>" required placeholder="-6.2088">
      </div>
      <div>
        <label for="lo" style="font-weight:500;font-size:12px;color:var(--mute);margin-bottom:4px">Longitude</label>
        <input id="lo" name="kantor_lng" value="<?= e(setting('kantor_lng')) ?>" required placeholder="106.8456">
      </div>
    </div>
    <p class="muted small" style="margin:6px 0 0">Koordinat diperbarui otomatis saat Anda klik peta / geser marker. Bisa juga diedit manual lalu tekan Tab.</p>
  </div>
  <div class="fg">
    <label for="ra">Radius presensi (meter)</label>
    <input id="ra" type="number" name="radius" min="10" max="5000" value="<?= e(setting('radius', 100)) ?>" required>
    <p class="muted small" style="margin:5px 0 0">Lingkaran biru di peta menunjukkan area radius. Mahasiswa harus berada di dalam area ini untuk bisa absen.</p>
  </div>
  <div class="fg"><label>Jam kerja</label>
    <div class="frow">
      <div class="fg"><label for="jm" style="font-weight:500;font-size:12px;color:var(--mute);margin-bottom:4px">Jam masuk</label><input id="jm" type="time" name="jam_masuk" value="<?= e(setting('jam_masuk')) ?>" required></div>
      <div class="fg"><label for="bt" style="font-weight:500;font-size:12px;color:var(--mute);margin-bottom:4px">Batas terlambat</label><input id="bt" type="time" name="batas_terlambat" value="<?= e(setting('batas_terlambat')) ?>" required></div>
      <div class="fg"><label for="jp" style="font-weight:500;font-size:12px;color:var(--mute);margin-bottom:4px">Jam pulang</label><input id="jp" type="time" name="jam_pulang" value="<?= e(setting('jam_pulang')) ?>" required></div>
    </div>
  </div>
  <button class="btn" name="simpan" value="1">Simpan pengaturan</button>
</form>
<form class="card" method="post" style="align-self:start">
  <h2>Ganti password admin</h2><?= csrf_field() ?>
  <div class="fg"><label for="l">Password lama</label><input id="l" type="password" name="lama" required autocomplete="current-password"></div>
  <div class="fg"><label for="b">Password baru</label><input id="b" type="password" name="baru" required minlength="6" autocomplete="new-password"></div>
  <div class="fg"><label for="r">Ulangi password baru</label><input id="r" type="password" name="ulang" required autocomplete="new-password"></div>
  <button class="btn">Ganti password</button>
</form>
</div>
<?php
page_end('<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script><script>
(function(){
var la=document.getElementById("la"),lo=document.getElementById("lo"),ra=document.getElementById("ra"),
    si=document.getElementById("searchInput"),sr=document.getElementById("searchResults"),
    gs=document.getElementById("gpsStatus");
var lat0=parseFloat(la.value)||3.5084694, lng0=parseFloat(lo.value)||98.5907339;
var map=L.map("map").setView([lat0,lng0],17);
L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png",{maxZoom:19,attribution:"&copy; OpenStreetMap"}).addTo(map);
var mk=L.marker([lat0,lng0],{draggable:true,title:"Geser untuk pindah lokasi"}).addTo(map);
var ci=L.circle([lat0,lng0],{radius:+ra.value||100,color:"#1B66B3",fillColor:"#1B66B3",fillOpacity:.1,weight:2}).addTo(map);
mk.bindTooltip("Geser untuk pindah lokasi");
function setPos(a,b,z){
  a=+parseFloat(a).toFixed(7);b=+parseFloat(b).toFixed(7);
  la.value=a;lo.value=b;mk.setLatLng([a,b]);ci.setLatLng([a,b]);
  if(z)map.setView([a,b],z);else map.setView([a,b]);
}
map.on("click",function(e){setPos(e.latlng.lat,e.latlng.lng);});
mk.on("dragend",function(e){var p=e.target.getLatLng();setPos(p.lat,p.lng);});
ra.addEventListener("input",function(){ci.setRadius(+ra.value||0);});
function syncInput(){
  var a=parseFloat(la.value),b=parseFloat(lo.value);
  if(!isNaN(a)&&!isNaN(b)&&Math.abs(a)<=90&&Math.abs(b)<=180){mk.setLatLng([a,b]);ci.setLatLng([a,b]);map.setView([a,b]);}
}
la.addEventListener("change",syncInput);lo.addEventListener("change",syncInput);
document.getElementById("btnGPS").addEventListener("click",function(){
  if(!navigator.geolocation){alert("Browser tidak mendukung GPS.");return;}
  gs.className="gps-badge";gs.style.display="";gs.textContent="Mendeteksi GPS...";
  navigator.geolocation.getCurrentPosition(
    function(p){setPos(p.coords.latitude,p.coords.longitude,17);gs.textContent="\u2713 Lokasi GPS berhasil didapat";setTimeout(function(){gs.style.display="none";},3000);},
    function(){gs.className="gps-badge err";gs.textContent="\u2717 Gagal. Izinkan akses lokasi di browser.";setTimeout(function(){gs.style.display="none";},4000);}
  );
});
var tmr;
function doSearch(q){
  if(!q||q.length<3){sr.style.display="none";return;}
  sr.innerHTML="<li style=\"color:var(--mute)\">Mencari...</li>";sr.style.display="";
  fetch("https://nominatim.openstreetmap.org/search?format=json&q="+encodeURIComponent(q)+"&limit=5&accept-language=id")
    .then(function(r){return r.json();})
    .then(function(data){
      sr.innerHTML="";
      if(!data||!data.length){sr.innerHTML="<li style=\"color:var(--mute)\">Lokasi tidak ditemukan.</li>";return;}
      data.forEach(function(item){
        var li=document.createElement("li");li.textContent=item.display_name;
        li.addEventListener("click",function(){setPos(parseFloat(item.lat),parseFloat(item.lon),17);si.value=item.display_name;sr.style.display="none";});
        sr.appendChild(li);
      });
    }).catch(function(){sr.innerHTML="<li style=\"color:var(--mute)\">Gagal terhubung ke layanan pencarian.</li>";});
}
si.addEventListener("input",function(){clearTimeout(tmr);tmr=setTimeout(function(){doSearch(si.value.trim());},500);});
document.getElementById("btnSearch").addEventListener("click",function(){doSearch(si.value.trim());});
si.addEventListener("keydown",function(e){if(e.key==="Enter"){e.preventDefault();doSearch(si.value.trim());}});
document.addEventListener("click",function(e){if(!e.target.closest(".search-wrap"))sr.style.display="none";});
})();
</script>');
