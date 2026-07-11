<?php
// update_pemasangan.php
header('Content-Type: application/json');
require 'db_config.php';

// --- Input JSON dari Flutter ---
$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Input tidak valid.']);
    exit;
}

// --- Validasi dasar ---
$id          = filter_var($input['id'] ?? null, FILTER_VALIDATE_INT);
$odp         = trim($input['odp'] ?? '');
$vlan        = trim($input['vlan'] ?? '');
$modem_id    = filter_var($input['modem'] ?? null, FILTER_VALIDATE_INT);
$dropcore_id = filter_var($input['dropcore'] ?? null, FILTER_VALIDATE_INT);
$teknisi_arr = isset($input['teknisi']) && is_array($input['teknisi']) ? $input['teknisi'] : [];

if (
    empty($id) ||
    $odp === '' ||
    $vlan === '' ||
    empty($modem_id) ||
    empty($dropcore_id) ||
    empty($teknisi_arr)
) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Semua field (ODP, VLAN, Modem, Dropcore, Teknisi) wajib diisi.'
    ]);
    exit;
}

// Gabung array teknisi jadi string: "user1, user2"
$teknisi = implode(', ', array_map('trim', $teknisi_arr));

// Inisialisasi Koneksi
$conn_pemasangan = get_conn_pemasangan();
$conn_umum       = get_conn_umum();

if (!$conn_pemasangan || $conn_pemasangan->connect_error) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Koneksi database pemasangan gagal.']);
    exit;
}
if (!$conn_umum || $conn_umum->connect_error) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Koneksi database umumdata gagal.']);
    exit;
}

$conn_pemasangan->set_charset('utf8mb4');
$conn_umum->set_charset('utf8mb4');

try {
    $conn_pemasangan->begin_transaction();
    $conn_umum->begin_transaction();

    // 1. Ambil info lama (untuk cek ganti modem & nama pelanggan)
    $stmt_old = $conn_pemasangan->prepare("
        SELECT modem, dropcore, nama 
        FROM pelanggan_instalasi 
        WHERE id = ?
        LIMIT 1
    ");
    $stmt_old->bind_param("i", $id);
    $stmt_old->execute();
    $res_old = $stmt_old->get_result()->fetch_assoc();
    $stmt_old->close();

    if (!$res_old) {
        throw new Exception("Data pemasangan ID $id tidak ditemukan.");
    }

    $old_modem_id     = (int) ($res_old['modem'] ?? 0);
    $old_dropcore_id  = (int) ($res_old['dropcore'] ?? 0);
    $nama_pelanggan   = $res_old['nama'] ?: ('Pelanggan #' . $id);

    // 2. Update Data Teknis di Tabel Pemasangan
    $stmt_update = $conn_pemasangan->prepare("
        UPDATE pelanggan_instalasi 
        SET 
            odp      = ?,
            vlan     = ?,
            modem    = ?,
            dropcore = ?,
            teknisi  = ?,
            status   = 'di proses'
        WHERE id = ?
    ");
    if (!$stmt_update) {
        throw new Exception("Gagal prepare update pemasangan: " . $conn_pemasangan->error);
    }
    $stmt_update->bind_param("ssiisi", $odp, $vlan, $modem_id, $dropcore_id, $teknisi, $id);
    if (!$stmt_update->execute()) {
        throw new Exception("Gagal update data pemasangan: " . $stmt_update->error);
    }
    $stmt_update->close();

    // 3. Update Inventory Modem: set status 'dipasang' & lokasi = nama pelanggan
    //    Catatan: modem yang dipilih dari form status-nya 'dibawa',
    //    jadi jangan batasi hanya 'tersedia' saja.
    $stmt_modem_new = $conn_umum->prepare("
        UPDATE jaringan_modem 
        SET 
            status = 'dipasang',
            lokasi_penyimpanan = ?
        WHERE id_modem = ?
          AND status IN ('dibawa','tersedia')
    ");
    if (!$stmt_modem_new) {
        throw new Exception("Gagal prepare update modem baru: " . $conn_umum->error);
    }
    $stmt_modem_new->bind_param("si", $nama_pelanggan, $modem_id);
    $stmt_modem_new->execute();

    // Opsional: jika ingin ketat, bisa cek affected_rows dan lempar error
    // kalau 0 dan modem_id tidak sama dengan lama.
    if ($stmt_modem_new->affected_rows === 0 && $modem_id != $old_modem_id) {
        // throw new Exception("Modem tidak ditemukan atau status tidak sesuai.");
    }
    $stmt_modem_new->close();

    // 4. Jika ganti modem, kembalikan modem lama jadi 'tersedia'
    if ($old_modem_id > 0 && $old_modem_id != $modem_id) {
        $stmt_reset = $conn_umum->prepare("
            UPDATE jaringan_modem 
            SET status='tersedia', lokasi_penyimpanan='' 
            WHERE id_modem = ?
        ");
        if ($stmt_reset) {
            $stmt_reset->bind_param("i", $old_modem_id);
            $stmt_reset->execute();
            $stmt_reset->close();
        }
    }

    // 5. (OPSIONAL) Dropcore: kalau ingin dikelola stoknya juga, bisa pakai pola sama:
    //    - Set dropcore baru -> status 'terpasang' / 'digunakan'
    //    - Dropcore lama -> dikembalikan 'tersedia' (kalau logikanya memang begitu)
    //
    // CONTOH (sesuaikan nama kolom jika ada):
    /*
    // Dropcore baru
    $stmt_dc_new = $conn_umum->prepare("
        UPDATE jaringan_kabel_dropcore 
        SET status='terpakai', lokasi_penyimpanan=? 
        WHERE id_kabel_dropcore = ? AND status='tersedia'
    ");
    if ($stmt_dc_new) {
        $stmt_dc_new->bind_param("si", $nama_pelanggan, $dropcore_id);
        $stmt_dc_new->execute();
        $stmt_dc_new->close();
    }

    // Dropcore lama bila beda
    if ($old_dropcore_id > 0 && $old_dropcore_id != $dropcore_id) {
        $stmt_dc_reset = $conn_umum->prepare("
            UPDATE jaringan_kabel_dropcore 
            SET status='tersedia', lokasi_penyimpanan='' 
            WHERE id_kabel_dropcore = ?
        ");
        if ($stmt_dc_reset) {
            $stmt_dc_reset->bind_param("i", $old_dropcore_id);
            $stmt_dc_reset->execute();
            $stmt_dc_reset->close();
        }
    }
    */

    // Commit kedua DB
    $conn_pemasangan->commit();
    $conn_umum->commit();

    echo json_encode(['success' => true, 'message' => 'Data teknis berhasil disimpan.']);

} catch (Exception $e) {
    if ($conn_pemasangan->errno === 0) {
        $conn_pemasangan->rollback();
    }
    if ($conn_umum->errno === 0) {
        $conn_umum->rollback();
    }
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
} finally {
    if ($conn_pemasangan) $conn_pemasangan->close();
    if ($conn_umum)       $conn_umum->close();
}
