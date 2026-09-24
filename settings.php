<?php
/**
 * settings.php — helper functions shared by admin pages, the public site,
 * and api/device.php. Requires $conn (mysqli) to already exist, so always
 * require_once "config.php" (or "../config.php") BEFORE this file.
 */

function getSetting($conn, $key, $default = null) {
    $stmt = $conn->prepare("SELECT `value` FROM pengaturan WHERE `key`=? LIMIT 1");
    $stmt->bind_param("s", $key);
    $stmt->execute();
    $r = $stmt->get_result();
    if ($r->num_rows === 0) return $default;
    return $r->fetch_assoc()["value"];
}

function setSetting($conn, $key, $value) {
    $stmt = $conn->prepare(
        "INSERT INTO pengaturan (`key`,`value`) VALUES (?,?)
         ON DUPLICATE KEY UPDATE `value`=VALUES(`value`)"
    );
    $stmt->bind_param("ss", $key, $value);
    return $stmt->execute();
}

function isHariLibur($conn, $tanggalYmd) {
    $stmt = $conn->prepare("SELECT id FROM hari_libur WHERE tanggal=? LIMIT 1");
    $stmt->bind_param("s", $tanggalYmd);
    $stmt->execute();
    return $stmt->get_result()->num_rows > 0;
}

function keteranganLibur($conn, $tanggalYmd) {
    $stmt = $conn->prepare("SELECT keterangan FROM hari_libur WHERE tanggal=? LIMIT 1");
    $stmt->bind_param("s", $tanggalYmd);
    $stmt->execute();
    $r = $stmt->get_result();
    return $r->num_rows ? $r->fetch_assoc()["keterangan"] : null;
}

function isHariMinggu($tanggalYmd) {
    // ISO-8601: 1 = Senin ... 7 = Minggu
    return (int) date('N', strtotime($tanggalYmd)) === 7;
}

/**
 * Tandai "Alpa" untuk semua siswa yang tidak punya catatan absensi
 * pada $tanggalYmd (Hadir/Terlambat/Izin/Sakit/Alpa apapun sudah dihitung
 * "punya catatan"), kecuali tanggal itu hari Minggu atau hari libur.
 */
function processAlpaForDate($conn, $tanggalYmd) {
    if (isHariMinggu($tanggalYmd) || isHariLibur($conn, $tanggalYmd)) {
        return ['processed' => false, 'reason' => 'libur', 'inserted' => 0];
    }

    $existing = [];
    $q = $conn->prepare("SELECT fingerprint_id FROM absensi WHERE tanggal=?");
    $q->bind_param("s", $tanggalYmd);
    $q->execute();
    $res = $q->get_result();
    while ($row = $res->fetch_assoc()) {
        $existing[(int) $row["fingerprint_id"]] = true;
    }

    $inserted = 0;
    $siswaRes = $conn->query("SELECT fingerprint_id, nama, kelas FROM siswa");
    while ($s = $siswaRes->fetch_assoc()) {
        $fid = (int) $s["fingerprint_id"];
        if (isset($existing[$fid])) continue;

        $ins = $conn->prepare(
            "INSERT INTO absensi (fingerprint_id, nama, kelas, tanggal, jam, status)
             VALUES (?, ?, ?, ?, NULL, 'Alpa')"
        );
        $ins->bind_param("isss", $fid, $s["nama"], $s["kelas"], $tanggalYmd);
        if ($ins->execute()) $inserted++;
    }

    return ['processed' => true, 'reason' => 'ok', 'inserted' => $inserted];
}

/**
 * Dipanggil otomatis (mis. tiap dashboard admin dibuka). Memproses semua
 * tanggal yang "tertinggal" sejak proses terakhir sampai kemarin, dibatasi
 * $maxDays sekali jalan supaya tidak berat kalau lama tidak dibuka.
 */
function runAutoAlpaBacklog($conn, $maxDays = 14) {
    $last = getSetting($conn, 'last_alpa_process_date', '');
    $yesterday = date('Y-m-d', strtotime('-1 day'));

    if ($last === '') {
        setSetting($conn, 'last_alpa_process_date', $yesterday);
        return ['ran' => false];
    }

    if ($last >= $yesterday) {
        return ['ran' => false];
    }

    $cursor = date('Y-m-d', strtotime($last . ' +1 day'));
    $count = 0;
    $totalInserted = 0;

    while ($cursor <= $yesterday && $count < $maxDays) {
        $r = processAlpaForDate($conn, $cursor);
        $totalInserted += $r['inserted'];
        $cursor = date('Y-m-d', strtotime($cursor . ' +1 day'));
        $count++;
    }

    setSetting($conn, 'last_alpa_process_date', date('Y-m-d', strtotime($cursor . ' -1 day')));

    return ['ran' => true, 'days' => $count, 'inserted' => $totalInserted];
}

/**
 * Simpan status Izin/Sakit untuk seorang siswa pada tanggal tertentu.
 * Kalau tanggal itu sudah ada catatan apapun (Hadir/Terlambat/Alpa/dll),
 * catatan lama akan DITIMPA — supaya admin bisa mengoreksi "Alpa" yang
 * ternyata cuma karena murid izin/sakit dan lupa lapor sebelumnya.
 */
function setIzinSakit($conn, $fingerprintId, $tanggalYmd, $status, $catatan = '') {
    if (!in_array($status, ['Izin', 'Sakit'], true)) {
        return ['ok' => false, 'message' => 'Status tidak valid.'];
    }

    $q = $conn->prepare("SELECT nama, kelas FROM siswa WHERE fingerprint_id=? LIMIT 1");
    $q->bind_param("i", $fingerprintId);
    $q->execute();
    $r = $q->get_result();
    if ($r->num_rows === 0) {
        return ['ok' => false, 'message' => 'Siswa tidak ditemukan.'];
    }
    $s = $r->fetch_assoc();

    $q = $conn->prepare("SELECT id FROM absensi WHERE fingerprint_id=? AND tanggal=? LIMIT 1");
    $q->bind_param("is", $fingerprintId, $tanggalYmd);
    $q->execute();
    $existing = $q->get_result()->fetch_assoc();

    if ($existing) {
        $u = $conn->prepare("UPDATE absensi SET status=?, jam=NULL, keterangan=? WHERE id=?");
        $u->bind_param("ssi", $status, $catatan, $existing["id"]);
        $ok = $u->execute();
        return ['ok' => $ok, 'message' => $ok ? "Catatan sebelumnya ditimpa jadi $status." : 'Gagal menyimpan.'];
    }

    $ins = $conn->prepare(
        "INSERT INTO absensi (fingerprint_id, nama, kelas, tanggal, jam, status, keterangan)
         VALUES (?, ?, ?, ?, NULL, ?, ?)"
    );
    $ins->bind_param("isssss", $fingerprintId, $s["nama"], $s["kelas"], $tanggalYmd, $status, $catatan);
    $ok = $ins->execute();
    return ['ok' => $ok, 'message' => $ok ? "$status berhasil dicatat." : 'Gagal menyimpan.'];
}

/**
 * Kelas CSS badge status, dipakai di admin & publik supaya warnanya konsisten.
 */
function statusBadgeClass($status) {
    $s = strtolower((string) $status);
    if ($s === 'terlambat') return 'late';
    if ($s === 'alpa') return 'alpa';
    if ($s === 'izin') return 'izin';
    if ($s === 'sakit') return 'sakit';
    return 'hadir';
}

/**
 * Apakah alat (ESP32) sedang online, dihitung dari kapan terakhir kontak,
 * bukan cuma dari kolom `online` (yang tidak pernah di-set 0 oleh device.php).
 */
function getDeviceStatus($conn) {
    $thresholdSec = (int) getSetting($conn, 'offline_threshold_detik', 60);
    $q = $conn->query("SELECT device_name, online, last_seen, ip_address FROM device_status WHERE id=1 LIMIT 1");
    $row = $q ? $q->fetch_assoc() : null;

    if (!$row) {
        return ['exists' => false, 'online' => false, 'last_seen' => null, 'device_name' => '-', 'ip_address' => '-'];
    }

    $isOnline = false;
    if (!empty($row['last_seen'])) {
        $diff = time() - strtotime($row['last_seen']);
        $isOnline = $diff <= $thresholdSec;
    }

    $row['exists'] = true;
    $row['online'] = $isOnline;
    return $row;
}
