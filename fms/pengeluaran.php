<?php
// Memasukkan header dan koneksi database
require_once 'templates/header.php';

// --- LOGIKA PEMROSESAN FORM ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Ambil data dari form dan amankan
    $keterangan = htmlspecialchars($_POST['keterangan']);
    $jumlah = $_POST['jumlah'];
    $tanggal = $_POST['tanggal'];
    
    // Validasi sederhana
    if (!empty($keterangan) && !empty($jumlah) && !empty($tanggal)) {
        // Query INSERT ke tabel 'pengeluaran'
        $stmt = $conn->prepare("INSERT INTO pengeluaran (keterangan, jumlah, tanggal) VALUES (?, ?, ?)");
        $stmt->bind_param("sds", $keterangan, $jumlah, $tanggal);

        if ($stmt->execute()) {
            echo "<div class='alert success'>Data pengeluaran berhasil ditambahkan.</div>";
        } else {
            echo "<div class='alert error'>Error: " . $stmt->error . "</div>";
        }
        $stmt->close();
    }
}
?>

<h1>Manajemen Pengeluaran</h1>

<div class="form-card">
    <h2>Tambah Pengeluaran Baru</h2>
    <form action="pengeluaran.php" method="POST">
        <div class="form-group">
            <label for="keterangan">Keterangan</label>
            <input type="text" id="keterangan" name="keterangan" placeholder="Contoh: Bayar tagihan internet" required>
        </div>
        <div class="form-group">
            <label for="jumlah">Jumlah (Rp)</label>
            <input type="number" step="100" id="jumlah" name="jumlah" placeholder="Contoh: 350000" required>
        </div>
        <div class="form-group">
            <label for="tanggal">Tanggal</label>
            <input type="date" id="tanggal" name="tanggal" value="<?php echo date('Y-m-d'); ?>" required>
        </div>
        <button type="submit" class="btn">Simpan Pengeluaran</button>
    </form>
</div>

<div class="table-container">
    <h2>Riwayat Pengeluaran</h2>
    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Tanggal</th>
                <th>Keterangan</th>
                <th>Jumlah</th>
            </tr>
        </thead>
        <tbody>
            <?php
            // Ambil data dari tabel 'pengeluaran'
            $result = $conn->query("SELECT * FROM pengeluaran ORDER BY tanggal DESC");
            $no = 1;
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    echo "<tr>";
                    echo "<td>" . $no++ . "</td>";
                    echo "<td>" . date("d M Y", strtotime($row['tanggal'])) . "</td>";
                    echo "<td>" . htmlspecialchars($row['keterangan']) . "</td>";
                    echo "<td>Rp " . number_format($row['jumlah'], 0, ',', '.') . "</td>";
                    echo "</tr>";
                }
            } else {
                echo "<tr><td colspan='4' style='text-align:center;'>Belum ada data pengeluaran.</td></tr>";
            }
            ?>
        </tbody>
    </table>
</div>

<?php
// Memasukkan footer
require_once 'templates/footer.php';
?>