<?php
require_once "config.php";
require_once "icons.php";

function countTable($conn,$table){$q=$conn->query("SELECT COUNT(*) total FROM `$table`");return $q?(int)$q->fetch_assoc()["total"]:0;}
$totalSiswa = countTable($conn, "siswa");
$totalAbsensi = countTable($conn, "absensi");
?>
<!doctype html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Tentang Sistem · Absensi Fingerprint</title>
<link rel="stylesheet" href="style.css">
<style>
.tech-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:14px;margin-top:16px}
.tech-item{display:flex;flex-direction:column;align-items:center;gap:10px;padding:20px 14px;border:1px solid var(--border);border-radius:var(--radius);background:var(--surface-2);text-align:center}
.tech-item .tech-icon{width:40px;height:40px;border-radius:10px;background:var(--accent-soft);color:var(--accent-dark);display:flex;align-items:center;justify-content:center}
.tech-item .tech-icon svg{width:20px;height:20px}
.tech-item .tech-name{font-weight:700;font-size:13px}
.tech-item .tech-desc{color:var(--muted);font-size:11px}
.about-hero{text-align:center;padding:40px 20px}
.about-hero h1{font-size:28px}
.about-hero p{color:var(--muted);margin-top:10px;max-width:520px;margin-left:auto;margin-right:auto;line-height:1.7}
.credit-box{display:flex;align-items:center;gap:16px;padding:20px;border:1px dashed var(--border-strong);border-radius:var(--radius);margin-top:16px;background:var(--surface-2)}
.credit-box .credit-icon{width:48px;height:48px;border-radius:50%;background:var(--accent-soft);color:var(--accent-dark);display:flex;align-items:center;justify-content:center;flex:none}
.credit-box .credit-icon svg{width:22px;height:22px}
.credit-box strong{display:block;font-size:14px}
.credit-box span{color:var(--muted);font-size:12px}
.flow-steps{display:flex;flex-direction:column;gap:0;margin-top:16px}
.flow-step{display:flex;gap:14px;padding:14px 0;border-bottom:1px solid var(--surface-2)}
.flow-step:last-child{border-bottom:none}
.flow-num{width:28px;height:28px;border-radius:50%;background:var(--accent);color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:12px;flex:none}
.flow-text strong{display:block;font-size:13.5px}
.flow-text span{color:var(--muted);font-size:12px}
</style>
</head>
<body>

<div class="sidebar">
    <div class="brand">
        <?= ic('home') ?>
        <div>
            <div class="brand-title">ABSENSI</div>
            <div class="brand-subtitle">Fingerprint System</div>
        </div>
    </div>
    <nav>
        <a href="index.php"><?= ic('home') ?> <span>Dashboard</span></a>
        <a href="siswa.php"><?= ic('users') ?> <span>Data Siswa</span></a>
        <a href="absensi.php"><?= ic('clipboard') ?> <span>Data Absensi</span></a>
        <a href="cek.php"><?= ic('search') ?> <span>Cek Absen Saya</span></a>
        <a href="tentang.php" class="active"><?= ic('inbox') ?> <span>Tentang Sistem</span></a>
    </nav>
    <div class="sidebar-footer">Sistem Absensi</div>
</div>

<div class="main">

    <div class="panel about-hero">
        <div class="hero-small" style="margin:0 auto 16px;display:inline-flex;background:var(--accent-soft);color:var(--accent-dark);border-color:transparent">
            <span style="background:var(--accent)"></span> Proyek Sekolah
        </div>
        <h1>Sistem Absensi Fingerprint</h1>
        <p>
            Sistem kehadiran siswa otomatis berbasis sidik jari, menggunakan mikrokontroler ESP32
            dan sensor fingerprint AS608, terhubung ke aplikasi web untuk pengelolaan data dan
            pemantauan kehadiran secara real-time.
        </p>
    </div>

    <div class="panel">
        <h2><?= ic('inbox') ?> Teknologi yang Digunakan</h2>
        <div class="tech-grid">
            <div class="tech-item">
                <div class="tech-icon"><?= ic('signal') ?></div>
                <div class="tech-name">ESP32</div>
                <div class="tech-desc">Mikrokontroler + WiFi</div>
            </div>
            <div class="tech-item">
                <div class="tech-icon"><?= ic('user') ?></div>
                <div class="tech-name">Sensor AS608</div>
                <div class="tech-desc">Pemindai sidik jari</div>
            </div>
            <div class="tech-item">
                <div class="tech-icon"><?= ic('clipboard') ?></div>
                <div class="tech-name">LCD I2C</div>
                <div class="tech-desc">Layar status alat</div>
            </div>
            <div class="tech-item">
                <div class="tech-icon"><?= ic('inbox') ?></div>
                <div class="tech-name">PHP + MySQL</div>
                <div class="tech-desc">Backend & database</div>
            </div>
            <div class="tech-item">
                <div class="tech-icon"><?= ic('home') ?></div>
                <div class="tech-name">HTML/CSS/JS</div>
                <div class="tech-desc">Tampilan web</div>
            </div>
            <div class="tech-item">
                <div class="tech-icon"><?= ic('arrow-right') ?></div>
                <div class="tech-name">REST API</div>
                <div class="tech-desc">Komunikasi alat &harr; server</div>
            </div>
        </div>
    </div>

    <div class="panel">
        <h2><?= ic('arrow-right') ?> Cara Kerja Sistem</h2>
        <div class="flow-steps">
            <div class="flow-step">
                <div class="flow-num">1</div>
                <div class="flow-text"><strong>Siswa menempelkan jari di sensor AS608</strong><span>ESP32 membaca dan mencocokkan pola sidik jari</span></div>
            </div>
            <div class="flow-step">
                <div class="flow-num">2</div>
                <div class="flow-text"><strong>ESP32 mengirim data ke server via WiFi</strong><span>Request dikirim ke device.php dengan API key untuk autentikasi</span></div>
            </div>
            <div class="flow-step">
                <div class="flow-num">3</div>
                <div class="flow-text"><strong>Server memproses dan menyimpan ke database</strong><span>Termasuk deteksi otomatis status Terlambat berdasarkan jam yang diatur admin</span></div>
            </div>
            <div class="flow-step">
                <div class="flow-num">4</div>
                <div class="flow-text"><strong>Data bisa dipantau lewat web</strong><span>Admin lewat panel khusus, orang tua/siswa lewat situs publik atau Kode Akses</span></div>
            </div>
        </div>
    </div>

    <div class="panel">
        <h2><?= ic('users') ?> Statistik Singkat</h2>
        <div class="dashboard-stats" style="margin-top:16px">
            <div class="stat-card">
                <div class="stat-icon"><?= ic('users') ?></div>
                <div class="stat-title">Total Siswa Terdaftar</div>
                <div class="stat-number"><?= $totalSiswa ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon green"><?= ic('clipboard') ?></div>
                <div class="stat-title">Total Data Absensi</div>
                <div class="stat-number"><?= $totalAbsensi ?></div>
            </div>
        </div>
    </div>

    <div class="panel">
        <h2><?= ic('user') ?> Dibuat Oleh</h2>
        <div class="credit-box">
            <div class="credit-icon"><?= ic('user') ?></div>
            <div>
                <strong>Fairus</strong>
                <span>XII TITL 3 · SMKN KARANGPUCUNG</span>
            </div>
        </div>
    </div>

    <div class="footer">Sistem Absensi Fingerprint · Proyek Sekolah</div>

</div>

<script src="theme.js"></script>
</body>
</html>
