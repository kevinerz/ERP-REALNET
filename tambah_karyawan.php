<?php
// File: tambah_karyawan.php (UPDATED: GAJI POKOK + TUNJANGAN)

ini_set('display_errors', 1);
error_reporting(E_ALL);

// =============================
// KONFIGURASI DATABASE
// =============================
$servername = "localhost";
$username   = "u272457353_kevinsamsung99";
$password   = "Admionkevin99";
$database   = "u272457353_umumdata";

$success_message = '';
$error_message   = '';

// =============================
// KONEKSI
// =============================
$conn = new mysqli($servername, $username, $password, $database);
if ($conn->connect_error) {
    die("Koneksi gagal: " . htmlspecialchars($conn->connect_error, ENT_QUOTES, 'UTF-8'));
}
$conn->set_charset("utf8mb4");

// =============================
// HELPER
// =============================
function calculateAge($birthdate) {
    try {
        $today = new DateTime();
        $dob   = new DateTime($birthdate);
        $diff  = $today->diff($dob);
        return (int)$diff->y;
    } catch (Exception $e) {
        return 0;
    }
}
function toIntRupiah($v): int {
    $s = (string)$v;
    $s = preg_replace('/[^\d]/', '', $s);
    return (int)($s === '' ? 0 : $s);
}

$default_tanggal_masuk = date('Y-m-d');

// =============================
// PROSES SIMPAN
// =============================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_add'])) {

    // Data form (pribadi)
    $nama               = trim($_POST['nama'] ?? '');
    $nik                = trim($_POST['nik'] ?? '');
    $nomor_kk           = trim($_POST['nomor_kk'] ?? '');
    $tipe_nomor_sim     = trim($_POST['tipe_nomor_sim'] ?? '');
    $jenis_kelamin      = trim($_POST['jenis_kelamin'] ?? '');
    $tempat_lahir       = trim($_POST['tempat_lahir'] ?? '');
    $tanggal_lahir      = trim($_POST['tanggal_lahir'] ?? '');
    $agama              = trim($_POST['agama'] ?? '');
    $status_pernikahan  = trim($_POST['status_pernikahan'] ?? '');
    $no_telp            = trim($_POST['no_telp'] ?? '');
    $email              = trim($_POST['email'] ?? '');
    $alamat             = trim($_POST['alamat'] ?? '');

    // Kepegawaian
    $status_kepegawaian = trim($_POST['status_kepegawaian'] ?? '');
    $divisi             = trim($_POST['divisi'] ?? '');
    $jabatan            = trim($_POST['jabatan'] ?? '');
    $tipe_petugas       = trim($_POST['tipe_petugas'] ?? 'Lainnya');
    $status_aktif       = ($_POST['status_aktif'] ?? '1') === '1' ? 1 : 0;
    $tanggal_masuk      = !empty($_POST['tanggal_masuk']) ? $_POST['tanggal_masuk'] : $default_tanggal_masuk;

    // Tambahan umum
    $id_pop_penempatan  = (int)($_POST['id_pop_penempatan'] ?? 0);

    // ✅ GAJI BARU
    $gaji_pokok            = toIntRupiah($_POST['gaji_pokok'] ?? 0);
    $tunjangan_jabatan     = toIntRupiah($_POST['tunjangan_jabatan'] ?? 0);
    $tunjangan_operasional = toIntRupiah($_POST['tunjangan_operasional'] ?? 0);

    // ✅ Bank & Rekening
    $bank     = trim($_POST['bank'] ?? '');
    $rekening = trim($_POST['rekening'] ?? '');

    // Akun Login
    $username_form = trim($_POST['username'] ?? '');
    $password_form = (string)($_POST['password'] ?? '');

    // Validasi minimal
    if ($nama === '' || $nik === '' || $nomor_kk === '' || $tanggal_lahir === '' || $username_form === '' || $password_form === '') {
        $error_message = 'Harap lengkapi field wajib.';
    } else {
        // tempat_tanggal_lahir & umur
        $tempat_tanggal_lahir = trim($tempat_lahir . ', ' . $tanggal_lahir);
        $umur = (int) calculateAge($tanggal_lahir);

        // Password plain text (sesuai permintaan)
        $password_hash = $password_form;

        // =============================
        // HANDLE UPLOAD AVATAR
        // =============================
        $avatar = 'uploads/default_avatar.png'; // default relatif
        if (isset($_FILES['avatar']) && is_uploaded_file($_FILES['avatar']['tmp_name'])) {
            $target_dir = __DIR__ . "/uploads/";
            if (!is_dir($target_dir)) {
                mkdir($target_dir, 0755, true);
            }

            $file_tmp  = $_FILES['avatar']['tmp_name'];
            $file_name = basename($_FILES['avatar']['name']);
            $ext       = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

            $allowed_ext = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            if (in_array($ext, $allowed_ext, true)) {
                $new_name    = 'avatar_' . time() . '_' . rand(1000, 9999) . '.' . $ext;
                $target_file = $target_dir . $new_name;

                if (move_uploaded_file($file_tmp, $target_file)) {
                    $avatar = 'uploads/' . $new_name; // simpan path relatif
                }
            }
        }

        // =============================
        // SIMPAN KE DATABASE (SESUIKAN KOLOM BARU)
        // =============================
        $sql = "
            INSERT INTO karyawan
            (
                nama,
                nik,
                nomor_kk,
                tipe_nomor_sim,
                jenis_kelamin,
                tempat_tanggal_lahir,
                umur,
                agama,
                status_pernikahan,
                no_telp,
                email,
                alamat,
                status_kepegawaian,
                divisi,
                jabatan,
                tipe_petugas,
                id_pop_penempatan,
                gaji_pokok,
                tunjangan_jabatan,
                tunjangan_operasional,
                bank,
                rekening,
                avatar,
                username,
                password,
                status_aktif,
                tanggal_masuk
            )
            VALUES
            (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ";

        $stmt = $conn->prepare($sql);
        if ($stmt) {
            // 27 params
            // types = 'ssssssisssssssssiiiisssssis'
            $stmt->bind_param(
                "ssssssisssssssssiiiisssssis",
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
                $divisi,
                $jabatan,
                $tipe_petugas,
                $id_pop_penempatan,
                $gaji_pokok,
                $tunjangan_jabatan,
                $tunjangan_operasional,
                $bank,
                $rekening,
                $avatar,
                $username_form,
                $password_hash,
                $status_aktif,
                $tanggal_masuk
            );

            if ($stmt->execute()) {
                $success_message = 'Data karyawan berhasil ditambahkan.';
            } else {
                $error_message = 'Terjadi kesalahan saat menyimpan data: ' . htmlspecialchars($stmt->error, ENT_QUOTES, 'UTF-8');
            }
            $stmt->close();
        } else {
            $error_message = 'Gagal menyiapkan query: ' . htmlspecialchars($conn->error, ENT_QUOTES, 'UTF-8');
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Karyawan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root { --primary-color:#16a085; --primary-hover:#138a71; }
        * { -webkit-touch-callout:none; }
        body {
            background: linear-gradient(135deg, #16a085 0%, #2ecc71 50%, #1abc9c 100%);
            min-height: 100vh;
            display:flex;
            align-items:center;
            justify-content:center;
            padding:10px;
        }
        .card-form { width:100%; max-width:900px; border-radius:16px; overflow:hidden; border:none; box-shadow:0 20px 40px rgba(0,0,0,0.15); }
        .card-header-custom { background: radial-gradient(circle at top left, #ffffff 0, #d1f2eb 40%, #16a085 100%); color:#0f3c34; padding:20px; }
        .card-header-custom h4 { margin-bottom:4px; font-weight:700; font-size:1.5rem; }
        .card-header-custom p { margin:0; font-size:.85rem; opacity:.85; line-height:1.4; }
        .badge-soft { background-color:rgba(255,255,255,0.8); color:#16a085; font-size:.75rem; }
        .form-label { font-weight:600; font-size:.85rem; color:#34495e; margin-bottom:6px; }
        .form-control,.form-select { border-radius:10px; border-color:#dde2eb; font-size:.9rem; padding:.6rem .75rem; height:auto; }
        .form-control:focus,.form-select:focus { border-color:var(--primary-color); box-shadow:0 0 0 .15rem rgba(22,160,133,0.25); }
        .section-title { font-size:.8rem; font-weight:700; text-transform:uppercase; letter-spacing:.06em; color:#95a5a6; margin-top:1.5rem; margin-bottom:1rem; }
        .btn-primary-custom { background-color:var(--primary-color); border-color:var(--primary-color); border-radius:10px; padding:12px 28px; font-weight:600; width:100%; font-size:.95rem; }
        .btn-primary-custom:hover { background-color:var(--primary-hover); border-color:var(--primary-hover); }
        .btn-outline-light-custom { border-radius:10px; font-size:.85rem; padding:8px 12px; }
        .avatar-preview { width:100px; height:100px; border-radius:50%; object-fit:cover; border:3px solid rgba(22,160,133,0.4); display:block; margin:0 auto; }
        .avatar-section { text-align:center; padding:15px; background:rgba(22,160,133,0.05); border-radius:10px; margin-bottom:1rem; }
        .file-input-wrapper { position:relative; overflow:hidden; display:inline-block; width:100%; }
        .file-input-wrapper input[type=file] { position:absolute; left:-9999px; }
        .btn-file-custom { display:flex; align-items:center; justify-content:center; gap:8px; background-color:var(--primary-color); color:#fff; border:2px solid var(--primary-color); padding:12px; border-radius:10px; cursor:pointer; font-weight:600; width:100%; font-size:.9rem; }
        .btn-file-custom:hover { background-color:var(--primary-hover); border-color:var(--primary-hover); }
        .form-text-small { font-size:.75rem; margin-top:4px; }
        .card-body { padding:20px; }
        @media (max-width:576px){
            .card-form{ border-radius:12px; }
            .card-header-custom{ padding:15px; }
            .card-header-custom h4{ font-size:1.25rem; }
            .card-header-custom p{ font-size:.8rem; }
            .card-body{ padding:15px; }
            .form-label{ font-size:.8rem; }
            .row.g-3{ gap:.75rem !important; }
            .btn-primary-custom{ padding:10px 20px; font-size:.9rem; }
            .btn-outline-light-custom{ width:100%; font-size:.8rem; padding:8px; }
        }
        @media (max-width:768px){
            .card-header-custom{ text-align:center; }
            .card-header-custom > div:last-child{ margin-top:15px; }
        }
    </style>
</head>
<body>

<div class="card card-form bg-white">
    <div class="card-header-custom">
        <h4 class="mb-1"><i class="bi bi-person-plus-fill me-2"></i>Tambah Karyawan Baru</h4>
        <p>Lengkapi data karyawan, termasuk gaji pokok dan tunjangan.</p>
        <div class="mt-3">
            <span class="badge badge-soft px-2 py-1 me-2"><i class="bi bi-buildings me-1"></i> REALNET HRIS</span>
            <a href="dashboard_karyawan.php" class="btn btn-outline-light btn-outline-light-custom">
                <i class="bi bi-arrow-left"></i> Kembali
            </a>
        </div>
    </div>

    <div class="card-body">
        <?php if ($success_message): ?>
            <div class="alert alert-success alert-dismissible fade show mb-3" role="alert">
                <i class="bi bi-check-circle me-2"></i><?= htmlspecialchars($success_message, ENT_QUOTES, 'UTF-8'); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
            </div>
        <?php endif; ?>

        <?php if ($error_message): ?>
            <div class="alert alert-danger alert-dismissible fade show mb-3" role="alert">
                <i class="bi bi-exclamation-triangle me-2"></i><?= htmlspecialchars($error_message, ENT_QUOTES, 'UTF-8'); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
            </div>
        <?php endif; ?>

        <form method="POST" action="tambah_karyawan.php" enctype="multipart/form-data">

            <!-- FOTO AVATAR -->
            <div class="section-title"><i class="bi bi-image me-1"></i>Foto Selfie</div>
            <div class="avatar-section">
                <img src="https://ui-avatars.com/api/?name=User&background=16a085&color=fff&size=128"
                     alt="Preview Avatar" class="avatar-preview" id="avatarPreview">

                <div class="mt-3 d-grid gap-2">
                    <div class="file-input-wrapper">
                        <input class="form-control d-none" type="file" id="avatar" name="avatar" accept="image/*" capture="environment">
                        <label for="avatar" class="btn-file-custom mb-2">
                            <i class="bi bi-camera-fill"></i> Ambil Foto (Kamera)
                        </label>
                    </div>

                    <div class="file-input-wrapper">
                        <input class="form-control d-none" type="file" id="avatarGallery" name="avatar_gallery" accept="image/*">
                        <label for="avatarGallery" class="btn-file-custom">
                            <i class="bi bi-image-fill"></i> Pilih dari Galeri
                        </label>
                    </div>
                </div>

                <p class="form-text-small mt-2">
                    <i class="bi bi-info-circle"></i> Opsional. Jika tidak ada, akan otomatis memakai default.
                </p>
            </div>

            <!-- DATA PRIBADI -->
            <div class="section-title"><i class="bi bi-person me-1"></i>Data Pribadi</div>
            <div class="row g-3">
                <div class="col-12">
                    <label for="nama" class="form-label">Nama Lengkap *</label>
                    <input type="text" class="form-control" id="nama" name="nama" placeholder="Nama sesuai KTP" required>
                </div>
                <div class="col-6">
                    <label for="nik" class="form-label">Nomor KTP *</label>
                    <input type="text" class="form-control" id="nik" name="nik" placeholder="16 digit" required>
                </div>
                <div class="col-6">
                    <label for="nomor_kk" class="form-label">Nomor KK *</label>
                    <input type="text" class="form-control" id="nomor_kk" name="nomor_kk" placeholder="Nomor KK" required>
                </div>
                <div class="col-12">
                    <label for="tipe_nomor_sim" class="form-label">Tipe & Nomor SIM</label>
                    <input type="text" class="form-control" id="tipe_nomor_sim" name="tipe_nomor_sim" placeholder="Contoh: SIM A 1234567890 (atau kosong jika tidak ada)">
                </div>
                <div class="col-6">
                    <label for="jenis_kelamin" class="form-label">Jenis Kelamin *</label>
                    <select class="form-select" id="jenis_kelamin" name="jenis_kelamin" required>
                        <option value="">-- Pilih --</option>
                        <option value="Laki-laki">Laki-laki</option>
                        <option value="Perempuan">Perempuan</option>
                    </select>
                </div>
                <div class="col-6">
                    <label for="agama" class="form-label">Agama *</label>
                    <input type="text" class="form-control" id="agama" name="agama" placeholder="Agama" required>
                </div>
                <div class="col-6">
                    <label for="tempat_lahir" class="form-label">Tempat Lahir *</label>
                    <input type="text" class="form-control" id="tempat_lahir" name="tempat_lahir" placeholder="Kota kelahiran" required>
                </div>
                <div class="col-6">
                    <label for="tanggal_lahir" class="form-label">Tanggal Lahir *</label>
                    <input type="date" class="form-control" id="tanggal_lahir" name="tanggal_lahir" required>
                </div>
                <div class="col-12">
                    <label for="status_pernikahan" class="form-label">Status Pernikahan *</label>
                    <select class="form-select" id="status_pernikahan" name="status_pernikahan" required>
                        <option value="">-- Pilih --</option>
                        <option value="Belum Menikah">Belum Menikah</option>
                        <option value="Menikah">Menikah</option>
                        <option value="Janda">Janda</option>
                        <option value="Duda">Duda</option>
                    </select>
                </div>
                <div class="col-6">
                    <label for="no_telp" class="form-label">Nomor Telepon</label>
                    <input type="text" class="form-control" id="no_telp" name="no_telp" placeholder="08xxxxxxxxxx">
                </div>
                <div class="col-6">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" class="form-control" id="email" name="email" placeholder="email@example.com">
                </div>
                <div class="col-12">
                    <label for="alamat" class="form-label">Alamat Lengkap</label>
                    <textarea class="form-control" id="alamat" name="alamat" rows="2" placeholder="Alamat domisili saat ini"></textarea>
                </div>
            </div>

            <!-- DATA KEPEGAWAIAN -->
            <div class="section-title mt-4"><i class="bi bi-briefcase me-1"></i>Data Kepegawaian</div>
            <div class="row g-3">
                <div class="col-12">
                    <label for="divisi" class="form-label">Divisi *</label>
                    <select class="form-select" id="divisi" name="divisi" required>
                        <option value="">-- Pilih --</option>
                        <option value="Leader Area">Leader Area</option>
                        <option value="Teknisi">Teknisi</option>
                        <option value="Manager">Manager</option>
                        <option value="Admin">Admin</option>
                        <option value="Finance">Finance</option>
                        <option value="IT">IT</option>
                        <option value="Outsourcing">Outsourcing</option>
                    </select>
                </div>
                <div class="col-12">
                    <label for="jabatan" class="form-label">Jabatan</label>
                    <input type="text" class="form-control" id="jabatan" name="jabatan" placeholder="Contoh: Koordinator Teknisi Rajeg">
                </div>
                <div class="col-12">
                    <label for="tipe_petugas" class="form-label">Tipe Petugas</label>
                    <select class="form-select" id="tipe_petugas" name="tipe_petugas">
                        <option value="Teknisi Lapangan">Teknisi Lapangan</option>
                        <option value="NOC">NOC</option>
                        <option value="Admin Billing">Admin Billing</option>
                        <option value="Sales/Marketing">Sales/Marketing</option>
                        <option value="Manajemen">Manajemen</option>
                        <option value="Lainnya">Lainnya</option>
                    </select>
                </div>
                <div class="col-6">
                    <label for="status_kepegawaian" class="form-label">Status Kepegawaian</label>
                    <select class="form-select" id="status_kepegawaian" name="status_kepegawaian">
                        <option value="Tetap">Tetap</option>
                        <option value="Kontrak">Kontrak</option>
                        <option value="Magang">Magang</option>
                    </select>
                </div>

                <div class="col-6">
                    <label for="id_pop_penempatan" class="form-label">ID POP Penempatan</label>
                    <input type="number" min="0" class="form-control" id="id_pop_penempatan" name="id_pop_penempatan" placeholder="Contoh: 12">
                </div>

                <!-- ✅ GAJI BARU -->
                <div class="col-12">
                    <label for="gaji_pokok" class="form-label">Gaji Pokok (Rp) *</label>
                    <input type="number" min="0" class="form-control" id="gaji_pokok" name="gaji_pokok" placeholder="3000000" required>
                </div>
                <div class="col-6">
                    <label for="tunjangan_jabatan" class="form-label">Tunjangan Jabatan (Rp)</label>
                    <input type="number" min="0" class="form-control" id="tunjangan_jabatan" name="tunjangan_jabatan" placeholder="0" value="0">
                </div>
                <div class="col-6">
                    <label for="tunjangan_operasional" class="form-label">Tunjangan Operasional (Rp)</label>
                    <input type="number" min="0" class="form-control" id="tunjangan_operasional" name="tunjangan_operasional" placeholder="0" value="0">
                </div>
                <div class="col-12">
                    <label class="form-label text-primary fw-bold">Total Gaji (Auto)</label>
                    <input type="text" class="form-control fw-bold text-primary" id="totalGaji" value="0" readonly>
                    <small class="text-muted">Total = Gaji Pokok + Tunj. Jabatan + Tunj. Operasional</small>
                </div>

                <!-- ✅ BANK & REKENING -->
                <div class="col-6">
                    <label for="bank" class="form-label">Bank</label>
                    <input type="text" class="form-control" id="bank" name="bank" placeholder="Contoh: BCA / BRI / Mandiri">
                </div>
                <div class="col-6">
                    <label for="rekening" class="form-label">Nomor Rekening</label>
                    <input type="text" class="form-control" id="rekening" name="rekening" placeholder="Contoh: 1234567890" inputmode="numeric">
                </div>

                <div class="col-6">
                    <label for="tanggal_masuk" class="form-label">Tanggal Masuk</label>
                    <input type="date" class="form-control" id="tanggal_masuk" name="tanggal_masuk"
                           value="<?= htmlspecialchars($default_tanggal_masuk, ENT_QUOTES, 'UTF-8'); ?>">
                </div>
                <div class="col-6">
                    <label for="status_aktif" class="form-label">Status Aktif</label>
                    <select class="form-select" id="status_aktif" name="status_aktif">
                        <option value="1">Aktif</option>
                        <option value="0">Nonaktif</option>
                    </select>
                </div>
            </div>

            <!-- AKUN LOGIN -->
            <div class="section-title mt-4"><i class="bi bi-lock me-1"></i>Akun Login</div>
            <div class="row g-3">
                <div class="col-12">
                    <label for="username" class="form-label">Username *</label>
                    <input type="text" class="form-control" id="username" name="username" placeholder="Username login" required>
                </div>
                <div class="col-12">
                    <label for="password" class="form-label">Password *</label>
                    <input type="text" class="form-control" id="password" name="password" placeholder="Minimal 6 karakter" required>
                    <p class="form-text-small">
                        <i class="bi bi-info-circle"></i> Password disimpan dalam bentuk plain text untuk kemudahan akses di mobile.
                    </p>
                </div>
            </div>

            <!-- TOMBOL SUBMIT -->
            <div class="d-grid gap-2 mt-4">
                <button type="submit" name="submit_add" class="btn btn-primary-custom">
                    <i class="bi bi-save me-2"></i> Simpan Karyawan
                </button>
            </div>

            <p class="text-muted text-center form-text-small mt-3">
                Pastikan semua data sudah benar sebelum disimpan.
            </p>
        </form>
    </div>
</div>

<script>
// Handle avatar upload dari kamera
document.getElementById('avatar')?.addEventListener('change', function (e) {
    const [file] = e.target.files;
    if (file) {
        const preview = document.getElementById('avatarPreview');
        if (preview) preview.src = URL.createObjectURL(file);
    }
});

// Handle avatar upload dari galeri
document.getElementById('avatarGallery')?.addEventListener('change', function (e) {
    const [file] = e.target.files;
    if (file) {
        const preview = document.getElementById('avatarPreview');
        const avatarInput = document.getElementById('avatar');

        if (preview) preview.src = URL.createObjectURL(file);

        const dataTransfer = new DataTransfer();
        dataTransfer.items.add(file);
        avatarInput.files = dataTransfer.files;
    }
});

// Auto total gaji
function toNumber(v) {
    const n = parseInt(String(v || '').replace(/[^\d]/g, ''), 10);
    return isNaN(n) ? 0 : n;
}
function formatID(n) { return new Intl.NumberFormat('id-ID').format(n || 0); }

function updateTotalGaji() {
    const gp = toNumber(document.getElementById('gaji_pokok')?.value);
    const tj = toNumber(document.getElementById('tunjangan_jabatan')?.value);
    const to = toNumber(document.getElementById('tunjangan_operasional')?.value);
    document.getElementById('totalGaji').value = formatID(gp + tj + to);
}

['gaji_pokok','tunjangan_jabatan','tunjangan_operasional'].forEach(id => {
    document.getElementById(id)?.addEventListener('input', updateTotalGaji);
});
updateTotalGaji();

// Optimasi iOS (cegah zoom)
if (window.innerWidth < 768) {
    document.querySelectorAll('.form-control, .form-select').forEach(el => {
        el.style.fontSize = '16px';
    });
}
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
$conn->close();
?>
