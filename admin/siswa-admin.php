<?php
require_once "auth.php";
require_once "icons.php";
require_once "../config.php";

function h($v){return htmlspecialchars((string)$v,ENT_QUOTES,"UTF-8");}

$msg="";
$err="";
$newCode="";

$hasCode=false;
$hasNoHp=false;

$chk=$conn->query("SHOW COLUMNS FROM siswa LIKE 'kode_akses'");
if($chk && $chk->num_rows>0)$hasCode=true;

$chkHp=$conn->query("SHOW COLUMNS FROM siswa LIKE 'no_hp'");
if($chkHp && $chkHp->num_rows>0)$hasNoHp=true;

function makeCode($conn){
    do{
        $c="SIS-".strtoupper(substr(bin2hex(random_bytes(4)),0,6));
        $q=$conn->prepare("SELECT id FROM siswa WHERE kode_akses=? LIMIT 1");
        $q->bind_param("s",$c);
        $q->execute();
    }while($q->get_result()->num_rows>0);
    return $c;
}

if($_SERVER["REQUEST_METHOD"]==="POST"){
    $action=$_POST["action"]??"";

    if($action==="tambah"){
        $fp=trim($_POST["fingerprint_id"]??"");
        $nama=trim($_POST["nama"]??"");
        $kelas=trim($_POST["kelas"]??"");
        $no_hp=trim($_POST["no_hp"]??"");

        if(!$fp||!$nama||!$kelas){
            $err="FINGERPRINT ID, NAMA, DAN KELAS WAJIB DIISI.";
        }elseif(!$hasCode){
            $err="KOLOM kode_akses BELUM ADA DI DATABASE.";
        }elseif(!$hasNoHp){
            $err="KOLOM no_hp BELUM ADA DI DATABASE. JALANKAN database_no_hp.sql.";
        }else{
            $q=$conn->prepare("SELECT id FROM siswa WHERE fingerprint_id=? LIMIT 1");
            $q->bind_param("s",$fp);
            $q->execute();

            if($q->get_result()->num_rows){
                $err="FINGERPRINT ID SUDAH TERDAFTAR.";
            }else{
                $c=makeCode($conn);
                $q=$conn->prepare("INSERT INTO siswa (fingerprint_id,nama,kelas,no_hp,kode_akses) VALUES (?,?,?,?,?)");
                $q->bind_param("sssss",$fp,$nama,$kelas,$no_hp,$c);

                if($q->execute()){
                    header("Location:siswa-admin.php?ok=tambah&kode=".urlencode($c));
                    exit;
                }

                $err="GAGAL MENAMBAH DATA: ".$q->error;
            }
        }
    }

    if($action==="edit"){
        $id=(int)($_POST["id"]??0);
        $fp=trim($_POST["fingerprint_id"]??"");
        $nama=trim($_POST["nama"]??"");
        $kelas=trim($_POST["kelas"]??"");
        $no_hp=trim($_POST["no_hp"]??"");

        if(!$id||!$fp||!$nama||!$kelas){
            $err="FINGERPRINT ID, NAMA, DAN KELAS WAJIB DIISI.";
        }elseif(!$hasNoHp){
            $err="KOLOM no_hp BELUM ADA DI DATABASE.";
        }else{
            $q=$conn->prepare("SELECT id FROM siswa WHERE fingerprint_id=? AND id!=? LIMIT 1");
            $q->bind_param("si",$fp,$id);
            $q->execute();

            if($q->get_result()->num_rows){
                $err="FINGERPRINT ID SUDAH DIPAKAI SISWA LAIN.";
            }else{
                $q=$conn->prepare("UPDATE siswa SET fingerprint_id=?,nama=?,kelas=?,no_hp=? WHERE id=?");
                $q->bind_param("ssssi",$fp,$nama,$kelas,$no_hp,$id);

                if($q->execute()){
                    header("Location:siswa-admin.php?ok=edit");
                    exit;
                }

                $err="GAGAL MENGEDIT DATA: ".$q->error;
            }
        }
    }
}

if(isset($_GET["hapus"])){
    $id=(int)$_GET["hapus"];

    if($id>0){
        $q=$conn->prepare("DELETE FROM siswa WHERE id=?");
        $q->bind_param("i",$id);
        $q->execute();
    }

    header("Location:siswa-admin.php?ok=hapus");
    exit;
}

$edit=null;

if(isset($_GET["edit"])){
    $id=(int)$_GET["edit"];
    $q=$conn->prepare("SELECT id,fingerprint_id,nama,kelas,no_hp,kode_akses FROM siswa WHERE id=? LIMIT 1");
    $q->bind_param("i",$id);
    $q->execute();
    $edit=$q->get_result()->fetch_assoc();
}

$ok=$_GET["ok"]??"";

if($ok==="tambah"){
    $msg="DATA SISWA BERHASIL DITAMBAHKAN.";
    $newCode=$_GET["kode"]??"";
}
if($ok==="edit")$msg="DATA SISWA BERHASIL DIPERBARUI.";
if($ok==="hapus")$msg="DATA SISWA BERHASIL DIHAPUS.";

$search=trim($_GET["cari"]??"");

if($search!==""){
    $like="%".$search."%";

    $q=$conn->prepare(
        "SELECT id,fingerprint_id,nama,kelas,no_hp,kode_akses
         FROM siswa
         WHERE fingerprint_id LIKE ?
            OR nama LIKE ?
            OR kelas LIKE ?
            OR no_hp LIKE ?
            OR kode_akses LIKE ?
         ORDER BY CAST(fingerprint_id AS UNSIGNED) ASC,id ASC"
    );

    $q->bind_param("sssss",$like,$like,$like,$like,$like);
    $q->execute();
    $result=$q->get_result();

}else{

    $result=$conn->query(
        "SELECT id,fingerprint_id,nama,kelas,no_hp,kode_akses
         FROM siswa
         ORDER BY CAST(fingerprint_id AS UNSIGNED) ASC,id ASC"
    );
}
?>
<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Data Siswa · Admin</title>
<link rel="stylesheet" href="admin-style.css">
<style>
table{min-width:920px}
</style>
</head>

<body>

<aside class="sidebar">
<div class="brand"><?= ic('fingerprint') ?> ABSENSI</div>
<div class="subtitle">Fingerprint System</div>

<nav class="nav">
<a href="dashboard.php"><?= ic('grid') ?> Dashboard</a>
<a class="active" href="siswa-admin.php"><?= ic('users') ?> Data Siswa</a>
<a href="absensi-admin.php"><?= ic('clipboard') ?> Data Absensi</a>
<a href="laporan.php"><?= ic('chart-bar') ?> Laporan</a>
<a href="enroll.php"><?= ic('fingerprint') ?> Enroll Fingerprint</a>
<a href="pengaturan.php"><?= ic('sliders') ?> Pengaturan</a>
<a class="logout" href="logout.php"><?= ic('logout') ?> Keluar</a>
</nav>
</aside>

<main class="main">

<div class="topbar">
<div class="title">
<h1>Data Siswa</h1>
<p>Kelola data siswa dan kode akses</p>
</div>
<div class="user-pill"><?= ic('users') ?> Data Siswa</div>
</div>

<?php if($msg): ?>
<div class="alert success"><?= ic('check') ?><?=h($msg)?></div>
<?php endif; ?>

<?php if($err): ?>
<div class="alert error"><?= ic('alert') ?><?=h($err)?></div>
<?php endif; ?>

<?php if($newCode): ?>
<div class="codebox">
<div>Kode akses siswa baru</div>
<strong><?=h($newCode)?></strong>
<div>Simpan dan berikan kode ini kepada siswa</div>
</div>
<?php endif; ?>


<!-- TAMBAH SISWA -->
<div class="panel">

<h2><?= $edit ? ic('edit')." Edit Siswa" : ic('plus')." Tambah Siswa" ?></h2>

<form method="post">

<input type="hidden"
       name="action"
       value="<?= $edit ? "edit" : "tambah" ?>">

<?php if($edit): ?>
<input type="hidden"
       name="id"
       value="<?=h($edit["id"])?>">
<?php endif; ?>

<div class="grid">

<div class="field">
<label>FINGERPRINT ID</label>
<input
 name="fingerprint_id"
 required
 placeholder="Contoh: 1"
 value="<?=h($edit["fingerprint_id"]??"")?>">
</div>

<div class="field">
<label>NAMA SISWA</label>
<input
 name="nama"
 required
 placeholder="Nama lengkap"
 value="<?=h($edit["nama"]??"")?>">
</div>

<div class="field">
<label>KELAS</label>
<input
 name="kelas"
 required
 placeholder="Contoh: XI TITL 1"
 value="<?=h($edit["kelas"]??"")?>">
</div>

<div class="field">
<label>NO. HP</label>
<input
 type="tel"
 name="no_hp"
 placeholder="08xxxxxxxxxx"
 value="<?=h($edit["no_hp"]??"")?>">
</div>

</div>

<button class="btn green" type="submit">
<?= $edit ? ic('save')." Simpan Perubahan" : ic('plus')." Tambah Siswa" ?>
</button>

<?php if($edit): ?>
<a class="btn gray" href="siswa-admin.php"><?= ic('close') ?> Batal</a>
<?php endif; ?>

</form>
</div>


<!-- SEARCH -->
<div class="panel">

<h2><?= ic('search') ?> Cari Siswa</h2>

<form method="get" class="search">

<input
 name="cari"
 placeholder="Cari nama / fingerprint / kelas / no. hp / kode akses"
 value="<?=h($search)?>">

<button class="btn" type="submit"><?= ic('search') ?> Cari</button>

<a class="btn gray" href="siswa-admin.php"><?= ic('reset') ?> Reset</a>

</form>
</div>


<!-- TABLE -->
<div class="panel">

<h2><?= ic('clipboard') ?> Daftar Siswa</h2>

<div class="table-wrap">

<table>

<thead>
<tr>
<th>No</th>
<th>Fingerprint ID</th>
<th>Nama</th>
<th>Kelas</th>
<th>No. HP</th>
<th>Kode Akses</th>
<th>Aksi</th>
</tr>
</thead>

<tbody>

<?php
$no=1;

if($result && $result->num_rows > 0):

while($r=$result->fetch_assoc()):
?>

<tr>

<td><?= $no++ ?></td>

<td class="mono">
#<?=h($r["fingerprint_id"])?>
</td>

<td>
<?=h($r["nama"])?>
</td>

<td>
<?=h($r["kelas"])?>
</td>

<td class="mono">
<?=h($r["no_hp"]??"-")?>
</td>

<td class="code">
<?=h($r["kode_akses"]??"Belum ada")?>
</td>

<td>

<div class="actions">

<a
 class="btn small"
 href="siswa-admin.php?edit=<?=h($r["id"])?>">
<?= ic('edit') ?> Edit
</a>

<a
 class="btn small enroll-btn"
 href="enroll.php?fingerprint_id=<?=h($r["fingerprint_id"])?>">
<?= ic('fingerprint') ?> Enroll
</a>

<a
 class="btn red small"
 href="siswa-admin.php?hapus=<?=h($r["id"])?>"
 onclick="return confirm('Yakin hapus siswa ini?')">
<?= ic('trash') ?> Hapus
</a>

</div>

</td>

</tr>

<?php
endwhile;

else:
?>

<tr>
<td colspan="7" style="text-align:center;padding:25px;color:var(--muted)">
Belum ada data siswa.
</td>
</tr>

<?php endif; ?>

</tbody>
</table>

</div>
</div>

</main>

<script src="../theme.js"></script>
</body>
</html>
