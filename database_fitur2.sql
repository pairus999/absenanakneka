-- =========================================================
-- FITUR: Absen Pulang, dan penunjang lainnya
-- Jalankan sekali lewat phpMyAdmin.
-- =========================================================

-- Jam pulang disimpan di kolom terpisah pada baris absen yang sama
-- (satu baris per siswa per hari, bukan baris baru).
ALTER TABLE absensi ADD COLUMN IF NOT EXISTS jam_pulang TIME NULL;
