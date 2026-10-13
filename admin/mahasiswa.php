<?php
require_once '../includes/cek_login.php';
cek_login('admin');
require_once '../config/koneksi.php';

// Aksi POST: aktif/nonaktif atau hapus
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $aksi = $_POST['aksi'] ?? '';
    $id   = (int) ($_POST['id'] ?? 0);

    if ($id > 0 && $aksi === 'toggle_status') {
        $stmt = $pdo->prepare('SELECT status FROM users WHERE id = ? AND role = ?');
        $stmt->execute([$id, 'mahasiswa']);
        $user = $stmt->fetch();
        if ($user) {
            $status_baru = ($user['status'] === 'aktif') ? 'nonaktif' : 'aktif';
            $pdo->prepare('UPDATE users SET status = ? WHERE id = ?')
                ->execute([$status_baru, $id]);
            $_SESSION['flash'] = ['tipe' => 'success', 'teks' => 'Status mahasiswa berhasil diubah.'];
        }
    } elseif ($id > 0 && $aksi === 'hapus') {
        // FK CASCADE akan hapus baris mahasiswa, presensi, dan izin
        $stmt = $pdo->prepare('DELETE FROM users WHERE id = ? AND role = ?');
        $stmt->execute([$id, 'mahasiswa']);
        $_SESSION['flash'] = ['tipe' => 'success', 'teks' => 'Data mahasiswa berhasil dihapus.'];
    } else {
        $_SESSION['flash'] = ['tipe' => 'danger', 'teks' => 'Aksi tidak valid.'];
    }

    header('Location: mahasiswa.php');
    exit;
}

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

// Filter pencarian
$cari = trim($_GET['cari'] ?? '');
$param_cari = '%' . $cari . '%';

$stmt = $pdo->prepare(
    'SELECT u.id, u.nama, u.username, u.status,
            m.nim, m.kampus, m.jurusan, m.tgl_mulai, m.tgl_selesai
     FROM users u
     JOIN mahasiswa m ON m.user_id = u.id
     WHERE u.role = ?
       AND (u.nama LIKE ? OR m.nim LIKE ?)
     ORDER BY u.nama'
);
$stmt->execute(['mahasiswa', $param_cari, $param_cari]);
$daftar = $stmt->fetchAll();

$judul_halaman = 'Kelola Data Mahasiswa';
require_once '../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">Data Mahasiswa</h4>
    <a href="mahasiswa_form.php" class="btn btn-primary btn-sm">Tambah Mahasiswa</a>
</div>

<?php if ($flash): ?>
    <div class="alert alert-<?= htmlspecialchars($flash['tipe']) ?> alert-dismissible fade show" role="alert">
        <?= htmlspecialchars($flash['teks']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<form method="get" class="row g-2 mb-3">
    <div class="col-auto">
        <input type="text" name="cari" class="form-control form-control-sm"
               placeholder="Cari nama atau NIM..."
               value="<?= htmlspecialchars($cari) ?>">
    </div>
    <div class="col-auto">
        <button type="submit" class="btn btn-secondary btn-sm">Cari</button>
        <?php if ($cari !== ''): ?>
            <a href="mahasiswa.php" class="btn btn-outline-secondary btn-sm">Reset</a>
        <?php endif; ?>
    </div>
</form>

<?php if (empty($daftar)): ?>
    <div class="alert alert-info">Tidak ada data mahasiswa<?= $cari !== '' ? ' yang cocok.' : '.' ?></div>
<?php else: ?>
<div class="table-responsive">
    <table class="table table-bordered table-hover table-sm align-middle">
        <thead class="table-light">
            <tr>
                <th>Nama</th>
                <th>Username</th>
                <th>NIM</th>
                <th>Kampus / Jurusan</th>
                <th>Periode Magang</th>
                <th>Status</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($daftar as $mhs): ?>
            <tr>
                <td><?= htmlspecialchars($mhs['nama']) ?></td>
                <td><?= htmlspecialchars($mhs['username']) ?></td>
                <td><?= htmlspecialchars($mhs['nim']) ?></td>
                <td>
                    <?= htmlspecialchars($mhs['kampus']) ?><br>
                    <small class="text-muted"><?= htmlspecialchars($mhs['jurusan']) ?></small>
                </td>
                <td class="text-nowrap">
                    <?= htmlspecialchars($mhs['tgl_mulai']) ?> s/d<br>
                    <?= htmlspecialchars($mhs['tgl_selesai']) ?>
                </td>
                <td>
                    <?php if ($mhs['status'] === 'aktif'): ?>
                        <span class="badge bg-success">Aktif</span>
                    <?php else: ?>
                        <span class="badge bg-secondary">Nonaktif</span>
                    <?php endif; ?>
                </td>
                <td class="text-nowrap">
                    <a href="mahasiswa_form.php?id=<?= $mhs['id'] ?>"
                       class="btn btn-warning btn-sm">Ubah</a>

                    <form method="post" class="d-inline">
                        <input type="hidden" name="aksi" value="toggle_status">
                        <input type="hidden" name="id" value="<?= $mhs['id'] ?>">
                        <button type="submit" class="btn btn-outline-secondary btn-sm">
                            <?= $mhs['status'] === 'aktif' ? 'Nonaktifkan' : 'Aktifkan' ?>
                        </button>
                    </form>

                    <form method="post" class="d-inline"
                          onsubmit="return confirm('Hapus <?= htmlspecialchars(addslashes($mhs['nama']), ENT_QUOTES) ?>? Seluruh data presensi dan izin mahasiswa ini ikut terhapus.')">
                        <input type="hidden" name="aksi" value="hapus">
                        <input type="hidden" name="id" value="<?= $mhs['id'] ?>">
                        <button type="submit" class="btn btn-danger btn-sm">Hapus</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<?php require_once '../includes/footer.php'; ?>