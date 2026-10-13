# Rancangan Sistem Presensi Mahasiswa Magang BPS Kota Medan

## Role
- **admin** - pengawas PKL
- **mahasiswa** - peserta magang

---

## Fitur Mahasiswa
- Login / logout
- Dashboard: status presensi hari ini, ringkasan bulan ini
- Presensi masuk dan pulang (jam dari server)
- Pengajuan izin/sakit dengan upload bukti
- Riwayat presensi
- Ganti password

## Fitur Admin
- Login / logout
- Dashboard: jumlah hadir, terlambat, izin, belum presensi hari ini
- Kelola data mahasiswa: tambah, ubah, hapus, aktif/nonaktif
- Rekap presensi: filter tanggal dan nama
- Persetujuan izin/sakit
- Pengaturan jam masuk/pulang/toleransi
- Cetak laporan rekap

---

## Tabel Database (nama database: presensi_bps)

### users
| Kolom      | Tipe                        | Keterangan                  |
|------------|-----------------------------|-----------------------------|
| id         | INT AUTO_INCREMENT PK       |                             |
| username   | VARCHAR(50) UNIQUE NOT NULL |                             |
| password   | VARCHAR(255) NOT NULL       | bcrypt hash                 |
| nama       | VARCHAR(100) NOT NULL       |                             |
| role       | ENUM(admin, mahasiswa)      |                             |
| status     | ENUM(aktif, nonaktif)       | default aktif               |
| created_at | TIMESTAMP DEFAULT NOW()     |                             |

### mahasiswa
| Kolom       | Tipe                    | Keterangan         |
|-------------|-------------------------|--------------------|
| id          | INT AUTO_INCREMENT PK   |                    |
| user_id     | INT FK -> users.id      |                    |
| nim         | VARCHAR(30) NOT NULL    |                    |
| kampus      | VARCHAR(100) NOT NULL   |                    |
| jurusan     | VARCHAR(100) NOT NULL   |                    |
| tgl_mulai   | DATE NOT NULL           |                    |
| tgl_selesai | DATE NOT NULL           |                    |
| no_hp       | VARCHAR(20)             |                    |

### presensi
| Kolom      | Tipe                         | Keterangan                             |
|------------|------------------------------|----------------------------------------|
| id         | INT AUTO_INCREMENT PK        |                                        |
| user_id    | INT FK -> users.id           |                                        |
| tanggal    | DATE NOT NULL                |                                        |
| jam_masuk  | TIME                         |                                        |
| jam_pulang | TIME                         |                                        |
| status     | ENUM(hadir,terlambat,izin,   |                                        |
|            |      sakit,alfa)             |                                        |
| keterangan | TEXT                         |                                        |
|            | UNIQUE KEY (user_id,tanggal) |                                        |

### izin
| Kolom      | Tipe                              | Keterangan                    |
|------------|-----------------------------------|-------------------------------|
| id         | INT AUTO_INCREMENT PK             |                               |
| user_id    | INT FK -> users.id                |                               |
| tanggal    | DATE NOT NULL                     |                               |
| jenis      | ENUM(izin, sakit)                 |                               |
| alasan     | TEXT NOT NULL                     |                               |
| bukti      | VARCHAR(255)                      | nama file upload              |
| status     | ENUM(menunggu,disetujui,ditolak)  | default menunggu              |
| created_at | TIMESTAMP DEFAULT NOW()           |                               |

### pengaturan
| Kolom          | Tipe          | Keterangan             |
|----------------|---------------|------------------------|
| id             | INT AUTO_INCREMENT PK |                |
| jam_masuk      | TIME NOT NULL | contoh 08:00:00        |
| jam_pulang     | TIME NOT NULL | contoh 16:00:00        |
| toleransi_menit| INT NOT NULL  | menit toleransi telat  |

---

## Struktur Folder

```
presensi-bps/
  config/
    koneksi.php
  auth/
    login.php
    logout.php
  admin/
    dashboard.php
    mahasiswa.php
    rekap.php
    izin.php
    pengaturan.php
  mahasiswa/
    dashboard.php
    presensi.php
    izin.php
    riwayat.php
  includes/
    header.php
    footer.php
    cek_login.php
  assets/
    css/
    uploads/
  database.sql
  index.php
```

---

## Urutan Pengerjaan Modul

1. database + koneksi
2. login + role
3. presensi mahasiswa
4. riwayat
5. kelola mahasiswa
6. rekap + cetak
7. izin/sakit
8. pengaturan jam
9. dashboard


## Aturan Bisnis

- **Status presensi**: jam masuk <= jam_masuk pengaturan + toleransi_menit berarti `hadir`, selebihnya `terlambat`.
- **Presensi masuk** hanya sekali per hari. **Presensi pulang** hanya bisa jika sudah presensi masuk hari itu, dan hanya sekali.
- **Jam dan tanggal** selalu dari server (PHP), bukan dari input atau perangkat pengguna.
- **Presensi** hanya bisa pada hari Senin sampai Jumat, dan selama tanggal hari ini berada di antara tgl_mulai dan tgl_selesai mahasiswa.
- **Izin/sakit**: status awal `menunggu`. Jika admin menyetujui, otomatis dibuat atau diubah baris presensi tanggal itu dengan status `izin` atau `sakit`.
- **Alfa**: hari kerja tanpa presensi dan tanpa izin disetujui dihitung alfa. Alfa dihitung saat ditampilkan (rekap dan dashboard), tidak disimpan otomatis.
- **Upload bukti izin**: jpg, png, atau pdf; maksimal 2 MB; nama file diacak (uniqid/random).
- **Ganti password**: wajib isi password lama, password baru minimal 6 karakter.