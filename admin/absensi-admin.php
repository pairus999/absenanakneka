<?php
require_once "auth.php";
require_once "icons.php";
require_once "../config.php";
require_once "../settings.php";

function h($v){return htmlspecialchars((string)$v,ENT_QUOTES,"UTF-8");}

$msg = "";
$err = "";

if ($_SERVER["REQUEST_METHOD"] === "POST" && ($_POST["action"] ?? "") === "izin_sakit") {
    $fid = (int)($_POST["fingerprint_id"] ?? 0);
    $tanggal = trim($_POST["tanggal"] ?? "");
    $status = $_POST["status_izin"] ?? "";
    $catatan = trim($_POST["catatan"] ?? "");

    if ($fid <= 0 || $tanggal === "" || !in_array($status, ["Izin", "Sakit"], true)) {
        $err = "Siswa, tanggal, dan status wajib diisi.";
    } else {
        $r = setIzinSakit($conn, $fid, $tanggal, $status, $catatan);
        if ($r["ok"]) { $msg = $r["message"]; } else { $err = $r["message"]; }
    }
}

$tanggal = trim($_GET["tanggal"] ?? "");
if ($tanggal !== "") {
    $stmt = $conn->prepare("SELECT * FROM absensi WHERE tanggal=? ORDER BY jam DESC");
    $stmt->bind_param("s", $tanggal);
} else {
    $stmt = $conn->prepare("SELECT * FROM absensi ORDER BY tanggal DESC, jam DESC");
}
$stmt->execute();
$result = $stmt->get_result();

$siswaList = $conn->query("SELECT fingerprint_id, nama, kelas FROM siswa ORDER BY CAST(fingerprint_id AS UNSIGNED) ASC");
?><!doctype html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Data Absensi · Admin</title><link rel="stylesheet" href="admin-style.css"></head><body>
<aside class="sidebar">
<div class="brand"><?= ic('fingerprint') ?> ABSENSI</div><div class="subtitle">Fingerprint System</div>
<nav class="nav">
<a href="dashboard.php"><?= ic('grid') ?> Dashboard</a>
<a href="siswa-admin.php"><?= ic('users') ?> Data Siswa</a>
<a class="active" href="absensi-admin.php"><?= ic('clipboard') ?> Data Absensi</a>
<a href="laporan.php"><?= ic('chart-bar') ?> Laporan</a>
<a href="enroll.php"><?= ic('fingerprint') ?> Enroll Fingerprint</a>
<a href="pengaturan.php"><?= ic('sliders') ?> Pengaturan</a>
<a class="logout" href="logout.php"><?= ic('logout') ?> Keluar</a>
</nav></aside>
<main class="main">
<div class="topbar"><div class="title"><h1>Data Absensi</h1><p>Riwayat kehadiran siswa</p></div><div class="user-pill"><?= ic('clipboard') ?> Data Kehadiran</div></div>

<?php if($msg): ?><div class="alert success"><?= ic('check') ?><?= h($msg) ?></div><?php endif; ?>
<?php if($err): ?><div class="alert error"><?= ic('alert') ?><?= h($err) ?></div><?php endif; ?>

<div class="panel">
<h2><?= ic('save') ?> Catat Izin / Sakit</h2>
<p class="note">Gunakan ini kalau ada siswa izin/sakit dan tidak sempat absen. Kalau tanggal itu sudah kejadian tercatat "Alpa", mengisi form ini akan otomatis menimpanya jadi Izin/Sakit.</p>
<form method="post" class="grid" style="margin-top:14px">
<input type="hidden" name="action" value="izin_sakit">
<div class="field">
<label>Siswa</label>
<select name="fingerprint_id" required style="width:100%;padding:11px 13px;border:1.5px solid var(--border);border-radius:10px;background:var(--surface-2)">
<option value="">Pilih siswa</option>
<?php if ($siswaList): while ($s = $siswaList->fetch_assoc()): ?>
<option value="<?= h($s["fingerprint_id"]) ?>">#<?= h($s["fingerprint_id"]) ?> — <?= h($s["nama"]) ?> (<?= h($s["kelas"]) ?>)</option>
<?php endwhile; endif; ?>
</select>
</div>
<div class="field">
<label>Tanggal</label>
<input type="date" name="tanggal" value="<?= h(date('Y-m-d')) ?>" required>
</div>
<div class="field">
<label>Status</label>
<select name="status_izin" required style="width:100%;padding:11px 13px;border:1.5px solid var(--border);border-radius:10px;background:var(--surface-2)">
<option value="Izin">Izin</option>
<option value="Sakit">Sakit</option>
</select>
</div>
<div class="field">
<label>Catatan (opsional)</label>
<input type="text" name="catatan" placeholder="Contoh: demam, acara keluarga">
</div>
<div class="field" style="align-self:flex-end">
<button class="btn green" type="submit" style="width:100%"><?= ic('save') ?> Simpan</button>
</div>
</form>
</div>

<div class="panel"><h2><?= ic('calendar') ?> Filter Absensi</h2><form method="get" class="filter"><label>Pilih tanggal</label><input type="date" name="tanggal" value="<?=htmlspecialchars($tanggal)?>"><button class="btn" type="submit"><?= ic('search') ?> Tampilkan</button><a class="btn gray" href="absensi-admin.php"><?= ic('reset') ?> Reset</a></form></div>

<div class="panel"><h2><?= ic('history') ?> Riwayat Absensi</h2><div class="table-wrap"><table><thead><tr><th>Fingerprint ID</th><th>Nama</th><th>Kelas</th><th>Tanggal</th><th>Jam Masuk</th><th>Jam Pulang</th><th>Status</th><th>Keterangan</th></tr></thead><tbody>
<?php if($result&&$result->num_rows){while($row=$result->fetch_assoc()){
    $cls = statusBadgeClass($row["status"] ?? "");
    echo "<tr>";
    echo "<td class=\"mono\">#".htmlspecialchars((string)$row["fingerprint_id"])."</td>";
    echo "<td>".htmlspecialchars((string)($row["nama"]??"-"))."</td>";
    echo "<td><span class=\"class-badge\">".htmlspecialchars((string)($row["kelas"]??"-"))."</span></td>";
    echo "<td class=\"mono\">".htmlspecialchars((string)$row["tanggal"])."</td>";
    echo "<td class=\"mono\">".htmlspecialchars((string)($row["jam"]??"-"))."</td>";
    echo "<td class=\"mono\">".htmlspecialchars((string)($row["jam_pulang"]??"-"))."</td>";
    echo "<td><span class=\"status-badge $cls\">".htmlspecialchars((string)$row["status"])."</span></td>";
    echo "<td>".htmlspecialchars((string)($row["keterangan"]??"-"))."</td>";
    echo "</tr>";
}}else echo "<tr><td colspan=\"8\" style=\"text-align:center;padding:25px;color:var(--muted)\">Belum ada data absensi.</td></tr>";?>
</tbody></table></div></div>
</main><script src="../theme.js"></script>
</body></html>
