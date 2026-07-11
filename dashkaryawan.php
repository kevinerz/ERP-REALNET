<?php
require_once __DIR__ . '/config/database.php';
session_start();

$dashboard_divisi = ['Admin', 'IT', 'Manager', 'SPV Teknis', 'Finance'];

if (!isset($_SESSION['username']) || !in_array($_SESSION['divisi'], $dashboard_divisi)) {
    header("Location: login.php");
    exit;
}
// ==========================================
// 1. KONFIGURASI & KONEKSI
// ==========================================
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$servername = "localhost";
$username   = "u272457353_kevinsamsung99";
$password   = "Admionkevin99";
$database   = "u272457353_umumdata";

$conn = getErpDbConnection();
if ($conn->connect_error) {
    die("Koneksi Gagal: " . $conn->connect_error);
}
$conn->set_charset("utf8mb4");

// ==========================================
// 2. QUERY DATA (SUDAH DISESUAIKAN: GAJI POKOK + TUNJANGAN)
// ==========================================
$sql = "SELECT
            id, avatar, nama, nik, nomor_kk, tipe_nomor_sim, jenis_kelamin, tempat_tanggal_lahir, umur, agama,
            status_pernikahan, no_telp, email, alamat, status_kepegawaian, tanggal_masuk, tanggal_keluar,
            status_aktif, divisi, jabatan, tipe_petugas, id_pop_penempatan,
            gaji_pokok, tunjangan_jabatan, tunjangan_operasional,
            bank, rekening, created_at, username, password, fcm_token
        FROM hr_karyawan
        ORDER BY created_at DESC";

$result = $conn->query($sql);

$total_karyawan = $result ? $result->num_rows : 0;
$aktif = 0;
$non_aktif = 0;
$data_karyawan = [];

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        if ((int)$row['status_aktif'] === 1) { $aktif++; } else { $non_aktif++; }
        $data_karyawan[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard HRIS Pro</title>

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

    <style>
        :root { --primary-color:#0d6efd; --bg-body:#f4f6f9; }
        body { font-family:'Inter',sans-serif; background-color:var(--bg-body); color:#333; padding-top:80px; padding-bottom:50px; }

        /* --- STAT CARDS --- */
        .stat-card{ background:#fff; border-radius:12px; padding:15px 20px; box-shadow:0 2px 10px rgba(0,0,0,0.03); border:1px solid #eef2f6; transition:transform .2s; height: 100%; }
        .stat-card:hover{ transform:translateY(-3px); }
        .stat-icon{ width:48px; height:48px; border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:1.5rem; margin-bottom:0; flex-shrink: 0; }

        /* --- TABLE --- */
        .table-card{ background:#fff; border-radius:16px; box-shadow:0 4px 12px rgba(0,0,0,0.03); overflow:hidden; border:none; }
        .table thead th{ background-color:#f8fafc; color:#64748b; font-weight:600; font-size:.8rem; text-transform:uppercase; letter-spacing:.5px; padding:16px; border-bottom:1px solid #e2e8f0; white-space: nowrap; }
        .table tbody td{ vertical-align:middle; padding:12px 16px; border-bottom:1px solid #f1f5f9; font-size:.9rem; }

        /* Table Responsive Tweaks */
        .table-responsive { max-height: 70vh; overflow-y: auto; -webkit-overflow-scrolling: touch; }
        @media (max-width: 768px) {
            .table-responsive td:first-child,
            .table-responsive th:first-child {
                position: sticky; left: 0; background-color: #fff; z-index: 10;
                box-shadow: 2px 0 5px rgba(0,0,0,0.05);
            }
            .table-responsive th:first-child { background-color: #f8fafc; z-index: 11; }
        }

        .avatar-sm{ width:40px; height:40px; object-fit:cover; border-radius:50%; border:2px solid #fff; box-shadow:0 2px 5px rgba(0,0,0,0.1); }
        .avatar-lg{ width:110px; height:110px; object-fit:cover; border-radius:12px; border:4px solid #fff; box-shadow:0 4px 15px rgba(0,0,0,0.1); background-color:#fff; }

        /* --- MODAL --- */
        .modal-content{ border-radius:16px; border:none; overflow:hidden; }
        .modal-header-pro{ background:linear-gradient(120deg,#2563eb,#1e40af); color:#fff; padding:20px 25px 15px 25px; position:relative; }
        .profile-img-container{ margin-top:-25px; padding-left:10px; margin-bottom:20px; }
        .detail-label{ font-size:.7rem; text-transform:uppercase; color:#94a3b8; font-weight:700; margin-bottom:2px; }
        .detail-value{ font-weight:500; color:#1e293b; font-size:.9rem; margin-bottom:12px; word-wrap:break-word; }
        .section-title{ font-size:.95rem; font-weight:700; color:#2563eb; margin-bottom:12px; padding-bottom:5px; border-bottom:1px solid #e2e8f0; margin-top:10px; }

        /* --- BADGES --- */
        .badge-soft-primary{ background:#e0f2fe; color:#0369a1; }
        .badge-soft-success{ background:#dcfce7; color:#15803d; }
        .badge-soft-warning{ background:#fef3c7; color:#b45309; }
        .badge-soft-secondary{ background:#f1f5f9; color:#64748b; }

        /* --- BUTTONS --- */
        .btn-action-group { display: flex; gap: 6px; align-items: center; }
        .btn-action-print { background: linear-gradient(135deg, #3b82f6, #2563eb); color: white; border: none; transition: all 0.3s ease; }
        .btn-action-print:hover { transform: scale(1.15); box-shadow: 0 4px 12px rgba(37, 99, 235, 0.4); }

        /* --- RESPONSIVE MEDIA QUERIES --- */
        @media (max-width:768px){
            body { padding-top: 20px; padding-bottom: 80px; }
            .page-header-row { flex-direction: column; align-items: flex-start; gap: 15px; }
            .btn-group-action { width: 100%; display: flex; flex-direction: column; gap: 10px; }
            .btn-group-action .btn { width: 100%; }
            .modal-col-divider { border-right: none !important; border-bottom: 1px solid #e2e8f0; margin-bottom: 20px; padding-bottom: 10px; }
            .avatar-lg { width: 85px; height: 85px; }
            .profile-img-container { margin-top: -20px; flex-direction: column; align-items: flex-start !important; }
            .profile-img-container .ms-3 { margin-left: 0 !important; margin-top: 10px; }
            .modal-footer { flex-direction: column-reverse; gap: 10px; }
            .modal-footer > div { width: 100%; display: flex; flex-direction: column; gap: 8px; }
            .modal-footer button[data-bs-dismiss="modal"] { width: 100%; }
            .modal-footer .btn { width: 100%; margin: 0 !important; }
        }
    </style>
</head>
<body>

<?php include 'navbar.php'; ?>

<div class="container-fluid container-lg">

    <div class="row align-items-center mb-4 page-header-row">
        <div class="col-md-6 col-12">
            <h3 class="fw-bold mb-1">Database Karyawan</h3>
            <p class="text-muted mb-0">Manajemen data pegawai secara terpusat.</p>
        </div>
        <div class="col-md-6 col-12 text-md-end">
            <div class="btn-group-action">
                <a href="jadwal_libur.php" class="btn btn-info rounded-pill px-4 shadow-sm fw-bold text-white">
                    <i class="bi bi-calendar2-week me-2"></i> Jadwal Libur
                </a>
                <a href="tambah_karyawan.php" class="btn btn-primary rounded-pill px-4 shadow-sm fw-bold">
                    <i class="bi bi-person-plus-fill me-2"></i> Tambah Baru
                </a>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-12 col-md-4">
            <div class="stat-card">
                <div class="d-flex align-items-center">
                    <div class="stat-icon bg-primary bg-opacity-10 text-primary"><i class="bi bi-people-fill"></i></div>
                    <div class="ms-3">
                        <h5 class="mb-0 fw-bold"><?= (int)$total_karyawan ?></h5>
                        <span class="text-muted small">Total Karyawan</span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4">
            <div class="stat-card">
                <div class="d-flex align-items-center">
                    <div class="stat-icon bg-success bg-opacity-10 text-success"><i class="bi bi-check-circle-fill"></i></div>
                    <div class="ms-3">
                        <h5 class="mb-0 fw-bold"><?= (int)$aktif ?></h5>
                        <span class="text-muted small">Akun Aktif</span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4">
            <div class="stat-card">
                <div class="d-flex align-items-center">
                    <div class="stat-icon bg-danger bg-opacity-10 text-danger"><i class="bi bi-slash-circle-fill"></i></div>
                    <div class="ms-3">
                        <h5 class="mb-0 fw-bold"><?= (int)$non_aktif ?></h5>
                        <span class="text-muted small">Non-Aktif</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-3">
        <div class="col-12 col-md-4 ms-auto">
            <div class="input-group shadow-sm rounded-pill overflow-hidden bg-white border">
                <span class="input-group-text bg-white border-0 ps-3"><i class="bi bi-search text-muted"></i></span>
                <input type="text" id="searchInput" class="form-control border-0" placeholder="Cari nama, NIK, atau divisi...">
            </div>
        </div>
    </div>

    <div class="table-card">
        <div class="table-responsive">
            <table class="table table-hover mb-0 text-nowrap" id="karyawanTable">
                <thead>
                <tr>
                    <th class="ps-4">Profil Pegawai</th>
                    <th>Divisi & Jabatan</th>
                    <th>Status Kerja</th>
                    <th>Kontak</th>
                    <th class="text-center">Aktif?</th>
                    <th class="text-end pe-4">Aksi</th>
                </tr>
                </thead>
                <tbody id="tableBody">
                <?php if (count($data_karyawan) > 0): ?>
                    <?php foreach ($data_karyawan as $index => $row):
                        $id      = (int)($row['id'] ?? 0);
                        $nama    = htmlspecialchars($row['nama'] ?? '-');
                        $avatar  = htmlspecialchars($row['avatar'] ?? '');
                        $nik     = htmlspecialchars($row['nik'] ?? '-');
                        $jabatan = htmlspecialchars($row['jabatan'] ?? '-');
                        $divisi  = htmlspecialchars($row['divisi'] ?? '-');
                        $status_raw = $row['status_kepegawaian'] ?? '-';
                        $status = strtolower((string)$status_raw);
                        $isAktif = (int)($row['status_aktif'] ?? 0);

                        $bank     = htmlspecialchars($row['bank'] ?? '-');
                        $rekening = htmlspecialchars($row['rekening'] ?? '-');

                        // GAJI BARU
                        $gaji_pokok           = (float)($row['gaji_pokok'] ?? 0);
                        $tunjangan_jabatan    = (float)($row['tunjangan_jabatan'] ?? 0);
                        $tunjangan_operasional= (float)($row['tunjangan_operasional'] ?? 0);
                        $total_gaji           = $gaji_pokok + $tunjangan_jabatan + $tunjangan_operasional;

                        if ($status === 'tetap') $bg_status = 'badge-soft-primary';
                        elseif ($status === 'kontrak') $bg_status = 'badge-soft-warning';
                        else $bg_status = 'badge-soft-secondary';
                        ?>
                        <tr>
                            <td class="ps-4">
                                <div class="d-flex align-items-center">
                                    <img src="<?= $avatar ?>" class="avatar-sm me-3" loading="lazy"
                                         onerror="this.src='https://ui-avatars.com/api/?name=<?= urlencode($nama) ?>&background=random&color=fff'">
                                    <div>
                                        <div class="fw-bold text-dark"><?= $nama ?></div>
                                        <div class="small text-muted font-monospace"><i class="bi bi-card-heading me-1"></i><?= $nik ?></div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="fw-bold text-dark"><?= $jabatan ?></div>
                                <div class="small text-muted"><?= $divisi ?></div>
                            </td>
                            <td><span class="badge <?= $bg_status ?> rounded-pill px-3 py-1 fw-normal"><?= htmlspecialchars(ucfirst($status)) ?></span></td>
                            <td><div class="small text-dark"><i class="bi bi-telephone me-1 text-primary"></i> <?= htmlspecialchars($row['no_telp'] ?? '-') ?></div></td>
                            <td class="text-center">
                                <?php if ($isAktif === 1): ?>
                                    <i class="bi bi-check-circle-fill text-success fs-5"></i>
                                <?php else: ?>
                                    <i class="bi bi-x-circle-fill text-muted fs-5"></i>
                                <?php endif; ?>
                            </td>
                            <td class="text-end pe-4">
                                <div class="btn-action-group justify-content-end">
                                    <a href="cetak_kartu_nama.php?id=<?= $id ?>&layout=standard&multiple=1"
                                       class="btn btn-sm btn-action-print rounded-circle shadow-sm"
                                       title="Cetak Kartu Nama" target="_blank">
                                        <i class="bi bi-credit-card"></i>
                                    </a>

                                    <button class="btn btn-sm btn-light border text-primary rounded-circle shadow-sm btn-detail"
                                            data-id="<?= $id ?>"
                                            data-nama="<?= $nama ?>"
                                            data-avatar="<?= $avatar ?>"
                                            data-nik="<?= $nik ?>"
                                            data-nomor_kk="<?= htmlspecialchars($row['nomor_kk'] ?? '-') ?>"
                                            data-jenis_kelamin="<?= htmlspecialchars($row['jenis_kelamin'] ?? '-') ?>"
                                            data-agama="<?= htmlspecialchars($row['agama'] ?? '-') ?>"
                                            data-tempat_tanggal_lahir="<?= htmlspecialchars($row['tempat_tanggal_lahir'] ?? '-') ?>"
                                            data-umur="<?= htmlspecialchars($row['umur'] ?? '-') ?>"
                                            data-status_pernikahan="<?= htmlspecialchars($row['status_pernikahan'] ?? '-') ?>"
                                            data-tipe_nomor_sim="<?= htmlspecialchars($row['tipe_nomor_sim'] ?? '-') ?>"
                                            data-alamat="<?= htmlspecialchars($row['alamat'] ?? '-') ?>"
                                            data-email="<?= htmlspecialchars($row['email'] ?? '-') ?>"
                                            data-no_telp="<?= htmlspecialchars($row['no_telp'] ?? '-') ?>"
                                            data-jabatan="<?= $jabatan ?>"
                                            data-divisi="<?= $divisi ?>"
                                            data-status_kepegawaian="<?= htmlspecialchars($row['status_kepegawaian'] ?? '-') ?>"
                                            data-tipe_petugas="<?= htmlspecialchars($row['tipe_petugas'] ?? '-') ?>"
                                            data-id_pop_penempatan="<?= htmlspecialchars($row['id_pop_penempatan'] ?? '-') ?>"
                                            data-gaji_pokok="<?= $gaji_pokok ?>"
                                            data-tunjangan_jabatan="<?= $tunjangan_jabatan ?>"
                                            data-tunjangan_operasional="<?= $tunjangan_operasional ?>"
                                            data-total_gaji="<?= $total_gaji ?>"
                                            data-bank="<?= $bank ?>"
                                            data-rekening="<?= $rekening ?>"
                                            data-tanggal_masuk="<?= htmlspecialchars($row['tanggal_masuk'] ?? '') ?>"
                                            data-tanggal_keluar="<?= htmlspecialchars($row['tanggal_keluar'] ?? '') ?>"
                                            data-username="<?= htmlspecialchars($row['username'] ?? '-') ?>"
                                            data-fcm_token="<?= htmlspecialchars($row['fcm_token'] ?? '') ?>">
                                        <i class="bi bi-eye-fill"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" class="text-center py-5">
                            <div class="text-muted opacity-50">
                                <i class="bi bi-folder-x fs-1 d-block mb-3"></i>
                                <h5 class="fw-light">Belum ada data karyawan</h5>
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="modalDetail" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header-pro">
                <button type="button" class="btn-close btn-close-white position-absolute top-0 end-0 m-3" data-bs-dismiss="modal"></button>
                <h5 class="fw-bold m-0"><i class="bi bi-person-badge me-2"></i>Detail Karyawan</h5>
            </div>
            <div class="modal-body px-4 pb-4" id="modalContent"></div>
            <div class="modal-footer bg-light border-0 justify-content-between py-2" id="modalFooter"></div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    const searchInput = document.getElementById('searchInput');
    const tableBody = document.getElementById('tableBody');
    const modalContent = document.getElementById('modalContent');
    const modalFooter = document.getElementById('modalFooter');
    const modalElement = document.getElementById('modalDetail');
    const modal = new bootstrap.Modal(modalElement);

    function debounce(func, wait) {
        let timeout;
        return function(...args) {
            clearTimeout(timeout);
            timeout = setTimeout(() => func.apply(this, args), wait);
        };
    }

    searchInput.addEventListener('input', debounce(function() {
        const filter = this.value.toUpperCase();
        const rows = tableBody.getElementsByTagName('tr');
        for (let i = 0; i < rows.length; i++) {
            const tdName = rows[i].getElementsByTagName('td')[0];
            const tdDiv = rows[i].getElementsByTagName('td')[1];
            if (tdName && tdDiv) {
                const txtName = tdName.textContent || tdName.innerText;
                const txtDiv = tdDiv.textContent || tdDiv.innerText;
                rows[i].style.display = (txtName.toUpperCase().indexOf(filter) > -1 || txtDiv.toUpperCase().indexOf(filter) > -1) ? '' : 'none';
            }
        }
    }, 200));

    function formatCurrency(num) {
        return new Intl.NumberFormat('id-ID').format(Number(num) || 0);
    }

    function formatDate(dateStr) {
        if (!dateStr) return '-';
        const date = new Date(dateStr);
        if (isNaN(date)) return '-';
        return date.toLocaleDateString('id-ID', { day: '2-digit', month: '2-digit', year: 'numeric' });
    }

    tableBody.addEventListener('click', function(e) {
        const btn = e.target.closest('.btn-detail');
        if (!btn) return;

        const data = btn.dataset;
        const avatarFallback = `https://ui-avatars.com/api/?name=${encodeURIComponent(data.nama || 'User')}&size=150`;

        modalContent.innerHTML = `
            <div class="d-flex align-items-end profile-img-container">
                <img src="${data.avatar || avatarFallback}" class="avatar-lg"
                     onerror="this.src='${avatarFallback}'">
                <div class="ms-3 mb-2 pt-3">
                    <h3 class="fw-bold text-dark mb-0 fs-4">${data.nama}</h3>
                    <div class="mt-2">
                        <span class="badge bg-primary rounded-pill">${data.jabatan}</span>
                        <span class="badge bg-secondary rounded-pill">${data.divisi}</span>
                    </div>
                </div>
            </div>

            <div class="row g-4">
                <div class="col-lg-6 border-end modal-col-divider">
                    <div class="section-title"><i class="bi bi-person-vcard"></i> Identitas & Kontak</div>
                    <div class="row">
                        <div class="col-6"><div class="detail-label">NIK (KTP)</div><div class="detail-value">${data.nik}</div></div>
                        <div class="col-6"><div class="detail-label">Nomor KK</div><div class="detail-value">${data.nomor_kk}</div></div>
                        <div class="col-6"><div class="detail-label">Gender</div><div class="detail-value">${data.jenis_kelamin}</div></div>
                        <div class="col-6"><div class="detail-label">Agama</div><div class="detail-value">${data.agama}</div></div>
                        <div class="col-12"><div class="detail-label">Tempat, Tanggal Lahir</div><div class="detail-value">${data.tempat_tanggal_lahir} (${data.umur} Thn)</div></div>
                        <div class="col-6"><div class="detail-label">Status Nikah</div><div class="detail-value">${data.status_pernikahan}</div></div>
                        <div class="col-6"><div class="detail-label">Tipe & No SIM</div><div class="detail-value">${data.tipe_nomor_sim}</div></div>
                        <div class="col-12"><div class="detail-label">Alamat Lengkap</div><div class="detail-value bg-light p-2 rounded small">${data.alamat}</div></div>
                        <div class="col-6"><div class="detail-label">Email</div><div class="detail-value text-primary small text-break">${data.email}</div></div>
                        <div class="col-6"><div class="detail-label">Telepon</div><div class="detail-value">${data.no_telp}</div></div>
                    </div>
                </div>

                <div class="col-lg-6">
                    <div class="section-title"><i class="bi bi-briefcase"></i> Data Pekerjaan</div>
                    <div class="row">
                        <div class="col-6"><div class="detail-label">Status Pegawai</div><div class="detail-value">${data.status_kepegawaian}</div></div>
                        <div class="col-6"><div class="detail-label">Tipe Petugas</div><div class="detail-value">${data.tipe_petugas}</div></div>
                        <div class="col-6"><div class="detail-label">ID POP</div><div class="detail-value">${data.id_pop_penempatan}</div></div>

                        <div class="col-6"><div class="detail-label">Gaji Pokok</div><div class="detail-value text-success fw-bold">Rp ${formatCurrency(data.gaji_pokok)}</div></div>
                        <div class="col-6"><div class="detail-label">Tunj. Jabatan</div><div class="detail-value text-success fw-bold">Rp ${formatCurrency(data.tunjangan_jabatan)}</div></div>
                        <div class="col-6"><div class="detail-label">Tunj. Operasional</div><div class="detail-value text-success fw-bold">Rp ${formatCurrency(data.tunjangan_operasional)}</div></div>
                        <div class="col-6"><div class="detail-label">Total Gaji</div><div class="detail-value text-primary fw-bold">Rp ${formatCurrency(data.total_gaji)}</div></div>

                        <div class="col-6"><div class="detail-label">Bank</div><div class="detail-value">${data.bank}</div></div>
                        <div class="col-6"><div class="detail-label">No Rekening</div><div class="detail-value font-monospace">${data.rekening}</div></div>
                        <div class="col-6"><div class="detail-label">Tgl Masuk</div><div class="detail-value">${formatDate(data.tanggal_masuk)}</div></div>
                        <div class="col-6"><div class="detail-label">Tgl Keluar</div><div class="detail-value text-danger">${formatDate(data.tanggal_keluar)}</div></div>
                    </div>

                    <div class="section-title mt-2"><i class="bi bi-shield-lock"></i> Akun & Sistem</div>
                    <div class="row">
                        <div class="col-6"><div class="detail-label">Username</div><div class="detail-value badge bg-dark text-white fw-normal">${data.username}</div></div>
                        <div class="col-6"><div class="detail-label">Password</div><div class="detail-value text-muted small">********</div></div>
                        <div class="col-12">
                            <div class="detail-label">FCM Token (Firebase)</div>
                            ${data.fcm_token ? `
                                <div class="input-group input-group-sm">
                                    <input type="text" class="form-control bg-light" value="${data.fcm_token}" readonly id="tokenCopy">
                                    <button class="btn btn-outline-secondary" onclick="copyToken()"><i class="bi bi-clipboard"></i> Copy</button>
                                </div>
                            ` : '<span class="text-muted small">- Tidak ada token -</span>'}
                        </div>
                    </div>
                </div>
            </div>
        `;

        modalFooter.innerHTML = `
            <div>
                <a href="edit_karyawan.php?id=${data.id}" class="btn btn-warning btn-sm fw-bold shadow-sm px-3"><i class="bi bi-pencil-square me-1"></i> Edit</a>
                <a href="cetak_kartu_nama.php?id=${data.id}&layout=standard&multiple=5" class="btn btn-info btn-sm fw-bold shadow-sm px-3 ms-1" target="_blank"><i class="bi bi-credit-card me-1"></i> Kartu</a>
                <a href="hapus_karyawan.php?id=${data.id}" class="btn btn-danger btn-sm fw-bold shadow-sm px-3 ms-1" onclick="return confirm('Yakin hapus data ${data.nama} secara permanen?')"><i class="bi bi-trash3-fill me-1"></i> Hapus</a>
            </div>
            <button type="button" class="btn btn-secondary btn-sm px-3" data-bs-dismiss="modal">Tutup</button>
        `;

        modal.show();
    });

    function copyToken() {
        const input = document.getElementById('tokenCopy');
        if (input) {
            input.select();
            input.setSelectionRange(0, 99999);
            navigator.clipboard.writeText(input.value).then(() => {
                alert("Token berhasil disalin!");
            });
        }
    }
</script>
</body>
</html>

<?php $conn->close(); ?>
