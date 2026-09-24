<?php
require_once "auth.php";
require_once "icons.php";
require_once "../config.php";
require_once "../settings.php";

function h($v){return htmlspecialchars((string)$v,ENT_QUOTES,"UTF-8");}

$msg = "";
$err = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $action = $_POST["action"] ?? "";

    if ($action === "simpan_jam") {
        $jam = trim($_POST["jam_masuk_max"] ?? "");
        if (!preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $jam)) {
            $err = "Format jam tidak valid.";
        } else {
            if (strlen($jam) === 5) $jam .= ":00";
            setSetting($conn, "jam_masuk_max", $jam);
            header("Location: pengaturan.php?ok=jam");
            exit;
        }
    }

    if ($action === "simpan_offline") {
        $detik = (int)($_POST["offline_threshold_detik"] ?? 60);
        if ($detik < 20) $detik = 20;
        setSetting($conn, "offline_threshold_detik", (string)$detik);
        header("Location: pengaturan.php?ok=offline");
        exit;
    }

    if ($action === "tambah_libur") {
        $tanggal = trim($_POST["tanggal"] ?? "");
        $keterangan = trim($_POST["keterangan"] ?? "");
        if ($tanggal === "" || $keterangan === "") {
            $err = "Tanggal dan keterangan hari libur wajib diisi.";
        } else {
            $q = $conn->prepare(
                "INSERT INTO hari_libur (tanggal, keterangan) VALUES (?,?)
                 ON DUPLICATE KEY UPDATE keterangan=VALUES(keterangan)"
            );
            $q->bind_param("ss", $tanggal, $keterangan);
            if ($q->execute()) {
                header("Location: pengaturan.php?ok=libur");
                exit;
            }
            $err = "Gagal menyimpan hari libur.";
        }
    }

    if ($action === "proses_alpa") {
        $tanggal = trim($_POST["tanggal_alpa"] ?? "");
        if ($tanggal === "") {
            $err = "Pilih tanggal untuk diproses.";
        } else {
            $r = processAlpaForDate($conn, $tanggal);
            if (!$r["processed"]) {
                $msg = "Tanggal " . h($tanggal) . " adalah hari libur/Minggu, tidak diproses.";
            } else {
                $msg = "Rekap Alpa untuk " . h($tanggal) . " selesai: " . $r["inserted"] . " siswa ditandai Alpa.";
            }
        }
    }
}

if (isset($_GET["hapus_libur"])) {
    $id = (int)$_GET["hapus_libur"];
    if ($id > 0) {
        $q = $conn->prepare("DELETE FROM hari_libur WHERE id=?");
        $q->bind_param("i", $id);
        $q->execute();
    }
    header("Location: pengaturan.php?ok=hapuslibur");
    exit;
}

$ok = $_GET["ok"] ?? "";
if ($ok === "jam") $msg = "Batas jam masuk berhasil diperbarui.";
if ($ok === "offline") $msg = "Ambang batas status offline berhasil diperbarui.";
if ($ok === "libur") $msg = "Hari libur berhasil disimpan.";
if ($ok === "hapuslibur") $msg = "Hari libur berhasil dihapus.";

$jamMasukMax = substr(getSetting($conn, "jam_masuk_max", "07:15:00"), 0, 5);
$offlineThreshold = getSetting($conn, "offline_threshold_detik", "60");

$liburList = $conn->query("SELECT id,tanggal,keterangan FROM hari_libur ORDER BY tanggal ASC");
?>
<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Pengaturan · Admin</title>
<link rel="stylesheet" href="admin-style.css">
</head>
<body>

<aside class="sidebar">
<div class="brand"><?= ic('fingerprint') ?> ABSENSI</div>
<div class="subtitle">Fingerprint System</div>
<nav class="nav">
<a href="dashboard.php"><?= ic('grid') ?> Dashboard</a>
<a href="siswa-admin.php"><?= ic('users') ?> Data Siswa</a>
<a href="absensi-admin.php"><?= ic('clipboard') ?> Data Absensi</a>
<a href="laporan.php"><?= ic('chart-bar') ?> Laporan</a>
<a href="enroll.php"><?= ic('fingerprint') ?> Enroll Fingerprint</a>
<a class="active" href="pengaturan.php"><?= ic('sliders') ?> Pengaturan</a>
<a class="logout" href="logout.php"><?= ic('logout') ?> Keluar</a>
</nav>
</aside>

<main class="main">

<div class="topbar">
<div class="title"><h1>Pengaturan</h1><p>Atur jam masuk, status alat, dan kalender libur</p></div>
<div class="user-pill"><?= ic('sliders') ?> Pengaturan</div>
</div>

<?php if($msg): ?><div class="alert success"><?= ic('check') ?><?= h($msg) ?></div><?php endif; ?>
<?php if($err): ?><div class="alert error"><?= ic('alert') ?><?= h($err) ?></div><?php endif; ?>

<div class="panel">
<h2><?= ic('sliders') ?> Batas Jam Masuk</h2>
<p class="note">Absen yang tercatat di atas jam ini otomatis berstatus <strong>Terlambat</strong>. Berlaku langsung untuk absen berikutnya, tanpa perlu mengubah alat ESP32.</p>
<form method="post" class="filter" style="margin-top:14px">
<input type="hidden" name="action" value="simpan_jam">
<div class="field" style="min-width:160px">
<label>Jam Masuk Maksimal</label>
<input type="time" name="jam_masuk_max" value="<?= h($jamMasukMax) ?>" required>
</div>
<button class="btn" type="submit" style="align-self:flex-end"><?= ic('save') ?> Simpan</button>
</form>
</div>

<div class="panel">
<h2><?= ic('signal') ?> Status Alat</h2>
<p class="note">Alat dianggap offline jika tidak ada kontak (heartbeat/absen) selama lebih dari nilai ini.</p>
<form method="post" class="filter" style="margin-top:14px">
<input type="hidden" name="action" value="simpan_offline">
<div class="field" style="min-width:160px">
<label>Ambang Batas Offline (detik)</label>
<input type="number" name="offline_threshold_detik" min="20" step="5" value="<?= h($offlineThreshold) ?>" required>
</div>
<button class="btn" type="submit" style="align-self:flex-end"><?= ic('save') ?> Simpan</button>
</form>
</div>

<div class="panel">
<h2><?= ic('history') ?> Proses Rekap Alpa Manual</h2>
<p class="note">Rekap Alpa berjalan otomatis setiap Dashboard dibuka (memproses hari-hari yang terlewat). Gunakan form ini kalau ingin memproses ulang tanggal tertentu secara manual.</p>
<form method="post" class="filter" style="margin-top:14px">
<input type="hidden" name="action" value="proses_alpa">
<div class="field" style="min-width:160px">
<label>Tanggal</label>
<input type="date" name="tanggal_alpa" value="<?= h(date('Y-m-d', strtotime('-1 day'))) ?>" required>
</div>
<button class="btn gray" type="submit" style="align-self:flex-end"><?= ic('reset') ?> Proses Sekarang</button>
</form>
</div>

<div class="panel">
<h2><?= ic('download') ?> Backup Database</h2>
<p class="note">Download salinan seluruh data (siswa, absensi, pengaturan, hari libur) dalam satu file .sql. Simpan berkala untuk jaga-jaga.</p>
<a class="btn green" href="backup.php" style="margin-top:14px;display:inline-flex"><?= ic('download') ?> Download Backup Sekarang</a>
</div>

<div class="panel">
<h2><?= ic('calendar') ?> Kalender Hari Libur</h2>
<p class="note">Tanggal di daftar ini (dan setiap hari Minggu) tidak akan ditandai Alpa otomatis.</p>

<form method="post" class="filter" style="margin-top:14px">
<input type="hidden" name="action" value="tambah_libur">
<div class="field" style="min-width:160px">
<label>Tanggal</label>
<input type="date" name="tanggal" required>
</div>
<div class="field" style="flex:1;min-width:220px">
<label>Keterangan</label>
<input type="text" name="keterangan" placeholder="Contoh: Libur Semester Ganjil" required>
</div>
<button class="btn green" type="submit" style="align-self:flex-end"><?= ic('plus') ?> Tambah</button>
</form>

<div class="table-wrap">
<table>
<thead><tr><th>Tanggal</th><th>Keterangan</th><th>Aksi</th></tr></thead>
<tbody>
<?php if ($liburList && $liburList->num_rows): ?>
<?php while ($l = $liburList->fetch_assoc()): ?>
<tr>
<td class="mono"><?= h($l["tanggal"]) ?></td>
<td><?= h($l["keterangan"]) ?></td>
<td>
<a class="btn red small" href="pengaturan.php?hapus_libur=<?= h($l["id"]) ?>" onclick="return confirm('Hapus hari libur ini?')"><?= ic('trash') ?> Hapus</a>
</td>
</tr>
<?php endwhile; ?>
<?php else: ?>
<tr><td colspan="3" style="text-align:center;padding:25px;color:var(--muted)">Belum ada hari libur yang didaftarkan.</td></tr>
<?php endif; ?>
</tbody>
</table>
</div>
</div>

</main>
<script src="../theme.js"></script>
</body>
</html>
