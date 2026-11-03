# Black Box Testing

Kolom **Hasil aktual** dan **Status** dikosongkan agar kamu isi sendiri setelah mencoba aplikasi di browser (kamera dan GPS hanya bisa diuji langsung di perangkat). Isi dengan "Lulus" atau "Tidak lulus".

Kantor diasumsikan di titik yang diatur pada menu Pengaturan, radius 100 meter.

## Tabel 1. Pengujian Login dan Hak Akses
| No | Skenario | Masukan | Keluaran yang diharapkan | Hasil aktual | Status |
|---|---|---|---|---|---|
| 1 | Login admin dengan data benar | admin / admin123 | Masuk ke Dashboard admin | | |
| 2 | Login mahasiswa dengan data benar | rafif / magang123 | Masuk ke Beranda mahasiswa | | |
| 3 | Login dengan password salah | username benar, password salah | Pesan "Username atau password salah" | | |
| 4 | Login 5 kali gagal beruntun | password salah 5 kali | Login dikunci sementara 30 detik | | |
| 5 | Akun nonaktif | akun yang dinonaktifkan admin | Login ditolak | | |
| 6 | Mahasiswa membuka halaman admin | URL /admin/dashboard.php | Dialihkan ke Beranda mahasiswa | | |
| 7 | Membuka halaman tanpa login | URL /user/dashboard.php | Dialihkan ke halaman login | | |

## Tabel 2. Pengujian Validasi Lokasi
| No | Skenario | Masukan | Keluaran yang diharapkan | Hasil aktual | Status |
|---|---|---|---|---|---|
| 1 | Berada dalam radius 100 m | Lokasi di kantor | Pesan "Lokasi sesuai", jarak tampil, centang lokasi aktif | | |
| 2 | Berada di luar radius | Lokasi di luar kantor (>100 m) | Pesan "Lokasi tidak sesuai", tombol presensi nonaktif | | |
| 3 | Izin lokasi ditolak | Tolak izin lokasi browser | Pesan izin lokasi ditolak, tombol nonaktif | | |
| 4 | Koordinat dimanipulasi ke server | Kirim koordinat di luar radius ke API | Server menolak: "Lokasi di luar radius presensi" | | |

## Tabel 3. Pengujian Pengambilan Foto Selfie
| No | Skenario | Masukan | Keluaran yang diharapkan | Hasil aktual | Status |
|---|---|---|---|---|---|
| 1 | Foto berhasil diambil | Klik "Ambil foto" | Pratinjau foto tampil, centang selfie aktif | | |
| 2 | Foto ulang | Klik "Foto ulang" | Kamera tampil kembali, tombol presensi nonaktif | | |
| 3 | Kamera tidak diizinkan | Tolak izin kamera | Pesan gagal mengakses kamera, tombol nonaktif | | |
| 4 | Kirim tanpa foto ke server | Kirim form tanpa foto | Server menolak: "Foto selfie wajib diambil" | | |
| 5 | Kirim data foto tidak valid | Data base64 rusak | Server menolak, data tidak tersimpan | | |

## Tabel 4. Pengujian Penyimpanan Presensi
| No | Skenario | Masukan | Keluaran yang diharapkan | Hasil aktual | Status |
|---|---|---|---|---|---|
| 1 | Clock In lengkap | Lokasi valid + selfie | Data tersimpan, status Hadir/Terlambat sesuai jam | | |
| 2 | Clock In setelah batas terlambat | Presensi setelah jam batas terlambat | Status Terlambat | | |
| 3 | Clock In dua kali | Clock In kedua di hari yang sama | Ditolak: "Anda sudah Clock In hari ini" | | |
| 4 | Clock Out sebelum Clock In | Clock Out tanpa Clock In | Ditolak: "Anda belum Clock In hari ini" | | |
| 5 | Clock Out lengkap | Lokasi valid + selfie | Jam pulang, jarak, dan foto tersimpan | | |
| 6 | Clock Out dua kali | Clock Out kedua | Ditolak: "Anda sudah Clock Out hari ini" | | |

## Tabel 5. Pengujian Fitur Admin
| No | Skenario | Masukan | Keluaran yang diharapkan | Hasil aktual | Status |
|---|---|---|---|---|---|
| 1 | Tambah mahasiswa | Form data mahasiswa | Akun baru muncul di daftar, password awal magang123 | | |
| 2 | Filter rekap presensi | Nama dan rentang tanggal | Tabel hanya menampilkan data sesuai filter | | |
| 3 | Detail presensi | Klik "Detail" | Foto, koordinat, jarak, dan peta tampil | | |
| 4 | Setujui izin/sakit | Klik "Setujui" | Status disetujui, hari tercatat izin/sakit di rekap | | |
| 5 | Ubah radius dan jam | Menu Pengaturan | Nilai baru dipakai pada presensi berikutnya | | |
| 6 | Cetak dan export | Tombol Cetak / Export CSV | Laporan tercetak / file CSV terunduh | | |
