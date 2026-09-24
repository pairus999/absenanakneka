<?php

require_once "config.php";
require_once "icons.php";


/* =========================================
   TOTAL SISWA
========================================= */

$result = $conn->query(
    "SELECT COUNT(*) AS total
     FROM siswa"
);

$totalSiswa = 0;

if ($result) {

    $data = $result->fetch_assoc();

    $totalSiswa =
        (int) $data["total"];

}


/* =========================================
   TOTAL ABSENSI
========================================= */

$result = $conn->query(
    "SELECT COUNT(*) AS total
     FROM absensi"
);

$totalAbsensi = 0;

if ($result) {

    $data = $result->fetch_assoc();

    $totalAbsensi =
        (int) $data["total"];

}


/* =========================================
   ABSENSI HARI INI
========================================= */

$result = $conn->query(
    "SELECT COUNT(*) AS total
     FROM absensi
     WHERE tanggal = CURDATE()"
);

$absensiHariIni = 0;

if ($result) {

    $data = $result->fetch_assoc();

    $absensiHariIni =
        (int) $data["total"];

}


/* =========================================
   ABSENSI TERBARU
========================================= */

$recentAbsensi = $conn->query(

    "SELECT
        a.fingerprint_id,
        s.nama,
        s.kelas,
        a.tanggal,
        a.jam,
        a.status

     FROM absensi a

     LEFT JOIN siswa s
        ON a.fingerprint_id =
           s.fingerprint_id

     ORDER BY
        a.tanggal DESC,
        a.jam DESC

     LIMIT 5"

);

?>
<!doctype html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard · Absensi Fingerprint</title>
<link rel="stylesheet" href="style.css">
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
        <a href="index.php" class="active"><?= ic('home') ?> <span>Dashboard</span></a>
        <a href="siswa.php"><?= ic('users') ?> <span>Data Siswa</span></a>
        <a href="absensi.php"><?= ic('clipboard') ?> <span>Data Absensi</span></a>
        <a href="cek.php"><?= ic('search') ?> <span>Cek Absen Saya</span></a>
        <a href="tentang.php"><?= ic('inbox') ?> <span>Tentang Sistem</span></a>
    </nav>
    <div class="sidebar-footer">Sistem Absensi</div>
</div>

<div class="main">

    <div class="topbar">
        <div>
            <h1>Dashboard</h1>
            <p>Sistem informasi absensi siswa</p>
        </div>
        <div class="status-online"><span></span> Sistem Online</div>
    </div>

    <section class="dashboard-hero">
        <div class="hero-content">
            <div class="hero-small"><span></span> Sistem Absensi Aktif</div>
            <div class="hero-title">Selamat datang</div>
            <div class="hero-description">
                Pantau data siswa dan kehadiran secara cepat melalui sistem absensi fingerprint.
            </div>
            <div class="hero-actions">
                <a href="siswa.php" class="hero-button primary"><?= ic('users') ?> Data Siswa</a>
                <a href="absensi.php" class="hero-button secondary"><?= ic('clipboard') ?> Data Absensi</a>
            </div>
        </div>
    </section>

    <div class="dashboard-stats">

        <div class="stat-card">
            <div class="stat-top">
                <div class="stat-icon"><?= ic('users') ?></div>
                <div class="stat-tag">Database</div>
            </div>
            <div class="stat-title">Total Siswa</div>
            <div class="stat-number"><?= $totalSiswa ?></div>
        </div>

        <div class="stat-card">
            <div class="stat-top">
                <div class="stat-icon green"><?= ic('clipboard') ?></div>
                <div class="stat-tag">Record</div>
            </div>
            <div class="stat-title">Total Absensi</div>
            <div class="stat-number"><?= $totalAbsensi ?></div>
        </div>

        <div class="stat-card">
            <div class="stat-top">
                <div class="stat-icon orange"><?= ic('clock') ?></div>
                <div class="stat-tag">Today</div>
            </div>
            <div class="stat-title">Absensi Hari Ini</div>
            <div class="stat-number"><?= $absensiHariIni ?></div>
        </div>

    </div>

    <div class="dashboard-grid">

        <div class="dashboard-box">
            <div class="box-header">
                <div>
                    <div class="box-title">Aktivitas Terbaru</div>
                    <div class="box-subtitle">Lima absensi terakhir</div>
                </div>
                <a href="absensi.php" class="view-all">Lihat semua <?= ic('arrow-right') ?></a>
            </div>

            <div class="activity-list">
                <?php if ($recentAbsensi && $recentAbsensi->num_rows > 0): ?>
                    <?php while ($row = $recentAbsensi->fetch_assoc()): ?>
                        <div class="activity-item">
                            <div class="activity-avatar"><?= ic('user') ?></div>
                            <div class="activity-info">
                                <div class="activity-name"><?= htmlspecialchars($row["nama"] ?? "Siswa") ?></div>
                                <div class="activity-class">
                                    <?= htmlspecialchars($row["kelas"] ?? "-") ?>
                                    · Fingerprint #<?= htmlspecialchars($row["fingerprint_id"]) ?>
                                </div>
                            </div>
                            <div class="activity-time">
                                <div class="activity-hour"><?= htmlspecialchars($row["jam"] ?? "-") ?></div>
                                <div class="activity-date"><?= htmlspecialchars($row["tanggal"] ?? "-") ?></div>
                                <div class="activity-status"><?= htmlspecialchars($row["status"] ?? "Hadir") ?></div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="no-activity">
                        <?= ic('clipboard') ?>
                        Belum ada aktivitas absensi.
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="quick-box">
            <div class="box-header">
                <div>
                    <div class="box-title">Akses Cepat</div>
                    <div class="box-subtitle">Menu utama sistem</div>
                </div>
            </div>

            <div class="quick-list">
                <a href="siswa.php" class="quick-link">
                    <div class="quick-link-icon"><?= ic('users') ?></div>
                    <div class="quick-link-text">
                        <div class="quick-link-title">Data Siswa</div>
                        <div class="quick-link-description">Lihat daftar siswa</div>
                    </div>
                    <div class="quick-arrow"><?= ic('arrow-right') ?></div>
                </a>

                <a href="absensi.php" class="quick-link">
                    <div class="quick-link-icon"><?= ic('clipboard') ?></div>
                    <div class="quick-link-text">
                        <div class="quick-link-title">Data Absensi</div>
                        <div class="quick-link-description">Lihat riwayat hadir</div>
                    </div>
                    <div class="quick-arrow"><?= ic('arrow-right') ?></div>
                </a>
            </div>

            <div class="system-status">
                <div class="system-status-top">
                    <div class="system-status-dot"></div>
                    <div class="system-status-title">Sistem Online</div>
                </div>
                <div class="system-status-text">
                    Database dan sistem absensi siap digunakan.
                </div>
            </div>
        </div>

    </div>

    <div class="footer">Sistem Absensi Fingerprint · Public System</div>

</div>

<script src="theme.js"></script>
</body>
</html>
