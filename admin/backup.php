<?php
require_once "auth.php";
require_once "../config.php";

/**
 * Backup sederhana berbasis PHP murni (tidak butuh akses exec/mysqldump,
 * jadi tetap jalan di shared hosting yang membatasi fungsi shell).
 */

$tables = ['siswa', 'absensi', 'hari_libur', 'pengaturan', 'device_status', 'device_commands'];

$filename = "backup-absensi-" . date('Y-m-d-His') . ".sql";
header("Content-Type: application/sql; charset=utf-8");
header("Content-Disposition: attachment; filename=$filename");

echo "-- Backup Sistem Absensi\n";
echo "-- Dibuat: " . date('Y-m-d H:i:s') . "\n\n";
echo "SET FOREIGN_KEY_CHECKS=0;\n\n";

foreach ($tables as $table) {
    $check = $conn->query("SHOW TABLES LIKE '$table'");
    if (!$check || $check->num_rows === 0) continue;

    // Struktur tabel
    $createRes = $conn->query("SHOW CREATE TABLE `$table`");
    if ($createRes) {
        $row = $createRes->fetch_assoc();
        echo "DROP TABLE IF EXISTS `$table`;\n";
        echo $row['Create Table'] . ";\n\n";
    }

    // Isi tabel
    $dataRes = $conn->query("SELECT * FROM `$table`");
    if ($dataRes && $dataRes->num_rows > 0) {
        $fields = $dataRes->fetch_fields();
        $colNames = array_map(fn($f) => "`{$f->name}`", $fields);

        while ($row = $dataRes->fetch_assoc()) {
            $values = array_map(function ($v) use ($conn) {
                if ($v === null) return "NULL";
                return "'" . $conn->real_escape_string((string)$v) . "'";
            }, array_values($row));

            echo "INSERT INTO `$table` (" . implode(",", $colNames) . ") VALUES (" . implode(",", $values) . ");\n";
        }
        echo "\n";
    }
}

echo "SET FOREIGN_KEY_CHECKS=1;\n";
