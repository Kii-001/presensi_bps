<?php
require_once '../includes/cek_login.php';
cek_login('mahasiswa');
require_once '../config/koneksi.php';

$user_id = $_SESSION['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pass_lama   = $_POST['pass_lama']    ?? '';
    $pass_baru   = $_POST['pass_baru']    ?? '';
    $konfirmasi  = $_POST['konfirmasi']   ?? '';

    // Ambil hash saat ini
    $stmt = $pdo->prepare('SELECT password FROM users WHERE id = ?');
    $stmt->execute([$user_id]);
    $row = $stmt->fetch();

    if (!$row || !password_verify($pass_lama, $row['password'])) {
        $_SESSION['flash'] = ['tipe' => 'danger', 'teks' => 'Password lama tidak cocok.'];
    } elseif (strlen($pass_baru) < 6) {
        $_SESSION['flash'] = ['tipe' => 'danger', 'teks' => 'Password baru minimal 6 karakter.'];
    } elseif ($pass_baru !== $konfirmasi) {
        $_SESSION['flash'] = ['tipe' => 'danger', 'teks' => 'Konfirmasi password baru tidak cocok.'];
    } elseif (password_verify($pass_baru, $row['password'])) {
        $_SESSION['flash'] = ['tipe' => 'warning', 'teks' => 'Password baru tidak boleh sama dengan password lama.'];
    } else {
        $hash = password_hash($pass_baru, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare('UPDATE users SET password = ? WHERE id = ?');
        $stmt->execute([$hash, $user_id]);
        $_SESSION['flash'] = ['tipe' => 'success', 'teks' => 'Password berhasil diubah.'];
    }

    header('Location: ganti_password.php');
    exit;
}

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

$judul_halaman = 'Ganti Password';
require_once '../includes/header.php';
?>

<h4 class="mb-4">Ganti Password</h4>

<?php if ($flash): ?>
    <div class="alert alert-<?= htmlspecialchars($flash['tipe']) ?> alert-dismissible fade show" role="alert">
        <?= htmlspecialchars($flash['teks']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="row">
    <div class="col-md-5">
        <form method="post">
            <div class="mb-3">
                <label for="pass_lama" class="form-label">Password Lama</label>
                <input type="password" id="pass_lama" name="pass_lama" class="form-control" required>
            </div>
            <div class="mb-3">
                <label for="pass_baru" class="form-label">Password Baru <span class="text-muted small">(min. 6 karakter)</span></label>
                <input type="password" id="pass_baru" name="pass_baru" class="form-control" required minlength="6">
            </div>
            <div class="mb-4">
                <label for="konfirmasi" class="form-label">Konfirmasi Password Baru</label>
                <input type="password" id="konfirmasi" name="konfirmasi" class="form-control" required minlength="6">
            </div>
            <button type="submit" class="btn btn-primary">Simpan</button>
        </form>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>