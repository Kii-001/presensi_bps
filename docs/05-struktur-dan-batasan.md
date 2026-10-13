# Dokumentasi Arsitektur Berkas, Teknologi, dan Batasan Sistem

Dokumen ini memaparkan struktur arsitektur direktori proyek, pemetaan tanggung jawab berkas kode sumber, tumpukan teknologi (*technology stack*) yang digunakan, serta evaluasi komprehensif mengenai batasan sistem saat ini berdasarkan audit kode sumber secara riil.

---

## 1. Struktur Pohon Berkas Proyek

Berikut adalah representasi hierarki direktori dan berkas pada repositori `presensi-bps`:

```
presensi-bps/
├── admin/
│   ├── dashboard.php
│   ├── izin.php
│   ├── mahasiswa.php
│   ├── mahasiswa_form.php
│   ├── pengaturan.php
│   ├── rekap.php
│   └── rekap_cetak.php
├── assets/
│   ├── css/
│   │   └── .gitkeep
│   └── uploads/
│       ├── .gitkeep
│       └── .htaccess
├── auth/
│   ├── login.php
│   └── logout.php
├── config/
│   └── koneksi.php
├── docs/
│   ├── 01-use-case.md
│   ├── 02-erd.md
│   ├── 03-activity-flowchart.md
│   ├── 04-pengujian-black-box.md
│   ├── 05-struktur-dan-batasan.md
│   └── README.md
├── includes/
│   ├── cek_login.php
│   ├── footer.php
│   ├── fungsi.php
│   ├── header.php
│   └── rekap_hitung.php
├── mahasiswa/
│   ├── dashboard.php
│   ├── ganti_password.php
│   ├── izin.php
│   ├── presensi.php
│   └── riwayat.php
├── AGENTS.md
├── database.sql
├── index.php
└── rancangan.md
```

---

## 2. Deskripsi dan Otorisasi Berkas

Tabel di bawah ini menjelaskan fungsi setiap berkas beserta batasan hak akses otorisasi perannya:

| Jalur Berkas (*File Path*) | Deskripsi Fungsionalitas | Hak Akses (*Role*) |
|---|---|---|
| `index.php` | Berkas gerbang utama sistem yang langsung mengalihkan pengunjung ke halaman login. | Publik (Semua Pengguna) |
| `config/koneksi.php` | Menginisialisasi zona waktu peladen (`Asia/Jakarta`) dan koneksi basis data PDO MySQL. | Internal Sistem |
| `auth/login.php` | Menangani otentikasi login pengguna, verifikasi password BCRYPT, dan pengalihan peran. | Publik (Belum Login) |
| `auth/logout.php` | Menghapus dan menghancurkan sesi pengguna aktif lalu mengarahkan ke halaman login. | Pengguna Terotentikasi |
| `includes/cek_login.php` | Gerbang pengendali akses otorisasi per halaman (`cek_login('admin')` / `cek_login('mahasiswa')`). | Internal Sistem |
| `includes/header.php` | Menyajikan struktur kepala HTML, pemanggilan CDN Bootstrap, dan navigasi utama sistem. | Pengguna Terotentikasi |
| `includes/footer.php` | Menyajikan penutup kontainer, hak cipta institusi, dan pemanggilan pustaka JavaScript Bootstrap. | Pengguna Terotentikasi |
| `includes/fungsi.php` | Menyimpan array nama hari/bulan bahasa Indonesia dan fungsi bantu `format_tgl_indo()`. | Internal Sistem |
| `includes/rekap_hitung.php` | Memuat fungsi sentral `ambil_rekap()` untuk menghitung rekap kehadiran dan status alfa dinamis. | Internal Sistem |
| `assets/uploads/.htaccess` | Konfigurasi Apache untuk melarang eksekusi skrip PHP di dalam direktori penyimpanan berkas bukti. | Server Web |
| `admin/dashboard.php` | Menyajikan kartu ringkasan presensi harian, notifikasi izin menunggu, dan tabel hadir mahasiswa aktif. | Admin |
| `admin/mahasiswa.php` | Menyajikan tabel manajemen mahasiswa, fitur pencarian nama/NIM, toggle status aktif, dan hapus akun. | Admin |
| `admin/mahasiswa_form.php` | Formulir penambahan mahasiswa baru dan pembaruan biodata/kredensial mahasiswa. | Admin |
| `admin/rekap.php` | Menyaring dan mengompilasi rekapitulasi kehadiran berdasarkan rentang tanggal dan identitas mahasiswa. | Admin |
| `admin/rekap_cetak.php` | Menghasilkan tata letak laporan formal cetak/PDF tanpa navigasi web dan memicu dialog cetak. | Admin |
| `admin/izin.php` | Menyajikan daftar pengajuan izin/sakit serta aksi persetujuan (dengan transaksi basis data) atau penolakan. | Admin |
| `admin/pengaturan.php` | Formulir konfigurasi jam masuk, jam pulang, dan menit batas toleransi keterlambatan presensi. | Admin |
| `mahasiswa/dashboard.php` | Menampilkan status presensi hari ini, tautan cepat presensi, notifikasi izin, dan ringkasan bulan berjalan. | Mahasiswa |
| `mahasiswa/presensi.php` | Antarmuka presensi masuk (dengan kalkulasi hadir/terlambat) dan presensi pulang kerja harian. | Mahasiswa |
| `mahasiswa/izin.php` | Formulir permohonan izin/sakit dengan validasi berkas pendukung dan tabel riwayat izin. | Mahasiswa |
| `mahasiswa/riwayat.php` | Rekapitulasi kalender presensi mandiri mahasiswa per bulan beserta kartu akumulasi kehadiran dan alfa. | Mahasiswa |
| `mahasiswa/ganti_password.php` | Formulir pembaruan kata sandi akun mahasiswa dengan verifikasi kecocokan kata sandi lama. | Mahasiswa |
| `database.sql` | Skrip DDL dan DML pembentukan skema tabel, indeks relasi, dan data pengguna bawaan sistem. | Administrator Basis Data |
| `rancangan.md` | Dokumen arsitektur dan spesifikasi aturan bisnis awal perancangan sistem presensi. | Pengembang Sistem |

---

## 3. Daftar Teknologi yang Digunakan

Seluruh komponen teknologi yang dicatat di bawah ini terverifikasi secara nyata dari kode program aplikasi:

1. **Bahasa Pemrograman Peladen**:
   - **PHP (Versi 8.x)**: Memanfaatkan paradigma prosedural murni tanpa penggunaan *class* atau *framework* eksternal. Menggunakan fungsi modern PHP seperti *strict type hints* pada fungsi, operator *null coalescing* (`??`), fungsi hash aman `password_hash()` dan `password_verify()` dengan algoritma bawaan BCRYPT, pustaka `finfo` (*File Information*) untuk inspeksi MIME berkas, serta generator nilai acak kriptografis `random_bytes()`.
2. **Mesin dan Klien Basis Data**:
   - **MySQL / MariaDB**: Menggunakan ekstensi **PHP Data Objects (PDO)** dengan konfigurasi penanganan kesalahan berbasis eksepsi (`PDO::ERRMODE_EXCEPTION`), penonaktifan emulasi prepared statement (`PDO::ATTR_EMULATE_PREPARES => false`), dan kumpulan karakter Unicode `utf8mb4`.
   - **Mesin Penyimpanan**: **InnoDB** pada seluruh tabel untuk mendukung transaksi ACID (`beginTransaction`, `commit`, `rollBack`), penguncian baris (`FOR UPDATE`), dan integritas kunci asing (*Foreign Key Constraints* dengan `ON DELETE CASCADE`).
3. **Kerangka Antarmuka (*Front-End*)**:
   - **HTML5 & CSS3**: Menggunakan tata letak semantik dan kueri media cetak (`@media print`).
   - **Bootstrap 5 (Versi 5.3.3)**: Diintegrasikan melalui Content Delivery Network (CDN) resmi untuk sistem kisi responsif (*grid system*), komponen kartu (*cards*), lencana status (*badges*), tabel, dan tata kelola formulir.
   - **Bootstrap Bundle JS (Versi 5.3.3)**: Digunakan untuk fungsi interaktif menu *dropdown* profil dan navigasi responsif (*navbar-toggler*).
4. **Peladen Web (*Web Server*)**:
   - **Apache**: Dijalankan pada lingkungan bundel peladen lokal XAMPP pada sistem operasi Microsoft Windows. Memanfaatkan konfigurasi `.htaccess` untuk pengamanan direktori statis.

---

## 4. Batasan Sistem Saat Ini dan Rekomendasi Pengembangan

Berdasarkan audit teknis terhadap kode sumber aplikasi, ditemukan beberapa batasan fungsionalitas dan keamanan yang perlu diperhatikan:

### 4.1. Hari Libur Nasional Belum Dikecualikan dari Perhitungan Alfa
- **Temuan Kode**: Pada berkas `includes/rekap_hitung.php` (baris 47-59) dan `admin/rekap.php`, sistem hanya memeriksa apakah suatu hari merupakan hari kerja kalender biasa (Senin sampai Jumat, `$hari_angka <= 5`). Sistem belum memiliki referensi data hari libur nasional atau cuti bersama, sehingga tanggal merah yang jatuh pada hari Senin sampai Jumat otomatis dihitung sebagai status `alfa` jika tidak ada rekaman presensi.
- **Rekomendasi**: Menambahkan tabel baru `hari_libur (id, tanggal, keterangan)` pada basis data dan menambahkan pengecekan tanggal libur pada perulangan fungsi `ambil_rekap()`.

### 4.2. Status Akun Nonaktif Tidak Diverifikasi pada Sesi Berjalan
- **Temuan Kode**: Pengecekan status keaktifan (`status === 'aktif'`) hanya dieksekusi pada saat proses login awal di `auth/login.php`. Pada berkas gerbang `includes/cek_login.php`, sistem hanya memeriksa eksistensi `$_SESSION['id']` dan kesesuaian `$_SESSION['role']`. Jika administrator menonaktifkan akun mahasiswa yang sedang login melalui menu `admin/mahasiswa.php`, mahasiswa tersebut tetap dapat melakukan presensi atau mengajukan izin hingga ia mengakhiri sesi (*logout*).
- **Rekomendasi**: Menambahkan kueri verifikasi cepat ke tabel `users` di dalam fungsi `cek_login()` untuk memastikan status pengguna masih `'aktif'`. Jika akun telah dinonaktifkan, sesi langsung dihancurkan dan dialihkan ke halaman login dengan pesan pemberitahuan.

### 4.3. Belum Ada Proteksi Token CSRF (*Cross-Site Request Forgery*)
- **Temuan Kode**: Seluruh formulir metode POST (pada presensi, pengajuan izin, persetujuan izin, kelola mahasiswa, pengaturan jam, dan ganti password) memproses permintaan tanpa validasi token acak berbasis sesi (*CSRF token*).
- **Rekomendasi**: Mengimplementasikan pembuatan token anti-CSRF unik pada sesi pengguna dan memvalidasi token tersebut pada setiap penanganan blok `$_SERVER['REQUEST_METHOD'] === 'POST'`.

### 4.4. Berkas Bukti Fisik Tidak Ikut Terhapus Saat Akun Mahasiswa Dihapus
- **Temuan Kode**: Pada berkas `admin/mahasiswa.php` (baris 21-25), penghapusan mahasiswa mengeksekusi kueri `DELETE FROM users WHERE id = ?`. Relasi kunci asing `ON DELETE CASCADE` menghapus baris data pada tabel `mahasiswa`, `presensi`, dan `izin`. Namun, berkas fisik surat bukti yang tersimpan pada direktori peladen `assets/uploads/` tidak dihapus melalui fungsi `unlink()`, sehingga berpotensi meninggalkan berkas yatim (*orphaned files*) yang membebani ruang penyimpanan peladen.
- **Rekomendasi**: Sebelum kueri `DELETE` dieksekusi, sistem sebaiknya melakukan kueri untuk mengambil seluruh nama berkas bukti pada tabel `izin` milik mahasiswa tersebut dan menghapus berkas fisiknya menggunakan `unlink()`.

### 4.5. Kolom `nim` pada Tabel `mahasiswa` Tidak Memiliki Indeks Unik
- **Temuan Kode**: Pada berkas `database.sql` dan `admin/mahasiswa_form.php`, kolom `nim` hanya diberi tipe `VARCHAR(30) NOT NULL` tanpa indeks `UNIQUE`. Sistem juga tidak melakukan pengecekan kueri apakah NIM yang diinput sudah pernah terdaftar. Hal ini memungkinkan terjadinya duplikasi pencatatan NIM mahasiswa yang sama secara tidak sengaja oleh administrator.
- **Rekomendasi**: Menambahkan indeks pembatasan unik `UNIQUE KEY uq_mhs_nim (nim)` pada skema basis data serta menambahkan validasi kueri pada formulir penambahan mahasiswa.

### 4.6. Presensi Belum Memiliki Validasi Geolokasi (GPS) dan Swafoto Kamera
- **Temuan Kode**: Pada berkas `mahasiswa/presensi.php`, tombol presensi masuk dan pulang langsung mencatat waktu peladen tanpa memvalidasi koordinat lokasi perangkat peramban maupun masukan citra kamera wajah. Mahasiswa dapat melakukan presensi dari jaringan manapun di luar kantor BPS selama memiliki koneksi internet.
- **Rekomendasi**: Mengintegrasikan antarmuka JavaScript *HTML5 Geolocation API* untuk menghitung jarak radius (*Haversine distance*) terhadap titik koordinat kantor BPS Kota Medan dan *MediaDevices.getUserMedia* untuk swafoto kehadiran jika dipersyaratkan oleh instansi.

### 4.7. Mekanisme Pemulihan Akun (*Reset Password*) Masih Bergantung Penuh pada Admin
- **Temuan Kode**: Pada antarmuka login `auth/login.php` dan `mahasiswa/ganti_password.php`, tidak tersedia alur pemulihan kata sandi mandiri (*Forgot Password*). Mahasiswa yang lupa kata sandi wajib melapor kepada admin agar admin mengubah kata sandi secara manual melalui `admin/mahasiswa_form.php`.
- **Rekomendasi**: Menyediakan tombol reset kata sandi mandiri dengan verifikasi kode OTP atau tautan pemulihan melalui kontak WhatsApp/Email mahasiswa.

### 4.8. Catatan Batasan Terkait Data Pejabat Pengesah Laporan
- **Keterangan**: Pada berkas cetak laporan `admin/rekap.php` dan `admin/rekap_cetak.php`, kolom titimangsa dan tanda tangan diset secara generik bertuliskan jabatan `"Pengawas Magang"`, nama pengguna admin yang sedang aktif, dan NIP bertitik-titik. Penentuan nama pejabat pembimbing definitif dan Nomor Induk Pegawai (NIP) resmi instansi berstatus **perlu konfirmasi** lebih lanjut dengan bagian kepegawaian BPS Kota Medan sebelum dokumen laporan dicetak secara resmi.