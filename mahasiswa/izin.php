<?php
require_once '../includes/cek_login.php';
cek_login('mahasiswa');
require_once '../config/koneksi.php';

$user_id  = $_SESSION['id'];
$hari_ini = date('Y-m-d');

// Ambil periode magang mahasiswa
$stmt = $pdo->prepare('SELECT tgl_mulai, tgl_selesai FROM mahasiswa WHERE user_id = ?');
$stmt->execute([$user_id]);
$data_mhs = $stmt->fetch();

// Array nama hari dan bulan bahasa Indonesia
$nama_hari = ['', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'];
$nama_bulan = [
    '', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
    'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
];

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

// Proses pengajuan izin / sakit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tanggal = trim($_POST['tanggal'] ?? '');
    $jenis   = trim($_POST['jenis']   ?? '');
    $alasan  = trim($_POST['alasan']  ?? '');

    $error = '';
    $nama_file_tersimpan = null;
    $target_file_path    = null;

    // Validasi input dasar
    if ($tanggal === '' || $jenis === '' || $alasan === '') {
        $error = 'Tanggal, jenis pengajuan, dan alasan wajib diisi.';
    } elseif (!in_array($jenis, ['izin', 'sakit'], true)) {
        $error = 'Jenis pengajuan tidak valid.';
    } elseif (!preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $tanggal, $m) || !checkdate((int) $m[2], (int) $m[3], (int) $m[1])) {
        $error = 'Format tanggal tidak valid.';
    } elseif ($tanggal < $hari_ini) {
        $error = 'Tanggal pengajuan tidak boleh lebih lampau dari hari ini.';
    } else {
        $hari_angka = (int) date('N', strtotime($tanggal));
        if ($hari_angka >= 6) {
            $error = 'Pengajuan hanya dapat dilakukan untuk hari kerja (Senin sampai Jumat).';
        } elseif (!$data_mhs || $tanggal < $data_mhs['tgl_mulai'] || $tanggal > $data_mhs['tgl_selesai']) {
            $error = 'Tanggal pengajuan harus berada dalam rentang periode magang Anda.';
        }
    }

    // Cek apakah sudah ada presensi pada tanggal tersebut
    if ($error === '') {
        $stmt = $pdo->prepare('SELECT id FROM presensi WHERE user_id = ? AND tanggal = ?');
        $stmt->execute([$user_id, $tanggal]);
        if ($stmt->fetch()) {
            $error = 'Anda sudah memiliki catatan presensi pada tanggal tersebut.';
        }
    }

    // Cek apakah sudah ada pengajuan izin lain yang menunggu atau disetujui pada tanggal tersebut
    if ($error === '') {
        $stmt = $pdo->prepare('SELECT id FROM izin WHERE user_id = ? AND tanggal = ? AND status IN ("menunggu", "disetujui")');
        $stmt->execute([$user_id, $tanggal]);
        if ($stmt->fetch()) {
            $error = 'Pengajuan izin/sakit untuk tanggal tersebut sudah pernah diajukan sebelumnya.';
        }
    }

    // Validasi file bukti
    $ada_file = isset($_FILES['bukti']) && $_FILES['bukti']['error'] === UPLOAD_ERR_OK;

    if ($error === '') {
        if ($jenis === 'sakit' && !$ada_file) {
            $error = 'Bukti surat dokter/keterangan sakit wajib diunggah untuk jenis sakit.';
        } elseif ($ada_file) {
            $file_size = $_FILES['bukti']['size'];
            $file_tmp  = $_FILES['bukti']['tmp_name'];
            $file_name = $_FILES['bukti']['name'];
            $ekstensi  = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

            $ekstensi_valid = ['jpg', 'jpeg', 'png', 'pdf'];
            $mime_valid     = ['image/jpeg', 'image/png', 'application/pdf'];

            if ($file_size > 2 * 1024 * 1024) {
                $error = 'Ukuran file bukti maksimal 2 MB.';
            } elseif (!in_array($ekstensi, $ekstensi_valid, true)) {
                $error = 'Format file bukti harus berupa JPG, PNG, atau PDF.';
            } else {
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mime_asli = finfo_file($finfo, $file_tmp);
                finfo_close($finfo);

                if (!in_array($mime_asli, $mime_valid, true)) {
                    $error = 'Tipe konten file tidak diizinkan. Hanya file gambar (JPG, PNG) atau dokumen PDF yang diperbolehkan.';
                } else {
                    $nama_file_tersimpan = bin2hex(random_bytes(8)) . '.' . $ekstensi;
                    $target_file_path    = '../assets/uploads/' . $nama_file_tersimpan;

                    if (!move_uploaded_file($file_tmp, $target_file_path)) {
                        $error = 'Gagal menyimpan file bukti ke server.';
                    }
                }
            }
        }
    }

    // Simpan ke database jika validasi lolos
    if ($error === '') {
        try {
            $stmt = $pdo->prepare(
                'INSERT INTO izin (user_id, tanggal, jenis, alasan, bukti, status)
                 VALUES (?, ?, ?, ?, ?, "menunggu")'
            );
            $stmt->execute([$user_id, $tanggal, $jenis, $alasan, $nama_file_tersimpan]);

            $_SESSION['flash'] = ['tipe' => 'success', 'teks' => 'Pengajuan berhasil dikirim dan menunggu persetujuan admin.'];
            header('Location: izin.php');
            exit;
        } catch (Exception $e) {
            // Hapus file yang sudah terlanjur diunggah jika query gagal
            if ($target_file_path && file_exists($target_file_path)) {
                unlink($target_file_path);
            }
            $_SESSION['flash'] = ['tipe' => 'danger', 'teks' => 'Terjadi kesalahan sistem saat menyimpan pengajuan.'];
            header('Location: izin.php');
            exit;
        }
    } else {
        // Hapus file jika ada error validasi lain
        if ($target_file_path && file_exists($target_file_path)) {
            unlink($target_file_path);
        }
        $_SESSION['flash'] = ['tipe' => 'danger', 'teks' => $error];
        header('Location: izin.php');
        exit;
    }
}

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

// Ambil riwayat pengajuan izin milik mahasiswa ini
$stmt = $pdo->prepare(
    'SELECT id, tanggal, jenis, alasan, bukti, status, created_at
     FROM izin
     WHERE user_id = ?
     ORDER BY tanggal DESC, id DESC'
);
$stmt->execute([$user_id]);
$riwayat_izin = $stmt->fetchAll();

$judul_halaman = 'Pengajuan Izin & Sakit';
require_once '../includes/header.php';
?>

<h4 class="mb-3">Pengajuan Izin dan Sakit</h4>

<?php if ($flash): ?>
    <div class="alert alert-<?= htmlspecialchars($flash['tipe']) ?> alert-dismissible fade show" role="alert">
        <?= htmlspecialchars($flash['teks']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="row mb-4">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header bg-light fw-semibold">
                Formulir Pengajuan
            </div>
            <div class="card-body">
                <form method="post" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label for="tanggal" class="form-label small fw-semibold">Tanggal</label>
                        <input type="date" id="tanggal" name="tanggal" class="form-control form-control-sm"
                               min="<?= htmlspecialchars($hari_ini) ?>" required>
                        <div class="form-text">Hanya hari kerja (Senin - Jumat) mulai hari ini.</div>
                    </div>

                    <div class="mb-3">
                        <label for="jenis" class="form-label small fw-semibold">Jenis Pengajuan</label>
                        <select id="jenis" name="jenis" class="form-select form-select-sm" required>
                            <option value="">Pilih Jenis</option>
                            <option value="izin">Izin</option>
                            <option value="sakit">Sakit (Wajib Surat Dokter)</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="alasan" class="form-label small fw-semibold">Alasan</label>
                        <textarea id="alasan" name="alasan" rows="3" class="form-control form-control-sm"
                                  placeholder="Tuliskan alasan pengajuan secara jelas..." required></textarea>
                    </div>

                    <div class="mb-3">
                        <label for="bukti" class="form-label small fw-semibold">Bukti Pendukung <span class="text-muted fw-normal">(Wajib untuk sakit)</span></label>
                        <input type="file" id="bukti" name="bukti" class="form-control form-control-sm"
                               accept=".jpg,.jpeg,.png,.pdf">
                        <div class="form-text">Format: JPG, PNG, atau PDF (maksimal 2 MB).</div>
                    </div>

                    <button type="submit" class="btn btn-primary btn-sm">Kirim Pengajuan</button>
                </form>
            </div>
        </div>
    </div>
</div>

<h5 class="mb-3">Riwayat Pengajuan</h5>

<?php if (empty($riwayat_izin)): ?>
    <div class="alert alert-info">Belum ada pengajuan izin atau sakit.</div>
<?php else: ?>
    <div class="table-responsive">
        <table class="table table-bordered table-hover table-sm align-middle">
            <thead class="table-light">
                <tr>
                    <th style="width: 40px;" class="text-center">No</th>
                    <th>Tanggal</th>
                    <th>Jenis</th>
                    <th>Alasan</th>
                    <th>Bukti</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($riwayat_izin as $idx => $row):
                $badge_status = [
                    'menunggu'  => 'warning text-dark',
                    'disetujui' => 'success',
                    'ditolak'   => 'danger',
                ];
                $kelas_badge = $badge_status[$row['status']] ?? 'secondary';
            ?>
                <tr>
                    <td class="text-center"><?= $idx + 1 ?></td>
                    <td><?= format_tgl_indo($row['tanggal'], $nama_bulan, $nama_hari) ?></td>
                    <td class="text-capitalize"><?= htmlspecialchars($row['jenis']) ?></td>
                    <td><?= htmlspecialchars($row['alasan']) ?></td>
                    <td>
                        <?php if (!empty($row['bukti'])): ?>
                            <a href="../assets/uploads/<?= htmlspecialchars($row['bukti']) ?>" target="_blank" class="btn btn-outline-secondary btn-sm py-0">
                                Lihat Bukti
                            </a>
                        <?php else: ?>
                            <span class="text-muted">-</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <span class="badge bg-<?= $kelas_badge ?>">
                            <?= ucfirst($row['status']) ?>
                        </span>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<?php require_once '../includes/footer.php'; ?>