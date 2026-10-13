<?php
require_once '../includes/cek_login.php';
cek_login('admin');
require_once '../config/koneksi.php';
require_once '../includes/fungsi.php';

$hari_ini   = date('Y-m-d');
$hari_angka = (int) date('N'); // 1=Senin ... 7=Minggu
$is_weekend = ($hari_angka >= 6);



// Ambil presensi hari ini untuk mahasiswa aktif dalam periode magangnya (satu query)
$stmt = $pdo->prepare(
    'SELECT u.id AS user_id, u.nama, m.nim, m.kampus, m.jurusan,
            p.jam_masuk, p.jam_pulang, p.status
     FROM users u
     JOIN mahasiswa m ON m.user_id = u.id
     LEFT JOIN presensi p ON p.user_id = u.id AND p.tanggal = ?
     WHERE u.role = "mahasiswa"
       AND u.status = "aktif"
       AND ? BETWEEN m.tgl_mulai AND m.tgl_selesai
     ORDER BY u.nama ASC'
);
$stmt->execute([$hari_ini, $hari_ini]);
$daftar_mahasiswa = $stmt->fetchAll();

// Hitung rekapitulasi kartu hari ini
$hadir          = 0;
$terlambat      = 0;
$izin_sakit     = 0;
$belum_presensi = 0;

if (!$is_weekend) {
    foreach ($daftar_mahasiswa as $row) {
        if ($row['status'] === 'hadir') {
            $hadir++;
        } elseif ($row['status'] === 'terlambat') {
            $terlambat++;
        } elseif (in_array($row['status'], ['izin', 'sakit'], true)) {
            $izin_sakit++;
        } elseif (empty($row['status'])) {
            $belum_presensi++;
        }
    }
}

// Ambil jumlah pengajuan izin berstatus menunggu
$stmt_izin = $pdo->query('SELECT COUNT(*) FROM izin WHERE status = "menunggu"');
$izin_menunggu = (int) $stmt_izin->fetchColumn();

$judul_halaman = 'Dashboard Admin';
require_once '../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h4 class="mb-0">Dashboard Admin</h4>
        <div class="text-muted small"><?= format_tgl_indo($hari_ini, $nama_bulan, $nama_hari) ?></div>
    </div>
</div>

<?php if ($is_weekend): ?>
    <div class="alert alert-secondary mb-4" role="alert">
        <strong>Hari ini bukan hari kerja (akhir pekan).</strong> Presensi hari ini bernilai 0.
    </div>
<?php endif; ?>

<!-- Kartu Ringkasan Hari Ini -->
<div class="row g-2 mb-4">
    <div class="col-6 col-md">
        <div class="card text-center border-success">
            <div class="card-body p-2">
                <div class="fw-bold fs-4 text-success"><?= $hadir ?></div>
                <div class="small text-muted">Hadir</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md">
        <div class="card text-center border-warning">
            <div class="card-body p-2">
                <div class="fw-bold fs-4 text-warning"><?= $terlambat ?></div>
                <div class="small text-muted">Terlambat</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md">
        <div class="card text-center border-info">
            <div class="card-body p-2">
                <div class="fw-bold fs-4 text-info"><?= $izin_sakit ?></div>
                <div class="small text-muted">Izin / Sakit</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md">
        <div class="card text-center border-danger">
            <div class="card-body p-2">
                <div class="fw-bold fs-4 text-danger"><?= $belum_presensi ?></div>
                <div class="small text-muted">Belum Presensi</div>
            </div>
        </div>
    </div>
    <div class="col-12 col-md">
        <div class="card text-center border-primary">
            <div class="card-body p-2">
                <div class="fw-bold fs-4 text-primary"><?= $izin_menunggu ?></div>
                <div class="small">
                    <a href="izin.php?status=menunggu" class="text-decoration-none">Izin Menunggu &rarr;</a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Tabel Presensi Hari Ini -->
<h5 class="mb-3">Presensi Mahasiswa Hari Ini</h5>

<?php if (empty($daftar_mahasiswa)): ?>
    <div class="alert alert-info">Tidak ada mahasiswa magang aktif pada tanggal hari ini.</div>
<?php else: ?>
    <div class="table-responsive">
        <table class="table table-bordered table-hover table-sm align-middle">
            <thead class="table-light">
                <tr>
                    <th style="width: 40px;" class="text-center">No</th>
                    <th>Nama Mahasiswa</th>
                    <th>NIM</th>
                    <th>Kampus</th>
                    <th>Jam Masuk</th>
                    <th>Jam Pulang</th>
                    <th class="text-center">Status</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($daftar_mahasiswa as $idx => $mhs):
                $st = $mhs['status'];
                if (empty($st)) {
                    $badge_class = 'bg-secondary';
                    $badge_text  = 'Belum presensi';
                } elseif ($st === 'hadir') {
                    $badge_class = 'bg-success';
                    $badge_text  = 'Hadir';
                } elseif ($st === 'terlambat') {
                    $badge_class = 'bg-warning text-dark';
                    $badge_text  = 'Terlambat';
                } elseif ($st === 'izin') {
                    $badge_class = 'bg-info';
                    $badge_text  = 'Izin';
                } elseif ($st === 'sakit') {
                    $badge_class = 'bg-secondary';
                    $badge_text  = 'Sakit';
                } else {
                    $badge_class = 'bg-danger';
                    $badge_text  = ucfirst($st);
                }
            ?>
                <tr>
                    <td class="text-center"><?= $idx + 1 ?></td>
                    <td><?= htmlspecialchars($mhs['nama']) ?></td>
                    <td><?= htmlspecialchars($mhs['nim']) ?></td>
                    <td><?= htmlspecialchars($mhs['kampus']) ?></td>
                    <td><?= !empty($mhs['jam_masuk']) ? htmlspecialchars($mhs['jam_masuk']) : '-' ?></td>
                    <td><?= !empty($mhs['jam_pulang']) ? htmlspecialchars($mhs['jam_pulang']) : '-' ?></td>
                    <td class="text-center">
                        <span class="badge <?= $badge_class ?>">
                            <?= $badge_text ?>
                        </span>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<?php require_once '../includes/footer.php'; ?>