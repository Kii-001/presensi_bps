<?php
require_once '../includes/cek_login.php';
cek_login('admin');
require_once '../config/koneksi.php';

// Ambil parameter filter (mendukung alias tgl_dari/tgl_awal dan tgl_sampai/tgl_akhir)
$hari_ini    = date('Y-m-d');
$tgl_dari    = $_GET['tgl_dari']   ?? ($_GET['tgl_awal']  ?? date('Y-m-01'));
$tgl_sampai  = $_GET['tgl_sampai'] ?? ($_GET['tgl_akhir'] ?? $hari_ini);
$user_id     = isset($_GET['user_id']) ? (int) $_GET['user_id'] : 0;
$status_pilih = trim($_GET['status'] ?? '');
$mode_cetak  = isset($_GET['cetak']) && $_GET['cetak'] === '1';

// Fungsi validasi tanggal format YYYY-MM-DD
if (!function_exists('valid_tanggal')) {
    function valid_tanggal($str): bool {
        if (!is_string($str) || !preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $str, $m)) {
            return false;
        }
        return checkdate((int) $m[2], (int) $m[3], (int) $m[1]);
    }
}

// Validasi format tanggal; jika format ngawur, kembalikan ke default aman
if (!valid_tanggal($tgl_dari)) {
    $tgl_dari = date('Y-m-01');
}
if (!valid_tanggal($tgl_sampai)) {
    $tgl_sampai = $hari_ini;
}

// Jika tanggal akhir lebih awal dari tanggal awal, tukar agar tidak terjadi error
if ($tgl_dari > $tgl_sampai) {
    $temp = $tgl_dari;
    $tgl_dari = $tgl_sampai;
    $tgl_sampai = $temp;
}

// Batasi batas bawah tahun maksimal 5 tahun ke belakang agar looping aman
$tahun_sekarang = (int) date('Y');
if ((int) substr($tgl_dari, 0, 4) < ($tahun_sekarang - 5)) {
    $tgl_dari = ($tahun_sekarang - 5) . '-01-01';
}

// Ambil daftar seluruh mahasiswa untuk dropdown filter
$stmt = $pdo->query(
    'SELECT u.id, u.nama, m.nim, m.kampus, m.jurusan, m.tgl_mulai, m.tgl_selesai
     FROM users u
     JOIN mahasiswa m ON m.user_id = u.id
     WHERE u.role = "mahasiswa"
     ORDER BY u.nama'
);
$semua_mhs = $stmt->fetchAll();

// Tentukan mahasiswa yang direkap
$target_mhs = [];
$mhs_terpilih = null;
if ($user_id > 0) {
    foreach ($semua_mhs as $mhs) {
        if ($mhs['id'] === $user_id) {
            $target_mhs[] = $mhs;
            $mhs_terpilih = $mhs;
            break;
        }
    }
}
if (empty($target_mhs)) {
    $target_mhs = $semua_mhs;
}

// Ambil presensi yang sudah tercatat pada rentang tanggal
$stmt = $pdo->prepare(
    'SELECT p.user_id, p.tanggal, p.jam_masuk, p.jam_pulang, p.status, p.keterangan
     FROM presensi p
     WHERE p.tanggal BETWEEN ? AND ?'
);
$stmt->execute([$tgl_dari, $tgl_sampai]);
$presensi_index = [];
foreach ($stmt->fetchAll() as $row) {
    $presensi_index[$row['user_id']][$row['tanggal']] = $row;
}

// Array nama hari dan bulan bahasa Indonesia
$nama_hari = ['', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'];
$nama_bulan = [
    '', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
    'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
];

// Bangun daftar baris rekap dan hitung ringkasan
$daftar_baris = [];
$ringkasan = ['hadir' => 0, 'terlambat' => 0, 'izin' => 0, 'sakit' => 0, 'alfa' => 0];

foreach ($target_mhs as $mhs) {
    $uid = $mhs['id'];
    $cur_tgl = $tgl_dari;

    while ($cur_tgl <= $tgl_sampai) {
        // Lewati tanggal di masa depan jika filter melampaui hari ini
        if ($cur_tgl > $hari_ini) {
            $cur_tgl = date('Y-m-d', strtotime($cur_tgl . ' +1 day'));
            continue;
        }

        $hari_angka  = (int) date('N', strtotime($cur_tgl)); // 1=Senin ... 7=Minggu
        $ada_presensi = isset($presensi_index[$uid][$cur_tgl]);

        // Sabtu dan Minggu: hanya tampilkan bila ada rekaman presensi
        if ($hari_angka >= 6 && !$ada_presensi) {
            $cur_tgl = date('Y-m-d', strtotime($cur_tgl . ' +1 day'));
            continue;
        }

        if ($ada_presensi) {
            $p      = $presensi_index[$uid][$cur_tgl];
            $status = $p['status'];
            $ringkasan[$status] = ($ringkasan[$status] ?? 0) + 1;

            if ($status_pilih === '' || $status_pilih === $status) {
                $daftar_baris[] = [
                    'user_id'    => $uid,
                    'nama'       => $mhs['nama'],
                    'nim'        => $mhs['nim'],
                    'kampus'     => $mhs['kampus'],
                    'jurusan'    => $mhs['jurusan'],
                    'tanggal'    => $cur_tgl,
                    'hari'       => $nama_hari[$hari_angka],
                    'jam_masuk'  => $p['jam_masuk']  ?? '-',
                    'jam_pulang' => $p['jam_pulang'] ?? '-',
                    'status'     => $status,
                    'keterangan' => $p['keterangan'] ?? '-',
                ];
            }
        } else {
            // Hitung alfa untuk hari kerja dalam periode magang yang sudah lewat
            $dalam_periode = ($cur_tgl >= $mhs['tgl_mulai'] && $cur_tgl <= $mhs['tgl_selesai']);

            if ($hari_angka <= 5 && $dalam_periode && $cur_tgl < $hari_ini) {
                $ringkasan['alfa']++;

                if ($status_pilih === '' || $status_pilih === 'alfa') {
                    $daftar_baris[] = [
                        'user_id'    => $uid,
                        'nama'       => $mhs['nama'],
                        'nim'        => $mhs['nim'],
                        'kampus'     => $mhs['kampus'],
                        'jurusan'    => $mhs['jurusan'],
                        'tanggal'    => $cur_tgl,
                        'hari'       => $nama_hari[$hari_angka],
                        'jam_masuk'  => '-',
                        'jam_pulang' => '-',
                        'status'     => 'alfa',
                        'keterangan' => 'Tanpa keterangan',
                    ];
                }
            }
        }

        $cur_tgl = date('Y-m-d', strtotime($cur_tgl . ' +1 day'));
    }
}

// Urutkan baris: tanggal terbaru di atas, lalu nama
usort($daftar_baris, function ($a, $b) {
    if ($a['tanggal'] === $b['tanggal']) {
        return strcmp($a['nama'], $b['nama']);
    }
    return strcmp($b['tanggal'], $a['tanggal']);
});

// Format tanggal Indonesia untuk tampilan teks
if (!function_exists('format_tgl_indo')) {
    function format_tgl_indo($tgl_str, $nama_bulan, $nama_hari = null) {
        $ts = strtotime($tgl_str);
        $d  = (int) date('j', $ts);
        $m  = (int) date('n', $ts);
        $y  = (int) date('Y', $ts);
        $teks = $d . ' ' . $nama_bulan[$m] . ' ' . $y;
        if ($nama_hari !== null) {
            $h = (int) date('N', $ts);
            $teks = $nama_hari[$h] . ', ' . $teks;
        }
        return $teks;
    }
}

// -------------------------------------------------------------
// TAMPILAN CETAK LAPORAN
// -------------------------------------------------------------
if ($mode_cetak):
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Laporan Rekap Presensi - BPS Kota Medan</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <style>
        body {
            font-size: 12px;
            color: #000;
            background: #fff;
        }
        .kop-surat {
            border-bottom: 2px solid #000;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        .table-sm th, .table-sm td {
            padding: 4px 6px;
        }
        @media print {
            .no-print {
                display: none !important;
            }
            body {
                padding: 0;
            }
        }
    </style>
</head>
<body class="p-4">

<div class="no-print mb-3 text-end">
    <button onclick="window.print()" class="btn btn-primary btn-sm">Cetak Laporan</button>
    <button onclick="window.close()" class="btn btn-secondary btn-sm">Tutup</button>
</div>

<!-- Kop Laporan Resmi -->
<div class="kop-surat text-center">
    <h5 class="fw-bold mb-1">BADAN PUSAT STATISTIK KOTA MEDAN</h5>
    <div class="small">Jl. Asrama No. 179, Dwi Kora, Kec. Medan Helvetia, Kota Medan, Sumatera Utara 20123</div>
    <div class="small">Telepon: (061) 8452344 | Email: bps1271@bps.go.id</div>
</div>

<div class="text-center mb-4">
    <h6 class="fw-bold text-uppercase mb-1">Laporan Rekapitulasi Presensi Mahasiswa Magang</h6>
    <div class="small">
        Periode: <?= format_tgl_indo($tgl_dari, $nama_bulan) ?> s/d <?= format_tgl_indo($tgl_sampai, $nama_bulan) ?>
    </div>
    <?php if ($mhs_terpilih): ?>
        <div class="small mt-1">
            <strong>Mahasiswa:</strong> <?= htmlspecialchars($mhs_terpilih['nama']) ?>
            (NIM: <?= htmlspecialchars($mhs_terpilih['nim']) ?> - <?= htmlspecialchars($mhs_terpilih['kampus']) ?>)
        </div>
    <?php else: ?>
        <div class="small mt-1"><strong>Filter Mahasiswa:</strong> Semua Mahasiswa</div>
    <?php endif; ?>
</div>

<!-- Ringkasan Kehadiran -->
<div class="row g-2 mb-3">
    <div class="col-12">
        <table class="table table-bordered table-sm text-center mb-0" style="width: 100%; max-width: 500px; margin: 0 auto;">
            <thead class="table-light">
                <tr>
                    <th>Hadir</th>
                    <th>Terlambat</th>
                    <th>Izin</th>
                    <th>Sakit</th>
                    <th>Alfa</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><?= $ringkasan['hadir'] ?></td>
                    <td><?= $ringkasan['terlambat'] ?></td>
                    <td><?= $ringkasan['izin'] ?></td>
                    <td><?= $ringkasan['sakit'] ?></td>
                    <td><?= $ringkasan['alfa'] ?></td>
                    <td class="fw-bold"><?= array_sum($ringkasan) ?></td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<!-- Tabel Rincian Data Presensi -->
<?php if (empty($daftar_baris)): ?>
    <div class="alert alert-info text-center py-2">Tidak ada catatan presensi pada periode ini.</div>
<?php else: ?>
    <table class="table table-bordered table-sm align-middle mt-2">
        <thead class="table-light text-center">
            <tr>
                <th style="width: 40px;">No</th>
                <th>Tanggal</th>
                <th>Nama Mahasiswa</th>
                <th>NIM</th>
                <th>Jam Masuk</th>
                <th>Jam Pulang</th>
                <th>Status</th>
                <th>Keterangan</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($daftar_baris as $idx => $b): ?>
            <tr>
                <td class="text-center"><?= $idx + 1 ?></td>
                <td><?= format_tgl_indo($b['tanggal'], $nama_bulan, $nama_hari) ?></td>
                <td><?= htmlspecialchars($b['nama']) ?></td>
                <td><?= htmlspecialchars($b['nim']) ?></td>
                <td class="text-center"><?= htmlspecialchars($b['jam_masuk']) ?></td>
                <td class="text-center"><?= htmlspecialchars($b['jam_pulang']) ?></td>
                <td class="text-center text-capitalize"><?= htmlspecialchars($b['status']) ?></td>
                <td><?= htmlspecialchars($b['keterangan']) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<!-- Kolom Tanda Tangan -->
<div class="row mt-5">
    <div class="col-7"></div>
    <div class="col-5 text-center">
        <div>Medan, <?= format_tgl_indo($hari_ini, $nama_bulan) ?></div>
        <div class="mb-5">Pengawas Magang,</div>
        <div class="fw-bold text-decoration-underline"><?= htmlspecialchars($_SESSION['nama']) ?></div>
        <div>NIP. .....................................</div>
    </div>
</div>

<script>
window.addEventListener('load', function() {
    window.print();
});
</script>
</body>
</html>
<?php
exit;
endif;

// -------------------------------------------------------------
// TAMPILAN HALAMAN WEB ADMIN
// -------------------------------------------------------------
$judul_halaman = 'Rekap Presensi';
require_once '../includes/header.php';

// Bangun link cetak dengan query parameter saat ini
$param_cetak = http_build_query([
    'tgl_dari'   => $tgl_dari,
    'tgl_sampai' => $tgl_sampai,
    'user_id'    => $user_id,
    'status'     => $status_pilih,
]);
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">Rekap Presensi Mahasiswa</h4>
    <a href="rekap_cetak.php?<?= $param_cetak ?>" target="_blank" class="btn btn-outline-primary btn-sm">
        Cetak Laporan
    </a>
</div>

<!-- Form Filter -->
<div class="card mb-4">
    <div class="card-body">
        <form method="get" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label for="tgl_dari" class="form-label small fw-semibold">Tanggal Dari</label>
                <input type="date" id="tgl_dari" name="tgl_dari" class="form-control form-control-sm"
                       value="<?= htmlspecialchars($tgl_dari) ?>">
            </div>
            <div class="col-md-3">
                <label for="tgl_sampai" class="form-label small fw-semibold">Tanggal Sampai</label>
                <input type="date" id="tgl_sampai" name="tgl_sampai" class="form-control form-control-sm"
                       value="<?= htmlspecialchars($tgl_sampai) ?>">
            </div>
            <div class="col-md-3">
                <label for="user_id" class="form-label small fw-semibold">Mahasiswa</label>
                <select id="user_id" name="user_id" class="form-select form-select-sm">
                    <option value="0">Semua Mahasiswa</option>
                    <?php foreach ($semua_mhs as $mhs): ?>
                        <option value="<?= $mhs['id'] ?>" <?= $mhs['id'] === $user_id ? 'selected' : '' ?>>
                            <?= htmlspecialchars($mhs['nama']) ?> (<?= htmlspecialchars($mhs['nim']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label for="status" class="form-label small fw-semibold">Status Presensi</label>
                <select id="status" name="status" class="form-select form-select-sm">
                    <option value="">Semua Status</option>
                    <option value="hadir"     <?= $status_pilih === 'hadir'     ? 'selected' : '' ?>>Hadir</option>
                    <option value="terlambat" <?= $status_pilih === 'terlambat' ? 'selected' : '' ?>>Terlambat</option>
                    <option value="izin"      <?= $status_pilih === 'izin'      ? 'selected' : '' ?>>Izin</option>
                    <option value="sakit"     <?= $status_pilih === 'sakit'     ? 'selected' : '' ?>>Sakit</option>
                    <option value="alfa"      <?= $status_pilih === 'alfa'      ? 'selected' : '' ?>>Alfa</option>
                </select>
            </div>
            <div class="col-12 text-end">
                <button type="submit" class="btn btn-primary btn-sm">Tampilkan</button>
                <a href="rekap.php" class="btn btn-outline-secondary btn-sm">Reset</a>
            </div>
        </form>
    </div>
</div>

<!-- Kartu Ringkasan -->
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
        <div class="col-6 col-sm-4 col-md-2">
            <div class="card text-center border-<?= $k['warna'] ?>">
                <div class="card-body p-2">
                    <div class="fw-bold fs-4"><?= $ringkasan[$key] ?></div>
                    <div class="small text-muted"><?= $k['label'] ?></div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
    <div class="col-6 col-sm-4 col-md-2">
        <div class="card text-center border-dark">
            <div class="card-body p-2">
                <div class="fw-bold fs-4"><?= array_sum($ringkasan) ?></div>
                <div class="small text-muted">Total Hari</div>
            </div>
        </div>
    </div>
</div>

<!-- Tabel Hasil Rekap -->
<?php if (empty($daftar_baris)): ?>
    <div class="alert alert-info">
        Tidak ada data presensi yang sesuai dengan filter yang dipilih.
    </div>
<?php else: ?>
    <div class="table-responsive">
        <table class="table table-bordered table-hover table-sm align-middle">
            <thead class="table-light">
                <tr>
                    <th style="width: 40px;" class="text-center">No</th>
                    <th>Tanggal</th>
                    <th>Nama Mahasiswa</th>
                    <th>NIM</th>
                    <th>Jam Masuk</th>
                    <th>Jam Pulang</th>
                    <th>Status</th>
                    <th>Keterangan</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($daftar_baris as $idx => $b):
                $badge = [
                    'hadir'     => 'success',
                    'terlambat' => 'warning text-dark',
                    'izin'      => 'info',
                    'sakit'     => 'secondary',
                    'alfa'      => 'danger',
                ];
                $kelas_badge = $badge[$b['status']] ?? 'secondary';
            ?>
                <tr>
                    <td class="text-center"><?= $idx + 1 ?></td>
                    <td><?= format_tgl_indo($b['tanggal'], $nama_bulan, $nama_hari) ?></td>
                    <td><?= htmlspecialchars($b['nama']) ?></td>
                    <td><?= htmlspecialchars($b['nim']) ?></td>
                    <td><?= htmlspecialchars($b['jam_masuk']) ?></td>
                    <td><?= htmlspecialchars($b['jam_pulang']) ?></td>
                    <td>
                        <span class="badge bg-<?= $kelas_badge ?>">
                            <?= ucfirst($b['status']) ?>
                        </span>
                    </td>
                    <td><?= htmlspecialchars($b['keterangan']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<?php require_once '../includes/footer.php'; ?>