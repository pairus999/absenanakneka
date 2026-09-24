<?php

require_once "config.php";
require_once "icons.php";


$cari = trim(
    $_GET["cari"] ?? ""
);


if ($cari !== "") {

    $like = "%" . $cari . "%";


    $stmt = $conn->prepare(

        "SELECT
            fingerprint_id,
            nama,
            kelas,
            kode_akses

         FROM siswa

         WHERE
            CAST(fingerprint_id AS CHAR) LIKE ?
            OR nama LIKE ?
            OR kelas LIKE ?
            OR kode_akses LIKE ?

         ORDER BY
            CAST(fingerprint_id AS UNSIGNED) ASC"

    );


    $stmt->bind_param(

        "ssss",

        $like,
        $like,
        $like,
        $like

    );


    $stmt->execute();


    $result =
        $stmt->get_result();


} else {


    $result = $conn->query(

        "SELECT
            fingerprint_id,
            nama,
            kelas,
            kode_akses

         FROM siswa

         ORDER BY
            CAST(fingerprint_id AS UNSIGNED) ASC"

    );

}

?>
<!doctype html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Data Siswa · Absensi Fingerprint</title>
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
        <a href="siswa.php" class="active"><?= ic('users') ?> <span>Data Siswa</span></a>
        <a href="absensi.php"><?= ic('clipboard') ?> <span>Data Absensi</span></a>
        <a href="cek.php"><?= ic('search') ?> <span>Cek Absen Saya</span></a>
        <a href="tentang.php"><?= ic('inbox') ?> <span>Tentang Sistem</span></a>
    </nav>
    <div class="sidebar-footer">Sistem Absensi</div>
</div>

<div class="main">

    <div class="topbar">
        <div>
            <h1>Data Siswa</h1>
            <p>Daftar siswa yang terdaftar</p>
        </div>
        <div class="status-online"><span></span> Sistem Online</div>
    </div>

    <div class="panel">

        <div class="panel-header">
            <div>
                <h2><?= ic('users') ?> Data Siswa</h2>
                <p>Cari siswa berdasarkan nama, Fingerprint ID, nomor HP, atau kelas.</p>
            </div>

            <?php if ($cari !== ""): ?>
                <div class="result-badge">Hasil Pencarian</div>
            <?php endif; ?>
        </div>

        <form method="GET" class="search-form">
            <div class="search-input">
                <?= ic('search') ?>
                <input type="text" name="cari" value="<?= htmlspecialchars($cari) ?>"
                    placeholder="Cari nama / Fingerprint ID / Kode Akses / kelas...">
            </div>

            <button type="submit" class="button"><?= ic('search') ?> Cari</button>

            <?php if ($cari !== ""): ?>
                <a href="siswa.php" class="reset-button"><?= ic('close') ?> Reset</a>
            <?php endif; ?>
        </form>

        <?php if ($cari !== ""): ?>
            <div class="search-result-info">
                Menampilkan hasil untuk: <strong><?= htmlspecialchars($cari) ?></strong>
            </div>
        <?php endif; ?>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Fingerprint ID</th>
                        <th>Nama</th>
                        <th>Kelas</th>
                        <th>Kode Akses</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $no = 1; ?>
                    <?php if ($result && $result->num_rows > 0): ?>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><span class="number-circle"><?= $no++ ?></span></td>
                                <td><span class="fingerprint-id">#<?= htmlspecialchars($row["fingerprint_id"]) ?></span></td>
                                <td><strong><?= htmlspecialchars($row["nama"]) ?></strong></td>
                                <td><span class="class-badge"><?= htmlspecialchars($row["kelas"]) ?></span></td>
                                <td><span class="fingerprint-id"><?= htmlspecialchars($row["kode_akses"] ?? "Belum ada") ?></span></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="empty">
                                <?= ic('search') ?>
                                <strong>Data tidak ditemukan</strong>
                                <p>Coba gunakan kata pencarian lain.</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

    </div>

    <div class="footer">Sistem Absensi Fingerprint</div>

</div>

<script src="theme.js"></script>
</body>
</html>
