-- =========================================================
-- FITUR BARU: pengaturan, hari libur, status terlambat/alpa
-- Jalankan file ini sekali lewat phpMyAdmin / mysql client.
-- =========================================================

-- Tabel pengaturan umum (key-value)
CREATE TABLE IF NOT EXISTS pengaturan (
  `key` VARCHAR(50) NOT NULL PRIMARY KEY,
  `value` VARCHAR(255) NOT NULL
);

INSERT INTO pengaturan (`key`,`value`) VALUES
  ('jam_masuk_max', '07:15:00'),
  ('offline_threshold_detik', '60'),
  ('last_alpa_process_date', '')
ON DUPLICATE KEY UPDATE `value` = `value`;

-- Tabel hari libur / kalender akademik
CREATE TABLE IF NOT EXISTS hari_libur (
  id INT AUTO_INCREMENT PRIMARY KEY,
  tanggal DATE NOT NULL UNIQUE,
  keterangan VARCHAR(150) NOT NULL
);

-- Absen "Alpa" tidak punya jam, jadi kolom jam perlu boleh kosong
ALTER TABLE absensi MODIFY jam TIME NULL;
