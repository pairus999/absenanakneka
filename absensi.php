<?php

require_once "config.php";
require_once "icons.php";
require_once "settings.php";


$cari = trim(
    $_GET["cari"] ?? ""
);


$tanggal = trim(
    $_GET["tanggal"] ?? ""
);


$sql = "

    SELECT

        a.fingerprint_id,
        s.nama,
        s.kelas,
        s.no_hp,
        a.tanggal,
        a.jam,
        a.jam_pulang,
        a.status

    FROM absensi a

    LEFT JOIN siswa s

        ON
        a.fingerprint_id =
        s.fingerprint_id

";


$where = [];

$params = [];

$types = "";


/* PENCARIAN */

if ($cari !== "") {

    $like = "%" . $cari . "%";


    $where[] = "

        (

            CAST(
                a.fingerprint_id
                AS CHAR
            ) LIKE ?

            OR s.nama LIKE ?

            OR s.kelas LIKE ?

            OR s.no_hp LIKE ?

        )

    ";


    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;


    $types .= "ssss";

}


/* FILTER TANGGAL */

if ($tanggal !== "") {

    $where[] =
        "a.tanggal = ?";


    $params[] =
        $tanggal;


    $types .= "s";

}


/* WHERE */

if (count($where) > 0) {

    $sql .=

        " WHERE " .

        implode(
            " AND ",
            $where
        );

}


/* URUTKAN */

$sql .= "

    ORDER BY
        a.tanggal DESC,
        a.jam DESC

";


/* QUERY */

if (count($params) > 0) {

    $stmt =
        $conn->prepare($sql);


    $stmt->bind_param(
        $types,
        ...$params
    );


    $stmt->execute();


    $result =
        $stmt->get_result();


} else {

    $result =
        $conn->query($sql);

}

?>
<!doctype html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Data Absensi · Absensi Fingerprint</title>
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
        <a href="absensi.php" class="active"><?= ic('clipboard') ?> <span>Data Absensi</span></a>
        <a href="cek.php"><?= ic('search') ?> <span>Cek Absen Saya</span></a>
        <a href="tentang.php"><?= ic('inbox') ?> <span>Tentang Sistem</span></a>
    </nav>
    <div class="sidebar-footer">Sistem Absensi</div>
</div>

<div class="main">

    <div class="topbar">
        <div>
            <h1>Data Absensi</h1>
            <p>Riwayat kehadiran siswa</p>
        </div>
        <div class="status-online"><span></span> Sistem Online</div>
    </div>

    <div class="panel">

        <div class="panel-header">
            <div>
                <h2><?= ic('clipboard') ?> Data Absensi</h2>
                <p>Cari berdasarkan nama, Fingerprint ID, No. HP, kelas, atau tanggal.</p>
            </div>
        </div>

        <form method="GET" class="search-form">
            <div class="search-input">
                <?= ic('search') ?>
                <input type="text" name="cari" value="<?= htmlspecialchars($cari) ?>"
                    placeholder="Nama / Fingerprint ID / No. HP / kelas...">
            </div>

            <input type="date" name="tanggal" value="<?= htmlspecialchars($tanggal) ?>" class="date-input">

            <button type="submit" class="button"><?= ic('search') ?> Cari</button>

            <?php if ($cari !== "" || $tanggal !== ""): ?>
                <a href="absensi.php" class="reset-button"><?= ic('close') ?> Reset</a>
            <?php endif; ?>
        </form>

        <?php if ($cari !== "" || $tanggal !== ""): ?>
            <div class="search-result-info">
                Filter aktif
                <?php if ($cari !== ""): ?>
                    · Pencarian: <strong><?= htmlspecialchars($cari) ?></strong>
                <?php endif; ?>
                <?php if ($tanggal !== ""): ?>
                    · Tanggal: <strong><?= htmlspecialchars($tanggal) ?></strong>
                <?php endif; ?>
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
                        <th>Tanggal</th>
                        <th>Jam Masuk</th>
                        <th>Jam Pulang</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $no = 1; ?>
                    <?php if ($result && $result->num_rows > 0): ?>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <?php $cls = statusBadgeClass($row["status"] ?? ""); ?>
                            <tr>
                                <td><span class="number-circle"><?= $no++ ?></span></td>
                                <td><span class="fingerprint-id">#<?= htmlspecialchars($row["fingerprint_id"]) ?></span></td>
                                <td><strong><?= htmlspecialchars($row["nama"] ?? "-") ?></strong></td>
                                <td><span class="class-badge"><?= htmlspecialchars($row["kelas"] ?? "-") ?></span></td>
                                <td><?= htmlspecialchars($row["tanggal"] ?? "-") ?></td>
                                <td><?= htmlspecialchars($row["jam"] ?? "-") ?></td>
                                <td><?= htmlspecialchars($row["jam_pulang"] ?? "-") ?></td>
                                <td><span class="status-badge <?= $cls ?>"><?= htmlspecialchars($row["status"] ?? "-") ?></span></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="empty">
                                <?= ic('clipboard') ?>
                                <strong>Data absensi tidak ditemukan</strong>
                                <p>Belum ada data yang sesuai pencarian.</p>
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
