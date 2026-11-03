-- Database: presensi_bps  |  Import lewat phpMyAdmin (tab Import) atau: mysql -u root < database.sql
CREATE DATABASE IF NOT EXISTS presensi_bps CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE presensi_bps;

DROP TABLE IF EXISTS kegiatan; DROP TABLE IF EXISTS izin; DROP TABLE IF EXISTS presensi; DROP TABLE IF EXISTS pengaturan; DROP TABLE IF EXISTS users;

CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nama VARCHAR(100) NOT NULL,
  username VARCHAR(50) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  role ENUM('admin','mahasiswa') NOT NULL DEFAULT 'mahasiswa',
  nim VARCHAR(30) DEFAULT NULL,
  universitas VARCHAR(100) DEFAULT NULL,
  jurusan VARCHAR(100) DEFAULT NULL,
  bidang VARCHAR(100) DEFAULT NULL,
  no_hp VARCHAR(20) DEFAULT NULL,
  mulai_magang DATE DEFAULT NULL,
  selesai_magang DATE DEFAULT NULL,
  status ENUM('aktif','nonaktif') NOT NULL DEFAULT 'aktif',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE presensi (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  tanggal DATE NOT NULL,
  jam_masuk TIME DEFAULT NULL,
  lat_masuk DECIMAL(10,7) DEFAULT NULL,
  lng_masuk DECIMAL(10,7) DEFAULT NULL,
  jarak_masuk DECIMAL(8,2) DEFAULT NULL,
  foto_masuk VARCHAR(200) DEFAULT NULL,
  jam_pulang TIME DEFAULT NULL,
  lat_pulang DECIMAL(10,7) DEFAULT NULL,
  lng_pulang DECIMAL(10,7) DEFAULT NULL,
  jarak_pulang DECIMAL(8,2) DEFAULT NULL,
  foto_pulang VARCHAR(200) DEFAULT NULL,
  status ENUM('hadir','terlambat','izin','sakit') NOT NULL DEFAULT 'hadir',
  UNIQUE KEY uq_user_tanggal (user_id, tanggal),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE izin (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  jenis ENUM('izin','sakit') NOT NULL,
  tanggal_mulai DATE NOT NULL,
  tanggal_selesai DATE NOT NULL,
  keterangan TEXT,
  bukti VARCHAR(200) DEFAULT NULL,
  status ENUM('menunggu','disetujui','ditolak') NOT NULL DEFAULT 'menunggu',
  catatan_admin VARCHAR(255) DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE kegiatan (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  tanggal DATE NOT NULL,
  uraian TEXT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE pengaturan (
  kunci VARCHAR(50) PRIMARY KEY,
  nilai VARCHAR(255) NOT NULL
) ENGINE=InnoDB;

INSERT INTO pengaturan (kunci, nilai) VALUES
 ('nama_kantor','BPS Kota Medan'),
 ('kantor_lat','3.5952000'),
 ('kantor_lng','98.6722000'),
 ('radius','100'),
 ('jam_masuk','07:30'),
 ('batas_terlambat','08:00'),
 ('jam_pulang','16:00');

-- Akun contoh: admin / admin123  |  mahasiswa / magang123
INSERT INTO users (nama, username, password, role) VALUES ('Pengawas Magang','admin','$2y$10$W2yWBJGEXI9oz1F22tf11.bsDRSHmvyPbLhcb7jlJFquZfhl14WDu','admin');
INSERT INTO users (nama, username, password, role, nim, universitas, jurusan, bidang, no_hp, mulai_magang, selesai_magang) VALUES
 ('Rafif Risdi Aulia','rafif','$2y$10$lwBBze82wIaE.7tXvsxth.pfMaJd2TMW6fkiiq4KjZ3yyYYSz2rUq','mahasiswa','0701222074','Universitas Islam Negeri Sumatera Utara','Sistem Informasi','Integrasi Pengolahan dan Diseminasi Statistik','081200000001','2026-10-01','2026-12-31'),
 ('Dina Maharani','dina','$2y$10$lwBBze82wIaE.7tXvsxth.pfMaJd2TMW6fkiiq4KjZ3yyYYSz2rUq','mahasiswa','2105010021','Universitas Sumatera Utara','Statistika','Statistik Sosial','081200000002','2026-10-01','2026-12-31'),
 ('Aldo Pratama','aldo','$2y$10$lwBBze82wIaE.7tXvsxth.pfMaJd2TMW6fkiiq4KjZ3yyYYSz2rUq','mahasiswa','2201020045','Politeknik Negeri Medan','Teknik Informatika','Nerwilis','081200000003','2026-10-05','2027-01-05');
