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
    $nama_provider          = $_POST['nama_provider'];
    $deskripsi_layanan      = $_POST['deskripsi_layanan'];
    $periode_bayar          = date('Y-m-01', strtotime($_POST['periode_bayar']));
    $tanggal_bayar          = $_POST['tanggal_bayar'];
    $biaya                  = $_POST['biaya'];
    $no_rekening_penerima   = $_POST['no_rekening_penerima'];
    $nama_rekening_penerima = $_POST['nama_rekening_penerima'];
    $keterangan             = $_POST['keterangan'];

    // Gunakan prepared statement untuk keamanan
    $stmt = $conn->prepare("INSERT INTO pembayaran_upstream (nama_provider, deskripsi_layanan, periode_bayar, tanggal_bayar, biaya, no_rekening_penerima, nama_rekening_penerima, keterangan) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssdsss", $nama_provider, $deskripsi_layanan, $periode_bayar, $tanggal_bayar, $biaya, $no_rekening_penerima, $nama_rekening_penerima, $keterangan);

    if ($stmt->execute()) {
        echo "<div class='alert alert-success'>Data pembayaran upstream berhasil dicatat.</div>";
    } else {
        echo "<div class='alert alert-danger'>Error: " . $stmt->error . "</div>";
    }
    $stmt->close();
}

// Ambil daftar nama provider yang sudah ada untuk saran input
$provider_list = [];
$query_provider = $conn->query("SELECT DISTINCT nama_provider FROM pembayaran_upstream ORDER BY nama_provider ASC");
if ($query_provider) {
    while($p_row = $query_provider->fetch_assoc()) {
        $provider_list[] = $p_row['nama_provider'];
    }
}
?>

<h1><i class="bi bi-reception-4"></i> Pencatatan Pembayaran Upstream</h1>

<div class="form-card">
    <h2>Catat Pembayaran Baru</h2>
    <form action="bayar_upstream.php" method="POST">
        <div class="row">
            <div class="col-md-6 mb-3">
                <label for="nama_provider" class="form-label">Nama Provider</label>
                <input type="text" id="nama_provider" name="nama_provider" class="form-control" list="datalistProvider" placeholder="Contoh: PT GRASI PUSAT" required>
                <datalist id="datalistProvider">
                    <?php foreach($provider_list as $nama): ?>
                        <option value="<?= htmlspecialchars($nama) ?>">
                    <?php endforeach; ?>
                </datalist>
            </div>
            <div class="col-md-6 mb-3">
                <label for="deskripsi_layanan" class="form-label">Nama / Deskripsi Layanan</label>
                <input type="text" id="deskripsi_layanan" name="deskripsi_layanan" class="form-control" placeholder="Contoh: Bandwith 5 Gbps / Metro" required>
            </div>
        </div>

        <div class="row">
             <div class="col-md-4 mb-3">
                <label for="periode_bayar" class="form-label">Untuk Periode</label>
                <input type="month" id="periode_bayar" name="periode_bayar" class="form-control" value="<?= date('Y-m') ?>" required>
            </div>
            <div class="col-md-4 mb-3">
                <label for="tanggal_bayar" class="form-label">Tanggal Bayar</label>
                <input type="date" id="tanggal_bayar" name="tanggal_bayar" class="form-control" value="<?= date('Y-m-d') ?>" required>
            </div>
            <div class="col-md-4 mb-3">
                <label for="biaya" class="form-label">Nilai / Harga (Rp)</label>
                <input type="number" id="biaya" name="biaya" class="form-control" placeholder="Contoh: 10000000" required>
            </div>
        </div>
        
        <hr>
        <h5 class="mb-3">Informasi Rekening Tujuan</h5>
        <div class="row">
            <div class="col-md-6 mb-3">
                <label for="no_rekening_penerima" class="form-label">Nomor Rekening Penerima</label>
                <input type="text" id="no_rekening_penerima" name="no_rekening_penerima" class="form-control">
            </div>
             <div class="col-md-6 mb-3">
                <label for="nama_rekening_penerima" class="form-label">Atas Nama Rekening</label>
                <input type="text" id="nama_rekening_penerima" name="nama_rekening_penerima" class="form-control">
            </div>
        </div>

        <div class="mb-3">
            <label for="keterangan" class="form-label">Keterangan (Opsional)</label>
            <textarea id="keterangan" name="keterangan" class="form-control" rows="2" placeholder="Contoh: Termasuk biaya kabel bersama"></textarea>
        </div>

        <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Simpan Pembayaran</button>
    </form>
</div>

<div class="table-container mt-4">
    <h2>Riwayat Pembayaran Upstream</h2>
    <div class="table-responsive">
        <table class="table table-striped table-hover">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Tanggal Bayar</th>
                    <th>Periode</th>
                    <th>Nama Provider</th>
                    <th>Layanan</th>
                    <th>Biaya</th>
                </tr>
            </thead>
            <tbody>
                <?php
                // Query untuk mengambil data riwayat
                $result = $conn->query("SELECT * FROM pembayaran_upstream ORDER BY tanggal_bayar DESC, id DESC LIMIT 100");
                $no = 1;
                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        echo "<tr>";
                        echo "<td>" . $no++ . "</td>";
                        echo "<td>" . date("d M Y", strtotime($row['tanggal_bayar'])) . "</td>";
                        echo "<td>" . date("F Y", strtotime($row['periode_bayar'])) . "</td>";
                        echo "<td><b>" . htmlspecialchars($row['nama_provider']) . "</b></td>";
                        echo "<td>" . htmlspecialchars($row['deskripsi_layanan']) . "</td>";
                        echo "<td>Rp " . number_format($row['biaya'], 0, ',', '.') . "</td>";
                        echo "</tr>";
                    }
                } else {
                    echo "<tr><td colspan='6' class='text-center text-muted p-4'>Belum ada riwayat pembayaran upstream.</td></tr>";
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