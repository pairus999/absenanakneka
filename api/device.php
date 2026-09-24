<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../settings.php';

const DEVICE_API_KEY = 'absenanaknekabyfairus';

function out(array $data, int $code = 200): void {
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

$apiKey = $_SERVER['HTTP_X_API_KEY'] ?? '';
if (!hash_equals(DEVICE_API_KEY, $apiKey)) {
    out(['success'=>false, 'message'=>'API key salah'], 401);
}

$action = $_GET['action'] ?? '';
$remoteIp = $_SERVER['REMOTE_ADDR'] ?? '';

// Every valid request also acts as a device heartbeat.
$st = $conn->prepare('UPDATE device_status SET online=1,last_seen=NOW(),ip_address=? WHERE id=1');
if ($st) {
    $st->bind_param('s', $remoteIp);
    $st->execute();
    $st->close();
}

function bodyJson(): array {
    $raw = file_get_contents('php://input');
    $data = json_decode($raw ?: '', true);
    return is_array($data) ? $data : [];
}

if ($action === 'heartbeat') {
    out(['success'=>true, 'message'=>'heartbeat', 'server_time'=>date('Y-m-d H:i:s')]);
}

if ($action === 'command') {
    // Recover a command abandoned by a reboot/network failure.
    $conn->query("UPDATE device_commands SET status='PENDING',updated_at=NOW()
                  WHERE status='PROCESSING' AND updated_at IS NOT NULL
                  AND updated_at < (NOW() - INTERVAL 2 MINUTE)");

    $q = $conn->query("SELECT id,command_type,fingerprint_id
                       FROM device_commands
                       WHERE status='PENDING'
                       ORDER BY id ASC LIMIT 1");
    if (!$q || $q->num_rows === 0) {
        out(['success'=>true, 'command'=>null]);
    }

    $r = $q->fetch_assoc();
    $u = $conn->prepare("UPDATE device_commands
                         SET status='PROCESSING',updated_at=NOW()
                         WHERE id=? AND status='PENDING'");
    $u->bind_param('i', $r['id']);
    $u->execute();
    $claimed = $u->affected_rows === 1;
    $u->close();

    if (!$claimed) {
        out(['success'=>true, 'command'=>null]);
    }

    out(['success'=>true, 'command'=>[
        'id'=>(int)$r['id'],
        'type'=>$r['command_type'],
        'fingerprint_id'=>(int)$r['fingerprint_id']
    ]]);
}

if ($action === 'result' || $action === 'command_ack') {
    $d = bodyJson();
    $id = (int)($d['command_id'] ?? $d['id'] ?? 0);
    $ok = isset($d['success']) ? (bool)$d['success'] :
          in_array(strtoupper((string)($d['status'] ?? '')), ['DONE','SUCCESS'], true);
    $msg = trim((string)($d['message'] ?? ''));

    if ($id <= 0) {
        out(['success'=>false, 'message'=>'ID command tidak valid'], 400);
    }

    $final = $ok ? 'SUCCESS' : 'FAILED';
    $u = $conn->prepare('UPDATE device_commands
                         SET status=?,result_message=?,updated_at=NOW()
                         WHERE id=? AND status="PROCESSING"');
    $u->bind_param('ssi', $final, $msg, $id);
    $u->execute();
    $changed = $u->affected_rows;
    $u->close();

    if ($changed !== 1) {
        out(['success'=>false, 'message'=>'Command tidak ditemukan atau sudah selesai'], 409);
    }
    out(['success'=>true, 'status'=>$final]);
}

if ($action === 'attendance') {
    $d = bodyJson();
    $fid = (int)($d['fingerprint_id'] ?? 0);
    if ($fid <= 0) {
        out(['success'=>false, 'message'=>'Fingerprint ID tidak valid'], 400);
    }

    $q = $conn->prepare('SELECT nama,kelas FROM siswa WHERE fingerprint_id=? LIMIT 1');
    $q->bind_param('i', $fid);
    $q->execute();
    $r = $q->get_result();
    if ($r->num_rows === 0) {
        out(['success'=>false, 'message'=>'Siswa belum terdaftar']);
    }
    $s = $r->fetch_assoc();

    $tgl = date('Y-m-d');
    $jam = date('H:i:s');

    $q = $conn->prepare('SELECT id, jam, status, jam_pulang FROM absensi WHERE fingerprint_id=? AND tanggal=? LIMIT 1');
    $q->bind_param('is', $fid, $tgl);
    $q->execute();
    $existing = $q->get_result()->fetch_assoc();

    // --- ABSEN KEDUA HARI INI = ABSEN PULANG ---
    if ($existing) {
        if (!empty($existing['jam_pulang'])) {
            out(['success'=>false, 'message'=>'Absen masuk & pulang hari ini sudah tercatat',
                 'nama'=>$s['nama'], 'kelas'=>$s['kelas']]);
        }

        $u = $conn->prepare('UPDATE absensi SET jam_pulang=? WHERE id=?');
        $u->bind_param('si', $jam, $existing['id']);
        if (!$u->execute()) {
            out(['success'=>false, 'message'=>'Gagal menyimpan absen pulang']);
        }

        out(['success'=>true, 'message'=>'Absen pulang berhasil', 'jenis'=>'Pulang',
             'nama'=>$s['nama'], 'kelas'=>$s['kelas'], 'tanggal'=>$tgl, 'jam'=>$jam, 'status'=>$existing['status']]);
    }

    // --- ABSEN PERTAMA HARI INI = ABSEN MASUK, DENGAN DETEKSI TERLAMBAT ---
    // Batas jam masuk diatur lewat halaman Pengaturan (tabel `pengaturan`).
    $jamMasukMax = getSetting($conn, 'jam_masuk_max', '07:15:00');
    $status = ($jam > $jamMasukMax) ? 'Terlambat' : 'Hadir';

    $q = $conn->prepare("INSERT INTO absensi
        (fingerprint_id,nama,kelas,tanggal,jam,status)
        VALUES (?,?,?,?,?,?)");
    $q->bind_param('isssss', $fid, $s['nama'], $s['kelas'], $tgl, $jam, $status);
    if (!$q->execute()) {
        out(['success'=>false, 'message'=>'Gagal menyimpan absensi']);
    }

    out(['success'=>true, 'message'=>($status === 'Terlambat' ? 'Absen masuk berhasil (terlambat)' : 'Absen masuk berhasil'),
         'jenis'=>'Masuk', 'nama'=>$s['nama'], 'kelas'=>$s['kelas'], 'tanggal'=>$tgl, 'jam'=>$jam, 'status'=>$status]);
}

out(['success'=>false, 'message'=>'Action tidak dikenal'], 404);
