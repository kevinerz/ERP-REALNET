<?php
session_start();
// Cek apakah user sudah login atau belum
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: login.php');
    exit;
}

// Memuat header FMS. Koneksi ke DB FMS ($conn) sudah otomatis tersedia dari 'templates/header.php'.
// Asumsi 'templates/header.php' sudah me-require 'config/db_connect.php' atau sejenisnya.
require_once 'templates/header.php';

$edit_id = null;
$edit_data = [];

// Ambil data untuk diedit jika ada parameter 'edit_id' di URL
if (isset($_GET['edit_id']) && is_numeric($_GET['edit_id'])) {
    $edit_id = $_GET['edit_id'];
    $stmt = $conn->prepare("SELECT * FROM pembayaran_kontribusi WHERE id = ?");
    $stmt->bind_param("i", $edit_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $edit_data = $result->fetch_assoc();
    } else {
        echo "<div class='alert alert-warning'>Data tidak ditemukan untuk ID: " . htmlspecialchars($edit_id) . "</div>";
        $edit_id = null; // Reset edit_id jika data tidak ditemukan
    }
    $stmt->close();
}

// Proses form jika ada data yang dikirim (method POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Ambil data dari form
    $id_to_process = $_POST['id_kontribusi'] ?? null; // Ambil ID jika ada (untuk update)
    $nama_penerima   = $_POST['nama_penerima'];
    $no_wa_penerima  = $_POST['no_wa_penerima'];
    $nama_kontribusi = $_POST['nama_kontribusi'];
    $tanggal_bayar   = $_POST['tanggal_bayar'];
    $nominal         = $_POST['nominal'];
    $keterangan      = $_POST['keterangan'];

    if ($id_to_process) {
        // Mode EDIT (UPDATE)
        $stmt = $conn->prepare("UPDATE pembayaran_kontribusi SET nama_penerima = ?, no_wa_penerima = ?, nama_kontribusi = ?, tanggal_bayar = ?, nominal = ?, keterangan = ? WHERE id = ?");
        $stmt->bind_param("ssssdsi", $nama_penerima, $no_wa_penerima, $nama_kontribusi, $tanggal_bayar, $nominal, $keterangan, $id_to_process);
        if ($stmt->execute()) {
            echo "<div class='alert alert-success'>Data kontribusi berhasil diperbarui.</div>";
            echo '<script>setTimeout(function(){ window.location.href = "kontribusi.php"; }, 1000);</script>'; // Refresh setelah update
        } else {
            echo "<div class='alert alert-danger'>Error saat memperbarui data: " . $stmt->error . "</div>";
        }
    } else {
        // Mode TAMBAH BARU (INSERT)
        $stmt = $conn->prepare("INSERT INTO pembayaran_kontribusi (nama_penerima, no_wa_penerima, nama_kontribusi, tanggal_bayar, nominal, keterangan) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssds", $nama_penerima, $no_wa_penerima, $nama_kontribusi, $tanggal_bayar, $nominal, $keterangan);

        if ($stmt->execute()) {
            echo "<div class='alert alert-success'>Data kontribusi berhasil dicatat.</div>";
        } else {
            echo "<div class='alert alert-danger'>Error: " . $stmt->error . "</div>";
        }
    }
    $stmt->close();
}
?>

<h1><i class="bi bi-gift-fill"></i> Pencatatan Kontribusi</h1>

<div class="form-card">
    <h2><?= $edit_id ? 'Edit Kontribusi' : 'Catat Kontribusi Baru' ?></h2>
    <form action="kontribusi.php" method="POST">
        <?php if ($edit_id): ?>
            <input type="hidden" name="id_kontribusi" value="<?= htmlspecialchars($edit_id) ?>">
        <?php endif; ?>

        <div class="row">
            <div class="col-md-6 mb-3">
                <label for="nama_penerima" class="form-label">Nama Penerima</label>
                <input type="text" id="nama_penerima" name="nama_penerima" class="form-control" placeholder="Nama perorangan atau lembaga" value="<?= htmlspecialchars($edit_data['nama_penerima'] ?? '') ?>" required>
            </div>
            <div class="col-md-6 mb-3">
                <label for="no_wa_penerima" class="form-label">Nomor WA Penerima</label>
                <input type="text" id="no_wa_penerima" name="no_wa_penerima" class="form-control" placeholder="Contoh: 0812xxxxxxxx" value="<?= htmlspecialchars($edit_data['no_wa_penerima'] ?? '') ?>">
            </div>
        </div>

        <div class="mb-3">
            <label for="nama_kontribusi" class="form-label">Nama/Tujuan Kontribusi</label>
            <input type="text" id="nama_kontribusi" name="nama_kontribusi" class="form-control" placeholder="Contoh: Sumbangan Acara 17an RT 05" value="<?= htmlspecialchars($edit_data['nama_kontribusi'] ?? '') ?>" required>
        </div>

        <div class="row">
            <div class="col-md-6 mb-3">
                <label for="tanggal_bayar" class="form-label">Tanggal Bayar</label>
                <input type="date" id="tanggal_bayar" name="tanggal_bayar" class="form-control" value="<?= htmlspecialchars($edit_data['tanggal_bayar'] ?? date('Y-m-d')) ?>" required>
            </div>
            <div class="col-md-6 mb-3">
                <label for="nominal" class="form-label">Nilai / Nominal (Rp)</label>
                <input type="number" id="nominal" name="nominal" class="form-control" placeholder="Contoh: 250000" value="<?= htmlspecialchars($edit_data['nominal'] ?? '') ?>" required>
            </div>
        </div>

        <div class="mb-3">
            <label for="keterangan" class="form-label">Keterangan (Opsional)</label>
            <textarea id="keterangan" name="keterangan" class="form-control" rows="2"><?= htmlspecialchars($edit_data['keterangan'] ?? '') ?></textarea>
        </div>

        <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> <?= $edit_id ? 'Update Kontribusi' : 'Simpan Kontribusi' ?></button>
        <?php if ($edit_id): ?>
            <a href="kontribusi.php" class="btn btn-secondary"><i class="bi bi-x-circle"></i> Batal Edit</a>
        <?php endif; ?>
    </form>
</div>

<div class="table-container mt-4">
    <h2>Riwayat Kontribusi
        <a href="print_kontribusi.php" class="btn btn-info btn-sm float-end" target="_blank">
            <i class="bi bi-printer"></i> Cetak Laporan PDF (Semua)
        </a>
    </h2>
    <div class="table-responsive">
        <table class="table table-striped table-hover">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Tanggal Bayar</th>
                    <th>Nama Kontribusi</th>
                    <th>Penerima</th>
                    <th>No. WA Penerima</th> <th>Nominal</th>
                    <th>Keterangan</th>
                    <th>Aksi</th> </tr>
            </thead>
            <tbody>
                <?php
                // Query untuk mengambil data riwayat
                $result = $conn->query("SELECT * FROM pembayaran_kontribusi ORDER BY tanggal_bayar DESC, id DESC LIMIT 100");
                $no = 1;
                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        echo "<tr>";
                        echo "<td>" . $no++ . "</td>";
                        echo "<td>" . date("d M Y", strtotime($row['tanggal_bayar'])) . "</td>";
                        echo "<td><b>" . htmlspecialchars($row['nama_kontribusi']) . "</b></td>";
                        echo "<td>" . htmlspecialchars($row['nama_penerima']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['no_wa_penerima']) . "</td>"; // Menampilkan Nomor WA
                        echo "<td>Rp " . number_format($row['nominal'], 0, ',', '.') . "</td>";
                        echo "<td>" . nl2br(htmlspecialchars($row['keterangan'])) . "</td>";
                        echo '<td>
                                <a href="kontribusi.php?edit_id=' . $row['id'] . '" class="btn btn-sm btn-warning me-1"><i class="bi bi-pencil"></i> Edit</a>
                                <a href="print_single_kontribusi.php?id=' . $row['id'] . '" class="btn btn-sm btn-secondary me-1" target="_blank"><i class="bi bi-file-earmark-pdf"></i> Cetak</a>
                                <a href="#" onclick="confirmDelete(' . $row['id'] . ')" class="btn btn-sm btn-danger"><i class="bi bi-trash"></i> Hapus</a>
                              </td>';
                        echo "</tr>";
                    }
                } else {
                    echo "<tr><td colspan='8' class='text-center text-muted p-4'>Belum ada riwayat kontribusi.</td></tr>";
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

<script>
function confirmDelete(id) {
    if (confirm("Anda yakin ingin menghapus data ini?")) {
        window.location.href = "kontribusi.php?delete_id=" + id;
    }
}
</script>

<?php
// Proses penghapusan jika ada parameter 'delete_id' di URL
if (isset($_GET['delete_id']) && is_numeric($_GET['delete_id'])) {
    $delete_id = $_GET['delete_id'];
    $stmt = $conn->prepare("DELETE FROM pembayaran_kontribusi WHERE id = ?");
    $stmt->bind_param("i", $delete_id);
    if ($stmt->execute()) {
        echo "<div class='alert alert-success'>Data kontribusi berhasil dihapus.</div>";
        echo '<script>setTimeout(function(){ window.location.href = "kontribusi.php"; }, 1000);</script>'; // Refresh setelah delete
    } else {
        echo "<div class='alert alert-danger'>Error saat menghapus data: " . $stmt->error . "</div>";
    }
    $stmt->close();
}
?>