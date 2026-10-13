# Rencana Pengujian Fungsional Black-Box

Dokumen ini memuat matriks rancangan kasus uji fungsional metode *black-box* untuk Sistem Presensi Mahasiswa Magang BPS Kota Medan. Seluruh butir pengujian dan hasil yang diharapkan dirumuskan secara presisi berdasarkan alur eksekusi logika dan teks pesan notifikasi yang tertulis pada kode sumber aplikasi.

*Catatan Pengujian*: Kolom **Hasil Pengujian** dan **Kesimpulan** sengaja dikosongkan dengan nilai `[diisi setelah diuji]` untuk diisi secara objektif oleh penguji saat pelaksanaan evaluasi sistem berlangsung di lingkungan pengujian.

---

## 1. Modul 1: Login dan Pembatasan Peran (Role-Based Access)

| No | ID Uji | Skenario Uji | Data Masukan | Hasil yang Diharapkan | Hasil Pengujian | Kesimpulan |
|---|---|---|---|---|---|---|
| 1 | TC-LOG-01 | Login berhasil sebagai Administrator | `username: "admin"`, `password: "admin123"` (valid), status aktif | Sesi tersimpan (`role: "admin"`), dialihkan ke `admin/dashboard.php`. | [diisi setelah diuji] | [diisi setelah diuji] |
| 2 | TC-LOG-02 | Login berhasil sebagai Mahasiswa | `username: "mhs1"`, `password: "mhs123"` (valid), status aktif | Sesi tersimpan (`role: "mahasiswa"`), dialihkan ke `mahasiswa/dashboard.php`. | [diisi setelah diuji] | [diisi setelah diuji] |
| 3 | TC-LOG-03 | Login dengan kata sandi salah | `username: "admin"`, `password: "salah"` | Login ditolak, tetap di halaman login dengan pesan: `"Username atau password salah."` | [diisi setelah diuji] | [diisi setelah diuji] |
| 4 | TC-LOG-04 | Login dengan nama pengguna tidak terdaftar | `username: "tidakada"`, `password: "bebas"` | Login ditolak, tetap di halaman login dengan pesan: `"Username atau password salah."` | [diisi setelah diuji] | [diisi setelah diuji] |
| 5 | TC-LOG-05 | Login akun dengan status nonaktif | `username: "mhs_nonaktif"`, `password: "password_benar"`, status nonaktif | Login ditolak, tetap di halaman login dengan pesan: `"Username atau password salah."` | [diisi setelah diuji] | [diisi setelah diuji] |
| 6 | TC-LOG-06 | Pengiriman formulir login dengan input kosong | `username: ""`, `password: ""` | Login ditolak, atribut HTML5 required aktif atau pesan `"Username atau password salah."` | [diisi setelah diuji] | [diisi setelah diuji] |
| 7 | TC-LOG-07 | Pengguna belum login mengakses halaman internal admin | URL langsung: `admin/dashboard.php` tanpa sesi login | Akses ditolak oleh `cek_login()`, dialihkan otomatis ke `auth/login.php`. | [diisi setelah diuji] | [diisi setelah diuji] |
| 8 | TC-LOG-08 | Pengguna belum login mengakses halaman internal mahasiswa | URL langsung: `mahasiswa/presensi.php` tanpa sesi login | Akses ditolak oleh `cek_login()`, dialihkan otomatis ke `auth/login.php`. | [diisi setelah diuji] | [diisi setelah diuji] |
| 9 | TC-LOG-09 | Mahasiswa mencoba mengakses halaman admin | Sesi aktif mahasiswa mengakses URL `admin/rekap.php` | Akses ditolak oleh `cek_login('admin')`, dialihkan ke `mahasiswa/dashboard.php`. | [diisi setelah diuji] | [diisi setelah diuji] |
| 10 | TC-LOG-10 | Admin mencoba mengakses halaman mahasiswa | Sesi aktif admin mengakses URL `mahasiswa/presensi.php` | Akses ditolak oleh `cek_login('mahasiswa')`, dialihkan ke `admin/dashboard.php`. | [diisi setelah diuji] | [diisi setelah diuji] |
| 11 | TC-LOG-11 | Logout pengguna aktif | Pengguna menekan tautan Logout (`auth/logout.php`) | Sesi dihancurkan sepenuhnya, dialihkan ke `auth/login.php`. | [diisi setelah diuji] | [diisi setelah diuji] |

---

## 2. Modul 2: Presensi Masuk dan Pulang Mahasiswa

| No | ID Uji | Skenario Uji | Data Masukan | Hasil yang Diharapkan | Hasil Pengujian | Kesimpulan |
|---|---|---|---|---|---|---|
| 12 | TC-PRS-01 | Presensi masuk tepat waktu (hadir) | Hari kerja, dalam masa magang, waktu server <= (jam masuk + toleransi) | Tersimpan ke tabel `presensi` dengan status `'hadir'`, pesan: `"Presensi masuk berhasil. Status: Hadir."` | [diisi setelah diuji] | [diisi setelah diuji] |
| 13 | TC-PRS-02 | Presensi masuk terlambat | Hari kerja, dalam masa magang, waktu server > (jam masuk + toleransi) | Tersimpan ke tabel `presensi` dengan status `'terlambat'`, pesan: `"Presensi masuk berhasil. Status: Terlambat."` | [diisi setelah diuji] | [diisi setelah diuji] |
| 14 | TC-PRS-03 | Presensi masuk pada hari Sabtu atau Minggu | Hari server: Sabtu atau Minggu, tombol `aksi: "masuk"` | Ditolak dengan pesan: `"Presensi hanya bisa pada hari Senin sampai Jumat."` | [diisi setelah diuji] | [diisi setelah diuji] |
| 15 | TC-PRS-04 | Presensi masuk di luar masa aktif magang | Tanggal hari ini < `tgl_mulai` atau > `tgl_selesai` | Ditolak dengan pesan: `"Hari ini di luar periode magang Anda."` | [diisi setelah diuji] | [diisi setelah diuji] |
| 16 | TC-PRS-05 | Presensi masuk ulang pada hari yang sama | Mahasiswa sudah memiliki baris presensi hari ini | Tombol disabled di antarmuka; jika dipaksa via POST ditolak dengan pesan: `"Anda sudah presensi hari ini."` | [diisi setelah diuji] | [diisi setelah diuji] |
| 17 | TC-PRS-06 | Presensi pulang normal | Mahasiswa sudah presensi masuk, `jam_pulang` masih NULL, status hadir/terlambat | Kolom `jam_pulang` terisi waktu server, pesan: `"Presensi pulang berhasil dicatat."` | [diisi setelah diuji] | [diisi setelah diuji] |
| 18 | TC-PRS-07 | Presensi pulang sebelum presensi masuk | Mahasiswa belum memiliki data presensi masuk hari ini | Tombol disabled; jika dipaksa via POST ditolak dengan pesan: `"Anda belum presensi masuk hari ini."` | [diisi setelah diuji] | [diisi setelah diuji] |
| 19 | TC-PRS-08 | Presensi pulang dua kali | Mahasiswa sudah memiliki data `jam_pulang` terisi | Tombol disabled; jika dipaksa via POST ditolak dengan pesan: `"Anda sudah presensi pulang hari ini."` | [diisi setelah diuji] | [diisi setelah diuji] |
| 20 | TC-PRS-09 | Presensi pulang pada status izin atau sakit | Mahasiswa memiliki presensi hari ini berstatus `'izin'` atau `'sakit'` | Tombol disabled; jika dipaksa via POST ditolak dengan pesan: `"Presensi pulang tidak berlaku untuk status izin/sakit."` | [diisi setelah diuji] | [diisi setelah diuji] |

---

## 3. Modul 3: Riwayat Presensi Mahasiswa

| No | ID Uji | Skenario Uji | Data Masukan | Hasil yang Diharapkan | Hasil Pengujian | Kesimpulan |
|---|---|---|---|---|---|---|
| 21 | TC-RIW-01 | Tampilan riwayat bulan berjalan | Mahasiswa membuka `mahasiswa/riwayat.php` tanpa parameter GET | Menampilkan data presensi bulan dan tahun saat ini, kartu ringkasan terhitung akurat. | [diisi setelah diuji] | [diisi setelah diuji] |
| 22 | TC-RIW-02 | Penyaringan riwayat berdasarkan bulan dan tahun magang | Memilih bulan dan tahun tertentu di dalam masa magang | Menampilkan rekaman tabel presensi dan perhitungan kartu ringkasan sesuai bulan terpilih. | [diisi setelah diuji] | [diisi setelah diuji] |
| 23 | TC-RIW-03 | Menampilkan bulan tanpa rekaman kehadiran | Memilih bulan tanpa data presensi | Ditampilkan panel informasi: `"Tidak ada data presensi pada bulan ini."` | [diisi setelah diuji] | [diisi setelah diuji] |
| 24 | TC-RIW-04 | Verifikasi kalkulasi status alfa pada riwayat | Hari kerja lampau dalam rentang magang tanpa catatan presensi | Tanggal bersangkutan otomatis muncul di tabel dengan status lencana merah `'Alfa'` dan jam `'-'`. | [diisi setelah diuji] | [diisi setelah diuji] |

---

## 4. Modul 4: Kelola Data Mahasiswa oleh Admin

| No | ID Uji | Skenario Uji | Data Masukan | Hasil yang Diharapkan | Hasil Pengujian | Kesimpulan |
|---|---|---|---|---|---|---|
| 25 | TC-MHS-01 | Tambah data mahasiswa lengkap dan valid | Nama: "Andi", Username: "andi.magang", Password: "password123", NIM: "12345", Kampus: "USU", Jurusan: "TI", Tgl Mulai: 2026-08-01, Tgl Selesai: 2026-10-31 | Data tersimpan di tabel `users` dan `mahasiswa`, dialihkan ke `admin/mahasiswa.php` dengan pesan: `"Data mahasiswa berhasil ditambahkan."` | [diisi setelah diuji] | [diisi setelah diuji] |
| 26 | TC-MHS-02 | Tambah mahasiswa dengan kolom wajib kosong | Kolom Nama atau NIM dikosongkan | Form ditolak dengan pesan kesalahan: `"Semua kolom wajib diisi kecuali No HP."` | [diisi setelah diuji] | [diisi setelah diuji] |
| 27 | TC-MHS-03 | Tambah mahasiswa dengan format username ilegal | Username: "ab" (kurang dari 4 karakter) atau mengandung spasi/simbol terlarang | Form ditolak dengan pesan kesalahan: `"Username hanya boleh huruf, angka, titik, garis bawah; panjang 4-50 karakter."` | [diisi setelah diuji] | [diisi setelah diuji] |
| 28 | TC-MHS-04 | Tambah mahasiswa dengan password kurang dari 6 karakter | Password: "12345" (5 karakter) | Form ditolak dengan pesan kesalahan: `"Password minimal 6 karakter."` | [diisi setelah diuji] | [diisi setelah diuji] |
| 29 | TC-MHS-05 | Tambah mahasiswa dengan tanggal selesai mendahului tanggal mulai | `tgl_mulai: "2026-10-31"`, `tgl_selesai: "2026-08-01"` | Form ditolak dengan pesan kesalahan: `"Tanggal selesai tidak boleh lebih awal dari tanggal mulai."` | [diisi setelah diuji] | [diisi setelah diuji] |
| 30 | TC-MHS-06 | Tambah mahasiswa dengan username duplikat | Username: sudah terdaftar sebelumnya di database | Form ditolak dengan pesan kesalahan: `"Username sudah dipakai, pilih username lain."` | [diisi setelah diuji] | [diisi setelah diuji] |
| 31 | TC-MHS-07 | Ubah data mahasiswa tanpa mengubah password | Memperbarui nama/kampus, kolom password dikosongkan | Data diperbarui tanpa mengubah hash password lama, dialihkan dengan pesan: `"Data mahasiswa berhasil diperbarui."` | [diisi setelah diuji] | [diisi setelah diuji] |
| 32 | TC-MHS-08 | Toggle status keaktifan mahasiswa | Menekan tombol Nonaktifkan / Aktifkan | Kolom `status` pada tabel `users` berganti dari aktif ke nonaktif (atau sebaliknya) dengan pesan: `"Status mahasiswa berhasil diubah."` | [diisi setelah diuji] | [diisi setelah diuji] |
| 33 | TC-MHS-09 | Hapus data mahasiswa | Menekan tombol Hapus pada baris mahasiswa | Data user, profil mahasiswa, riwayat presensi, dan izin terhapus secara beruntun (*CASCADE*) dengan pesan: `"Data mahasiswa berhasil dihapus."` | [diisi setelah diuji] | [diisi setelah diuji] |
| 34 | TC-MHS-10 | Pencarian data mahasiswa berdasarkan nama atau NIM | Parameter pencarian `cari: "Budi"` | Tabel hanya menyajikan baris mahasiswa yang namanya atau NIM-nya memuat kata kunci pencarian. | [diisi setelah diuji] | [diisi setelah diuji] |

---

## 5. Modul 5: Rekapitulasi Presensi dan Cetak Laporan (Admin)

| No | ID Uji | Skenario Uji | Data Masukan | Hasil yang Diharapkan | Hasil Pengujian | Kesimpulan |
|---|---|---|---|---|---|---|
| 35 | TC-REK-01 | Filter rekapitulasi semua mahasiswa pada rentang normal | `tgl_dari: "2026-10-01"`, `tgl_sampai: "2026-10-13"`, `user_id: 0` | Menyajikan data gabungan seluruh mahasiswa, kartu ringkasan dan baris alfa terhitung akurat. | [diisi setelah diuji] | [diisi setelah diuji] |
| 36 | TC-REK-02 | Filter rekapitulasi untuk satu mahasiswa spesifik | `tgl_dari: "2026-10-01"`, `tgl_sampai: "2026-10-13"`, `user_id: 2` | Menyajikan data presensi khusus mahasiswa ID 2, angka ringkasan cocok dengan total baris tabel. | [diisi setelah diuji] | [diisi setelah diuji] |
| 37 | TC-REK-03 | Filter tanggal akhir lebih awal dari tanggal awal | `tgl_dari: "2026-10-15"`, `tgl_sampai: "2026-10-01"` | Sistem otomatis menukar posisi kedua tanggal secara aman tanpa galat, memproses rentang 2026-10-01 s/d 2026-10-15. | [diisi setelah diuji] | [diisi setelah diuji] |
| 38 | TC-REK-04 | Masukan parameter tanggal URL tidak valid | `tgl_awal=abc`, `tgl_akhir=xyz` | Sistem mengembalikan nilai tanggal ke setelan default yang aman (tanggal 1 bulan berjalan s/d hari ini). | [diisi setelah diuji] | [diisi setelah diuji] |
| 39 | TC-REK-05 | Masukan rentang tanggal luas (3 tahun) | `tgl_dari: "2023-10-01"`, `tgl_sampai: "2026-10-13"` | Sistem memproses tanpa galat memori, perhitungan alfa dibatasi hanya pada periode magang aktif mahasiswa. | [diisi setelah diuji] | [diisi setelah diuji] |
| 40 | TC-REK-06 | Membuka cetak laporan rekapitulasi | Menekan tombol Cetak Laporan (`admin/rekap_cetak.php`) | Halaman terbuka di tab baru tanpa navbar, menampilkan kop BPS, tabel data, kolom tanda tangan, dan memicu dialog cetak peramban. | [diisi setelah diuji] | [diisi setelah diuji] |
| 41 | TC-REK-07 | Mahasiswa mencoba mengakses berkas cetak rekapitulasi | Sesi mahasiswa mengakses langsung `admin/rekap_cetak.php` | Ditolak oleh `cek_login('admin')` dan dialihkan otomatis ke `mahasiswa/dashboard.php`. | [diisi setelah diuji] | [diisi setelah diuji] |

---

## 6. Modul 6: Pengajuan Izin dan Sakit oleh Mahasiswa

| No | ID Uji | Skenario Uji | Data Masukan | Hasil yang Diharapkan | Hasil Pengujian | Kesimpulan |
|---|---|---|---|---|---|---|
| 42 | TC-IZN-01 | Pengajuan izin tanpa lampiran bukti | Tanggal kerja masa magang, `jenis: "izin"`, alasan diisi, tanpa berkas | Permohonan tersimpan di tabel `izin` dengan status `'menunggu'`, pesan: `"Pengajuan berhasil dikirim dan menunggu persetujuan admin."` | [diisi setelah diuji] | [diisi setelah diuji] |
| 43 | TC-IZN-02 | Pengajuan sakit dengan lampiran bukti sah (PDF <= 2MB) | Tanggal kerja masa magang, `jenis: "sakit"`, alasan diisi, berkas valid PDF 500 KB | Berkas disimpan ke `assets/uploads/` dengan nama acak, permohonan tersimpan dengan status `'menunggu'`. | [diisi setelah diuji] | [diisi setelah diuji] |
| 44 | TC-IZN-03 | Pengajuan sakit tanpa mengunggah berkas bukti | Tanggal kerja masa magang, `jenis: "sakit"`, alasan diisi, tanpa berkas | Formulir ditolak dengan pesan: `"Bukti surat dokter/keterangan sakit wajib diunggah untuk jenis sakit."` | [diisi setelah diuji] | [diisi setelah diuji] |
| 45 | TC-IZN-04 | Pengajuan dengan tanggal lampau | Tanggal input < tanggal hari ini server | Formulir ditolak dengan pesan: `"Tanggal pengajuan tidak boleh lebih lampau dari hari ini."` | [diisi setelah diuji] | [diisi setelah diuji] |
| 46 | TC-IZN-05 | Pengajuan pada hari Sabtu atau Minggu | Tanggal input jatuh pada hari Sabtu atau Minggu | Formulir ditolak dengan pesan: `"Pengajuan hanya dapat dilakukan untuk hari kerja (Senin sampai Jumat)."` | [diisi setelah diuji] | [diisi setelah diuji] |
| 47 | TC-IZN-06 | Pengajuan di luar periode magang | Tanggal input di luar rentang `tgl_mulai` dan `tgl_selesai` | Formulir ditolak dengan pesan: `"Tanggal pengajuan harus berada dalam rentang periode magang Anda."` | [diisi setelah diuji] | [diisi setelah diuji] |
| 48 | TC-IZN-07 | Pengajuan pada tanggal yang sudah memiliki presensi | Tanggal input sudah memiliki rekaman pada tabel `presensi` | Formulir ditolak dengan pesan: `"Anda sudah memiliki catatan presensi pada tanggal tersebut."` | [diisi setelah diuji] | [diisi setelah diuji] |
| 49 | TC-IZN-08 | Pengajuan ganda pada tanggal yang sama | Tanggal input sudah pernah diajukan dan berstatus menunggu/disetujui | Formulir ditolak dengan pesan: `"Pengajuan izin/sakit untuk tanggal tersebut sudah pernah diajukan sebelumnya."` | [diisi setelah diuji] | [diisi setelah diuji] |
| 50 | TC-IZN-09 | Unggah berkas bukti melebihi batas 2 MB | Berkas gambar/PDF dengan ukuran 2,5 MB | Formulir ditolak dengan pesan: `"Ukuran file bukti maksimal 2 MB."` | [diisi setelah diuji] | [diisi setelah diuji] |
| 51 | TC-IZN-10 | Unggah berkas dengan ekstensi tidak diizinkan | Berkas dokumen berformat `.docx` atau `.exe` | Formulir ditolak dengan pesan: `"Format file bukti harus berupa JPG, PNG, atau PDF."` | [diisi setelah diuji] | [diisi setelah diuji] |
| 52 | TC-IZN-11 | Unggah berkas dengan tipe MIME palsu | Berkas teks yang diubah ekstensinya menjadi `.jpg` secara paksa | Pengecekan `finfo` gagal, ditolak dengan pesan: `"Tipe konten file tidak diizinkan. Hanya file gambar (JPG, PNG) atau dokumen PDF yang diperbolehkan."` | [diisi setelah diuji] | [diisi setelah diuji] |

---

## 7. Modul 7: Persetujuan Izin oleh Admin

| No | ID Uji | Skenario Uji | Data Masukan | Hasil yang Diharapkan | Hasil Pengujian | Kesimpulan |
|---|---|---|---|---|---|---|
| 53 | TC-APR-01 | Setujui permohonan izin (mahasiswa belum presensi) | Tombol Setujui (`aksi: "setujui"`), izin status menunggu, belum ada presensi masuk | Status izin menjadi `'disetujui'`, tabel `presensi` terisi otomatis status izin/sakit dengan jam NULL, pesan: `"Pengajuan izin berhasil disetujui dan data presensi telah diperbarui."` | [diisi setelah diuji] | [diisi setelah diuji] |
| 54 | TC-APR-02 | Setujui izin saat mahasiswa sudah presensi masuk | Tombol Setujui (`aksi: "setujui"`), mahasiswa sudah memiliki `jam_masuk` di tanggal itu | Transaksi di-rollback, status izin tetap menunggu, muncul pesan: `"Persetujuan dibatalkan: Mahasiswa bersangkutan sudah melakukan presensi pada tanggal tersebut."` | [diisi setelah diuji] | [diisi setelah diuji] |
| 55 | TC-APR-03 | Tolak permohonan izin | Tombol Tolak (`aksi: "tolak"`), izin status menunggu | Status izin berubah menjadi `'ditolak'`, tabel `presensi` tidak diubah, pesan: `"Pengajuan izin berhasil ditolak."` | [diisi setelah diuji] | [diisi setelah diuji] |
| 56 | TC-APR-04 | Pemrosesan izin yang statusnya sudah bukan menunggu | Form POST setujui/tolak pada izin yang sudah disetujui/ditolak | Ditolak dengan pesan: `"Pengajuan tidak ditemukan atau sudah tidak berstatus menunggu."` | [diisi setelah diuji] | [diisi setelah diuji] |

---

## 8. Modul 8: Pengaturan Jam Kerja (Admin)

| No | ID Uji | Skenario Uji | Data Masukan | Hasil yang Diharapkan | Hasil Pengujian | Kesimpulan |
|---|---|---|---|---|---|---|
| 57 | TC-SET-01 | Pembaruan jam kerja dengan data valid | `jam_masuk: "08:00:00"`, `jam_pulang: "16:00:00"`, `toleransi_menit: 15` | Tabel `pengaturan` diperbarui, dialihkan dengan pesan: `"Pengaturan jam kerja berhasil diperbarui."` | [diisi setelah diuji] | [diisi setelah diuji] |
| 58 | TC-SET-02 | Jam pulang lebih awal dari jam masuk | `jam_masuk: "16:00:00"`, `jam_pulang: "08:00:00"`, `toleransi_menit: 15` | Formulir ditolak dengan pesan: `"Jam pulang harus lebih besar dari jam masuk."` | [diisi setelah diuji] | [diisi setelah diuji] |
| 59 | TC-SET-03 | Format jam tidak valid | `jam_masuk: "25:00:00"` atau karakter teks sembarang | Formulir ditolak dengan pesan: `"Format jam masuk tidak valid."` | [diisi setelah diuji] | [diisi setelah diuji] |
| 60 | TC-SET-04 | Toleransi menit bernilai negatif atau bukan bilangan bulat | `toleransi_menit: -5` atau `toleransi_menit: "abc"` | Formulir ditolak dengan pesan: `"Toleransi keterlambatan harus berupa bilangan bulat."` | [diisi setelah diuji] | [diisi setelah diuji] |
| 61 | TC-SET-05 | Toleransi menit melebihi batas 120 menit | `toleransi_menit: 150` | Formulir ditolak dengan pesan: `"Toleransi keterlambatan harus berada di antara 0 sampai 120 menit."` | [diisi setelah diuji] | [diisi setelah diuji] |

---

## 9. Modul 9: Dashboard Admin dan Mahasiswa

| No | ID Uji | Skenario Uji | Data Masukan | Hasil yang Diharapkan | Hasil Pengujian | Kesimpulan |
|---|---|---|---|---|---|---|
| 62 | TC-DSH-01 | Dashboard admin pada hari kerja | Hari Senin s/d Jumat, mahasiswa aktif magang | Kartu ringkasan hadir, terlambat, izin/sakit, dan belum presensi terhitung akurat; tabel hadir harian tersaji via satu query. | [diisi setelah diuji] | [diisi setelah diuji] |
| 63 | TC-DSH-02 | Dashboard admin pada hari akhir pekan | Hari Sabtu atau Minggu | Ditampilkan pesan `"Hari ini bukan hari kerja (akhir pekan). Presensi hari ini bernilai 0."` dan kartu bernilai 0. | [diisi setelah diuji] | [diisi setelah diuji] |
| 64 | TC-DSH-03 | Tautan izin menunggu pada dashboard admin | Terdapat 2 pengajuan izin berstatus menunggu | Angka kartu izin menunggu menampilkan angka 2 dan dapat diklik mengarah ke `admin/izin.php?status=menunggu`. | [diisi setelah diuji] | [diisi setelah diuji] |
| 65 | TC-DSH-04 | Dashboard mahasiswa dengan izin menunggu | Mahasiswa memiliki pengajuan izin berstatus menunggu | Tampil kotak peringatan: `"Anda memiliki [N] pengajuan izin/sakit yang masih menunggu persetujuan admin."` dengan tautan ke `izin.php`. | [diisi setelah diuji] | [diisi setelah diuji] |
| 66 | TC-DSH-05 | Ringkasan bulan berjalan pada dashboard mahasiswa | Mahasiswa login melihat dashboard | Kartu hadir, terlambat, izin, sakit, dan alfa memanggil `ambil_rekap()` secara konsisten. | [diisi setelah diuji] | [diisi setelah diuji] |

---

## 10. Modul 10: Ganti Password Mahasiswa

| No | ID Uji | Skenario Uji | Data Masukan | Hasil yang Diharapkan | Hasil Pengujian | Kesimpulan |
|---|---|---|---|---|---|---|
| 67 | TC-PWD-01 | Pengubahan password sukses | Password lama cocok, password baru 8 karakter, konfirmasi cocok | Hash password di tabel `users` diperbarui, muncul pesan: `"Password berhasil diubah."` | [diisi setelah diuji] | [diisi setelah diuji] |
| 68 | TC-PWD-02 | Password lama tidak cocok | `pass_lama: "salah"`, `pass_baru: "baru123"`, `konfirmasi: "baru123"` | Ditolak dengan pesan: `"Password lama tidak cocok."` | [diisi setelah diuji] | [diisi setelah diuji] |
| 69 | TC-PWD-03 | Password baru kurang dari 6 karakter | `pass_lama: "benar"`, `pass_baru: "12345"`, `konfirmasi: "12345"` | Ditolak dengan pesan: `"Password baru minimal 6 karakter."` | [diisi setelah diuji] | [diisi setelah diuji] |
| 70 | TC-PWD-04 | Konfirmasi password baru tidak cocok | `pass_lama: "benar"`, `pass_baru: "baru123"`, `konfirmasi: "beda123"` | Ditolak dengan pesan: `"Konfirmasi password baru tidak cocok."` | [diisi setelah diuji] | [diisi setelah diuji] |
| 71 | TC-PWD-05 | Password baru sama persis dengan password lama | `pass_lama: "lama123"`, `pass_baru: "lama123"`, `konfirmasi: "lama123"` | Ditolak dengan pesan: `"Password baru tidak boleh sama dengan password lama."` | [diisi setelah diuji] | [diisi setelah diuji] |

---

## 11. Daftar Data Uji yang Perlu Disiapkan di Basis Data (Test Fixtures)

Sebelum rangkaian pengujian di atas dilaksanakan pada peramban web, data awal berikut wajib tersedia pada basis data `presensi_bps`:

1. **Akun Administrator**:
   - `username: "admin"`, peran: `'admin'`, status: `'aktif'`.
2. **Akun Mahasiswa Uji 1 (Aktif)**:
   - `username: "mhs1"`, peran: `'mahasiswa'`, status: `'aktif'`.
   - Data magang: `nim: "2021001001"`, `kampus: "Universitas Sumatera Utara"`, `jurusan: "Statistika"`, rentang magang: mencakup tanggal hari ini (contoh: 2026-08-01 s/d 2026-10-31).
3. **Akun Mahasiswa Uji 2 (Aktif)**:
   - `username: "mhs2"`, peran: `'mahasiswa'`, status: `'aktif'`.
   - Data magang: `nim: "2021002002"`, `kampus: "Politeknik Negeri Medan"`, `jurusan: "Komputerisasi Akuntansi"`, rentang magang: mencakup tanggal hari ini.
4. **Akun Mahasiswa Uji 3 (Nonaktif)**:
   - `username: "mhs_nonaktif"`, peran: `'mahasiswa'`, status: `'nonaktif'`.
5. **Akun Mahasiswa Uji 4 (Masa Magang Kadaluarsa / Di Luar Rentang)**:
   - `username: "mhs_kadaluarsa"`, status: `'aktif'`, rentang magang masa lalu (contoh: 2025-01-01 s/d 2025-03-31).
6. **Data Pengaturan Jam Kerja (Tabel `pengaturan`)**:
   - Baris konfigurasi: `jam_masuk: "08:00:00"`, `jam_pulang: "16:00:00"`, `toleransi_menit: 15`.
7. **Berkas Sampel Uji Unggah**:
   - Berkas dokumen PDF valid (< 2 MB).
   - Berkas gambar JPG/PNG valid (< 2 MB).
   - Berkas dokumen PDF besar (> 2 MB) untuk uji batas ukuran.
   - Berkas teks/eksekutabel bertipe non-gambar untuk uji validasi MIME.