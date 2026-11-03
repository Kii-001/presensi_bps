# Data Flow Diagram (DFD)

Gunakan diagram di bawah sebagai acuan untuk digambar ulang di draw.io / Visio untuk laporan. Tampilan teks Mermaid bisa dilihat di VS Code (ekstensi Markdown Preview Mermaid) atau mermaid.live.

## DFD Level 0 (Diagram Konteks)

```mermaid
flowchart LR
  M[Mahasiswa Magang] -- "Login, lokasi, foto selfie, pengajuan izin, kegiatan harian" --> S((Sistem Presensi<br/>Magang BPS Kota Medan))
  S -- "Status presensi, riwayat, info izin" --> M
  A[Admin / Pengawas] -- "Data mahasiswa, persetujuan izin, pengaturan" --> S
  S -- "Rekap, peta presensi, laporan" --> A
  S <--> D[(Database MySQL)]
```

## DFD Level 1

| Proses | Nama proses | Masukan | Keluaran | Data store |
|---|---|---|---|---|
| 1.0 | Autentikasi | Username, password | Sesi login (role admin/mahasiswa) | users |
| 2.0 | Presensi (Clock In/Out) | Koordinat GPS, foto selfie | Status hadir/terlambat, jarak, foto tersimpan | presensi, pengaturan |
| 3.0 | Pengelolaan izin & sakit | Pengajuan izin, persetujuan admin | Status izin, catatan di rekap presensi | izin, presensi |
| 4.0 | Kegiatan harian | Uraian kegiatan | Daftar kegiatan | kegiatan |
| 5.0 | Pengelolaan data | Data mahasiswa, pengaturan kantor | Akun, radius, jam masuk | users, pengaturan |
| 6.0 | Laporan | Filter tanggal/bulan | Rekap harian, bulanan, per universitas, keterlambatan, peta | presensi, users, izin |

```mermaid
flowchart TB
  M[Mahasiswa] --> P1((1.0 Autentikasi))
  A[Admin] --> P1
  P1 <--> D1[(users)]
  M -- "lokasi + selfie" --> P2((2.0 Presensi))
  P2 <--> D2[(presensi)]
  P2 --> D5[(pengaturan)]
  M -- "pengajuan" --> P3((3.0 Izin & Sakit))
  A -- "setuju/tolak" --> P3
  P3 <--> D3[(izin)]
  P3 --> D2
  M -- "uraian" --> P4((4.0 Kegiatan Harian))
  P4 <--> D4[(kegiatan)]
  A -- "data & pengaturan" --> P5((5.0 Pengelolaan Data))
  P5 <--> D1
  P5 <--> D5
  D2 --> P6((6.0 Laporan))
  D1 --> P6
  P6 --> A
```
