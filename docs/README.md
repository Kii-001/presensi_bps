# Dokumentasi Sistem Presensi Mahasiswa Magang BPS Kota Medan

Dokumentasi ini disusun sebagai bahan pendukung penyusunan Laporan Praktik Kerja Lapangan (PKL) / Magang pada Badan Pusat Statistik (BPS) Kota Medan. Seluruh isi dokumen diselaraskan secara langsung dengan struktur kode sumber, aturan bisnis, dan skema basis data yang diterapkan pada aplikasi.

---

## Daftar Berkas Dokumentasi

Berikut adalah daftar berkas yang tersedia di dalam direktori `docs/`:

1. **`01-use-case.md`**  
   Memuat diagram use case berskala sistem, tabel identifikasi seluruh use case per peran pengguna (Mahasiswa dan Admin/Pengawas PKL), keterkaitan berkas kode sumber PHP yang mengimplementasikannya, serta spesifikasi skenario terperinci untuk empat use case utama.

2. **`02-erd.md`**  
   Memuat diagram relasi entitas (*Entity Relationship Diagram* - ERD) berbasis Mermaid, kamus data lengkap untuk seluruh lima tabel basis data, serta analisis aturan integritas referensial dan pembatasan unik (*unique constraint*).

3. **`03-activity-flowchart.md`**  
   Memuat diagram alir aktivitas (*activity flowchart*) berbasis Mermaid yang memetakan logika pengambilan keputusan secara runtut sesuai urutan kode sumber PHP, disertai penjelasan deskriptif untuk setiap alur proses.

4. **`04-pengujian-black-box.md`**  
   Memuat matriks tabel pengujian *black-box* fungsional yang mencakup skenario normal, skenario batas, dan penanganan kondisi kesalahan per modul, dilengkapi daftar kebutuhan data uji mula (*test fixtures*).

5. **`05-struktur-dan-batasan.md`**  
   Memuat diagram pohon struktur direktori berkas proyek, tabel deskripsi peran dan otorisasi berkas, inventarisasi teknologi yang digunakan, serta temuan teknis mengenai batasan sistem saat ini beserta rekomendasi pengembangannya.

---

## Petunjuk Penggunaan Diagram Mermaid

Seluruh diagram di dalam dokumentasi ini ditulis menggunakan sintaks kode diagram **Mermaid** di dalam blok kode ` ```mermaid `. Untuk memindahkan diagram ke dalam dokumen laporan Microsoft Word atau dokumen pengolah kata lainnya, ikuti langkah berikut:

1. Buka berkas markdown terkait dan salin teks yang berada di dalam blok ` ```mermaid `.
2. Buka peramban web dan kunjungi editor visual resmi di alamat:  
   `https://mermaid.live`
3. Tempelkan (*paste*) kode sintaks ke panel editor di sisi kiri. Panel pratinjau di sisi kanan akan langsung merender diagram secara visual.
4. Pada panel atas atau bawah pratinjau, klik tombol **Actions** lalu pilih format ekspor yang diinginkan:
   - **PNG** (disarankan memilih skala 2x atau 3x untuk hasil gambar tajam bebas pecah saat dicetak).
   - **SVG** (format grafik vektor yang dapat diperbesar tanpa kehilangan kualitas).
5. Masukkan gambar hasil unduhan ke dalam dokumen laporan laporan PKL (*Insert Picture*).