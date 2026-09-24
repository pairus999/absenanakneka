<?php

require_once "config.php";
require_once "icons.php";
require_once "settings.php";

function h($v){return htmlspecialchars((string)$v,ENT_QUOTES,"UTF-8");}

$kode = trim($_GET["kode"] ?? "");
$siswa = null;
$riwayat = [];
$err = "";

if ($kode !== "") {
    $q = $conn->prepare("SELECT fingerprint_id, nama, kelas FROM siswa WHERE kode_akses = ? LIMIT 1");
    $q->bind_param("s", $kode);
    $q->execute();
    $r = $q->get_result();

    if ($r->num_rows === 0) {
        $err = "Kode akses tidak ditemukan. Periksa kembali penulisannya.";
    } else {
        $siswa = $r->fetch_assoc();

        $q = $conn->prepare(
            "SELECT tanggal, jam, jam_pulang, status
             FROM absensi
             WHERE fingerprint_id = ?
             ORDER BY tanggal DESC
             LIMIT 30"
        );
        $q->bind_param("i", $siswa["fingerprint_id"]);
        $q->execute();
        $res = $q->get_result();
        while ($row = $res->fetch_assoc()) $riwayat[] = $row;
    }
}
?>
<!doctype html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Cek Absen Mandiri · Absensi Fingerprint</title>
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
        <a href="index.php"><?= ic('home') ?> <span>Dashboard</span></a>
        <a href="siswa.php"><?= ic('users') ?> <span>Data Siswa</span></a>
        <a href="absensi.php"><?= ic('clipboard') ?> <span>Data Absensi</span></a>
        <a href="cek.php" class="active"><?= ic('search') ?> <span>Cek Absen Saya</span></a>
        <a href="tentang.php"><?= ic('inbox') ?> <span>Tentang Sistem</span></a>
    </nav>
    <div class="sidebar-footer">Sistem Absensi</div>
</div>

<div class="main">

    <div class="topbar">
        <div>
            <h1>Cek Absen Mandiri</h1>
            <p>Masukkan Kode Akses untuk melihat riwayat kehadiran</p>
        </div>
        <div class="status-online"><span></span> Sistem Online</div>
    </div>

    <div class="panel">
        <div class="panel-header">
            <div>
                <h2><?= ic('search') ?> Masukkan Kode Akses</h2>
                <p>Kode akses diberikan oleh admin sekolah, formatnya seperti SIS-A1B2C3.</p>
            </div>
        </div>

        <form method="GET" class="search-form">
            <div class="search-input">
                <?= ic('user') ?>
                <input type="text" name="kode" value="<?= h($kode) ?>" placeholder="Contoh: SIS-A1B2C3" style="text-transform:uppercase">
            </div>
            <button type="submit" class="button"><?= ic('search') ?> Cek Sekarang</button>
        </form>

        <?php if ($err): ?>
            <div class="error"><?= ic('alert') ?><?= h($err) ?></div>
        <?php endif; ?>
    </div>

    <?php if ($siswa): ?>
    <div class="panel">
        <div class="panel-header">
            <div>
                <h2><?= ic('user') ?> <?= h($siswa["nama"]) ?></h2>
                <p>Kelas <?= h($siswa["kelas"]) ?> · Fingerprint #<?= h($siswa["fingerprint_id"]) ?></p>
            </div>
        </div>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>Jam Masuk</th>
                        <th>Jam Pulang</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($riwayat)): ?>
                        <?php foreach ($riwayat as $row): $cls = statusBadgeClass($row["status"] ?? ""); ?>
                            <tr>
                                <td><?= h($row["tanggal"]) ?></td>
                                <td><?= h($row["jam"] ?? "-") ?></td>
                                <td><?= h($row["jam_pulang"] ?? "-") ?></td>
                                <td><span class="status-badge <?= $cls ?>"><?= h($row["status"]) ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="4" class="empty"><?= ic('clipboard') ?><strong>Belum ada riwayat absen.</strong></td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <div class="footer">Sistem Absensi Fingerprint</div>

</div>

<script src="theme.js"></script>
</body>
</html>
