<?php

require_once "auth.php";
require_once "config.php";
require_once "icons.php";

$id = intval($_GET["id"] ?? 0);

if ($id <= 0) {

    header("Location: siswa.php");
    exit;

}


/* AMBIL DATA */

$stmt = $conn->prepare(
    "SELECT *
     FROM siswa
     WHERE id = ?
     LIMIT 1"
);

$stmt->bind_param(
    "i",
    $id
);

$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows === 0) {

    header("Location: siswa.php");
    exit;

}

$siswa = $result->fetch_assoc();

$error = "";


/* UPDATE */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $fingerprint = intval(
        $_POST["fingerprint_id"] ?? 0
    );

    $nama = trim(
        $_POST["nama"] ?? ""
    );

    $kelas = trim(
        $_POST["kelas"] ?? ""
    );

    $hp = trim(
        $_POST["no_hp"] ?? ""
    );


    if ($fingerprint <= 0) {

        $error =
            "Fingerprint ID tidak valid.";

    } elseif ($nama === "") {

        $error =
            "Nama siswa wajib diisi.";

    } elseif ($kelas === "") {

        $error =
            "Kelas wajib diisi.";

    } elseif ($hp === "") {

        $error =
            "Nomor HP wajib diisi.";

    } else {


        /* CEK FINGERPRINT */

        $cek = $conn->prepare(
            "SELECT id, nama
             FROM siswa
             WHERE fingerprint_id = ?
             AND id != ?
             LIMIT 1"
        );

        $cek->bind_param(
            "ii",
            $fingerprint,
            $id
        );

        $cek->execute();

        $hasil =
            $cek->get_result();


        if ($hasil->num_rows > 0) {

            $orangLain =
                $hasil->fetch_assoc();

            $error =
                "Fingerprint ID " .
                $fingerprint .
                " sudah digunakan oleh " .
                $orangLain["nama"] .
                ".";

        } else {


            /* UPDATE DATA */

            $update = $conn->prepare(
                "UPDATE siswa
                 SET
                    fingerprint_id = ?,
                    nama = ?,
                    kelas = ?,
                    no_hp = ?
                 WHERE id = ?"
            );

            $update->bind_param(
                "isssi",
                $fingerprint,
                $nama,
                $kelas,
                $hp,
                $id
            );


            if ($update->execute()) {

                header(
                    "Location: siswa.php"
                );

                exit;

            } else {

                $error =
                    "Gagal memperbarui data.";

            }

        }

    }

}

?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Edit Siswa · Absensi Fingerprint</title>
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
        <a href="logout.php" class="logout-button"><?= ic('close') ?> <span>Logout</span></a>
    </nav>
    <div class="sidebar-footer">Sistem Absensi</div>
</div>

<div class="main">

    <div class="header">
        <div>
            <h1>Edit Siswa</h1>
            <p>Ubah data siswa</p>
        </div>
    </div>

    <div class="panel form-panel">

        <?php if ($error): ?>
            <div class="error"><?= ic('alert') ?><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST">

            <label>Fingerprint ID</label>
            <input type="number" name="fingerprint_id" min="1" required
                value="<?= htmlspecialchars($_POST["fingerprint_id"] ?? $siswa["fingerprint_id"]) ?>">

            <label>Nama Siswa</label>
            <input type="text" name="nama" required
                value="<?= htmlspecialchars($_POST["nama"] ?? $siswa["nama"]) ?>">

            <label>Kelas</label>
            <input type="text" name="kelas" required
                value="<?= htmlspecialchars($_POST["kelas"] ?? $siswa["kelas"]) ?>">

            <label>Nomor HP</label>
            <input type="text" name="no_hp" placeholder="Contoh: 081234567890" required
                value="<?= htmlspecialchars($_POST["no_hp"] ?? $siswa["no_hp"]) ?>">

            <div class="form-actions">
                <button type="submit" class="btn-save"><?= ic('save') ?> Simpan Perubahan</button>
                <a href="siswa.php" class="btn-cancel"><?= ic('close') ?> Batal</a>
            </div>

        </form>

    </div>

</div>

</body>
</html>
