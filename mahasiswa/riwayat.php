<?php
require_once '../includes/cek_login.php';
cek_login('mahasiswa');
require_once '../config/koneksi.php';

$user_id  = $_SESSION['id'];
$hari_ini = date('Y-m-d');

// Ambil data mahasiswa
$stmt = $pdo->prepare('SELECT tgl_mulai, tgl_selesai FROM mahasiswa WHERE user_id = ?');
$stmt->execute([$user_id]);
$data_mhs = $stmt->fetch();

$tahun_mulai   = $data_mhs ? (int) date('Y', strtotime($data_mhs['tgl_mulai']))   : (int) date('Y');
$tahun_selesai = $data_mhs ? (int) date('Y', strtotime($data_mhs['tgl_selesai'])) : (int) date('Y');

// Validasi dan ambil filter bulan/tahun dari GET
$bulan_default = (int) date('m');
$tahun_default = (int) date('Y');

$bulan = isset($_GET['bulan']) ? (int) $_GET['bulan'] : $bulan_default;
$tahun = isset($_GET['tahun']) ? (int) $_GET['tahun'] : $tahun_default;

if ($bulan < 1 || $bulan > 12) $bulan = $bulan_default;
if (strlen((string) $tahun) !== 4) $tahun = $tahun_default;

// Ambil semua presensi bulan ini, indeks per tanggal
$stmt = $pdo->prepare(
    'SELECT tanggal, jam_masuk, jam_pulang, status, keterangan
     FROM presensi
     WHERE user_id = ?
       AND YEAR(tanggal) = ?
       AND MONTH(tanggal) = ?'
);
$stmt->execute([$user_id, $tahun, $bulan]);
$presensi_index = [];
foreach ($stmt->fetchAll() as $baris) {
    $presensi_index[$baris['tanggal']] = $baris;
}

// Array nama hari dan bulan bahasa Indonesia
$nama_hari  = ['', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'];
$nama_bulan = [
    '', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
    'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
];

// Bangun daftar baris yang akan ditampilkan
$jumlah_hari = cal_days_in_month(CAL_GREGORIAN, $bulan, $tahun);
$daftar_baris = [];

$ringkasan = ['hadir' => 0, 'terlambat' => 0, 'izin' => 0, 'sakit' => 0, 'alfa' => 0];

for ($d = 1; $d <= $jumlah_hari; $d++) {
    $tgl_str   = sprintf('%04d-%02d-%02d', $tahun, $bulan, $d);
    $hari_angka = (int) date('N', strtotime($tgl_str)); // 1=Sen ... 7=Min

    // Tidak tampilkan tanggal setelah hari ini
    if ($tgl_str > $hari_ini) {
        continue;
    }

    $ada_presensi = isset($presensi_index[$tgl_str]);

    // Sabtu/Minggu: hanya tampilkan kalau ada baris presensi
    if ($hari_angka >= 6 && !$ada_presensi) {
        continue;
    }

    if ($ada_presensi) {
        $baris   = $presensi_index[$tgl_str];
        $status  = $baris['status'];
        $ringkasan[$status] = ($ringkasan[$status] ?? 0) + 1;
        $daftar_baris[] = [
            'tanggal'   => $tgl_str,
            'hari'      => $nama_hari[$hari_angka],
            'jam_masuk' => $baris['jam_masuk']  ?? '-',
            'jam_pulang'=> $baris['jam_pulang'] ?? '-',
            'status'    => $status,
            'keterangan'=> $baris['keterangan'] ?? '',
        ];
    } else {
        // Alfa: hari kerja, dalam periode magang, sudah lewat (bukan hari ini)
        $dalam_periode = $data_mhs
            && $tgl_str >= $data_mhs['tgl_mulai']
            && $tgl_str <= $data_mhs['tgl_selesai'];

        if ($hari_angka <= 5 && $dalam_periode && $tgl_str < $hari_ini) {
            $ringkasan['alfa']++;
            $daftar_baris[] = [
                'tanggal'   => $tgl_str,
                'hari'      => $nama_hari[$hari_angka],
                'jam_masuk' => '-',
                'jam_pulang'=> '-',
                'status'    => 'alfa',
                'keterangan'=> '',
            ];
        }
        // Di luar periode atau hari ini tanpa presensi: tidak ditampilkan
    }
}

// Urutkan dari terbaru ke terlama
$daftar_baris = array_reverse($daftar_baris);

$judul_halaman = 'Riwayat Presensi';
require_once '../includes/header.php';
?>

<h4 class="mb-4">Riwayat Presensi</h4>

<form method="get" class="row g-2 align-items-end mb-4">
    <div class="col-auto">
        <label class="form-label">Bulan</label>
        <select name="bulan" class="form-select form-select-sm">
            <?php for ($m = 1; $m <= 12; $m++): ?>
                <option value="<?= $m ?>" <?= $m === $bulan ? 'selected' : '' ?>>
                    <?= $nama_bulan[$m] ?>
                </option>
            <?php endfor; ?>
        </select>
    </div>
    <div class="col-auto">
        <label class="form-label">Tahun</label>
        <select name="tahun" class="form-select form-select-sm">
            <?php for ($y = $tahun_mulai; $y <= $tahun_selesai; $y++): ?>
                <option value="<?= $y ?>" <?= $y === $tahun ? 'selected' : '' ?>>
                    <?= $y ?>
                </option>
            <?php endfor; ?>
        </select>
    </div>
    <div class="col-auto">
        <button type="submit" class="btn btn-primary btn-sm">Tampilkan</button>
    </div>
</form>

<?php if (empty($daftar_baris)): ?>
    <div class="alert alert-info">Tidak ada data presensi pada bulan ini.</div>
<?php else: ?>

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
    </div>

    <div class="table-responsive">
        <table class="table table-bordered table-hover table-sm align-middle">
            <thead class="table-light">
                <tr>
                    <th>Tanggal</th>
                    <th>Masuk</th>
                    <th>Pulang</th>
                    <th>Status</th>
                    <th>Keterangan</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($daftar_baris as $baris):
                $d_angka = (int) date('j', strtotime($baris['tanggal']));
                $m_angka = (int) date('n', strtotime($baris['tanggal']));
                $y_angka = (int) date('Y', strtotime($baris['tanggal']));
                $label_tgl = $baris['hari'] . ', ' . $d_angka . ' ' . $nama_bulan[$m_angka] . ' ' . $y_angka;

                $badge = [
                    'hadir'     => 'success',
                    'terlambat' => 'warning text-dark',
                    'izin'      => 'info',
                    'sakit'     => 'secondary',
                    'alfa'      => 'danger',
                ];
                $kelas_badge = $badge[$baris['status']] ?? 'secondary';
            ?>
                <tr>
                    <td><?= htmlspecialchars($label_tgl) ?></td>
                    <td><?= htmlspecialchars($baris['jam_masuk']) ?></td>
                    <td><?= htmlspecialchars($baris['jam_pulang']) ?></td>
                    <td><span class="badge bg-<?= $kelas_badge ?>"><?= ucfirst($baris['status']) ?></span></td>
                    <td><?= htmlspecialchars($baris['keterangan']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

<?php endif; ?>

<?php require_once '../includes/footer.php'; ?>