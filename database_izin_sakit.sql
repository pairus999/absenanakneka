-- =========================================================
-- FITUR IZIN / SAKIT
-- Jalankan sekali lewat phpMyAdmin.
-- =========================================================

-- Kolom catatan opsional (misal alasan izin, atau catatan sakit apa)
ALTER TABLE absensi ADD COLUMN IF NOT EXISTS keterangan VARCHAR(150) NULL;
