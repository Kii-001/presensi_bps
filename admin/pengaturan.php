<?php
require_once '../includes/cek_login.php';
cek_login('admin');
require_once '../config/koneksi.php';

// Ambil baris pengaturan dengan ID terkecil
$stmt = $pdo->query('SELECT id, jam_masuk, jam_pulang, toleransi_menit FROM pengaturan ORDER BY id ASC LIMIT 1');
$pengaturan = $stmt->fetch();

if (!$pengaturan) {
    // Buat data default jika belum ada baris di database
    $pdo->exec("INSERT INTO pengaturan (jam_masuk, jam_pulang, toleransi_menit) VALUES ('08:00:00', '16:00:00', 15)");
    $pengaturan = [
        'id'              => $pdo->lastInsertId(),
        'jam_masuk'       => '08:00:00',
        'jam_pulang'      => '16:00:00',
        'toleransi_menit' => 15,
    ];
}

// Proses form POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $jam_masuk_input  = trim($_POST['jam_masuk']  ?? '');
    $jam_pulang_input = trim($_POST['jam_pulang'] ?? '');
    $toleransi_input  = $_POST['toleransi_menit']  ?? '';

    // Normalisasi format jam HH:MM ke HH:MM:00
    if (preg_match('/^([01][0-9]|2[0-3]):[0-5][0-9]$/', $jam_masuk_input)) {
        $jam_masuk_input .= ':00';
    }
    if (preg_match('/^([01][0-9]|2[0-3]):[0-5][0-9]$/', $jam_pulang_input)) {
        $jam_pulang_input .= ':00';
    }

    $format_jam_valid = '/^([01][0-9]|2[0-3]):[0-5][0-9]:[0-5][0-9]$/';

    $error = '';
    if (!preg_match($format_jam_valid, $jam_masuk_input)) {
        $error = 'Format jam masuk tidak valid.';
    } elseif (!preg_match($format_jam_valid, $jam_pulang_input)) {
        $error = 'Format jam pulang tidak valid.';
    } elseif ($jam_pulang_input <= $jam_masuk_input) {
        $error = 'Jam pulang harus lebih besar dari jam masuk.';
    } elseif (!is_numeric($toleransi_input) || (int) $toleransi_input != $toleransi_input) {
        $error = 'Toleransi keterlambatan harus berupa bilangan bulat.';
    } else {
        $toleransi_val = (int) $toleransi_input;
        if ($toleransi_val < 0 || $toleransi_val > 120) {
            $error = 'Toleransi keterlambatan harus berada di antara 0 sampai 120 menit.';
        }
    }

    if ($error === '') {
        $stmt_update = $pdo->prepare(
            'UPDATE pengaturan
             SET jam_masuk = ?, jam_pulang = ?, toleransi_menit = ?
             WHERE id = ?'
        );
        $stmt_update->execute([
            $jam_masuk_input,
            $jam_pulang_input,
            (int) $toleransi_input,
            $pengaturan['id']
        ]);

        $_SESSION['flash'] = [
            'tipe' => 'success',
            'teks' => 'Pengaturan jam kerja berhasil diperbarui.'
        ];
    } else {
        $_SESSION['flash'] = [
            'tipe' => 'danger',
            'teks' => $error
        ];
    }

    header('Location: pengaturan.php');
    exit;
}

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

$judul_halaman = 'Pengaturan Jam Kerja';
require_once '../includes/header.php';
?>

<h4 class="mb-3">Pengaturan Jam Kerja</h4>

<?php if ($flash): ?>
    <div class="alert alert-<?= htmlspecialchars($flash['tipe']) ?> alert-dismissible fade show" role="alert">
        <?= htmlspecialchars($flash['teks']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="row">
    <div class="col-md-6 col-lg-5">
        <div class="card mb-3">
            <div class="card-body">
                <form method="post">
                    <div class="mb-3">
                        <label for="jam_masuk" class="form-label small fw-semibold">Jam Masuk</label>
                        <input type="time" step="1" id="jam_masuk" name="jam_masuk" class="form-control"
                               value="<?= htmlspecialchars($pengaturan['jam_masuk']) ?>" required>
                    </div>

                    <div class="mb-3">
                        <label for="jam_pulang" class="form-label small fw-semibold">Jam Pulang</label>
                        <input type="time" step="1" id="jam_pulang" name="jam_pulang" class="form-control"
                               value="<?= htmlspecialchars($pengaturan['jam_pulang']) ?>" required>
                    </div>

                    <div class="mb-3">
                        <label for="toleransi_menit" class="form-label small fw-semibold">Toleransi Keterlambatan (menit)</label>
                        <input type="number" id="toleransi_menit" name="toleransi_menit" class="form-control"
                               min="0" max="120" value="<?= (int) $pengaturan['toleransi_menit'] ?>" required>
                        <div class="form-text">Batas toleransi antara 0 sampai 120 menit.</div>
                    </div>

                    <button type="submit" class="btn btn-primary">Simpan Pengaturan</button>
                </form>
            </div>
        </div>

        <div class="alert alert-info py-2 small" role="alert">
            <strong>Catatan:</strong> Perubahan hanya berlaku untuk presensi berikutnya, status presensi yang sudah tersimpan tidak berubah.
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>