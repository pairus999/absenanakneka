<?php
session_start();
require_once 'icons.php';
if(isset($_SESSION["admin_logged_in"])&&$_SESSION["admin_logged_in"]===true){header("Location: dashboard.php");exit;}
$error="";
if($_SERVER["REQUEST_METHOD"]==="POST"){
 $username=trim($_POST["username"]??"");$password=$_POST["password"]??"";
 if($username==="pairus"&&$password==="xiititl3"){$_SESSION["admin_logged_in"]=true;$_SESSION["admin_username"]="admin";header("Location: dashboard.php");exit;}
 $error="Username atau password salah.";
}
?>
<!doctype html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Login Admin</title><link rel="stylesheet" href="admin-style.css"></head>
<body class="login-page"><div class="login-box">
<div class="login-brand"><?= ic('fingerprint') ?> ABSENSI</div><div class="login-sub">Panel administrator</div>
<?php if($error):?><div class="alert error"><?= ic('alert') ?><?=htmlspecialchars($error)?></div><?php endif;?>
<form method="post">
<div class="field"><label>Username</label><input name="username" autocomplete="username" required autofocus></div>
<div class="field"><label>Password</label><input type="password" name="password" autocomplete="current-password" required></div>
<button class="btn" type="submit"><?= ic('key') ?> Masuk ke Dashboard</button>
</form></div><script src="../theme.js"></script>
</body></html>
