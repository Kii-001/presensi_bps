<?php
require_once '../includes/cek_login.php';
cek_login('mahasiswa');
require_once '../config/koneksi.php';

$user_id    = $_SESSION['id'];
$hari_ini   = date('Y-m-d');
$jam_server = date('H:i:s');

// Ambil data mahasiswa untuk validasi rentang magang
$stmt = $pdo->prepare('SELECT tgl_mulai, tgl_selesai FROM mahasiswa WHERE user_id = ?');
$stmt->execute([$user_id]);
$data_mhs = $stmt->fetch();

// Ambil pengaturan jam kerja (baris pertama)
$pengaturan = $pdo->query('SELECT jam_masuk, jam_pulang, toleransi_menit FROM pengaturan LIMIT 1')->fetch();

// Ambil presensi hari ini jika ada
$stmt = $pdo->prepare('SELECT jam_masuk, jam_pulang, status FROM presensi WHERE user_id = ? AND tanggal = ?');
$stmt->execute([$user_id, $hari_ini]);
$presensi_hari_ini = $stmt->fetch();

// Proses POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $aksi = $_POST['aksi'] ?? '';

    if ($aksi === 'masuk') {
        $pesan = proses_masuk($pdo, $user_id, $hari_ini, $jam_server, $data_mhs, $pengaturan);
    } elseif ($aksi === 'pulang') {
        $pesan = proses_pulang($pdo, $user_id, $hari_ini, $jam_server, $presensi_hari_ini);
    } else {
        $pesan = ['tipe' => 'danger', 'teks' => 'Aksi tidak dikenal.'];
    }

    $_SESSION['flash'] = $pesan;
    header('Location: presensi.php');
    exit;
}

// Ambil dan hapus pesan flash
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

// Tentukan kondisi tombol
$hari_angka        = (int) date('N'); // 1=Sen ... 7=Min
$di_luar_hari_kerja = $hari_angka >= 6;
$di_luar_periode   = !$data_mhs
    || $hari_ini < $data_mhs['tgl_mulai']
    || $hari_ini > $data_mhs['tgl_selesai'];

$boleh_masuk  = !$di_luar_hari_kerja && !$di_luar_periode && !$presensi_hari_ini;
$boleh_pulang = $presensi_hari_ini
    && $presensi_hari_ini['jam_masuk'] !== null
    && $presensi_hari_ini['jam_pulang'] === null
    && in_array($presensi_hari_ini['status'], ['hadir', 'terlambat']);

// ---- Fungsi bantu ----

function proses_masuk($pdo, $user_id, $hari_ini, $jam_server, $data_mhs, $pengaturan): array
{
    $hari_angka = (int) date('N');
    if ($hari_angka >= 6) {
        return ['tipe' => 'warning', 'teks' => 'Presensi hanya bisa pada hari Senin sampai Jumat.'];
    }
    if (!$data_mhs || $hari_ini < $data_mhs['tgl_mulai'] || $hari_ini > $data_mhs['tgl_selesai']) {
        return ['tipe' => 'warning', 'teks' => 'Hari ini di luar periode magang Anda.'];
    }

    // Cek sudah presensi (termasuk izin/sakit yang sudah ada)
    $cek = $pdo->prepare('SELECT id FROM presensi WHERE user_id = ? AND tanggal = ?');
    $cek->execute([$user_id, $hari_ini]);
    if ($cek->fetch()) {
        return ['tipe' => 'warning', 'teks' => 'Anda sudah presensi hari ini.'];
    }

    // Tentukan status: hadir atau terlambat
    $batas = date('H:i:s', strtotime($pengaturan['jam_masuk']) + $pengaturan['toleransi_menit'] * 60);
    $status = ($jam_server <= $batas) ? 'hadir' : 'terlambat';

    try {
        $stmt = $pdo->prepare(
            'INSERT INTO presensi (user_id, tanggal, jam_masuk, status) VALUES (?, ?, ?, ?)'
        );
        $stmt->execute([$user_id, $hari_ini, $jam_server, $status]);
    } catch (PDOException $e) {
        // UNIQUE KEY dilanggar: klik ganda atau race condition
        if ($e->getCode() === '23000') {
            return ['tipe' => 'warning', 'teks' => 'Anda sudah presensi hari ini.'];
        }
        return ['tipe' => 'danger', 'teks' => 'Terjadi kesalahan, coba lagi.'];
    }

    $label = ($status === 'hadir') ? 'Hadir' : 'Terlambat';
    return ['tipe' => 'success', 'teks' => "Presensi masuk berhasil. Status: $label."];
}

function proses_pulang($pdo, $user_id, $hari_ini, $jam_server, $presensi_hari_ini): array
{
    if (!$presensi_hari_ini || $presensi_hari_ini['jam_masuk'] === null) {
        return ['tipe' => 'warning', 'teks' => 'Anda belum presensi masuk hari ini.'];
    }
    if ($presensi_hari_ini['jam_pulang'] !== null) {
        return ['tipe' => 'warning', 'teks' => 'Anda sudah presensi pulang hari ini.'];
    }
    if (!in_array($presensi_hari_ini['status'], ['hadir', 'terlambat'])) {
        return ['tipe' => 'warning', 'teks' => 'Presensi pulang tidak berlaku untuk status izin/sakit.'];
    }

    $stmt = $pdo->prepare(
        'UPDATE presensi SET jam_pulang = ? WHERE user_id = ? AND tanggal = ? AND jam_pulang IS NULL'
    );
    $stmt->execute([$jam_server, $user_id, $hari_ini]);

    if ($stmt->rowCount() === 0) {
        return ['tipe' => 'warning', 'teks' => 'Presensi pulang sudah tercatat atau tidak ditemukan.'];
    }

    return ['tipe' => 'success', 'teks' => 'Presensi pulang berhasil dicatat.'];
}

// Nama hari untuk tampilan
$nama_hari = ['', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'];
$label_hari = $nama_hari[(int) date('N')];

$judul_halaman = 'Presensi Hari Ini';
require_once '../includes/header.php';
?>

<h4 class="mb-4">Presensi Hari Ini</h4>

<?php if ($flash): ?>
    <div class="alert alert-<?= htmlspecialchars($flash['tipe']) ?> alert-dismissible fade show" role="alert">
        <?= htmlspecialchars($flash['teks']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="card mb-4">
    <div class="card-body">
        <p class="mb-1"><strong>Tanggal:</strong> <?= htmlspecialchars($label_hari) ?>, <?= date('d/m/Y') ?></p>
        <p class="mb-1"><strong>Jam server:</strong> <?= date('H:i:s') ?></p>
        <p class="mb-1">
            <strong>Jam masuk:</strong>
            <?= $presensi_hari_ini && $presensi_hari_ini['jam_masuk'] ? htmlspecialchars($presensi_hari_ini['jam_masuk']) : '-' ?>
        </p>
        <p class="mb-1">
            <strong>Jam pulang:</strong>
            <?= $presensi_hari_ini && $presensi_hari_ini['jam_pulang'] ? htmlspecialchars($presensi_hari_ini['jam_pulang']) : '-' ?>
        </p>
        <p class="mb-0">
            <strong>Status:</strong>
            <?php if ($presensi_hari_ini): ?>
                <?php
                $badge = [
                    'hadir'     => 'success',
                    'terlambat' => 'warning text-dark',
                    'izin'      => 'info',
                    'sakit'     => 'secondary',
                    'alfa'      => 'danger',
                ];
                $s = $presensi_hari_ini['status'];
                $kelas = $badge[$s] ?? 'secondary';
                ?>
                <span class="badge bg-<?= $kelas ?>"><?= ucfirst(htmlspecialchars($s)) ?></span>
            <?php else: ?>
                <span class="text-muted">Belum presensi</span>
            <?php endif; ?>
        </p>
    </div>
</div>

<form method="post" class="d-flex gap-3">
    <button type="submit" name="aksi" value="masuk"
            class="btn btn-success"
            <?= $boleh_masuk ? '' : 'disabled' ?>>
        Presensi Masuk
    </button>
    <button type="submit" name="aksi" value="pulang"
            class="btn btn-primary"
            <?= $boleh_pulang ? '' : 'disabled' ?>>
        Presensi Pulang
    </button>
</form>

<?php require_once '../includes/footer.php'; ?>