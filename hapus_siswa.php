<?php

require_once "auth.php";
require_once "config.php";

$id = intval($_GET["id"] ?? 0);

if ($id > 0) {
    $stmt = $conn->prepare(
        "DELETE FROM siswa WHERE id = ?"
    );

    $stmt->bind_param("i", $id);
    $stmt->execute();

    $stmt->close();
}

header("Location: siswa.php");
exit;