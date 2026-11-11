-- Database UTS PDCS - Muhammad Farraz Naufal Prakasa (2511510012)
CREATE DATABASE IF NOT EXISTS db_mhsapi CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE db_mhsapi;

DROP TABLE IF EXISTS matakuliah;
DROP TABLE IF EXISTS mahasiswa;
DROP TABLE IF EXISTS jurusan;

CREATE TABLE jurusan (
  id INT(11) NOT NULL AUTO_INCREMENT,
  kode_jurusan VARCHAR(32) NOT NULL,
  nama_jurusan VARCHAR(64) NOT NULL,
  fakultas VARCHAR(32) NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_kode_jurusan (kode_jurusan)
) ENGINE=InnoDB;

CREATE TABLE mahasiswa (
  id INT(11) NOT NULL AUTO_INCREMENT,
  jurusan_id INT(11) NOT NULL,
  nama VARCHAR(32) NOT NULL,
  nim VARCHAR(32) NOT NULL,
  created_at DATE NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_nim (nim),
  CONSTRAINT fk_mahasiswa_jurusan FOREIGN KEY (jurusan_id) REFERENCES jurusan(id)
) ENGINE=InnoDB;

CREATE TABLE matakuliah (
  id INT(11) NOT NULL AUTO_INCREMENT,
  jurusan_id INT(11) NOT NULL,
  kode_mk VARCHAR(32) NOT NULL,
  nama_mk VARCHAR(64) NOT NULL,
  sks INT(8) NOT NULL,
  semester INT(8) NOT NULL,
  PRIMARY KEY (id),
  CONSTRAINT fk_matakuliah_jurusan FOREIGN KEY (jurusan_id) REFERENCES jurusan(id)
) ENGINE=InnoDB;

-- Data contoh
INSERT INTO jurusan (kode_jurusan, nama_jurusan, fakultas) VALUES
('TI', 'Teknik Informatika', 'Teknologi Informasi'),
('SI', 'Sistem Informasi', 'Teknologi Informasi');

INSERT INTO mahasiswa (jurusan_id, nama, nim, created_at) VALUES
(1, 'Budi Santoso', '202401001', CURDATE()),
(2, 'Siti Aminah', '202401002', CURDATE());

INSERT INTO matakuliah (jurusan_id, kode_mk, nama_mk, sks, semester) VALUES
(1, 'IT027', 'Pemrograman Database Client Server', 3, 3);
