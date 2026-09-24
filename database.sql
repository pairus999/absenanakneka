CREATE DATABASE IF NOT EXISTS absensi_sekolah;

USE absensi_sekolah;

CREATE TABLE siswa (
    id INT AUTO_INCREMENT PRIMARY KEY,
    fingerprint_id INT NOT NULL UNIQUE,
    nama VARCHAR(100) NOT NULL,
    kelas VARCHAR(50) NOT NULL,
    no_wa_ortu VARCHAR(20) NOT NULL
);

CREATE TABLE absensi (
    id INT AUTO_INCREMENT PRIMARY KEY,
    fingerprint_id INT NOT NULL,
    nama VARCHAR(100) NOT NULL,
    kelas VARCHAR(50) NOT NULL,
    tanggal DATE NOT NULL,
    jam TIME NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'Hadir'
);

INSERT INTO siswa
(fingerprint_id, nama, kelas, no_wa_ortu)
VALUES
(1, 'Budi', 'XI TITL 1', '628123456789'),
(2, 'Andi', 'XI TITL 1', '628987654321');