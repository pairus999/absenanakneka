<?php
require_once 'auth.php'; require_once 'icons.php'; require_once '../config.php';
function h($v){return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');}
function statusBadge($status){
  $map=['PENDING'=>'pending','PROCESSING'=>'processing','SUCCESS'=>'done','DONE'=>'done','FAILED'=>'failed','ERROR'=>'failed'];
  $cls=$map[strtoupper((string)$status)]??'pending';
  return '<span class="badge '.$cls.'">'.h($status).'</span>';
}
$fid=(int)($_GET['fingerprint_id']??$_POST['fingerprint_id']??0); $msg=''; $err='';
if($_SERVER['REQUEST_METHOD']==='POST'){
  $q=$conn->prepare('SELECT nama,kelas FROM siswa WHERE fingerprint_id=? LIMIT 1'); $q->bind_param('i',$fid); $q->execute(); $r=$q->get_result();
  if($fid<=0 || $r->num_rows===0){$err='Siswa belum terdaftar. Tambahkan data siswa terlebih dahulu.';}
  else{$s=$r->fetch_assoc();
    $q=$conn->prepare("SELECT id FROM device_commands WHERE status IN ('PENDING','PROCESSING') LIMIT 1"); $q->execute();
    if($q->get_result()->num_rows>0){$err='Masih ada proses fingerprint yang sedang berjalan. Tunggu sampai selesai.';}
    else{$q=$conn->prepare("INSERT INTO device_commands(command_type,fingerprint_id,status) VALUES('ENROLL',?,'PENDING')");$q->bind_param('i',$fid);
      if($q->execute()){$msg='Perintah enroll dikirim untuk '.h($s['nama']).'. Sekarang lihat LCD dan tempelkan jari saat diminta.';}else{$err='Gagal membuat perintah enroll: '.h($q->error);}
    }
  }
}
$rows=$conn->query("SELECT id,fingerprint_id,status,result_message,created_at,updated_at FROM device_commands ORDER BY id DESC LIMIT 20");
?>
<!doctype html><html lang='id'><head><meta charset='utf-8'><meta name='viewport' content='width=device-width,initial-scale=1'><meta http-equiv='refresh' content='15'><title>Enroll Fingerprint · Admin</title><link rel='stylesheet' href='admin-style.css'><style>
.form{display:flex;gap:10px;flex-wrap:wrap;align-items:center}
.form input{padding:11px 13px;border:1.5px solid var(--border);border-radius:10px;background:var(--surface-2);font-family:var(--font-mono);font-size:14px;width:200px}
.form input:focus{outline:0;border-color:var(--accent);background:var(--surface);box-shadow:0 0 0 3px var(--accent-soft)}
</style></head><body>
<aside class='sidebar'>
<div class='brand'><?= ic('fingerprint') ?> ABSENSI</div><div class='subtitle'>Fingerprint System</div>
<nav class='nav'>
<a href='dashboard.php'><?= ic('grid') ?> Dashboard</a>
<a href='siswa-admin.php'><?= ic('users') ?> Data Siswa</a>
<a href='absensi-admin.php'><?= ic('clipboard') ?> Data Absensi</a>
<a href='laporan.php'><?= ic('chart-bar') ?> Laporan</a>
<a class='active' href='enroll.php'><?= ic('fingerprint') ?> Enroll Fingerprint</a>
<a href='pengaturan.php'><?= ic('sliders') ?> Pengaturan</a>
<a class='logout' href='logout.php'><?= ic('logout') ?> Keluar</a>
</nav></aside>
<main class='main'>
<div class='topbar'><div class='title'><h1>Enroll Fingerprint</h1><p>Daftarkan sidik jari siswa ke sensor AS608 melalui ESP32</p></div><div class="user-pill"><?= ic('fingerprint') ?> Enroll</div></div>
<?php if($msg):?><div class='alert success'><?= ic('check') ?><?= $msg ?></div><?php endif;?>
<?php if($err):?><div class='alert error'><?= ic('alert') ?><?= $err ?></div><?php endif;?>
<div class='panel'><h2><?= ic('fingerprint') ?> Daftarkan Sidik Jari</h2><p class='note'>Siswa harus sudah ada di Data Siswa. Masukkan fingerprint ID yang sama persis dengan data siswa. Setelah klik tombol, ESP32 mengambil perintah dalam waktu sekitar 2 detik.</p><form method='post' class='form'><input type='number' name='fingerprint_id' min='1' max='127' value='<?=h($fid?:'')?>' placeholder='Fingerprint ID' required><button class='btn'><?= ic('send') ?> Mulai Enroll</button></form></div>
<div class='panel'><h2><?= ic('history') ?> Riwayat Proses</h2><div class='table-wrap'><table><tr><th>ID</th><th>Fingerprint</th><th>Status</th><th>Hasil</th><th>Waktu</th></tr><?php if($rows):while($x=$rows->fetch_assoc()):?><tr><td class="mono"><?=h($x['id'])?></td><td class="mono">#<?=h($x['fingerprint_id'])?></td><td><?= statusBadge($x['status']) ?></td><td><?=h($x['result_message']??'-')?></td><td class="mono"><?=h($x['created_at'])?></td></tr><?php endwhile;endif;?></table></div></div></main><script src="../theme.js"></script>
</body></html>
