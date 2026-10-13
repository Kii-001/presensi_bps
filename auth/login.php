<?php
session_start();

// Sudah login, arahkan ke dashboard sesuai role
if (isset($_SESSION['id'])) {
    $tujuan = ($_SESSION['role'] === 'admin') ? '../admin/dashboard.php' : '../mahasiswa/dashboard.php';
    header('Location: ' . $tujuan);
    exit;
}

require_once '../config/koneksi.php';

$pesan = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username !== '' && $password !== '') {
        $stmt = $pdo->prepare('SELECT id, nama, password, role, status FROM users WHERE username = ?');
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && $user['status'] === 'aktif' && password_verify($password, $user['password'])) {
            session_regenerate_id(true);
            $_SESSION['id']   = $user['id'];
            $_SESSION['nama'] = $user['nama'];
            $_SESSION['role'] = $user['role'];

            $tujuan = ($user['role'] === 'admin') ? '../admin/dashboard.php' : '../mahasiswa/dashboard.php';
            header('Location: ' . $tujuan);
            exit;
        }
    }

    // Satu pesan untuk semua kasus gagal (salah data, nonaktif, kosong)
    $pesan = 'Username atau password salah.';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login - Presensi Magang BPS Kota Medan</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <style>
        body { background: #f0f2f5; }
        .card-login { max-width: 420px; margin: 100px auto; }
    </style>
</head>
<body>
<div class="card card-login shadow">
    <div class="card-body p-4">
        <h5 class="card-title text-center mb-1">Presensi Magang</h5>
        <p class="text-center text-muted small mb-4">BPS Kota Medan</p>

        <?php if ($pesan !== ''): ?>
            <div class="alert alert-danger py-2"><?= htmlspecialchars($pesan) ?></div>
        <?php endif; ?>

        <form method="post">
            <div class="mb-3">
                <label for="username" class="form-label">Username</label>
                <input type="text" id="username" name="username" class="form-control"
                       value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required autofocus>
            </div>
            <div class="mb-4">
                <label for="password" class="form-label">Password</label>
                <input type="password" id="password" name="password" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-primary w-100">Masuk</button>
        </form>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
