<?php
require_once '../includes/cek_login.php';
cek_login('admin');
require_once '../config/koneksi.php';

$id_ubah = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$mode    = ($id_ubah > 0) ? 'ubah' : 'tambah';

// Untuk mode ubah: ambil data lama, pastikan role mahasiswa
$data_lama = null;
$data_mhs_lama = null;
if ($mode === 'ubah') {
    $stmt = $pdo->prepare('SELECT id, nama, username, status FROM users WHERE id = ? AND role = ?');
    $stmt->execute([$id_ubah, 'mahasiswa']);
    $data_lama = $stmt->fetch();
    if (!$data_lama) {
        $_SESSION['flash'] = ['tipe' => 'danger', 'teks' => 'Data mahasiswa tidak ditemukan.'];
        header('Location: mahasiswa.php');
        exit;
    }
    $stmt2 = $pdo->prepare('SELECT nim, kampus, jurusan, tgl_mulai, tgl_selesai, no_hp FROM mahasiswa WHERE user_id = ?');
    $stmt2->execute([$id_ubah]);
    $data_mhs_lama = $stmt2->fetch();
}

// Nilai awal form (dari POST saat validasi gagal, atau dari data lama)
$form = [
    'nama'        => $_POST['nama']        ?? ($data_lama['nama']              ?? ''),
    'username'    => $_POST['username']    ?? ($data_lama['username']          ?? ''),
    'nim'         => $_POST['nim']         ?? ($data_mhs_lama['nim']           ?? ''),
    'kampus'      => $_POST['kampus']      ?? ($data_mhs_lama['kampus']        ?? ''),
    'jurusan'     => $_POST['jurusan']     ?? ($data_mhs_lama['jurusan']       ?? ''),
    'tgl_mulai'   => $_POST['tgl_mulai']   ?? ($data_mhs_lama['tgl_mulai']    ?? ''),
    'tgl_selesai' => $_POST['tgl_selesai'] ?? ($data_mhs_lama['tgl_selesai']  ?? ''),
    'no_hp'       => $_POST['no_hp']       ?? ($data_mhs_lama['no_hp']        ?? ''),
];

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Ambil input
    $nama        = trim($_POST['nama']        ?? '');
    $username    = trim($_POST['username']    ?? '');
    $password    = $_POST['password']         ?? '';
    $nim         = trim($_POST['nim']         ?? '');
    $kampus      = trim($_POST['kampus']      ?? '');
    $jurusan     = trim($_POST['jurusan']     ?? '');
    $tgl_mulai   = trim($_POST['tgl_mulai']   ?? '');
    $tgl_selesai = trim($_POST['tgl_selesai'] ?? '');
    $no_hp       = trim($_POST['no_hp']       ?? '');

    // Perbarui nilai form agar isian tetap ada jika validasi gagal
    $form = compact('nama', 'username', 'nim', 'kampus', 'jurusan', 'tgl_mulai', 'tgl_selesai', 'no_hp');

    // Validasi
    if ($nama === '' || $username === '' || $nim === '' || $kampus === '' ||
        $jurusan === '' || $tgl_mulai === '' || $tgl_selesai === '') {
        $error = 'Semua kolom wajib diisi kecuali No HP.';
    } elseif (!preg_match('/^[a-zA-Z0-9._]{4,50}$/', $username)) {
        $error = 'Username hanya boleh huruf, angka, titik, garis bawah; panjang 4-50 karakter.';
    } elseif ($mode === 'tambah' && strlen($password) < 6) {
        $error = 'Password minimal 6 karakter.';
    } elseif ($mode === 'ubah' && $password !== '' && strlen($password) < 6) {
        $error = 'Password minimal 6 karakter (kosongkan jika tidak ingin diubah).';
    } elseif ($tgl_selesai < $tgl_mulai) {
        $error = 'Tanggal selesai tidak boleh lebih awal dari tanggal mulai.';
    }

    if ($error === '') {
        try {
            $pdo->beginTransaction();

            if ($mode === 'tambah') {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare(
                    'INSERT INTO users (username, password, nama, role, status) VALUES (?, ?, ?, ?, ?)'
                );
                $stmt->execute([$username, $hash, $nama, 'mahasiswa', 'aktif']);
                $user_id_baru = (int) $pdo->lastInsertId();

                $stmt2 = $pdo->prepare(
                    'INSERT INTO mahasiswa (user_id, nim, kampus, jurusan, tgl_mulai, tgl_selesai, no_hp)
                     VALUES (?, ?, ?, ?, ?, ?, ?)'
                );
                $stmt2->execute([$user_id_baru, $nim, $kampus, $jurusan, $tgl_mulai, $tgl_selesai, $no_hp ?: null]);
            } else {
                // Ubah users
                if ($password !== '') {
                    $hash = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare(
                        'UPDATE users SET nama = ?, username = ?, password = ? WHERE id = ?'
                    );
                    $stmt->execute([$nama, $username, $hash, $id_ubah]);
                } else {
                    $stmt = $pdo->prepare('UPDATE users SET nama = ?, username = ? WHERE id = ?');
                    $stmt->execute([$nama, $username, $id_ubah]);
                }

                // Ubah mahasiswa
                $stmt2 = $pdo->prepare(
                    'UPDATE mahasiswa SET nim = ?, kampus = ?, jurusan = ?, tgl_mulai = ?, tgl_selesai = ?, no_hp = ?
                     WHERE user_id = ?'
                );
                $stmt2->execute([$nim, $kampus, $jurusan, $tgl_mulai, $tgl_selesai, $no_hp ?: null, $id_ubah]);
            }

            $pdo->commit();
            $label = ($mode === 'tambah') ? 'ditambahkan' : 'diperbarui';
            $_SESSION['flash'] = ['tipe' => 'success', 'teks' => "Data mahasiswa berhasil $label."];
            header('Location: mahasiswa.php');
            exit;

        } catch (PDOException $e) {
            $pdo->rollBack();
            if ($e->getCode() === '23000') {
                $error = 'Username sudah dipakai, pilih username lain.';
            } else {
                $error = 'Terjadi kesalahan, coba lagi.';
            }
        }
    }
}

$judul_halaman = ($mode === 'tambah') ? 'Tambah Mahasiswa' : 'Ubah Data Mahasiswa';
require_once '../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0"><?= htmlspecialchars($judul_halaman) ?></h4>
    <a href="mahasiswa.php" class="btn btn-secondary btn-sm">Kembali</a>
</div>

<?php if ($error !== ''): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div class="row">
    <div class="col-md-7">
        <form method="post">
            <fieldset class="mb-3">
                <legend class="h6">Akun</legend>
                <div class="mb-3">
                    <label for="nama" class="form-label">Nama Lengkap</label>
                    <input type="text" id="nama" name="nama" class="form-control"
                           value="<?= htmlspecialchars($form['nama']) ?>" required>
                </div>
                <div class="mb-3">
                    <label for="username" class="form-label">Username</label>
                    <input type="text" id="username" name="username" class="form-control"
                           value="<?= htmlspecialchars($form['username']) ?>"
                           pattern="[a-zA-Z0-9._]{4,50}" required>
                    <div class="form-text">Huruf, angka, titik, garis bawah; 4-50 karakter.</div>
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" id="password" name="password"
                           class="form-control" <?= $mode === 'tambah' ? 'required minlength="6"' : '' ?>>
                    <?php if ($mode === 'ubah'): ?>
                        <div class="form-text">Kosongkan jika tidak ingin mengubah password.</div>
                    <?php else: ?>
                        <div class="form-text">Minimal 6 karakter.</div>
                    <?php endif; ?>
                </div>
            </fieldset>

            <fieldset class="mb-3">
                <legend class="h6">Data Magang</legend>
                <div class="mb-3">
                    <label for="nim" class="form-label">NIM</label>
                    <input type="text" id="nim" name="nim" class="form-control"
                           value="<?= htmlspecialchars($form['nim']) ?>" required>
                </div>
                <div class="mb-3">
                    <label for="kampus" class="form-label">Kampus</label>
                    <input type="text" id="kampus" name="kampus" class="form-control"
                           value="<?= htmlspecialchars($form['kampus']) ?>" required>
                </div>
                <div class="mb-3">
                    <label for="jurusan" class="form-label">Jurusan</label>
                    <input type="text" id="jurusan" name="jurusan" class="form-control"
                           value="<?= htmlspecialchars($form['jurusan']) ?>" required>
                </div>
                <div class="row g-3 mb-3">
                    <div class="col-sm-6">
                        <label for="tgl_mulai" class="form-label">Tanggal Mulai</label>
                        <input type="date" id="tgl_mulai" name="tgl_mulai" class="form-control"
                               value="<?= htmlspecialchars($form['tgl_mulai']) ?>" required>
                    </div>
                    <div class="col-sm-6">
                        <label for="tgl_selesai" class="form-label">Tanggal Selesai</label>
                        <input type="date" id="tgl_selesai" name="tgl_selesai" class="form-control"
                               value="<?= htmlspecialchars($form['tgl_selesai']) ?>" required>
                    </div>
                </div>
                <div class="mb-3">
                    <label for="no_hp" class="form-label">No HP <span class="text-muted small">(opsional)</span></label>
                    <input type="text" id="no_hp" name="no_hp" class="form-control"
                           value="<?= htmlspecialchars($form['no_hp']) ?>">
                </div>
            </fieldset>

            <button type="submit" class="btn btn-primary">
                <?= $mode === 'tambah' ? 'Simpan' : 'Perbarui' ?>
            </button>
        </form>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>