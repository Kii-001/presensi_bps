-- Buat database
CREATE DATABASE IF NOT EXISTS presensi_bps
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE presensi_bps;

-- Tabel users
CREATE TABLE IF NOT EXISTS users (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  username   VARCHAR(50)  NOT NULL UNIQUE,
  password   VARCHAR(255) NOT NULL,
  nama       VARCHAR(100) NOT NULL,
  role       ENUM('admin','mahasiswa') NOT NULL,
  status     ENUM('aktif','nonaktif') NOT NULL DEFAULT 'aktif',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabel mahasiswa
CREATE TABLE IF NOT EXISTS mahasiswa (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  user_id     INT  NOT NULL,
  nim         VARCHAR(30)  NOT NULL,
  kampus      VARCHAR(100) NOT NULL,
  jurusan     VARCHAR(100) NOT NULL,
  tgl_mulai   DATE NOT NULL,
  tgl_selesai DATE NOT NULL,
  no_hp       VARCHAR(20),
  CONSTRAINT fk_mhs_user FOREIGN KEY (user_id) REFERENCES users(id)
    ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabel presensi
CREATE TABLE IF NOT EXISTS presensi (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  user_id     INT  NOT NULL,
  tanggal     DATE NOT NULL,
  jam_masuk   TIME,
  jam_pulang  TIME,
  status      ENUM('hadir','terlambat','izin','sakit','alfa') NOT NULL DEFAULT 'alfa',
  keterangan  TEXT,
  CONSTRAINT fk_presensi_user FOREIGN KEY (user_id) REFERENCES users(id)
    ON UPDATE CASCADE ON DELETE CASCADE,
  UNIQUE KEY uq_presensi_harian (user_id, tanggal)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabel izin
CREATE TABLE IF NOT EXISTS izin (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  user_id    INT  NOT NULL,
  tanggal    DATE NOT NULL,
  jenis      ENUM('izin','sakit') NOT NULL,
  alasan     TEXT NOT NULL,
  bukti      VARCHAR(255),
  status     ENUM('menunggu','disetujui','ditolak') NOT NULL DEFAULT 'menunggu',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_izin_user FOREIGN KEY (user_id) REFERENCES users(id)
    ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabel pengaturan
CREATE TABLE IF NOT EXISTS pengaturan (
  id              INT AUTO_INCREMENT PRIMARY KEY,
  jam_masuk       TIME NOT NULL,
  jam_pulang      TIME NOT NULL,
  toleransi_menit INT  NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data awal: admin
INSERT INTO users (username, password, nama, role, status) VALUES
('admin', '$2y$10$lDiFbufxVoRK6NYC6ah0u.EJPEArqdxCtWRjXS1KA649auZvZhc0y', 'Administrator BPS', 'admin', 'aktif');

-- Data awal: mahasiswa 1 dan 2
INSERT INTO users (username, password, nama, role, status) VALUES
('mhs1', '$2y$10$SkciAtlMsYvvmWIzShlaY.0zYlF4vxs6rGLKC9jLOUznhUZ/cI5Ea', 'Budi Santoso', 'mahasiswa', 'aktif'),
('mhs2', '$2y$10$SkciAtlMsYvvmWIzShlaY.0zYlF4vxs6rGLKC9jLOUznhUZ/cI5Ea', 'Siti Aminah',  'mahasiswa', 'aktif');

-- Detail mahasiswa
INSERT INTO mahasiswa (user_id, nim, kampus, jurusan, tgl_mulai, tgl_selesai, no_hp)
SELECT id, '2021001001', 'Universitas Sumatera Utara', 'Statistika',
       '2026-08-01', '2026-10-31', '081234567890'
FROM users WHERE username = 'mhs1';

INSERT INTO mahasiswa (user_id, nim, kampus, jurusan, tgl_mulai, tgl_selesai, no_hp)
SELECT id, '2021002002', 'Politeknik Negeri Medan', 'Komputerisasi Akuntansi',
       '2026-08-01', '2026-10-31', '082345678901'
FROM users WHERE username = 'mhs2';

-- Pengaturan jam kerja
INSERT INTO pengaturan (jam_masuk, jam_pulang, toleransi_menit) VALUES
('08:00:00', '16:00:00', 15);
