<?php

header(
    "Content-Type: application/json"
);

require_once "config.php";

$data = json_decode(
    file_get_contents("php://input"),
    true
);

$fingerprint_id =
    intval(
        $data["fingerprint_id"] ?? 0
    );


if ($fingerprint_id <= 0) {

    echo json_encode([
        "success" => false,
        "message" => "Fingerprint ID tidak valid"
    ]);

    exit;
}


$stmt = $conn->prepare(
    "SELECT *
     FROM siswa
     WHERE fingerprint_id = ?"
);

$stmt->bind_param(
    "i",
    $fingerprint_id
);

$stmt->execute();

$result =
    $stmt->get_result();


if ($result->num_rows === 0) {

    echo json_encode([
        "success" => false,
        "message" => "Siswa belum terdaftar"
    ]);

    exit;
}


$siswa =
    $result->fetch_assoc();

$nama =
    $siswa["nama"];

$kelas =
    $siswa["kelas"];

$tanggal =
    date("Y-m-d");

$jam =
    date("H:i:s");


/* Cegah absen dua kali */

$cek = $conn->prepare(
    "SELECT id
     FROM absensi
     WHERE fingerprint_id = ?
     AND tanggal = ?"
);

$cek->bind_param(
    "is",
    $fingerprint_id,
    $tanggal
);

$cek->execute();

$cekResult =
    $cek->get_result();


if ($cekResult->num_rows > 0) {

    echo json_encode([
        "success" => false,
        "message" => "Sudah absen hari ini",
        "nama" => $nama
    ]);

    exit;
}


/* Simpan */

$insert = $conn->prepare(
    "INSERT INTO absensi
    (
        fingerprint_id,
        nama,
        kelas,
        tanggal,
        jam,
        status
    )
    VALUES (?, ?, ?, ?, ?, 'Hadir')"
);

$insert->bind_param(
    "issss",
    $fingerprint_id,
    $nama,
    $kelas,
    $tanggal,
    $jam
);

$insert->execute();


echo json_encode([
    "success" => true,
    "message" => "Absen berhasil",
    "nama" => $nama,
    "kelas" => $kelas,
    "tanggal" => $tanggal,
    "jam" => $jam
]);