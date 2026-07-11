<?php
// scan_gudang_modem.php
session_start();
date_default_timezone_set('Asia/Jakarta');

// =======================
// Koneksi Database
// =======================
$servername = "localhost";
$username   = "u272457353_kevinsamsung99";
$password   = "Admionkevin99";
$database   = "u272457353_umumdata";

$conn = new mysqli($servername, $username, $password, $database);
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}
$conn->set_charset("utf8mb4");

// =======================
// Helper flash message sederhana
// =======================
if (!isset($_SESSION['flash'])) {
    $_SESSION['flash'] = [];
}

function flash($type, $msg) {
    $_SESSION['flash'][] = ['type' => $type, 'msg' => $msg];
}

function show_flash() {
    if (empty($_SESSION['flash'])) return;
    foreach ($_SESSION['flash'] as $f) {
        $cls = $f['type'] === 'success' ? 'alert-success' : 'alert-danger';
        echo '<div class="alert '.$cls.' alert-dismissible fade show" role="alert">'
           . htmlspecialchars($f['msg'], ENT_QUOTES)
           . '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>'
           . '</div>';
    }
    $_SESSION['flash'] = [];
}

// =======================
// Proses Scan (POST)
// =======================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sn = strtoupper(trim($_POST['sn'] ?? ''));

    if ($sn === '') {
        flash('error', 'Serial Number kosong, ulangi scan.');
    } else {
        // Cek apakah SN sudah ada di tabel modem
        $stmt = $conn->prepare("SELECT id_modem FROM modem WHERE serial_number = ?");
        $stmt->bind_param("s", $sn);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            // UPDATE: set status=ready, lokasi=GUDANG
            $stmt->bind_result($id_modem);
            $stmt->fetch();
            $stmt->close();

            $status = 'ready';
            $lokasi = 'GUDANG';

            $upd = $conn->prepare("
                UPDATE modem
                SET status = ?, lokasi_penyimpanan = ?, tanggal_masuk = CURDATE()
                WHERE id_modem = ?
            ");
            $upd->bind_param("ssi", $status, $lokasi, $id_modem);
            if ($upd->execute()) {
                flash('success', "SN {$sn} ditemukan, diupdate menjadi READY di GUDANG.");
            } else {
                flash('error', "Gagal update SN {$sn}: " . $conn->error);
            }
            $upd->close();
        } else {
            // INSERT baru
            $stmt->close();

            $status = 'ready';
            $lokasi = 'GUDANG';
            $model  = '';    // opsional, bisa diisi default
            $merk   = '';    // opsional

            $ins = $conn->prepare("
                INSERT INTO modem (serial_number, model, merk, status, tanggal_masuk, lokasi_penyimpanan)
                VALUES (?, ?, ?, ?, CURDATE(), ?)
            ");
            $ins->bind_param("sssss", $sn, $model, $merk, $status, $lokasi);
            if ($ins->execute()) {
                flash('success', "SN {$sn} berhasil DITAMBAHKAN sebagai READY di GUDANG.");
            } else {
                flash('error', "Gagal insert SN {$sn}: " . $conn->error);
            }
            $ins->close();
        }
    }

    // Redirect supaya F5 tidak mengulang submit
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// =======================
// Ambil 20 data terakhir di GUDANG
// =======================
$recent = $conn->query("
    SELECT serial_number, model, merk, status, tanggal_masuk, lokasi_penyimpanan
    FROM modem
    WHERE lokasi_penyimpanan = 'GUDANG'
    ORDER BY id_modem DESC
    LIMIT 20
");
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Scan Modem Gudang - Ready GUDANG</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<style>
body {
    background: #0f172a;
    color: #e5e7eb;
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
}
.main-box {
    background: #020617;
    border-radius: 16px;
    padding: 24px;
    max-width: 800px;
    width: 100%;
    box-shadow: 0 20px 60px rgba(0,0,0,0.6);
}
.form-control-lg {
    font-size: 1.4rem;
    font-weight: bold;
    letter-spacing: 2px;
}
.table-dark-green {
    background-color: #020617;
}
</style>
</head>
<body>

<div class="main-box">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h3 class="mb-0"><i class="bi bi-upc-scan text-success"></i> Scan Gudang Modem</h3>
            <small class="text-secondary">Status otomatis: <span class="text-success fw-bold">READY</span>, Lokasi: <span class="text-info fw-bold">GUDANG</span></small>
        </div>
        <div class="text-end">
            <span class="badge bg-success">Online</span>
            <br>
            <small class="text-secondary"><?=date('d/m/Y H:i')?></small>
        </div>
    </div>

    <?php show_flash(); ?>

    <form method="post" autocomplete="off" class="mb-3" id="scanForm">
        <label for="sn" class="form-label">Scan Serial Number</label>
        <div class="input-group input-group-lg mb-2">
            <span class="input-group-text bg-dark text-success"><i class="bi bi-barcode"></i></span>
            <input type="text" name="sn" id="sn" class="form-control form-control-lg" placeholder="Scan di sini..." autofocus>
        </div>
        <small class="text-muted">Pastikan kursor selalu di kolom ini. Barcode scanner akan mengirim SN + Enter.</small>
    </form>

    <hr class="border-secondary">

    <h5 class="mb-2"><i class="bi bi-clock-history"></i> 20 Data Terakhir di GUDANG</h5>
    <div class="table-responsive">
        <table class="table table-sm table-dark table-striped align-middle">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Serial Number</th>
                    <th>Model</th>
                    <th>Merk</th>
                    <th>Status</th>
                    <th>Tgl Masuk</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if ($recent && $recent->num_rows > 0):
                    $no = 1;
                    while ($row = $recent->fetch_assoc()):
                ?>
                <tr>
                    <td><?=$no++?></td>
                    <td class="fw-bold text-info"><?=$row['serial_number']?></td>
                    <td><?=htmlspecialchars($row['model'] ?: '-', ENT_QUOTES)?></td>
                    <td><?=htmlspecialchars($row['merk'] ?: '-', ENT_QUOTES)?></td>
                    <td>
                        <?php
                        $badge = 'secondary';
                        if ($row['status'] === 'ready')    $badge = 'success';
                        elseif ($row['status'] === 'dipasang') $badge = 'warning';
                        elseif ($row['status'] === 'rusak')    $badge = 'danger';
                        ?>
                        <span class="badge bg-<?=$badge?>"><?=htmlspecialchars($row['status'], ENT_QUOTES)?></span>
                    </td>
                    <td><?=date('d/m/Y', strtotime($row['tanggal_masuk']))?></td>
                </tr>
                <?php
                    endwhile;
                else:
                ?>
                <tr>
                    <td colspan="6" class="text-center text-secondary py-3">Belum ada data di GUDANG.</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Jaga supaya input selalu fokus dan otomatis clear setelah submit
const snInput  = document.getElementById('sn');
const scanForm = document.getElementById('scanForm');

// Fokus awal
window.addEventListener('load', () => {
    snInput.focus();
});

// Setelah submit server-side, halaman reload dan autofocus di input (via atribut autofocus).
// Tambahan jaga fokus jika user klik sembarang tempat:
document.addEventListener('click', function(e) {
    // Jika klik bukan di input SN, kembalikan fokus ke input
    if (e.target !== snInput) {
        snInput.focus();
    }
});
</script>
</body>
</html>
<?php
$conn->close();
?>
