<?php
session_start();
// Cek apakah user sudah login atau belum
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: login.php');
    exit;
}

// Memuat header FMS. Koneksi ke DB FMS ($conn) sudah otomatis tersedia.
require_once 'templates/header.php';

// Daftar lokasi yang sudah ditentukan. Ini membuatnya konsisten.
$daftar_lokasi = [
    'Server Rajeg',
    'Server Mauk',
    'Server Karangserang',
    'Server Sasak',
    'Server Kemeri',
    'Kantor Rajeg'
];

// Proses form jika ada data yang dikirim (method POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Ambil data dari form
    $lokasi          = $_POST['lokasi'];
    $jenis_pembayaran = $_POST['jenis_pembayaran'];
    $nomor_referensi = $_POST['nomor_referensi'];
    $tanggal_bayar   = $_POST['tanggal_bayar'];
    $biaya           = $_POST['biaya'];
    $keterangan      = $_POST['keterangan'];

    // Gunakan prepared statement untuk keamanan
    $stmt = $conn->prepare("INSERT INTO biaya_listrik (lokasi, jenis_pembayaran, nomor_referensi, tanggal_bayar, biaya, keterangan) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssds", $lokasi, $jenis_pembayaran, $nomor_referensi, $tanggal_bayar, $biaya, $keterangan);

    if ($stmt->execute()) {
        echo "<div class='alert alert-success'>Data biaya listrik berhasil ditambahkan.</div>";
    } else {
        echo "<div class='alert alert-danger'>Error: " . $stmt->error . "</div>";
    }
    $stmt->close();
}
?>

<h1><i class="bi bi-lightning-charge-fill"></i> Manajemen Biaya Listrik</h1>

<div class="form-card">
    <h2>Tambah Data Biaya Listrik</h2>
    <form action="biaya_listrik.php" method="POST">
        <div class="row">
            <div class="col-md-6 mb-3">
                <label for="lokasi" class="form-label">Lokasi</label>
                <select id="lokasi" name="lokasi" class="form-select" required>
                    <option value="">-- Pilih Lokasi --</option>
                    <?php foreach ($daftar_lokasi as $lok): ?>
                        <option value="<?= htmlspecialchars($lok) ?>"><?= htmlspecialchars($lok) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-6 mb-3">
                <label for="jenis_pembayaran" class="form-label">Jenis Pembayaran</label>
                <select id="jenis_pembayaran" name="jenis_pembayaran" class="form-select" required>
                    <option value="Token">Token</option>
                    <option value="Tagihan">Tagihan</option>
                </select>
            </div>
        </div>
        <div class="row">
            <div class="col-md-6 mb-3">
                <label for="nomor_referensi" class="form-label">Nomor Token / ID Pelanggan</label>
                <input type="text" id="nomor_referensi" name="nomor_referensi" class="form-control" placeholder="Masukkan nomor...">
            </div>
             <div class="col-md-6 mb-3">
                <label for="tanggal_bayar" class="form-label">Tanggal Bayar / Isi</label>
                <input type="date" id="tanggal_bayar" name="tanggal_bayar" class="form-control" value="<?= date('Y-m-d') ?>" required>
            </div>
        </div>
        <div class="mb-3">
            <label for="biaya" class="form-label">Total Biaya (Rp)</label>
            <input type="number" step="100" id="biaya" name="biaya" class="form-control" placeholder="Contoh: 500000" required>
        </div>
        <div class="mb-3">
            <label for="keterangan" class="form-label">Keterangan (Opsional)</label>
            <textarea id="keterangan" name="keterangan" class="form-control" rows="2"></textarea>
        </div>

        <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Simpan Data</button>
    </form>
</div>

<div class="table-container mt-4">
    <h2>Riwayat Pembayaran Biaya Listrik</h2>
    <div class="table-responsive">
        <table class="table table-striped table-hover">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Tanggal</th>
                    <th>Lokasi</th>
                    <th>Jenis</th>
                    <th>No. Referensi</th>
                    <th>Biaya</th>
                    <th>Keterangan</th>
                </tr>
            </thead>
            <tbody>
                <?php
                // Query untuk mengambil data riwayat
                $result = $conn->query("SELECT * FROM biaya_listrik ORDER BY tanggal_bayar DESC, id DESC");
                $no = 1;
                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        echo "<tr>";
                        echo "<td>" . $no++ . "</td>";
                        echo "<td>" . date("d M Y", strtotime($row['tanggal_bayar'])) . "</td>";
                        echo "<td>" . htmlspecialchars($row['lokasi']) . "</td>";
                        echo "<td><span class='badge " . ($row['jenis_pembayaran'] == 'Token' ? 'bg-primary' : 'bg-success') . "'>" . htmlspecialchars($row['jenis_pembayaran']) . "</span></td>";
                        echo "<td>" . htmlspecialchars($row['nomor_referensi']) . "</td>";
                        echo "<td>Rp " . number_format($row['biaya'], 0, ',', '.') . "</td>";
                        echo "<td>" . nl2br(htmlspecialchars($row['keterangan'])) . "</td>";
                        echo "</tr>";
                    }
                } else {
                    echo "<tr><td colspan='7' class='text-center text-muted p-4'>Belum ada data biaya listrik.</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
</div>

<?php
// Memuat footer FMS
require_once 'templates/footer.php';
?>