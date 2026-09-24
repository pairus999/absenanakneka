<?php
require_once "auth.php";
require_once "icons.php";
require_once "../config.php";
require_once "../settings.php";

function countTable($conn,$table,$where=""){if(!in_array($table,["siswa","absensi"],true))return 0;$q=$conn->query("SELECT COUNT(*) total FROM `$table`".$where);return $q?(int)$q->fetch_assoc()["total"]:0;}
$totalSiswa=countTable($conn,"siswa");$totalAbsensi=countTable($conn,"absensi");$hariIni=countTable($conn,"absensi"," WHERE tanggal=CURDATE()");

// Rekap Alpa berjalan diam-diam setiap dashboard dibuka (memproses hari yang terlewat).
runAutoAlpaBacklog($conn);

$device = getDeviceStatus($conn);

// Ranking kehadiran bulan ini: paling rajin & paling perlu perhatian.
$rankingRajin = $conn->query(
    "SELECT s.nama, s.kelas, COUNT(a.id) AS jumlah
     FROM siswa s
     LEFT JOIN absensi a ON a.fingerprint_id = s.fingerprint_id
        AND a.status IN ('Hadir','Terlambat')
        AND MONTH(a.tanggal) = MONTH(CURDATE()) AND YEAR(a.tanggal) = YEAR(CURDATE())
     GROUP BY s.id
     ORDER BY jumlah DESC, s.nama ASC
     LIMIT 5"
);

$rankingPerhatian = $conn->query(
    "SELECT s.nama, s.kelas,
        SUM(CASE WHEN a.status='Alpa' THEN 1 ELSE 0 END) AS alpa,
        SUM(CASE WHEN a.status='Terlambat' THEN 1 ELSE 0 END) AS terlambat
     FROM siswa s
     LEFT JOIN absensi a ON a.fingerprint_id = s.fingerprint_id
        AND MONTH(a.tanggal) = MONTH(CURDATE()) AND YEAR(a.tanggal) = YEAR(CURDATE())
     GROUP BY s.id
     HAVING (SUM(CASE WHEN a.status='Alpa' THEN 1 ELSE 0 END) + SUM(CASE WHEN a.status='Terlambat' THEN 1 ELSE 0 END)) > 0
     ORDER BY (SUM(CASE WHEN a.status='Alpa' THEN 1 ELSE 0 END)*2 + SUM(CASE WHEN a.status='Terlambat' THEN 1 ELSE 0 END)) DESC, s.nama ASC
     LIMIT 5"
);

// Rekap per kelas untuk hari ini.
$rekapKelas = $conn->query(
    "SELECT s.kelas,
        COUNT(DISTINCT s.id) AS total_siswa,
        SUM(CASE WHEN a.status IN ('Hadir','Terlambat') THEN 1 ELSE 0 END) AS hadir_count
     FROM siswa s
     LEFT JOIN absensi a ON a.fingerprint_id = s.fingerprint_id AND a.tanggal = CURDATE()
     GROUP BY s.kelas
     ORDER BY s.kelas ASC"
);

// Data grafik: 7 hari terakhir, jumlah per status.
$chartLabels = [];
$chartHadir = [];
$chartTerlambat = [];
$chartAlpa = [];
for ($i = 6; $i >= 0; $i--) {
    $tgl = date('Y-m-d', strtotime("-$i day"));
    $chartLabels[] = date('d/m', strtotime($tgl));
    $q = $conn->prepare("SELECT status, COUNT(*) c FROM absensi WHERE tanggal=? GROUP BY status");
    $q->bind_param("s", $tgl);
    $q->execute();
    $res = $q->get_result();
    $h = 0; $t = 0; $a = 0;
    while ($row = $res->fetch_assoc()) {
        if ($row["status"] === "Hadir") $h = (int)$row["c"];
        elseif ($row["status"] === "Terlambat") $t = (int)$row["c"];
        elseif ($row["status"] === "Alpa") $a = (int)$row["c"];
    }
    $chartHadir[] = $h; $chartTerlambat[] = $t; $chartAlpa[] = $a;
}
?><!doctype html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Dashboard Admin</title><link rel="stylesheet" href="admin-style.css">
<style>
.device-row{display:flex;align-items:center;gap:14px}
.device-dot{width:10px;height:10px;border-radius:50%;flex:none}
.device-dot.on{background:var(--success);box-shadow:0 0 0 4px var(--success-soft)}
.device-dot.off{background:var(--danger);box-shadow:0 0 0 4px var(--danger-soft)}
.device-info{flex:1}
.device-name{font-weight:700;font-size:14px}
.device-meta{color:var(--muted);font-size:12px;margin-top:3px;font-family:var(--font-mono)}
.chart-wrap{margin-top:10px;height:260px}
</style>
</head><body>
<aside class="sidebar">
<div class="brand"><?= ic('fingerprint') ?> ABSENSI</div><div class="subtitle">Fingerprint System</div>
<nav class="nav">
<a class="active" href="dashboard.php"><?= ic('grid') ?> Dashboard</a>
<a href="siswa-admin.php"><?= ic('users') ?> Data Siswa</a>
<a href="absensi-admin.php"><?= ic('clipboard') ?> Data Absensi</a>
<a href="laporan.php"><?= ic('chart-bar') ?> Laporan</a>
<a href="enroll.php"><?= ic('fingerprint') ?> Enroll Fingerprint</a>
<a href="pengaturan.php"><?= ic('sliders') ?> Pengaturan</a>
<a class="logout" href="logout.php"><?= ic('logout') ?> Keluar</a>
</nav></aside>
<main class="main">
<div class="topbar"><div class="title"><h1>Dashboard Admin</h1><p>Sistem informasi absensi siswa</p></div><div class="user-pill"><?= ic('users') ?> Admin</div></div>
<div class="cards">
<div class="stat"><div class="icon-badge"><?= ic('users') ?></div><div class="label">Total Siswa</div><div class="num"><?= $totalSiswa ?></div></div>
<div class="stat"><div class="icon-badge"><?= ic('clipboard') ?></div><div class="label">Total Absensi</div><div class="num"><?= $totalAbsensi ?></div></div>
<div class="stat"><div class="icon-badge"><?= ic('calendar') ?></div><div class="label">Absensi Hari Ini</div><div class="num"><?= $hariIni ?></div></div>
</div>

<div class="panel">
<h2><?= ic('signal') ?> Status Alat</h2>
<div class="device-row" style="margin-top:12px">
<div class="device-dot <?= $device['online'] ? 'on' : 'off' ?>"></div>
<div class="device-info">
<div class="device-name"><?= htmlspecialchars($device['device_name'] ?? 'ESP32 Fingerprint') ?> — <?= $device['online'] ? 'Online' : 'Offline' ?></div>
<div class="device-meta">
IP: <?= htmlspecialchars($device['ip_address'] ?? '-') ?> ·
Kontak terakhir: <?= $device['last_seen'] ? htmlspecialchars($device['last_seen']) : 'belum pernah' ?>
</div>
</div>
<a class="btn gray small" href="pengaturan.php"><?= ic('sliders') ?> Atur</a>
</div>
</div>

<div class="panel">
<h2><?= ic('chart-bar') ?> Tren Kehadiran 7 Hari Terakhir</h2>
<div class="chart-wrap"><canvas id="attendanceChart"></canvas></div>
</div>

<div class="panel">
<h2><?= ic('users') ?> Rekap Kehadiran per Kelas (Hari Ini)</h2>
<div class="table-wrap"><table>
<thead><tr><th>Kelas</th><th>Hadir / Total</th><th>Persentase</th></tr></thead>
<tbody>
<?php if ($rekapKelas && $rekapKelas->num_rows): while ($k = $rekapKelas->fetch_assoc()):
    $total = (int)$k["total_siswa"]; $hadir = (int)$k["hadir_count"];
    $persen = $total > 0 ? round($hadir / $total * 100) : 0;
    $barColor = $persen >= 80 ? 'var(--success)' : ($persen >= 50 ? 'var(--amber)' : 'var(--danger)');
?>
<tr>
<td><span class="class-badge"><?= htmlspecialchars($k["kelas"]) ?></span></td>
<td class="mono"><?= $hadir ?> / <?= $total ?></td>
<td>
<div style="display:flex;align-items:center;gap:10px">
<div style="flex:1;height:8px;border-radius:99px;background:var(--surface-2);overflow:hidden">
<div style="width:<?= $persen ?>%;height:100%;background:<?= $barColor ?>"></div>
</div>
<span class="mono" style="font-size:12px;min-width:38px"><?= $persen ?>%</span>
</div>
</td>
</tr>
<?php endwhile; else: ?>
<tr><td colspan="3" style="text-align:center;padding:20px;color:var(--muted)">Belum ada data siswa.</td></tr>
<?php endif; ?>
</tbody>
</table></div>
</div>

<div class="cards" style="grid-template-columns:1fr 1fr">
<div class="panel" style="margin-top:0">
<h2><?= ic('check') ?> Paling Rajin Bulan Ini</h2>
<div class="table-wrap"><table>
<thead><tr><th>Nama</th><th>Kelas</th><th>Jumlah Hadir</th></tr></thead>
<tbody>
<?php if ($rankingRajin && $rankingRajin->num_rows): while ($r = $rankingRajin->fetch_assoc()): ?>
<tr><td><?= htmlspecialchars($r["nama"]) ?></td><td><span class="class-badge"><?= htmlspecialchars($r["kelas"]) ?></span></td><td class="mono"><?= (int)$r["jumlah"] ?></td></tr>
<?php endwhile; else: ?>
<tr><td colspan="3" style="text-align:center;padding:20px;color:var(--muted)">Belum ada data.</td></tr>
<?php endif; ?>
</tbody>
</table></div>
</div>

<div class="panel" style="margin-top:0">
<h2><?= ic('alert') ?> Perlu Perhatian Bulan Ini</h2>
<div class="table-wrap"><table>
<thead><tr><th>Nama</th><th>Kelas</th><th>Alpa</th><th>Terlambat</th></tr></thead>
<tbody>
<?php if ($rankingPerhatian && $rankingPerhatian->num_rows): while ($r = $rankingPerhatian->fetch_assoc()): ?>
<tr><td><?= htmlspecialchars($r["nama"]) ?></td><td><span class="class-badge"><?= htmlspecialchars($r["kelas"]) ?></span></td><td><span class="status-badge alpa"><?= (int)$r["alpa"] ?></span></td><td><span class="status-badge late"><?= (int)$r["terlambat"] ?></span></td></tr>
<?php endwhile; else: ?>
<tr><td colspan="4" style="text-align:center;padding:20px;color:var(--muted)">Tidak ada, semua aman.</td></tr>
<?php endif; ?>
</tbody>
</table></div>
</div>
</div>

<div class="panel"><h2>Selamat datang, Admin</h2><p class="note">Pilih menu di bawah untuk mengelola sistem absensi.</p>
<div class="quick">
<a href="siswa-admin.php"><?= ic('users') ?> Kelola Data Siswa</a>
<a href="absensi-admin.php"><?= ic('clipboard') ?> Lihat Data Absensi</a>
<a href="laporan.php"><?= ic('chart-bar') ?> Laporan Bulanan</a>
<a href="enroll.php"><?= ic('fingerprint') ?> Enroll Fingerprint</a>
</div></div>
</main>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<script>
const ctx = document.getElementById('attendanceChart');
new Chart(ctx, {
  type: 'bar',
  data: {
    labels: <?= json_encode($chartLabels) ?>,
    datasets: [
      { label: 'Hadir', data: <?= json_encode($chartHadir) ?>, backgroundColor: '#0d9488' },
      { label: 'Terlambat', data: <?= json_encode($chartTerlambat) ?>, backgroundColor: '#b45309' },
      { label: 'Alpa', data: <?= json_encode($chartAlpa) ?>, backgroundColor: '#dc2626' }
    ]
  },
  options: {
    responsive: true,
    maintainAspectRatio: false,
    scales: { x: { stacked: true }, y: { stacked: true, beginAtZero: true, ticks: { precision: 0 } } },
    plugins: { legend: { position: 'bottom' } }
  }
});
</script>
<script src="../theme.js"></script>
</body></html>
