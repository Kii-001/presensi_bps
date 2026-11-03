# ERD dan Struktur Tabel

```mermaid
erDiagram
  users ||--o{ presensi : "melakukan"
  users ||--o{ izin : "mengajukan"
  users ||--o{ kegiatan : "mencatat"
  users {
    int id PK
    varchar nama
    varchar username UK
    varchar password
    enum role "admin|mahasiswa"
    varchar nim
    varchar universitas
    varchar jurusan
    varchar bidang
    varchar no_hp
    date mulai_magang
    date selesai_magang
    enum status "aktif|nonaktif"
  }
  presensi {
    int id PK
    int user_id FK
    date tanggal
    time jam_masuk
    decimal lat_masuk
    decimal lng_masuk
    decimal jarak_masuk
    varchar foto_masuk
    time jam_pulang
    decimal lat_pulang
    decimal lng_pulang
    decimal jarak_pulang
    varchar foto_pulang
    enum status "hadir|terlambat|izin|sakit"
  }
  izin {
    int id PK
    int user_id FK
    enum jenis "izin|sakit"
    date tanggal_mulai
    date tanggal_selesai
    text keterangan
    varchar bukti
    enum status "menunggu|disetujui|ditolak"
    varchar catatan_admin
  }
  kegiatan {
    int id PK
    int user_id FK
    date tanggal
    text uraian
  }
  pengaturan {
    varchar kunci PK
    varchar nilai
  }
```

## Keterangan tabel

- **users**: akun admin dan mahasiswa magang. Password disimpan dengan `password_hash` (bcrypt).
- **presensi**: satu baris per mahasiswa per hari (kunci unik `user_id + tanggal`). Menyimpan waktu, koordinat, jarak dari kantor, dan path foto untuk Clock In dan Clock Out.
- **izin**: pengajuan izin/sakit. Jika disetujui, sistem mencatat status izin/sakit pada hari kerja di tabel presensi.
- **kegiatan**: logbook harian mahasiswa.
- **pengaturan**: koordinat kantor, radius, jam masuk, batas terlambat, jam pulang.

Foto selfie disimpan sebagai file di folder `uploads/selfie/TAHUN/BULAN/`, database hanya menyimpan path-nya.
