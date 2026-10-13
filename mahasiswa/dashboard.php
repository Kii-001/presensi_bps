<?php
require_once '../includes/cek_login.php';
cek_login('mahasiswa');
require_once '../config/koneksi.php';
require_once '../includes/rekap_hitung.php';
require_once '../includes/fungsi.php';

$user_id  = (int) $_SESSION['id'];
$hari_ini = date('Y-m-d');



// Ambil status presensi hari ini
$stmt_hari_ini = $pdo->prepare('SELECT jam_masuk, jam_pulang, status FROM presensi WHERE user_id = ? AND tanggal = ?');
$stmt_hari_ini->execute([$user_id, $hari_ini]);
$presensi_hari_ini = $stmt_hari_ini->fetch();

// Ambil ringkasan bulan ini dari fungsi sentral ambil_rekap()
$tgl_awal_bulan = date('Y-m-01');
$ringkasan      = ambil_rekap($pdo, $user_id, $tgl_awal_bulan, $hari_ini);

// Ambil jumlah pengajuan izin berstatus menunggu milik mahasiswa ini
$stmt_izin = $pdo->prepare('SELECT COUNT(*) FROM izin WHERE user_id = ? AND status = "menunggu"');
$stmt_izin->execute([$user_id]);
$izin_menunggu = (int) $stmt_izin->fetchColumn();

$judul_halaman = 'Dashboard Mahasiswa';
require_once '../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h4 class="mb-0">Dashboard Mahasiswa</h4>
        <div class="text-muted small">Selamat datang, <?= htmlspecialchars($_SESSION['nama']) ?></div>
    </div>
</div>

<?php if ($izin_menunggu > 0): ?>
    <div class="alert alert-warning py-2 mb-4" role="alert">
        Anda memiliki <strong><?= $izin_menunggu ?></strong> pengajuan izin/sakit yang masih menunggu persetujuan admin.
        <a href="izin.php" class="alert-link ms-2">Lihat Pengajuan &rarr;</a>
    </div>
<?php endif; ?>

<!-- Kartu Status Hari Ini -->
<div class="card mb-4 border-primary">
    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center py-2">
        <span class="fw-semibold">Status Presensi Hari Ini</span>
        <span class="small"><?= format_tgl_indo($hari_ini, $nama_bulan, $nama_hari) ?></span>
    </div>
    <div class="card-body">
        <div class="row align-items-center">
            <div class="col-md-8">
                <div class="row g-3">
                    <div class="col-sm-4">
                        <div class="text-muted small">Jam Masuk</div>
                        <div class="fs-5 fw-bold">
                            <?= !empty($presensi_hari_ini['jam_masuk']) ? htmlspecialchars($presensi_hari_ini['jam_masuk']) : '-' ?>
                        </div>
                    </div>
                    <div class="col-sm-4">
                        <div class="text-muted small">Jam Pulang</div>
                        <div class="fs-5 fw-bold">
                            <?= !empty($presensi_hari_ini['jam_pulang']) ? htmlspecialchars($presensi_hari_ini['jam_pulang']) : '-' ?>
                        </div>
                    </div>
                    <div class="col-sm-4">
                        <div class="text-muted small">Status</div>
                        <div>
                            <?php
                            $st = $presensi_hari_ini['status'] ?? null;
                            if (!$st) {
                                echo '<span class="badge bg-secondary">Belum presensi</span>';
                            } elseif ($st === 'hadir') {
                                echo '<span class="badge bg-success">Hadir</span>';
                            } elseif ($st === 'terlambat') {
                                echo '<span class="badge bg-warning text-dark">Terlambat</span>';
                            } elseif ($st === 'izin') {
                                echo '<span class="badge bg-info">Izin</span>';
                            } elseif ($st === 'sakit') {
                                echo '<span class="badge bg-secondary">Sakit</span>';
                            } else {
                                echo '<span class="badge bg-danger">' . ucfirst($st) . '</span>';
                            }
                            ?>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4 text-md-end mt-3 mt-md-0">
                <a href="presensi.php" class="btn btn-primary">
                    Ke Halaman Presensi
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Ringkasan Bulan Ini -->
<h5 class="mb-3">Ringkasan Bulan Ini (<?= $nama_bulan[(int) date('n')] ?> <?= date('Y') ?>)</h5>
<div class="row g-2 mb-4">
    <?php
    $kartu = [
        'hadir'     => ['label' => 'Hadir',     'warna' => 'success'],
        'terlambat' => ['label' => 'Terlambat', 'warna' => 'warning'],
        'izin'      => ['label' => 'Izin',      'warna' => 'info'],
        'sakit'     => ['label' => 'Sakit',     'warna' => 'secondary'],
        'alfa'      => ['label' => 'Alfa',      'warna' => 'danger'],
    ];
    foreach ($kartu as $key => $k): ?>
        <div class="col-6 col-sm-4 col-md">
            <div class="card text-center border-<?= $k['warna'] ?>">
                <div class="card-body p-2">
                    <div class="fw-bold fs-4"><?= $ringkasan[$key] ?></div>
                    <div class="small text-muted"><?= $k['label'] ?></div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<?php require_once '../includes/footer.php'; ?>