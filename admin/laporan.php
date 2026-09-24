<?php
require_once "auth.php";
require_once "icons.php";
require_once "../config.php";
require_once "../settings.php";

function h($v){return htmlspecialchars((string)$v,ENT_QUOTES,"UTF-8");}

$dari = trim($_GET["dari"] ?? date("Y-m-01"));
$sampai = trim($_GET["sampai"] ?? date("Y-m-d"));
$kelas = trim($_GET["kelas"] ?? "");
$export = $_GET["export"] ?? "";

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dari)) $dari = date("Y-m-01");
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $sampai)) $sampai = date("Y-m-d");
if ($dari > $sampai) { $tmp = $dari; $dari = $sampai; $sampai = $tmp; }

$kelasList = $conn->query("SELECT DISTINCT kelas FROM siswa ORDER BY kelas ASC");

$sql = "SELECT s.id, s.fingerprint_id, s.nama, s.kelas,
        SUM(CASE WHEN a.status='Hadir' THEN 1 ELSE 0 END) AS hadir,
        SUM(CASE WHEN a.status='Terlambat' THEN 1 ELSE 0 END) AS terlambat,
        SUM(CASE WHEN a.status='Izin' THEN 1 ELSE 0 END) AS izin,
        SUM(CASE WHEN a.status='Sakit' THEN 1 ELSE 0 END) AS sakit,
        SUM(CASE WHEN a.status='Alpa' THEN 1 ELSE 0 END) AS alpa
        FROM siswa s
        LEFT JOIN absensi a ON a.fingerprint_id = s.fingerprint_id
            AND a.tanggal BETWEEN ? AND ?";
$types = "ss";
$params = [$dari, $sampai];

if ($kelas !== "") {
    $sql .= " WHERE s.kelas = ?";
    $types .= "s";
    $params[] = $kelas;
}

$sql .= " GROUP BY s.id ORDER BY CAST(s.fingerprint_id AS UNSIGNED) ASC";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

$rows = [];
while ($r = $result->fetch_assoc()) $rows[] = $r;

/* ================== EXPORT EXCEL ================== */
if ($export === "excel") {
    header("Content-Type: application/vnd.ms-excel; charset=utf-8");
    header("Content-Disposition: attachment; filename=laporan-absensi-" . $dari . "_" . $sampai . ".xls");
    echo "\xEF\xBB\xBF";
    echo "<table border='1'>";
    echo "<tr><th colspan='9'>Laporan Absensi - " . h($dari) . " s/d " . h($sampai) . ($kelas ? " - Kelas " . h($kelas) : "") . "</th></tr>";
    echo "<tr><th>No</th><th>Fingerprint ID</th><th>Nama</th><th>Kelas</th><th>Hadir</th><th>Terlambat</th><th>Izin</th><th>Sakit</th><th>Alpa</th></tr>";
    $no = 1;
    foreach ($rows as $r) {
        echo "<tr>";
        echo "<td>" . $no++ . "</td>";
        echo "<td>" . h($r["fingerprint_id"]) . "</td>";
        echo "<td>" . h($r["nama"]) . "</td>";
        echo "<td>" . h($r["kelas"]) . "</td>";
        echo "<td>" . h($r["hadir"]) . "</td>";
        echo "<td>" . h($r["terlambat"]) . "</td>";
        echo "<td>" . h($r["izin"]) . "</td>";
        echo "<td>" . h($r["sakit"]) . "</td>";
        echo "<td>" . h($r["alpa"]) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    exit;
}

// Preset cepat untuk memudahkan (bulan ini, bulan lalu)
$presetBulanIni = ['dari' => date('Y-m-01'), 'sampai' => date('Y-m-d')];
$presetBulanLalu = ['dari' => date('Y-m-01', strtotime('first day of last month')), 'sampai' => date('Y-m-t', strtotime('last day of last month'))];
?>
<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Laporan · Admin</title>
<link rel="stylesheet" href="admin-style.css">
<style>
@media print{
  .sidebar,.topbar,.no-print{display:none !important}
  .main{margin-left:0 !important;padding:0 !important}
  .panel{box-shadow:none;border:none}
  body{background:#fff}
}
.preset-links{display:flex;gap:8px;flex-wrap:wrap;margin-top:8px}
.preset-links a{font-size:11.5px;color:var(--accent-dark);text-decoration:none;font-weight:700;padding:5px 10px;border-radius:8px;background:var(--accent-soft)}
.preset-links a:hover{background:var(--accent);color:#fff}
</style>
</head>
<body>

<aside class="sidebar no-print">
<div class="brand"><?= ic('fingerprint') ?> ABSENSI</div>
<div class="subtitle">Fingerprint System</div>
<nav class="nav">
<a href="dashboard.php"><?= ic('grid') ?> Dashboard</a>
<a href="siswa-admin.php"><?= ic('users') ?> Data Siswa</a>
<a href="absensi-admin.php"><?= ic('clipboard') ?> Data Absensi</a>
<a class="active" href="laporan.php"><?= ic('chart-bar') ?> Laporan</a>
<a href="enroll.php"><?= ic('fingerprint') ?> Enroll Fingerprint</a>
<a href="pengaturan.php"><?= ic('sliders') ?> Pengaturan</a>
<a class="logout" href="logout.php"><?= ic('logout') ?> Keluar</a>
</nav>
</aside>

<main class="main">

<div class="topbar no-print">
<div class="title"><h1>Laporan</h1><p>Rekap kehadiran per siswa, rentang tanggal bebas</p></div>
<div class="user-pill"><?= ic('chart-bar') ?> Laporan</div>
</div>

<div class="panel">
<h2 class="no-print"><?= ic('calendar') ?> Filter Laporan</h2>
<form method="get" class="filter no-print" style="margin-top:14px">
<div class="field">
<label>Dari Tanggal</label>
<input type="date" name="dari" value="<?= h($dari) ?>" required>
</div>
<div class="field">
<label>Sampai Tanggal</label>
<input type="date" name="sampai" value="<?= h($sampai) ?>" required>
</div>
<div class="field">
<label>Kelas</label>
<select name="kelas" style="padding:11px 13px;border:1.5px solid var(--border);border-radius:10px;background:var(--surface-2)">
<option value="">Semua Kelas</option>
<?php if ($kelasList): while ($k = $kelasList->fetch_assoc()): ?>
<option value="<?= h($k["kelas"]) ?>" <?= $k["kelas"] === $kelas ? "selected" : "" ?>><?= h($k["kelas"]) ?></option>
<?php endwhile; endif; ?>
</select>
</div>
<button class="btn" type="submit" style="align-self:flex-end"><?= ic('search') ?> Tampilkan</button>
<a class="btn green" style="align-self:flex-end" href="laporan.php?dari=<?= $dari ?>&sampai=<?= $sampai ?>&kelas=<?= urlencode($kelas) ?>&export=excel"><?= ic('download') ?> Export Excel</a>
<button class="btn gray" type="button" style="align-self:flex-end" onclick="window.print()"><?= ic('print') ?> Cetak / PDF</button>
</form>
<div class="preset-links no-print">
<a href="laporan.php?dari=<?= $presetBulanIni['dari'] ?>&sampai=<?= $presetBulanIni['sampai'] ?>&kelas=<?= urlencode($kelas) ?>">Bulan Ini</a>
<a href="laporan.php?dari=<?= $presetBulanLalu['dari'] ?>&sampai=<?= $presetBulanLalu['sampai'] ?>&kelas=<?= urlencode($kelas) ?>">Bulan Lalu</a>
</div>

<h2 style="margin-top:22px"><?= ic('chart-bar') ?> Laporan <?= h($dari) ?> s/d <?= h($sampai) ?><?= $kelas ? " · Kelas " . h($kelas) : "" ?></h2>

<div class="table-wrap">
<table>
<thead><tr><th>No</th><th>Fingerprint</th><th>Nama</th><th>Kelas</th><th>Hadir</th><th>Terlambat</th><th>Izin</th><th>Sakit</th><th>Alpa</th></tr></thead>
<tbody>
<?php if (count($rows)): $no = 1; ?>
<?php foreach ($rows as $r): ?>
<tr>
<td><?= $no++ ?></td>
<td class="mono">#<?= h($r["fingerprint_id"]) ?></td>
<td><?= h($r["nama"]) ?></td>
<td><?= h($r["kelas"]) ?></td>
<td><span class="badge done"><?= (int)$r["hadir"] ?></span></td>
<td><span class="badge pending"><?= (int)$r["terlambat"] ?></span></td>
<td><span class="badge processing"><?= (int)$r["izin"] ?></span></td>
<td><span class="badge processing"><?= (int)$r["sakit"] ?></span></td>
<td><span class="badge failed"><?= (int)$r["alpa"] ?></span></td>
</tr>
<?php endforeach; ?>
<?php else: ?>
<tr><td colspan="9" style="text-align:center;padding:25px;color:var(--muted)">Belum ada siswa terdaftar.</td></tr>
<?php endif; ?>
</tbody>
</table>
</div>
</div>

</main>
<script src="../theme.js"></script>
</body>
</html>
