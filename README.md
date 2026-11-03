# Sistem Presensi Mahasiswa Magang BPS Kota Medan

Aplikasi web (PHP native + MySQL) untuk laporan PKL "Perancangan Sistem Presensi Mahasiswa Magang di BPS Kota Medan". Presensi memakai validasi lokasi (Geolocation API, radius default 100 m) dan selfie dari kamera (WebRTC).

## Cara instal (XAMPP / Laragon)
1. Extract folder `presensi-bps` ke `htdocs` (XAMPP) atau `www` (Laragon).
2. Jalankan Apache dan MySQL, buka phpMyAdmin, lalu **Import** file `database.sql`. Database `presensi_bps` terbuat otomatis.
3. Jika user/password MySQL berbeda, ubah `config/config.php`.
4. Buka `http://localhost/presensi-bps/`.

Kebutuhan: PHP 7.4 atau lebih baru (8.x disarankan) dengan ekstensi `pdo_mysql`. Koneksi internet dipakai untuk font dan peta (Leaflet/OpenStreetMap).

## Akun contoh
| Role | Username | Password |
|---|---|---|
| Admin (pengawas) | admin | admin123 |
| Mahasiswa | rafif, dina, aldo | magang123 |

Segera ganti password setelah instalasi (menu Pengaturan untuk admin, menu Profil untuk mahasiswa).

## Langkah pertama setelah login admin
1. Menu **Pengaturan**: klik peta atau tombol "gunakan lokasi saya" untuk menandai titik kantor BPS Kota Medan, atur radius dan jam masuk/terlambat. Koordinat bawaan hanyalah titik contoh di Medan.
2. Menu **Data Mahasiswa**: tambah akun mahasiswa magang (password awal `magang123`).

## Logo BPS Kota Medan
Letakkan file logo di `assets/img/logo-bps.png`. Jika file itu ada, otomatis dipakai menggantikan logo placeholder `logo-bps.svg`.

## Catatan penting
- Kamera dan GPS di browser hanya jalan di `localhost` atau HTTPS. Untuk uji dari HP lewat IP jaringan (http://192.168...), browser akan memblokirnya; gunakan HTTPS (mis. tunnel ngrok/Cloudflare) atau uji di laptop.
- Lokasi divalidasi dua kali: di browser (supaya tombol aktif) dan di server PHP dengan rumus Haversine (supaya tidak bisa dimanipulasi).
- Foto disimpan di `uploads/selfie/`; pastikan folder `uploads` bisa ditulis. Folder ini sudah dilindungi `.htaccess` agar skrip tidak bisa dieksekusi.
- Perhitungan "Alpa" memakai hari kerja Senin-Jumat dan belum mengecualikan hari libur nasional.
- Selfie hanya bisa diambil dari kamera langsung; tidak ada upload dari galeri.

## Struktur folder
```
config/      pengaturan database
includes/    fungsi bersama dan layout
admin/       halaman pengawas (dashboard, mahasiswa, rekap, detail, peta, izin, kegiatan, laporan, pengaturan)
user/        halaman mahasiswa (beranda, presensi, riwayat, kegiatan, izin, profil)
api/         endpoint presensi (Clock In/Out)
assets/      css, js, gambar
uploads/     foto selfie dan bukti izin
dokumentasi/ DFD, ERD, struktur tabel, tabel Black Box Testing
database.sql
```
