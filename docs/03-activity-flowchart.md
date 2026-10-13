# Dokumentasi Diagram Alir Aktivitas (Activity Flowchart)

Dokumen ini memetakan seluruh logika pemrosesan data dan percabangan pengambilan keputusan di dalam sistem presensi. Seluruh urutan percabangan (*decision*) disusun secara runtut mengikuti urutan eksekusi logika pada kode sumber PHP aplikasi.

---

## 1. Alur Aktivitas Login dan Pengalihan Peran

Diagram alir berikut memetakan alur proses autentikasi akun pengguna dan pengalihan ke antarmuka yang sesuai berdasarkan peran (*role*) pada berkas `auth/login.php`:

```mermaid
flowchart TD
    Start(["Mulai"]) --> Input["Pengguna memasukkan username dan password"]
    Input --> CheckEmpty{"Apakah username dan password diisi?"}
    CheckEmpty -- "Tidak" --> ErrMsg["Tetapkan pesan: 'Username atau password salah.'"]
    CheckEmpty -- "Ya" --> QueryUser["Query tabel users berdasarkan username"]
    QueryUser --> CheckUser{"Apakah user ditemukan, status aktif, dan password cocok?"}
    CheckUser -- "Tidak" --> ErrMsg
    ErrMsg --> ShowLogin["Tampilkan pesan error di halaman login"] --> EndLogin(["Selesai"])
    CheckUser -- "Ya" --> RegenSession["Regenerasi ID sesi session_regenerate_id"]
    RegenSession --> SaveSession["Simpan session id, nama, role"]
    SaveSession --> CheckRole{"Apakah role sama dengan admin?"}
    CheckRole -- "Ya" --> RedirectAdmin["Pengalihan ke admin/dashboard.php"] --> EndLogin
    CheckRole -- "Tidak" --> RedirectMhs["Pengalihan ke mahasiswa/dashboard.php"] --> EndLogin
```

### Penjelasan Alur Login
Proses dimulai ketika pengguna mengirimkan kredensial melalui formulir login. Sistem terlebih dahulu memvalidasi bahwa kedua kolom tidak kosong sebelum mengeksekusi *prepared statement* ke tabel `users`. Selanjutnya, sistem memverifikasi bahwa rekaman data ditemukan, kolom `status` bernilai `'aktif'`, dan fungsi `password_verify` menghasilkan kecocokan terhadap hash kata sandi. Apabila salah satu kondisi tidak terpenuhi, sistem mengembalikan pesan kegagalan generik `"Username atau password salah."` untuk mencegah teknik *username enumeration*. Setelah berhasil, sesi diperbarui dengan `session_regenerate_id(true)` dan pengguna dialihkan ke dashboard sesuai perannya.

---

## 2. Alur Aktivitas Presensi Masuk

Diagram alir berikut memetakan verifikasi aturan bisnis dan penentuan status kehadiran kerja pada fungsi `proses_masuk()` di berkas `mahasiswa/presensi.php`:

```mermaid
flowchart TD
    StartIn(["Mulai Presensi Masuk"]) --> CheckDay{"Apakah hari kerja Senin sampai Jumat?"}
    CheckDay -- "Tidak (Sabtu atau Minggu)" --> MsgWeekend["Kembalikan peringatan: 'Presensi hanya bisa pada hari Senin sampai Jumat.'"]
    CheckDay -- "Ya" --> CheckPeriod{"Apakah tanggal hari ini dalam rentang magang?"}
    CheckPeriod -- "Tidak" --> MsgPeriod["Kembalikan peringatan: 'Hari ini di luar periode magang Anda.'"]
    CheckPeriod -- "Ya" --> CheckExists{"Apakah sudah ada presensi hari ini di database?"}
    CheckExists -- "Ya" --> MsgExists["Kembalikan peringatan: 'Anda sudah presensi hari ini.'"]
    CheckExists -- "Tidak" --> CalcLimit["Hitung batas toleransi: jam_masuk ditambah toleransi_menit"]
    CalcLimit --> CompareTime{"Apakah jam server saat ini kurang dari atau sama dengan batas?"}
    CompareTime -- "Ya" --> SetHadir["Tetapkan status = 'hadir'"]
    CompareTime -- "Tidak" --> SetLate["Tetapkan status = 'terlambat'"]
    SetHadir --> TryInsert["Eksekusi INSERT ke tabel presensi"]
    SetLate --> TryInsert
    TryInsert --> CatchError{"Apakah terjadi pelanggaran UNIQUE KEY error 23000?"}
    CatchError -- "Ya" --> MsgExists
    CatchError -- "Tidak (Gagal Lain)" --> MsgFail["Kembalikan pesan: 'Terjadi kesalahan, coba lagi.'"]
    CatchError -- "Tidak (Sukses)" --> MsgSuccess["Kembalikan sukses: 'Presensi masuk berhasil. Status: Hadir/Terlambat.'"]
    MsgWeekend --> FlashRedirect["Simpan pesan ke session flash dan redirect ke presensi.php"]
    MsgPeriod --> FlashRedirect
    MsgExists --> FlashRedirect
    MsgFail --> FlashRedirect
    MsgSuccess --> FlashRedirect
    FlashRedirect --> EndIn(["Selesai"])
```

### Penjelasan Alur Presensi Masuk
Proses presensi masuk diawali dengan memeriksa apakah hari akses merupakan hari kerja (Senin sampai Jumat) dan memastikan tanggal server berada di dalam masa aktif magang mahasiswa. Sistem kemudian memeriksa apakah baris presensi hari ini telah tercatat untuk mencegah presensi berulang. Waktu server selanjutnya dibandingkan terhadap waktu batas masuk ditambah toleransi keterlambatan guna menentukan status `'hadir'` atau `'terlambat'`. Terakhir, kueri `INSERT` dijalankan dengan blok penangkap pengecualian integritas untuk menangani klik ganda secara aman sebelum pengguna dialihkan kembali ke antarmuka presensi.

---

## 3. Alur Aktivitas Presensi Pulang

Diagram alir berikut memetakan pemrosesan pencatatan waktu kepulangan kerja pada fungsi `proses_pulang()` di berkas `mahasiswa/presensi.php`:

```mermaid
flowchart TD
    StartOut(["Mulai Presensi Pulang"]) --> CheckInDone{"Apakah sudah ada presensi hari ini dan jam_masuk terisi?"}
    CheckInDone -- "Tidak" --> MsgNoIn["Kembalikan peringatan: 'Anda belum presensi masuk hari ini.'"]
    CheckInDone -- "Ya" --> CheckOutDone{"Apakah jam_pulang sudah pernah terisi sebelumnya?"}
    CheckOutDone -- "Ya" --> MsgAlreadyOut["Kembalikan peringatan: 'Anda sudah presensi pulang hari ini.'"]
    CheckOutDone -- "Tidak" --> CheckStatus{"Apakah status presensi termasuk hadir atau terlambat?"}
    CheckStatus -- "Tidak (Izin atau Sakit)" --> MsgInvalidStatus["Kembalikan peringatan: 'Presensi pulang tidak berlaku untuk status izin/sakit.'"]
    CheckStatus -- "Ya" --> ExecUpdate["UPDATE presensi SET jam_pulang = jam_server WHERE jam_pulang IS NULL"]
    ExecUpdate --> CheckRows{"Apakah jumlah baris terpengaruh rowCount lebih dari 0?"}
    CheckRows -- "Tidak" --> MsgNotFound["Kembalikan peringatan: 'Presensi pulang sudah tercatat atau tidak ditemukan.'"]
    CheckRows -- "Ya" --> MsgSuccessOut["Kembalikan sukses: 'Presensi pulang berhasil dicatat.'"]
    MsgNoIn --> FlashRedirectOut["Simpan pesan ke session flash dan redirect ke presensi.php"]
    MsgAlreadyOut --> FlashRedirectOut
    MsgInvalidStatus --> FlashRedirectOut
    MsgNotFound --> FlashRedirectOut
    MsgSuccessOut --> FlashRedirectOut
    FlashRedirectOut --> EndOut(["Selesai"])
```

### Penjelasan Alur Presensi Pulang
Pencatatan kepulangan kerja mewajibkan mahasiswa telah memiliki rekaman presensi masuk terlebih dahulu pada hari berjalan. Sistem memvalidasi bahwa kolom `jam_pulang` masih bernilai kosong dan status kehadiran bukan merupakan dispensasi izin atau sakit. Kueri `UPDATE` dieksekusi dengan memanfaatkan jam otoritatif server PHP dan membatasi pembaruan hanya pada baris yang belum memiliki waktu pulang. Hasil eksekusi dikonfirmasi melalui nilai `rowCount()` sebelum pesan status disimpan ke dalam sesi kilat dan halaman dimuat ulang.

---

## 4. Alur Aktivitas Pengajuan Izin dan Sakit oleh Mahasiswa

Diagram alir berikut memetakan tahapan validasi masukan formulir, verifikasi berkas bukti fisik, dan penyimpanan pengajuan pada berkas `mahasiswa/izin.php`:

```mermaid
flowchart TD
    StartIzin(["Mulai Pengajuan Izin"]) --> ValBasic{"Apakah tanggal, jenis, dan alasan terisi lengkap?"}
    ValBasic -- "Tidak" --> ErrBasic["Pesan: 'Tanggal, jenis pengajuan, dan alasan wajib diisi.'"]
    ValBasic -- "Ya" --> ValTypeDate{"Validasi jenis izin/sakit, format tanggal, dan tanggal tidak lampau?"}
    ValTypeDate -- "Tidak" --> ErrTypeDate["Tetapkan pesan kesalahan terkait jenis atau tanggal"]
    ValTypeDate -- "Ya" --> ValWorkPeriod{"Apakah hari kerja Senin-Jumat dan dalam rentang magang?"}
    ValWorkPeriod -- "Tidak" --> ErrWorkPeriod["Tetapkan pesan: 'Pengajuan hanya hari kerja / harus dalam rentang magang.'"]
    ValWorkPeriod -- "Ya" --> CheckPresensi{"Apakah sudah ada catatan presensi pada tanggal tersebut?"}
    CheckPresensi -- "Ya" --> ErrPresensi["Pesan: 'Anda sudah memiliki catatan presensi pada tanggal tersebut.'"]
    CheckPresensi -- "Tidak" --> CheckPendingIzin{"Apakah sudah ada izin menunggu atau disetujui pada tanggal itu?"}
    CheckPendingIzin -- "Ya" --> ErrPending["Pesan: 'Pengajuan izin/sakit untuk tanggal tersebut sudah pernah diajukan sebelumnya.'"]
    CheckPendingIzin -- "Tidak" --> CheckBukti{"Apakah jenis sakit dan berkas bukti diunggah?"}
    CheckBukti -- "Sakit tanpa bukti" --> ErrNoFile["Pesan: 'Bukti surat dokter/keterangan sakit wajib diunggah untuk jenis sakit.'"]
    CheckBukti -- "Izin tanpa bukti" --> SaveDB["Simpan nama bukti = NULL"]
    CheckBukti -- "Ada berkas bukti" --> ValFile{"Periksa ukuran maksimal 2MB, ekstensi jpg/png/pdf, dan MIME riil finfo"}
    ValFile -- "Gagal validasi file" --> ErrFile["Tetapkan pesan kesalahan berkas bukti"]
    ValFile -- "Lolos validasi file" --> MoveFile["Simpan acak ke assets/uploads/ dengan bin2hex random_bytes"]
    MoveFile --> SaveDB
    SaveDB --> ExecInsert["INSERT ke tabel izin dengan status = 'menunggu'"]
    ExecInsert --> CheckInsert{"Apakah penyimpanan query berhasil?"}
    CheckInsert -- "Gagal" --> DeleteFile["Hapus berkas fisik yang terlanjur diunggah via unlink"]
    DeleteFile --> ErrSys["Pesan: 'Terjadi kesalahan sistem saat menyimpan pengajuan.'"]
    CheckInsert -- "Berhasil" --> OkIzin["Pesan: 'Pengajuan berhasil dikirim dan menunggu persetujuan admin.'"]
    ErrBasic --> FlashIzin["Simpan flash session dan redirect ke izin.php"]
    ErrTypeDate --> FlashIzin
    ErrWorkPeriod --> FlashIzin
    ErrPresensi --> FlashIzin
    ErrPending --> FlashIzin
    ErrNoFile --> FlashIzin
    ErrFile --> FlashIzin
    ErrSys --> FlashIzin
    OkIzin --> FlashIzin
    FlashIzin --> EndIzin(["Selesai"])
```

### Penjelasan Alur Pengajuan Izin
Mahasiswa mengirimkan formulir izin dengan validasi ketat terhadap kelengkapan isian, format kalender, hari kerja, dan masa magang aktif. Sistem memastikan tidak ada konflik jadwal terhadap presensi harian maupun izin lain yang telah berstatus menunggu atau disetujui. Untuk pengajuan kategori sakit, surat dokter diwajibkan dan diverifikasi ukuran, ekstensi, serta tipe MIME aslinya sebelum disimpan menggunakan penamaan acak yang aman. Apabila kueri penyimpanan basis data gagal setelah berkas terunggah, sistem secara otomatis mengeksekusi penghapusan fisik berkas (*rollback cleanup*) guna mencegah penumpukan berkas sampah pada peladen.

---

## 5. Alur Aktivitas Persetujuan Izin oleh Admin

Diagram alir berikut memetakan mekanisme transaksi basis data pada pemrosesan persetujuan atau penolakan izin di berkas `admin/izin.php`:

```mermaid
flowchart TD
    StartApprove(["Mulai Pemrosesan Izin"]) --> CheckAction{"Apakah aksi yang dipilih setujui atau tolak?"}
    CheckAction -- "Tolak" --> UpdateReject["UPDATE izin SET status = 'ditolak' WHERE id = ? AND status = 'menunggu'"]
    UpdateReject --> CheckRejectRow{"Apakah baris berhasil diperbarui?"}
    CheckRejectRow -- "Ya" --> MsgRejectOk["Pesan: 'Pengajuan izin berhasil ditolak.'"]
    CheckRejectRow -- "Tidak" --> MsgRejectFail["Pesan: 'Pengajuan tidak ditemukan atau sudah tidak berstatus menunggu.'"]
    CheckAction -- "Setujui" --> BeginTx["Mulai transaksi PDO beginTransaction"]
    BeginTx --> LockRow["SELECT izin WHERE id = ? FOR UPDATE"]
    LockRow --> CheckPending{"Apakah baris ditemukan dan status masih menunggu?"}
    CheckPending -- "Tidak" --> Rollback1["Rollback transaksi"] --> MsgNotPending["Pesan: 'Pengajuan tidak ditemukan atau sudah tidak berstatus menunggu.'"]
    CheckPending -- "Ya" --> CheckAttend["Query tabel presensi pada tanggal dan user bersangkutan"]
    CheckAttend --> HasClockIn{"Apakah mahasiswa sudah pernah melakukan presensi masuk?"}
    HasClockIn -- "Ya (jam_masuk terisi)" --> Rollback2["Rollback transaksi"] --> MsgAlreadyAttend["Pesan: 'Persetujuan dibatalkan: Mahasiswa bersangkutan sudah presensi.'"]
    HasClockIn -- "Tidak" --> ApproveIzin["UPDATE izin SET status = 'disetujui' WHERE status = 'menunggu'"]
    ApproveIzin --> UpsertPresensi["INSERT presensi ON DUPLICATE KEY UPDATE status = jenis, keterangan = alasan"]
    UpsertPresensi --> CommitTx["Konfirmasi transaksi commit"] --> MsgApproveOk["Pesan: 'Pengajuan izin berhasil disetujui dan data presensi telah diperbarui.'"]
    MsgRejectOk --> FlashAdmin["Set flash session dan redirect ke izin.php"]
    MsgRejectFail --> FlashAdmin
    MsgNotPending --> FlashAdmin
    MsgAlreadyAttend --> FlashAdmin
    MsgApproveOk --> FlashAdmin
    FlashAdmin --> EndApprove(["Selesai"])
```

### Penjelasan Alur Persetujuan Izin
Administrator memproses pengajuan izin melalui aksi persetujuan yang dibungkus di dalam transaksi basis data PDO dengan penguncian baris (`FOR UPDATE`). Sistem memeriksa integritas jadwal untuk memastikan mahasiswa belum melakukan presensi masuk pada tanggal permohonan tersebut; apabila telah presensi masuk, persetujuan dibatalkan demi mencegah manipulasi data kehadiran. Jika validasi terpenuhi, status izin dimutakhirkan menjadi disetujui dan data presensi pada tanggal terkait disinkronkan menggunakan kueri upsert dengan jam kerja dikosongkan. Untuk aksi penolakan, sistem cukup memperbarui status menjadi ditolak tanpa memengaruhi data pada tabel presensi.

---

## 6. Alur Perhitungan Status Alfa Dinamis

Diagram alir berikut memetakan logika kalkulasi dinamis untuk menentukan status alfa pada rekapitulasi dan riwayat kehadiran pada berkas `includes/rekap_hitung.php` dan `admin/rekap.php`:

```mermaid
flowchart TD
    StartAlfa(["Mulai Perhitungan Rekap"]) --> InitLoop["Ambil rentang tanggal tgl_dari sampai tgl_sampai dan data magang mahasiswa"]
    InitLoop --> FetchDB["Query seluruh catatan presensi yang ada pada rentang tanggal ke array presensi_index"]
    FetchDB --> InitCounts["Inisialisasi ringkasan: hadir=0, terlambat=0, izin=0, sakit=0, alfa=0"]
    InitCounts --> DateLoop{"Apakah tanggal kalender perulangan kurang dari atau sama dengan tgl_sampai?"}
    DateLoop -- "Tidak (Selesai Rentang)" --> DisplayResult["Kompilasi ringkasan dan urutkan daftar baris tabel"] --> EndAlfa(["Selesai"])
    DateLoop -- "Ya" --> CheckFuture{"Apakah tanggal perulangan lebih besar dari hari_ini?"}
    CheckFuture -- "Ya (Masa Depan)" --> NextDate["Lanjut ke tanggal berikutnya +1 hari"] --> DateLoop
    CheckFuture -- "Tidak" --> CheckWeekend{"Apakah hari Sabtu atau Minggu (hari_angka >= 6)?"}
    CheckWeekend -- "Ya" --> CheckWeekendRow{"Apakah ada rekaman presensi di database?"}
    CheckWeekendRow -- "Tidak" --> NextDate
    CheckWeekendRow -- "Ya" --> CountWeekend["Tambah status terdata ke ringkasan dan masukkan ke tabel"] --> NextDate
    CheckWeekend -- "Tidak (Hari Kerja)" --> CheckPresensiRow{"Apakah ada rekaman presensi di database?"}
    CheckPresensiRow -- "Ada" --> CountDB["Tambah status ke ringkasan (hadir/terlambat/izin/sakit) dan masukkan ke tabel"] --> NextDate
    CheckPresensiRow -- "Tidak Ada" --> CheckPeriodAlfa{"Apakah tanggal dalam periode magang dan tanggal lampau (tgl < hari_ini)?"}
    CheckPeriodAlfa -- "Ya" --> AddAlfa["Hitung alfa bertambah 1, jam = '-', keterangan = 'Tanpa keterangan'"] --> NextDate
    CheckPeriodAlfa -- "Tidak (Hari ini / Luar Magang)" --> NextDate
```

### Penjelasan Alur Perhitungan Alfa Dinamis
Status alfa tidak disimpan secara fisik setiap hari ke dalam basis data, melainkan dihitung secara dinamis saat halaman rekapitulasi atau riwayat diakses. Sistem memeriksa setiap tanggal dalam rentang yang diminta dan mengabaikan tanggal masa depan serta akhir pekan yang tidak memiliki catatan presensi. Pada hari kerja (Senin sampai Jumat) di dalam masa aktif magang yang telah berlalu tanpa catatan kehadiran maupun izin disetujui, sistem menetapkannya sebagai status alfa. Tanggal hari ini yang belum memiliki presensi tidak dihitung sebagai alfa karena jam operasional kerja hari berjalan masih berlangsung.