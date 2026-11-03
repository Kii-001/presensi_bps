<?php
require __DIR__ . '/../includes/functions.php';
header('Content-Type: application/json; charset=utf-8');
function out(bool $ok, string $msg, array $x = []): void { echo json_encode(['ok' => $ok, 'msg' => $msg] + $x); exit; }

$u = me();
if (!$u || $u['role'] !== 'mahasiswa') { http_response_code(401); out(false, 'Sesi berakhir. Silakan login ulang.'); }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); out(false, 'Metode tidak valid.'); }
if (!hash_equals($_SESSION['csrf'] ?? 'x', $_POST['csrf'] ?? '')) out(false, 'Sesi kedaluwarsa. Muat ulang halaman.');

$aksi = $_POST['aksi'] ?? '';
if (!in_array($aksi, ['masuk', 'pulang'], true)) out(false, 'Aksi tidak dikenal.');

// 1) Validasi lokasi di SERVER (tidak hanya di browser)
$lat = filter_var($_POST['lat'] ?? null, FILTER_VALIDATE_FLOAT);
$lng = filter_var($_POST['lng'] ?? null, FILTER_VALIDATE_FLOAT);
if ($lat === false || $lng === false || abs($lat) > 90 || abs($lng) > 180) out(false, 'Lokasi tidak terbaca. Aktifkan GPS dan izin lokasi.');
$jarak = jarak_m($lat, $lng, (float)setting('kantor_lat'), (float)setting('kantor_lng'));
$radius = (float)setting('radius', 100);
if ($jarak > $radius) out(false, 'Lokasi di luar radius presensi (' . round($jarak) . ' m dari kantor, batas ' . round($radius) . ' m).');

// 2) Aturan harian
$today = date('Y-m-d'); $now = date('H:i:s');
$row = q('SELECT * FROM presensi WHERE user_id=? AND tanggal=?', [$u['id'], $today])->fetch();
if ($aksi === 'masuk' && $row) out(false, $row['jam_masuk'] ? 'Anda sudah Clock In hari ini.' : 'Hari ini tercatat ' . $row['status'] . '.');
if ($aksi === 'pulang') {
    if (!$row || !$row['jam_masuk']) out(false, 'Anda belum Clock In hari ini.');
    if ($row['jam_pulang']) out(false, 'Anda sudah Clock Out hari ini.');
}

// 3) Foto selfie wajib
$foto = save_selfie($_POST['foto'] ?? '', (int)$u['id'], $aksi);
if (!$foto) out(false, 'Foto selfie wajib diambil dari kamera.');

if ($aksi === 'masuk') {
    $status = substr($now, 0, 5) > setting('batas_terlambat', '08:00') ? 'terlambat' : 'hadir';
    q('INSERT INTO presensi (user_id,tanggal,jam_masuk,lat_masuk,lng_masuk,jarak_masuk,foto_masuk,status) VALUES (?,?,?,?,?,?,?,?)',
      [$u['id'], $today, $now, $lat, $lng, round($jarak, 2), $foto, $status]);
    flash('Clock In berhasil pukul ' . substr($now, 0, 5) . '. Status: ' . $status . '.');
    out(true, 'Clock In berhasil.', ['status' => $status]);
}
q('UPDATE presensi SET jam_pulang=?, lat_pulang=?, lng_pulang=?, jarak_pulang=?, foto_pulang=? WHERE id=?',
  [$now, $lat, $lng, round($jarak, 2), $foto, $row['id']]);
flash('Clock Out berhasil pukul ' . substr($now, 0, 5) . '.');
out(true, 'Clock Out berhasil.');
