<?php
require_once __DIR__ . '/config/database.php';
// ==========================================
// SESSION & AUTH GUARD — harus di baris paling atas
// ==========================================
session_start();

$dashboard_divisi = ['Admin', 'IT', 'Manager', 'SPV Teknis', 'Finance'];

if (!isset($_SESSION['username']) || !in_array($_SESSION['divisi'], $dashboard_divisi)) {
    header("Location: login.php");
    exit;
}
// ==========================================
// 1. KONEKSI & LOGIC UPDATE (GAJI POKOK + TUNJANGAN)
// ==========================================
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Konfigurasi Database
$servername = "localhost";
$username   = "u272457353_kevinsamsung99";
$password   = "Admionkevin99";
$database   = "u272457353_umumdata";

$conn = getErpDbConnection();
if ($conn->connect_error) {
    die("Koneksi Gagal: " . $conn->connect_error);
}
$conn->set_charset("utf8mb4");

// Cek ID di URL
if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo "<script>alert('ID Karyawan tidak ditemukan!'); window.location='dashkaryawan.php';</script>";
    exit;
}

$id = (int)$_GET['id'];

// Ambil Data Lama
$sql_get = "SELECT
                id, avatar, nama, nik, nomor_kk, tipe_nomor_sim, jenis_kelamin, tempat_tanggal_lahir, umur, agama,
                status_pernikahan, no_telp, email, alamat, status_kepegawaian, tanggal_masuk, tanggal_keluar,
                status_aktif, divisi, jabatan, tipe_petugas, id_pop_penempatan,
                gaji_pokok, tunjangan_jabatan, tunjangan_operasional,
                bank, rekening, username, password, fcm_token
            FROM hr_karyawan
            WHERE id = ?";
$stmt_get = $conn->prepare($sql_get);
$stmt_get->bind_param("i", $id);
$stmt_get->execute();
$result_get = $stmt_get->get_result();

if ($result_get->num_rows == 0) {
    die("Data karyawan tidak ditemukan di database.");
}
$d = $result_get->fetch_assoc(); // data lama

// Helper angka (aman untuk input "1.000.000" atau "1000000")
function toIntRupiah($v): int {
    $s = (string)$v;
    $s = preg_replace('/[^\d]/', '', $s);
    return (int)($s === '' ? 0 : $s);
}

// --- PROSES UPDATE SAAT TOMBOL SIMPAN DITEKAN ---
if (isset($_POST['update'])) {

    // 1. Tampung Semua Input (Sesuai Kolom Database)
    $nama                 = trim($_POST['nama'] ?? '');
    $nik                  = trim($_POST['nik'] ?? '');
    $nomor_kk             = trim($_POST['nomor_kk'] ?? '');
    $tipe_nomor_sim       = trim($_POST['tipe_nomor_sim'] ?? '');
    $jenis_kelamin        = trim($_POST['jenis_kelamin'] ?? '');
    $tempat_tanggal_lahir = trim($_POST['tempat_tanggal_lahir'] ?? '');
    $umur                 = (int)($_POST['umur'] ?? 0);
    $agama                = trim($_POST['agama'] ?? '');
    $status_pernikahan    = trim($_POST['status_pernikahan'] ?? '');
    $no_telp              = trim($_POST['no_telp'] ?? '');
    $email                = trim($_POST['email'] ?? '');
    $alamat               = trim($_POST['alamat'] ?? '');

    // Kepegawaian
    $divisi             = trim($_POST['divisi'] ?? '');
    $jabatan            = trim($_POST['jabatan'] ?? '');
    $tipe_petugas       = trim($_POST['tipe_petugas'] ?? '');
    $status_kepegawaian = trim($_POST['status_kepegawaian'] ?? '');
    $status_aktif       = (int)($_POST['status_aktif'] ?? 1);
    $tanggal_masuk      = $_POST['tanggal_masuk'] ?? null;
    $tanggal_keluar     = !empty($_POST['tanggal_keluar']) ? $_POST['tanggal_keluar'] : null;
    $id_pop_penempatan  = (int)($_POST['id_pop_penempatan'] ?? 0);

    // ✅ GAJI BARU
    $gaji_pokok            = toIntRupiah($_POST['gaji_pokok'] ?? 0);
    $tunjangan_jabatan     = toIntRupiah($_POST['tunjangan_jabatan'] ?? 0);
    $tunjangan_operasional = toIntRupiah($_POST['tunjangan_operasional'] ?? 0);

    // ✅ Bank & Rekening
    $bank     = trim($_POST['bank'] ?? '');
    $rekening = trim($_POST['rekening'] ?? '');

    // Akun & Token
    $username_akun = trim($_POST['username'] ?? '');
    $password_akun = trim($_POST['password'] ?? '');
    $fcm_token     = trim($_POST['fcm_token'] ?? '');

    // 2. Logic Upload Foto (Avatar)
    $avatar_path = $d['avatar']; // Default pakai foto lama

    if (!empty($_FILES['foto']['name'])) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/jpg', 'image/webp'];
        $file_type = $_FILES['foto']['type'] ?? '';

        if (in_array($file_type, $allowed_types, true)) {
            $target_dir = "uploads/";
            if (!file_exists($target_dir)) { mkdir($target_dir, 0777, true); }

            $file_ext  = strtolower(pathinfo($_FILES["foto"]["name"], PATHINFO_EXTENSION));
            $file_name = time() . "_" . uniqid() . "." . $file_ext;
            $target_file = $target_dir . $file_name;

            if (move_uploaded_file($_FILES["foto"]["tmp_name"], $target_file)) {
                $avatar_path = $target_file;
            }
        } else {
            echo "<script>alert('Format foto harus JPG/PNG/WEBP!');</script>";
        }
    }

    // 3. Query Update (Semua Kolom) + ✅ gaji_pokok + tunjangan
    $sql_update = "UPDATE hr_karyawan SET
        avatar=?,
        nama=?,
        nik=?,
        nomor_kk=?,
        tipe_nomor_sim=?,
        jenis_kelamin=?,
        tempat_tanggal_lahir=?,
        umur=?,
        agama=?,
        status_pernikahan=?,
        no_telp=?,
        email=?,
        alamat=?,
        status_kepegawaian=?,
        tanggal_masuk=?,
        tanggal_keluar=?,
        status_aktif=?,
        divisi=?,
        jabatan=?,
        tipe_petugas=?,
        id_pop_penempatan=?,
        gaji_pokok=?,
        tunjangan_jabatan=?,
        tunjangan_operasional=?,
        bank=?,
        rekening=?,
        username=?,
        password=?,
        fcm_token=?
        WHERE id=?";

    $stmt = $conn->prepare($sql_update);

    if (!$stmt) {
        echo "<script>alert('Gagal prepare query: " . addslashes($conn->error) . "');</script>";
    } else {
        // 30 parameter
        $stmt->bind_param(
    "sssssssissssssssisssiiiisssssi",
    $avatar_path,
    $nama,
    $nik,
    $nomor_kk,
    $tipe_nomor_sim,
    $jenis_kelamin,
    $tempat_tanggal_lahir,
    $umur,
    $agama,
    $status_pernikahan,
    $no_telp,
    $email,
    $alamat,
    $status_kepegawaian,
    $tanggal_masuk,
    $tanggal_keluar,
    $status_aktif,
    $divisi,
    $jabatan,
    $tipe_petugas,
    $id_pop_penempatan,
    $gaji_pokok,
    $tunjangan_jabatan,
    $tunjangan_operasional,
    $bank,
    $rekening,
    $username_akun,
    $password_akun,
    $fcm_token,
    $id


        );

        if ($stmt->execute()) {
            echo "<script>alert('Data karyawan berhasil diperbarui!'); window.location='dashkaryawan.php';</script>";
            exit;
        } else {
            echo "<script>alert('Gagal update database: " . addslashes($stmt->error) . "');</script>";
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Karyawan Pro</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        body { background-color:#f4f6f9; font-family:'Inter',sans-serif; padding-top:80px; padding-bottom:60px; }
        .card { border:none; border-radius:12px; box-shadow:0 2px 10px rgba(0,0,0,0.03); margin-bottom:20px; }
        .card-header { background-color:#fff; border-bottom:1px solid #f0f0f0; padding:15px 20px; font-weight:700; color:#2c3e50; border-radius:12px 12px 0 0 !important; }
        .form-label { font-weight:500; font-size:0.85rem; color:#555; margin-bottom:6px; }
        .form-control, .form-select { border-radius:8px; border:1px solid #dee2e6; padding:10px 12px; font-size:0.95rem; }
        .form-control:focus, .form-select:focus { border-color:#0d6efd; box-shadow:0 0 0 3px rgba(13,110,253,0.1); }
        .avatar-preview { width:120px; height:120px; object-fit:cover; border-radius:50%; border:4px solid #fff; box-shadow:0 4px 10px rgba(0,0,0,0.1); }
        .section-icon { color:#0d6efd; margin-right:8px; font-size:1.1rem; }
        .btn-save { padding:12px 30px; font-weight:600; border-radius:30px; letter-spacing:0.5px; }
    </style>
</head>
<body>

<?php include 'navbar.php'; ?>

<div class="container">
    <form method="POST" enctype="multipart/form-data">

        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h3 class="fw-bold text-dark m-0">Edit Data Karyawan</h3>
                <p class="text-muted small">Perbarui informasi lengkap pegawai.</p>
            </div>
            <div>
                <a href="dashkaryawan.php" class="btn btn-light border text-secondary me-2 rounded-pill"><i class="bi bi-arrow-left me-1"></i> Kembali</a>
                <button type="submit" name="update" class="btn btn-primary rounded-pill px-4 shadow-sm fw-bold"><i class="bi bi-save me-1"></i> Simpan Perubahan</button>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-7">

                <div class="card">
                    <div class="card-header"><i class="bi bi-person-vcard section-icon"></i> Identitas Pribadi</div>
                    <div class="card-body p-4">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label">Nama Lengkap</label>
                                <input type="text" name="nama" class="form-control fw-bold" value="<?= htmlspecialchars($d['nama'] ?? '') ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">NIK (KTP)</label>
                                <input type="text" name="nik" class="form-control" value="<?= htmlspecialchars($d['nik'] ?? '') ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Nomor KK</label>
                                <input type="text" name="nomor_kk" class="form-control" value="<?= htmlspecialchars($d['nomor_kk'] ?? '') ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Jenis Kelamin</label>
                                <select name="jenis_kelamin" class="form-select">
                                    <option value="Laki-laki" <?= (($d['jenis_kelamin'] ?? '') == 'Laki-laki') ? 'selected' : '' ?>>Laki-laki</option>
                                    <option value="Perempuan" <?= (($d['jenis_kelamin'] ?? '') == 'Perempuan') ? 'selected' : '' ?>>Perempuan</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Tempat, Tanggal Lahir</label>
                                <input type="text" name="tempat_tanggal_lahir" class="form-control" value="<?= htmlspecialchars($d['tempat_tanggal_lahir'] ?? '') ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Umur (Thn)</label>
                                <input type="number" name="umur" class="form-control" value="<?= (int)($d['umur'] ?? 0) ?>">
                            </div>
                            <div class="col-md-5">
                                <label class="form-label">Agama</label>
                                <select name="agama" class="form-select">
                                    <?php $agamaVal = $d['agama'] ?? ''; ?>
                                    <option value="Islam"   <?= ($agamaVal == 'Islam') ? 'selected' : '' ?>>Islam</option>
                                    <option value="Kristen" <?= ($agamaVal == 'Kristen') ? 'selected' : '' ?>>Kristen</option>
                                    <option value="Katolik" <?= ($agamaVal == 'Katolik') ? 'selected' : '' ?>>Katolik</option>
                                    <option value="Hindu"   <?= ($agamaVal == 'Hindu') ? 'selected' : '' ?>>Hindu</option>
                                    <option value="Buddha"  <?= ($agamaVal == 'Buddha') ? 'selected' : '' ?>>Buddha</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Status Nikah</label>
                                <select name="status_pernikahan" class="form-select">
                                    <?php $nikahVal = $d['status_pernikahan'] ?? ''; ?>
                                    <option value="Belum Menikah" <?= ($nikahVal == 'Belum Menikah') ? 'selected' : '' ?>>Belum Menikah</option>
                                    <option value="Menikah"       <?= ($nikahVal == 'Menikah') ? 'selected' : '' ?>>Menikah</option>
                                    <option value="Cerai"         <?= ($nikahVal == 'Cerai') ? 'selected' : '' ?>>Cerai</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header"><i class="bi bi-geo-alt section-icon"></i> Kontak & Dokumen</div>
                    <div class="card-body p-4">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">No. Telepon / WA</label>
                                <input type="text" name="no_telp" class="form-control" value="<?= htmlspecialchars($d['no_telp'] ?? '') ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Email</label>
                                <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($d['email'] ?? '') ?>">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Alamat Lengkap</label>
                                <textarea name="alamat" class="form-control" rows="2"><?= htmlspecialchars($d['alamat'] ?? '') ?></textarea>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">Tipe & Nomor SIM</label>
                                <input type="text" name="tipe_nomor_sim" class="form-control" value="<?= htmlspecialchars($d['tipe_nomor_sim'] ?? '') ?>" placeholder="Cth: SIM C - 123456789">
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            <div class="col-lg-5">

                <div class="card text-center">
                    <div class="card-body p-4">
                        <div class="mb-3 position-relative d-inline-block">
                            <img src="<?= htmlspecialchars($d['avatar'] ?? '') ?>" class="avatar-preview" id="imgPreview"
                                 onerror="this.src='https://ui-avatars.com/api/?name=<?= urlencode($d['nama'] ?? 'User') ?>&background=random'">
                        </div>
                        <h6 class="fw-bold"><?= htmlspecialchars($d['nama'] ?? '') ?></h6>
                        <p class="text-muted small mb-3"><?= htmlspecialchars($d['jabatan'] ?? '-') ?> - <?= htmlspecialchars($d['divisi'] ?? '-') ?></p>

                        <div class="input-group">
                            <input type="file" name="foto" class="form-control" id="fotoInput" accept="image/*" onchange="previewImage()">
                        </div>
                        <small class="text-muted d-block mt-2">Format: JPG/PNG/WEBP. Biarkan kosong jika tidak diganti.</small>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header"><i class="bi bi-briefcase section-icon"></i> Kepegawaian</div>
                    <div class="card-body p-4">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Divisi</label>
                                <input type="text" name="divisi" class="form-control" value="<?= htmlspecialchars($d['divisi'] ?? '') ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Jabatan</label>
                                <input type="text" name="jabatan" class="form-control" value="<?= htmlspecialchars($d['jabatan'] ?? '') ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Status Pegawai</label>
                                <select name="status_kepegawaian" class="form-select">
                                    <?php $sk = strtolower($d['status_kepegawaian'] ?? ''); ?>
                                    <option value="Tetap"     <?= ($sk == 'tetap') ? 'selected' : '' ?>>Tetap</option>
                                    <option value="Kontrak"   <?= ($sk == 'kontrak') ? 'selected' : '' ?>>Kontrak</option>
                                    <option value="Freelance" <?= ($sk == 'freelance') ? 'selected' : '' ?>>Freelance</option>
                                    <option value="Magang"    <?= ($sk == 'magang') ? 'selected' : '' ?>>Magang</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Tipe Petugas</label>
                                <input type="text" name="tipe_petugas" class="form-control" value="<?= htmlspecialchars($d['tipe_petugas'] ?? '') ?>">
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">ID POP Penempatan</label>
                                <input type="number" name="id_pop_penempatan" class="form-control" value="<?= (int)($d['id_pop_penempatan'] ?? 0) ?>" placeholder="ID POP (Angka)">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Tanggal Masuk</label>
                                <input type="date" name="tanggal_masuk" class="form-control" value="<?= htmlspecialchars($d['tanggal_masuk'] ?? '') ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Tanggal Keluar</label>
                                <input type="date" name="tanggal_keluar" class="form-control" value="<?= htmlspecialchars($d['tanggal_keluar'] ?? '') ?>">
                            </div>

                            <!-- ✅ GAJI BARU -->
                            <div class="col-md-12">
                                <label class="form-label text-success fw-bold">Gaji Pokok</label>
                                <div class="input-group">
                                    <span class="input-group-text">Rp</span>
                                    <input type="number" name="gaji_pokok" class="form-control fw-bold text-dark" value="<?= (int)($d['gaji_pokok'] ?? 0) ?>">
                                </div>
                            </div>

                            <div class="col-md-12">
                                <label class="form-label fw-bold">Tunj. Jabatan</label>
                                <div class="input-group">
                                    <span class="input-group-text">Rp</span>
                                    <input type="number" name="tunjangan_jabatan" class="form-control" value="<?= (int)($d['tunjangan_jabatan'] ?? 0) ?>">
                                </div>
                            </div>

                            <div class="col-md-12">
                                <label class="form-label fw-bold">Tunj. Operasional</label>
                                <div class="input-group">
                                    <span class="input-group-text">Rp</span>
                                    <input type="number" name="tunjangan_operasional" class="form-control" value="<?= (int)($d['tunjangan_operasional'] ?? 0) ?>">
                                </div>
                            </div>

                            <div class="col-md-12">
                                <?php
                                $total_gaji_view = (int)($d['gaji_pokok'] ?? 0) + (int)($d['tunjangan_jabatan'] ?? 0) + (int)($d['tunjangan_operasional'] ?? 0);
                                ?>
                                <label class="form-label text-primary fw-bold">Total Gaji (Auto)</label>
                                <div class="input-group">
                                    <span class="input-group-text">Rp</span>
                                    <input type="text" class="form-control fw-bold text-primary" id="totalGaji" value="<?= number_format($total_gaji_view, 0, ',', '.') ?>" readonly>
                                </div>
                                <small class="text-muted">Total dihitung otomatis dari 3 komponen di atas.</small>
                            </div>

                            <!-- ✅ BANK & REKENING -->
                            <div class="col-md-6">
                                <label class="form-label">Bank</label>
                                <input type="text" name="bank" class="form-control" value="<?= htmlspecialchars($d['bank'] ?? '') ?>" placeholder="Contoh: BCA / BRI / Mandiri">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Nomor Rekening</label>
                                <input type="text" name="rekening" class="form-control" value="<?= htmlspecialchars($d['rekening'] ?? '') ?>" placeholder="Contoh: 1234567890" inputmode="numeric">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header"><i class="bi bi-shield-lock section-icon"></i> Akun Login</div>
                    <div class="card-body p-4">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Username</label>
                                <input type="text" name="username" class="form-control" value="<?= htmlspecialchars($d['username'] ?? '') ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Password</label>
                                <input type="text" name="password" class="form-control" value="<?= htmlspecialchars($d['password'] ?? '') ?>">
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">FCM Token (App Mobile)</label>
                                <input type="text" name="fcm_token" class="form-control form-control-sm text-muted" value="<?= htmlspecialchars($d['fcm_token'] ?? '') ?>" placeholder="Token Firebase">
                            </div>
                            <div class="col-md-12 border-top pt-3">
                                <label class="form-label d-block mb-2">Status Aktif Akun</label>
                                <div class="btn-group w-100" role="group">
                                    <input type="radio" class="btn-check" name="status_aktif" id="aktif1" value="1" <?= ((int)($d['status_aktif'] ?? 1) === 1) ? 'checked' : '' ?>>
                                    <label class="btn btn-outline-success" for="aktif1"><i class="bi bi-check-circle-fill"></i> Aktif</label>

                                    <input type="radio" class="btn-check" name="status_aktif" id="aktif0" value="0" <?= ((int)($d['status_aktif'] ?? 1) === 0) ? 'checked' : '' ?>>
                                    <label class="btn btn-outline-danger" for="aktif0"><i class="bi bi-x-circle-fill"></i> Nonaktif</label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </form>
</div>

<script>
function previewImage() {
    const file = document.getElementById("fotoInput").files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById("imgPreview").src = e.target.result;
        }
        reader.readAsDataURL(file);
    }
}

function toNumber(v) {
    const n = parseInt(String(v || '').replace(/[^\d]/g, ''), 10);
    return isNaN(n) ? 0 : n;
}
function formatID(n) {
    return new Intl.NumberFormat('id-ID').format(n || 0);
}
function updateTotalGaji() {
    const gp = toNumber(document.querySelector('[name="gaji_pokok"]')?.value);
    const tj = toNumber(document.querySelector('[name="tunjangan_jabatan"]')?.value);
    const to = toNumber(document.querySelector('[name="tunjangan_operasional"]')?.value);
    const total = gp + tj + to;
    const totalEl = document.getElementById('totalGaji');
    if (totalEl) totalEl.value = formatID(total);
}
document.addEventListener('input', function(e){
    if (e.target && (e.target.name === 'gaji_pokok' || e.target.name === 'tunjangan_jabatan' || e.target.name === 'tunjangan_operasional')) {
        updateTotalGaji();
    }
});
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
$conn->close();
?>
