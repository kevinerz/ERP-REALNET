<?php
session_start();
require_once 'db_config.php'; // Separate DB config
require_once 'notification_handler.php'; // Separate notification handler

if (
    !isset($_SESSION['username']) ||
    !in_array($_SESSION['divisi'], ['Admin', 'SPV Teknis'])
) {
    set_notification('Akses hanya untuk Admin & SPV Teknis!');
    header("Location: dashboard.php");
    exit;
}

// DB koneksi
$connPemasangan = getDbConnection('pemasangan');
$connUmum = getDbConnection('umum');

$paketArray = [];
$resPaket = $connUmum->query("SELECT * FROM paket ORDER BY id_paket ASC");
if ($resPaket) {
    while ($rowPaket = $resPaket->fetch_assoc()) {
        $paketArray[$rowPaket['id_paket']] = $rowPaket;
    }
    $resPaket->free(); // Free result set
} else {
    error_log("Error fetching packages: " . $connUmum->error);
}

// Mapping grup WhatsApp per POP
function getGroupIdForPop($pop) {
    $groups = [
        "rajeg"     => "6281293958590-1587210420@g.us",
        "kemeri"    => "6287770366015-1628875457@g.us",
        "cianjur"   => "120363399972363054@g.us",
        "mauk"      => "120363419348224895@g.us",
        "brebes"    => "120363297070607107@g.us",
        "sengon"    => "120363366069803212@g.us",
        "grinting"  => "120363399972363054@g.us"
    ];
    // Return the group ID, or null if POP is not found
    return $groups[strtolower($pop)] ?? null;
}

// Function to send WhatsApp notification
function sendWhatsAppNotification($customerData, $paketData) {
    $nama_pop = $customerData['pop'] ?? 'N/A';
    $group_id = getGroupIdForPop($nama_pop);

    if (!$group_id) {
        error_log("No WhatsApp group found for POP: " . $nama_pop);
        return false; // Indicate failure if no group is found
    }

    $nama = htmlspecialchars($customerData['nama'] ?? 'N/A');
    $url_maps = htmlspecialchars($customerData['url_maps'] ?? 'N/A');
    $alamat = htmlspecialchars($customerData['alamat'] ?? 'N/A');
    $ktp = htmlspecialchars($customerData['ktp'] ?? 'N/A');
    $telp = htmlspecialchars($customerData['telp'] ?? 'N/A');
    $email = htmlspecialchars($customerData['email'] ?? 'N/A');
    $marketing = htmlspecialchars($customerData['marketing'] ?? 'N/A');
    $userPpp = htmlspecialchars($customerData['userppp'] ?? 'N/A');
    $passwordPpp = htmlspecialchars($customerData['passwordppp'] ?? 'N/A');
    $vlan = htmlspecialchars($customerData['vlan'] ?? 'N/A');

    $nama_paket = htmlspecialchars($paketData['nama_paket'] ?? 'N/A');
    $kecepatan = htmlspecialchars($paketData['kecepatan'] ?? 'N/A');
    $harga_paket = $paketData['harga'] ?? 0;

    $message = "📢 *PELANGGAN BARU BERHASIL DIAKTIVASI ({$nama_pop})*

Nama Pelanggan: *{$nama}*
Paket: *{$nama_paket}* ({$kecepatan}) Rp" . number_format($harga_paket, 0, ',', '.') . "
Alamat: {$alamat}
Username PPPoE: *{$userPpp}*
Password PPPoE: *{$passwordPpp}*
VLAN: *{$vlan}*

*Mohon disesuaikan dengan modem yang terpasang.*
---
Detail Pemasangan Awal:
URL Maps: {$url_maps}
KTP: {$ktp}
Telp: {$telp}
Email: {$email}
Marketing: {$marketing}
";

    $curl = curl_init('https://api.starsender.online/api/send');
    curl_setopt_array($curl, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode([
            "messageType" => "text",
            "to"          => $group_id,
            "body"        => $message
        ]),
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'Authorization: e9c50247-3b8d-4cd8-924a-024a4d2b3124'
        ],
    ]);

    $resp = curl_exec($curl);
    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    $curlError = curl_error($curl);

    // Log the WhatsApp API response for debugging
    file_put_contents('log_whatsapp_notification.txt', date('Y-m-d H:i:s') . " Sending to {$group_id} - HTTP Code: {$httpCode} - CURL Error: {$curlError} - RESP: {$resp}\n", FILE_APPEND);
    curl_close($curl);

    if ($httpCode === 200) {
        return true;
    } else {
        error_log("WhatsApp notification failed for POP {$nama_pop}: HTTP Code {$httpCode}, Response: {$resp}, Error: {$curlError}");
        return false;
    }
}


// Process activation from modal
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_aktivasi'])) {
    $id = filter_var($_POST['id_aktivasi'], FILTER_VALIDATE_INT);
    $userPpp = trim($_POST['userppp']);
    $passwordPpp = trim($_POST['passwordppp']);
    $vlan = trim($_POST['vlan']);
    $paket = filter_var($_POST['paket'], FILTER_VALIDATE_INT);
    $status = "aktivasi";
    $lastUpdatedBy = $_SESSION['username'];

    if (!$id || $paket === false || empty($userPpp) || empty($passwordPpp) || empty($vlan)) {
        set_notification("Mohon lengkapi semua data dengan benar.");
        header("Location: aktivasi_pelanggan.php");
        exit;
    }

    $stmt = $connPemasangan->prepare("UPDATE pemasangan SET userppp=?, passwordppp=?, vlan=?, paket=?, status=?, last_updated_by=? WHERE id=?");
    if ($stmt) {
        $stmt->bind_param("ssssssi", $userPpp, $passwordPpp, $vlan, $paket, $status, $lastUpdatedBy, $id);
        $stmt->execute();
        if ($stmt->affected_rows > 0) {
            set_notification("Pelanggan berhasil diaktivasi!", "info");

            // --- START WhatsApp Notification Integration ---
            // Fetch the updated customer data for the WhatsApp message
            $stmtFetch = $connPemasangan->prepare("SELECT * FROM pemasangan WHERE id = ?");
            if ($stmtFetch) {
                $stmtFetch->bind_param("i", $id);
                $stmtFetch->execute();
                $resultFetch = $stmtFetch->get_result();
                $customerData = $resultFetch->fetch_assoc();
                $stmtFetch->close();

                // Get the package data from the previously loaded $paketArray
                $paketData = $paketArray[$paket] ?? [];

                // Send the WhatsApp notification
                sendWhatsAppNotification($customerData, $paketData);
            } else {
                error_log("Failed to prepare statement for fetching customer data: " . $connPemasangan->error);
            }
            // --- END WhatsApp Notification Integration ---

        } else {
            set_notification("Aktivasi gagal atau tidak ada perubahan data.");
        }
        $stmt->close();
    } else {
        error_log("Failed to prepare statement for activation: " . $connPemasangan->error);
        set_notification("Terjadi kesalahan sistem saat persiapan aktivasi.");
    }
    header("Location: aktivasi_pelanggan.php"); // Redirect to prevent re-submission
    exit;
}

$sql = "SELECT * FROM pemasangan WHERE status='belum diproses' ORDER BY tanggal DESC";
$result = $connPemasangan->query($sql);
if (!$result) {
    error_log("Error fetching 'belum diproses' data: " . $connPemasangan->error);
    // Create an empty object to prevent errors in the loop if query fails
    $result = (object)['num_rows' => 0];
}

include('navbar.php');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Aktivasi Pelanggan (Admin)</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        body { background: #f4f7fb; }
        .container-max { max-width: 1200px; margin: auto; }
        .table-card { background: #fff; box-shadow: 0 2px 14px rgba(0, 0, 0, 0.05); border-radius: 16px; overflow: hidden; }
        .table thead th { background: linear-gradient(90deg,#3498db 0%,#16a085 100%) !important; color: #fff !important; border: none; }
        .table-bordered td, .table-bordered th { border: 1px solid #e5e7eb; }
        .table tbody tr:hover { background: #f1f5f9; }
        .form-control:focus { border-color: #16a085; box-shadow: 0 0 0 .12rem rgba(22, 160, 133, 0.2); }
        .btn-success, .btn-success:active, .btn-success:focus { background: linear-gradient(90deg,#16a085,#27ae60) !important; border: none; }
        .btn-success:hover { filter: brightness(1.1); }
        .alert-info { background: #eafaf1; color: #27ae60; border: 1px solid #d1fae5; }
        .alert-danger { background: #ffeaea; color: #e74c3c; border: 1px solid #f9cacb; }
        .floating-btn { position: fixed; bottom: 30px; right: 30px; z-index: 1000; background: #16a085; color: #fff; border-radius: 50%; width: 56px; height: 56px; box-shadow: 0 6px 20px rgba(22, 160, 133, 0.2); display: flex; align-items: center; justify-content: center; font-size: 1.7em; border: none; transition: background .2s;}
        .floating-btn:hover { background: #1abc9c; }
        @media (max-width: 600px) {
            .container-max { padding: 0 2px;}
            .table { font-size: 12px; min-width: 900px; }
            .btn, .badge { font-size: 11px;}
            h2 { font-size: 20px;}
            .table-responsive { overflow-x: auto !important; -webkit-overflow-scrolling: touch;}
        }
    </style>
</head>
<body>
<?php display_notification(); // Display notifications at the top of the body ?>
<div class="container container-max mt-4">
    <h2 class="mb-4 text-center fw-bold text-primary"><i class="bi bi-lightning-charge-fill"></i> Aktivasi Pelanggan</h2>
    <div class="table-responsive table-card">
        <table class="table table-bordered align-middle mb-0">
            <thead>
                <tr class="text-center">
                    <th>No</th>
                    <th>POP</th>
                    <th>Nama</th>
                    <th>Paket</th>
                    <th>KTP</th>
                    <th>Alamat</th>
                    <th>No Telp</th>
                    <th>Email</th>
                    <th>Tanggal</th>
                    <th>Aktivasi</th>
                </tr>
            </thead>
            <tbody>
            <?php if ($result->num_rows == 0): ?>
                <tr><td colspan="10" class="text-center text-danger">Belum ada data untuk diaktivasi</td></tr>
            <?php else: $no = 1; while($row = $result->fetch_assoc()): ?>
                <tr>
                    <td class="text-center"><?= $no++; ?></td>
                    <td><?= htmlspecialchars($row['pop']) ?></td>
                    <td><?= htmlspecialchars($row['nama']) ?></td>
                    <td>
                        <?php
                        $idPaket = intval($row['paket']);
                        if(isset($paketArray[$idPaket])){
                            $p = $paketArray[$idPaket];
                            echo "<span class='fw-bold text-success'>".htmlspecialchars($p['nama_paket'])."</span> <small>(".htmlspecialchars($p['kecepatan']).")</small><br><span class='badge bg-info text-dark'>Rp ".number_format($p['harga'],0,',','.')."</span>";
                        } else {
                            echo "<span class='text-danger'>-</span>";
                        }
                        ?>
                    </td>
                    <td><?= htmlspecialchars($row['ktp']) ?></td>
                    <td><?= htmlspecialchars($row['alamat']) ?></td>
                    <td><?= htmlspecialchars($row['telp']) ?></td>
                    <td><?= htmlspecialchars($row['email']) ?></td>
                    <td class="text-center"><?= date('d-m-Y', strtotime($row['tanggal'])) ?></td>
                    <td class="text-center">
                        <button class="btn btn-success btn-sm"
                                data-bs-toggle="modal"
                                data-bs-target="#modalAktivasi<?= $row['id'] ?>">
                            <i class="bi bi-person-check"></i> Aktivasi
                        </button>
                        <div class="modal fade" id="modalAktivasi<?= $row['id'] ?>" tabindex="-1" aria-labelledby="modalLabel<?= $row['id'] ?>" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered">
                                <div class="modal-content">
                                    <form method="post">
                                        <input type="hidden" name="id_aktivasi" value="<?= $row['id'] ?>">
                                        <div class="modal-header">
                                            <h5 class="modal-title" id="modalLabel<?= $row['id'] ?>"><i class="bi bi-person-check"></i> Aktivasi Pelanggan</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="mb-2">
                                                <label class="form-label">Paket</label>
                                                <select name="paket" class="form-control" required>
                                                    <option value="">Pilih Paket</option>
                                                    <?php foreach($paketArray as $paket_option): ?>
                                                        <option value="<?= $paket_option['id_paket'] ?>" <?= ($paket_option['id_paket']==$row['paket']?'selected':'') ?>>
                                                            <?= htmlspecialchars($paket_option['nama_paket']) ?> (<?= htmlspecialchars($paket_option['kecepatan']) ?>) - Rp <?= number_format($paket_option['harga'],0,',','.') ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div class="mb-2">
                                                <label class="form-label">Username PPPoE</label>
                                                <input type="text" name="userppp" class="form-control" placeholder="Username PPPoE" required value="<?= htmlspecialchars($row['userppp'] ?? '') ?>">
                                            </div>
                                            <div class="mb-2">
                                                <label class="form-label">Password PPPoE</label>
                                                <input type="text" name="passwordppp" class="form-control" placeholder="Password PPPoE" required value="<?= htmlspecialchars($row['passwordppp'] ?? '') ?>">
                                            </div>
                                            <div class="mb-2">
                                                <label class="form-label">VLAN</label>
                                                <input type="text" name="vlan" class="form-control" placeholder="VLAN" required value="<?= htmlspecialchars($row['vlan'] ?? '') ?>">
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                            <button type="submit" class="btn btn-success"><i class="bi bi-check2-circle"></i> Simpan & Aktivasi</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </td>
                </tr>
            <?php endwhile; endif; ?>
            </tbody>
        </table>
    </div>
    <a href="dashboard.php" class="floating-btn" title="Kembali ke Dashboard"><i class="bi bi-arrow-left"></i></a>
    <div class="text-center mt-4 mb-2">
        <small class="text-muted">© <?= date('Y') ?> PT. Real Data Solusindo</small>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php
// Close database connections
$connPemasangan->close();
$connUmum->close();
?>