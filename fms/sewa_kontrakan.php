<?php
session_start();
// Cek apakah user sudah login atau belum
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: login.php');
    exit;
}

// Memuat header FMS. Koneksi ke DB FMS ($conn) sudah otomatis tersedia.
require_once 'templates/header.php';

// Proses form jika ada data yang dikirim (method POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Ambil data dari form
    $nama_kontrakan  = $_POST['nama_kontrakan'];
    $lokasi          = $_POST['lokasi'];
    $alamat          = $_POST['alamat'];
    // Input bulan diubah menjadi format tanggal (hari pertama bulan tersebut)
    $periode_sewa    = date('Y-m-01', strtotime($_POST['periode_sewa']));
    $tanggal_bayar   = $_POST['tanggal_bayar'];
    $biaya_per_bulan = $_POST['biaya_per_bulan'];
    $keterangan      = $_POST['keterangan'];

    // Gunakan prepared statement untuk keamanan
    $stmt = $conn->prepare("INSERT INTO keu_pembayaran_sewa (nama_kontrakan, lokasi, alamat, periode_sewa, tanggal_bayar, biaya_per_bulan, keterangan) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssds", $nama_kontrakan, $lokasi, $alamat, $periode_sewa, $tanggal_bayar, $biaya_per_bulan, $keterangan);

    if ($stmt->execute()) {
        echo "<div class='alert alert-success'>Data pembayaran sewa berhasil dicatat.</div>";
    } else {
        echo "<div class='alert alert-danger'>Error: " . $stmt->error . "</div>";
    }
    $stmt->close();
}

// Ambil daftar nama kontrakan yang sudah ada untuk saran input
$kontrakan_list = [];
$query_kontrakan = $conn->query("SELECT DISTINCT nama_kontrakan FROM keu_pembayaran_sewa ORDER BY nama_kontrakan ASC");
if ($query_kontrakan) {
    while($k_row = $query_kontrakan->fetch_assoc()) {
        $kontrakan_list[] = $k_row['nama_kontrakan'];
    }
}
?>

<h1><i class="bi bi-house-door-fill"></i> Pencatatan Sewa Kontrakan</h1>

<div class="form-card">
    <h2>Catat Pembayaran Sewa Baru</h2>
    <form action="sewa_kontrakan.php" method="POST">
        <div class="row">
            <div class="col-md-6 mb-3">
                <label for="nama_kontrakan" class="form-label">Nama Kontrakan</label>
                <input type="text" id="nama_kontrakan" name="nama_kontrakan" class="form-control" list="datalistKontrakan" placeholder="Contoh: Ruko Cabang Mauk" required>
                <datalist id="datalistKontrakan">
                    <?php foreach($kontrakan_list as $nama): ?>
                        <option value="<?= htmlspecialchars($nama) ?>">
                    <?php endforeach; ?>
                </datalist>
            </div>
            <div class="col-md-6 mb-3">
                <label for="lokasi" class="form-label">Lokasi</label>
                <input type="text" id="lokasi" name="lokasi" class="form-control" placeholder="Contoh: Mauk, Tangerang">
            </div>
        </div>

        <div class="mb-3">
            <label for="alamat" class="form-label">Alamat Lengkap</label>
            <textarea id="alamat" name="alamat" class="form-control" rows="2"></textarea>
        </div>

        <div class="row">
            <div class="col-md-4 mb-3">
                <label for="periode_sewa" class="form-label">Untuk Periode Sewa</label>
                <input type="month" id="periode_sewa" name="periode_sewa" class="form-control" value="<?= date('Y-m') ?>" required>
            </div>
            <div class="col-md-4 mb-3">
                <label for="tanggal_bayar" class="form-label">Tanggal Bayar</label>
                <input type="date" id="tanggal_bayar" name="tanggal_bayar" class="form-control" value="<?= date('Y-m-d') ?>" required>
            </div>
            <div class="col-md-4 mb-3">
                <label for="biaya_per_bulan" class="form-label">Biaya Sewa (Rp)</label>
                <input type="number" id="biaya_per_bulan" name="biaya_per_bulan" class="form-control" placeholder="Contoh: 2000000" required>
            </div>
        </div>

        <div class="mb-3">
            <label for="keterangan" class="form-label">Keterangan (Opsional)</label>
            <textarea id="keterangan" name="keterangan" class="form-control" rows="2" placeholder="Contoh: Pembayaran via transfer Bank BCA"></textarea>
        </div>

        <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Simpan Pembayaran</button>
    </form>
</div>

<div class="table-container mt-4">
    <h2>Riwayat Pembayaran Sewa</h2>
    <div class="table-responsive">
        <table class="table table-striped table-hover">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Tanggal Bayar</th>
                    <th>Periode Sewa</th>
                    <th>Nama Kontrakan</th>
                    <th>Lokasi</th>
                    <th>Biaya</th>
                </tr>
            </thead>
            <tbody>
                <?php
                // Query untuk mengambil data riwayat
                $result = $conn->query("SELECT * FROM keu_pembayaran_sewa ORDER BY tanggal_bayar DESC, id DESC LIMIT 100");
                $no = 1;
                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        echo "<tr>";
                        echo "<td>" . $no++ . "</td>";
                        echo "<td>" . date("d M Y", strtotime($row['tanggal_bayar'])) . "</td>";
                        echo "<td>" . date("F Y", strtotime($row['periode_sewa'])) . "</td>"; // Format "Bulan Tahun"
                        echo "<td>" . htmlspecialchars($row['nama_kontrakan']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['lokasi']) . "</td>";
                        echo "<td>Rp " . number_format($row['biaya_per_bulan'], 0, ',', '.') . "</td>";
                        echo "</tr>";
                    }
                } else {
                    echo "<tr><td colspan='6' class='text-center text-muted p-4'>Belum ada riwayat pembayaran sewa.</td></tr>";
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