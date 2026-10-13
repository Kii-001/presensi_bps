<?php
// Panggil: cek_login('admin') atau cek_login('mahasiswa')
function cek_login(string $role_diizinkan): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // Belum login
    if (empty($_SESSION['id'])) {
        header('Location: ../auth/login.php');
        exit;
    }

    // Role tidak sesuai, arahkan ke dashboard miliknya
    if ($_SESSION['role'] !== $role_diizinkan) {
        header('Location: ../' . $_SESSION['role'] . '/dashboard.php');
        exit;
    }
}

