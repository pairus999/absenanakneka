ABSENSI FINGERPRINT - FINAL READY
================================

ISI:
- Website PHP/MySQL
- Admin login + data siswa
- Enroll fingerprint dari website melalui device_commands
- API device untuk ESP32
- ESP32 + AS608 + LCD I2C + buzzer
- Absensi fingerprint dan pencatatan ke MySQL

1. DATABASE
-----------
Jangan hapus database website yang sudah berjalan.

Di phpMyAdmin database hosting yang dipakai website, jalankan:
- admin/database_kode_akses.sql
- admin/database_no_hp.sql
- database_enroll.sql

Jika kolom no_hp/kode_akses sudah ada, jangan jalankan ALTER yang sama dua kali.

2. UPLOAD WEBSITE
------------------
Upload folder/file dengan struktur:
/absensi/
  config.php
  api/
    device.php
  admin/
    enroll.php
    siswa-admin.php
    ...

config.php harus berisi koneksi database hosting milik Anda.

3. API KEY
----------
API key di:
- api/device.php
- esp32/absensi_fingerprint_FINAL.ino
harus sama.

4. ESP32 / AS608
----------------
Pin default sketch FINAL:
AS608 TX -> ESP32 GPIO18 (RX)
AS608 RX -> ESP32 GPIO19 (TX)
AS608 VCC -> 5V sesuai modul/sensor Anda
AS608 GND -> GND
LCD SDA -> GPIO26
LCD SCL -> GPIO25
Buzzer -> GPIO15

PENTING: TX sensor harus masuk ke RX ESP32 dan RX sensor ke TX ESP32.
Jika AS608 tidak terdeteksi, cek juga tegangan dan GND bersama.

5. CARA ENROLL
--------------
A. Admin > Data Siswa
B. Tambahkan siswa.
C. Tentukan Fingerprint ID, misalnya 1.
D. Klik ENROLL.
E. ESP32 harus online dan terhubung internet.
F. Dalam sekitar 2 detik ESP32 mengambil command.
G. LCD: TEMPELKAN JARI PERTAMA.
H. Tempel jari, angkat saat LCD meminta.
I. Tempel jari yang SAMA untuk kedua kalinya.
J. Jika berhasil LCD menunjukkan ENROLL BERHASIL.
K. Website akan berubah dari PROCESSING menjadi SUCCESS.

6. ABSENSI
----------
Setelah enroll sukses, tempelkan jari.
ESP32 mencari fingerprint ID di AS608, lalu mengirim fingerprint_id ke API.
API mencari siswa berdasarkan fingerprint_id dan mencatat absensi hari itu.
Satu siswa hanya bisa tercatat sekali per hari.

7. JIKA COMMAND TETAP PENDING
------------------------------
Periksa:
- ESP32 benar-benar tersambung WiFi.
- SERVER_BASE benar dan dapat diakses dari internet.
- API key sama.
- /absensi/api/device.php bisa diakses oleh ESP32.
- Serial Monitor 115200.
- Muncul [AS608] OK.
- Muncul [WIFI] OK IP: ...
- Setelah command dibuat harus muncul [COMMAND] ...

8. JIKA LCD TIDAK BERUBAH SAAT ENROLL
--------------------------------------
Kemungkinan ESP32 belum menerima command. Lihat Serial Monitor.
Normalnya akan muncul:
[COMMAND] id=... type=ENROLL fingerprint=...
kemudian LCD masuk proses enroll.

9. CATATAN
-----------
- Sketch menggunakan HardwareSerial ESP32 UART2.
- Kecepatan AS608 57600.
- ID fingerprint yang digunakan untuk enroll harus sama dengan fingerprint_id di tabel siswa.
- ID 1-127 didukung oleh sketch ini.
- Command PROCESSING yang tertinggal lebih dari 2 menit akan otomatis dikembalikan menjadi PENDING oleh API.
