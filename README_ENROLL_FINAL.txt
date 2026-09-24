CARA PASANG VERSI FINAL

1. BACKUP WEBSITE LAMA.
2. Jalankan database_enroll.sql di phpMyAdmin DATABASE YANG SUDAH ADA.
3. Upload api/device.php ke folder /absensi/api/.
4. Upload admin/enroll.php ke /absensi/admin/.
5. Admin > Data Siswa > pastikan siswa sudah ada dengan fingerprint_id.
6. Buka admin/enroll.php, masukkan fingerprint_id, klik MULAI ENROLL.
7. ESP32 akan mengambil command maksimal sekitar 3 detik.
8. LCD akan meminta jari pertama, angkat, lalu jari kedua.
9. Setelah sukses, template tersimpan di AS608.
10. Untuk absensi, ESP32 mengirim fingerprint_id ke device.php dan device.php mencatat ke tabel absensi.

PENTING:
- Gunakan config.php website lama yang sudah berhasil konek database.
- API key di device.php dan sketch harus sama.
- Pin sketch: AS608 TX -> ESP32 GPIO16 (RX2), AS608 RX -> ESP32 GPIO17 (TX2), LCD SDA/SCL GPIO26/25, buzzer GPIO15.
- Jangan import database.sql lama untuk mengganti database yang sudah berjalan.
- Jangan mengganti absensi.php/api_absen.php lama kecuali memang diperlukan; versi ini mempertahankan file yang sudah ada.
