<?php
// Konfigurasi database
$servername = "localhost";
$username = "u272457353_kevinsamsung";
$password = "Admionkevin99";
$database = "u272457353_tiket_helpdesk";

// Koneksi ke database
$conn = new mysqli($servername, $username, $password, $database);
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

// Sertakan navbar
include('navbar.php');

$ticket = null;
$message = '';
$message_type = ''; // 'success' or 'danger'

// Cek apakah ada ID tiket yang dikirim melalui GET
if (isset($_GET['id']) && !empty($_GET['id'])) {
    $id = intval($_GET['id']); // Pastikan ID adalah integer untuk keamanan

    // Ambil data tiket berdasarkan ID
    $stmt = $conn->prepare("SELECT * FROM tiket WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $ticket = $result->fetch_assoc();
    } else {
        $message = "Tiket tidak ditemukan.";
        $message_type = "danger";
    }
    $stmt->close();
} else {
    $message = "ID tiket tidak disediakan.";
    $message_type = "danger";
}

// Proses jika formulir disubmit (metode POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $id = intval($_POST['id']);
    $nama_pelanggan = $_POST['nama_pelanggan'];
    $alamat = $_POST['alamat'];
    $pop = $_POST['pop'];
    $whatsapp = $_POST['whatsapp'];
    $vlan = $_POST['vlan'];
    $sn = $_POST['sn'];
    $keluhan = $_POST['keluhan'];
    $maps_url = $_POST['maps_url'];
    $teknisi = $_POST['teknisi'];
    $status = $_POST['status'];
    $tanggal_selesai = null;

    // Jika status diubah menjadi 'selesai', set tanggal_selesai ke waktu saat ini
    if ($status === 'selesai') {
        $tanggal_selesai = date('Y-m-d H:i:s');
    } else {
        // Jika status bukan 'selesai', atau diubah dari 'selesai' ke status lain,
        // pastikan tanggal_selesai di-reset ke NULL di database jika sebelumnya ada nilai.
        // Anda mungkin perlu mengambil nilai yang ada dari DB terlebih dahulu jika ingin mempertahankan tanggal_selesai jika status berubah kembali ke 'selesai'
        // Untuk kesederhanaan, kita akan reset ke NULL jika status tidak 'selesai'
        $stmt_check_status = $conn->prepare("SELECT status, tanggal_selesai FROM tiket WHERE id = ?");
        $stmt_check_status->bind_param("i", $id);
        $stmt_check_status->execute();
        $result_check = $stmt_check_status->get_result();
        $old_ticket_data = $result_check->fetch_assoc();
        $stmt_check_status->close();

        if ($old_ticket_data['status'] === 'selesai' && $status !== 'selesai') {
            $tanggal_selesai = NULL; // Clear tanggal_selesai if status reverted
        } else {
            // Keep existing tanggal_selesai if it was already set and status is not 'selesai'
            // or if it was not 'selesai' and still isn't 'selesai'.
            $tanggal_selesai = $old_ticket_data['tanggal_selesai'];
        }
    }


    // Query untuk update data
    $sql = "UPDATE tiket SET
                nama_pelanggan = ?,
                alamat = ?,
                pop = ?,
                whatsapp = ?,
                vlan = ?,
                sn = ?,
                keluhan = ?,
                maps_url = ?,
                teknisi = ?,
                status = ?,
                tanggal_selesai = ?
            WHERE id = ?";

    $stmt = $conn->prepare($sql);
    // 'ssissssssss' mewakili tipe data: string, string, int, string, string, string, string, string, string, string, string (tanggal_selesai bisa null)
    // Untuk tanggal_selesai, gunakan 's' dan jika null, MySQL akan menanganinya sebagai NULL jika kolomnya NULLABLE
    $stmt->bind_param(
        "ssissssssssi",
        $nama_pelanggan, $alamat, $pop, $whatsapp, $vlan, $sn,
        $keluhan, $maps_url, $teknisi, $status, $tanggal_selesai, $id
    );

    if ($stmt->execute()) {
        $message = "Tiket berhasil diperbarui!";
        $message_type = "success";
        // Refresh data tiket setelah update untuk menampilkan perubahan
        $stmt_refresh = $conn->prepare("SELECT * FROM tiket WHERE id = ?");
        $stmt_refresh->bind_param("i", $id);
        $stmt_refresh->execute();
        $result_refresh = $stmt_refresh->get_result();
        $ticket = $result_refresh->fetch_assoc();
        $stmt_refresh->close();
    } else {
        $message = "Error: " . $stmt->error;
        $message_type = "danger";
    }
    $stmt->close();
}

// Jika tiket tidak ditemukan setelah cek GET atau POST
if (!$ticket && $message_type === '') {
    $message = "Tiket tidak valid atau tidak ada ID tiket.";
    $message_type = "danger";
}

// Ambil daftar POP unik untuk dropdown (jika diperlukan untuk input POP)
$query_all_pop = "SELECT DISTINCT pop FROM tiket ORDER BY pop ASC";
$result_all_pop = $conn->query($query_all_pop);
$all_pops = [];
while ($row_pop = $result_all_pop->fetch_assoc()) {
    $all_pops[] = $row_pop['pop'];
}
// Tambahkan POP yang saat ini jika belum ada di daftar (misal untuk tiket baru)
if ($ticket && !in_array($ticket['pop'], $all_pops)) {
    $all_pops[] = $ticket['pop'];
    sort($all_pops); // Urutkan lagi
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Data Gangguan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background-color: #f8f9fa;
        }
        .card-header.bg-primary {
            background-color: #007bff !important;
            color: white !important;
        }
        .form-label {
            font-weight: 500;
        }
    </style>
</head>
<body>
<div class="container mt-4">
    <h2 class="text-center mb-4">Edit Data Gangguan Pelanggan</h2>

    <?php if ($message): ?>
        <div class="alert alert-<?= $message_type; ?> alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($message); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if ($ticket): ?>
        <div class="card shadow-sm">
            <div class="card-header bg-primary">
                <h5 class="mb-0">ID Tiket: <?= htmlspecialchars($ticket['id']); ?> - <?= htmlspecialchars($ticket['nama_pelanggan']); ?></h5>
            </div>
            <div class="card-body">
                <form action="edit_gangguan.php" method="POST">
                    <input type="hidden" name="id" value="<?= htmlspecialchars($ticket['id']); ?>">

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="nama_pelanggan" class="form-label">Nama Pelanggan</label>
                            <input type="text" class="form-control" id="nama_pelanggan" name="nama_pelanggan"
                                   value="<?= htmlspecialchars($ticket['nama_pelanggan']); ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="alamat" class="form-label">Alamat</label>
                            <input type="text" class="form-control" id="alamat" name="alamat"
                                   value="<?= htmlspecialchars($ticket['alamat']); ?>" required>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="pop" class="form-label">POP</label>
                            <select class="form-select" id="pop" name="pop" required>
                                <?php foreach ($all_pops as $p): ?>
                                    <option value="<?= htmlspecialchars($p); ?>"
                                            <?= $ticket['pop'] === $p ? 'selected' : ''; ?>>
                                        <?= htmlspecialchars($p); ?>
                                    </option>
                                <?php endforeach; ?>
                                <?php if (!in_array($ticket['pop'], $all_pops)): ?>
                                    <option value="<?= htmlspecialchars($ticket['pop']); ?>" selected>
                                        <?= htmlspecialchars($ticket['pop']); ?> (Saat Ini)
                                    </option>
                                <?php endif; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="whatsapp" class="form-label">Nomor WhatsApp</label>
                            <input type="text" class="form-control" id="whatsapp" name="whatsapp"
                                   value="<?= htmlspecialchars($ticket['whatsapp']); ?>">
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="vlan" class="form-label">VLAN</label>
                            <input type="text" class="form-control" id="vlan" name="vlan"
                                   value="<?= htmlspecialchars($ticket['vlan']); ?>">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="sn" class="form-label">SN</label>
                            <input type="text" class="form-control" id="sn" name="sn"
                                   value="<?= htmlspecialchars($ticket['sn']); ?>">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="teknisi" class="form-label">Teknisi</label>
                            <input type="text" class="form-control" id="teknisi" name="teknisi"
                                   value="<?= htmlspecialchars($ticket['teknisi']); ?>">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="keluhan" class="form-label">Keluhan</label>
                        <textarea class="form-control" id="keluhan" name="keluhan" rows="3" required><?= htmlspecialchars($ticket['keluhan']); ?></textarea>
                    </div>

                    <div class="mb-3">
                        <label for="maps_url" class="form-label">Maps URL (Google Maps Link)</label>
                        <input type="url" class="form-control" id="maps_url" name="maps_url"
                               value="<?= htmlspecialchars($ticket['maps_url']); ?>" placeholder="Contoh: https://maps.app.goo.gl/...">
                    </div>

                    <div class="mb-3">
                        <label for="status" class="form-label">Status</label>
                        <select class="form-select" id="status" name="status" required>
                            <option value="belum dikerjakan" <?= $ticket['status'] === 'belum dikerjakan' ? 'selected' : ''; ?>>Belum dikerjakan</option>
                            <option value="di proses" <?= $ticket['status'] === 'di proses' ? 'selected' : ''; ?>>Di proses</option>
                            <option value="selesai" <?= $ticket['status'] === 'selesai' ? 'selected' : ''; ?>>Selesai</option>
                        </select>
                    </div>

                    <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                        <button type="submit" class="btn btn-primary me-md-2">Simpan Perubahan</button>
                        <a href="gangguan.php" class="btn btn-secondary">Kembali ke Daftar Gangguan</a>
                    </div>
                </form>
            </div>
        </div>
    <?php else: ?>
        <div class="alert alert-info text-center" role="alert">
            Silakan pilih tiket yang ingin diedit dari <a href="gangguan.php">daftar gangguan</a>.
        </div>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php $conn->close(); ?>