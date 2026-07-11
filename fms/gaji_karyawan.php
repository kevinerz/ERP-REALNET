<?php
// gaji_karyawan.php (ENHANCED PRODUCTION - UPDATED: GAJI POKOK + TUNJANGAN)
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/gaji_error.log');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Cek login
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: login.php');
    exit;
}

// Config database
require_once 'config/database.php';
date_default_timezone_set('Asia/Jakarta');

// Verifikasi koneksi
if (!isset($conn) || $conn->connect_error) die("ERROR: Koneksi database FMS gagal!");
if (!isset($conn_bbm) || $conn_bbm->connect_error) die("ERROR: Koneksi database UMUMDATA gagal!");

// CONFIG
define('APP_BASE_URL', 'https://datarealsolution.net/fms');
define('STARSENDER_API_KEY', 'e9c50247-3b8d-4cd8-924a-024a4d2b3124');
define('STARSENDER_URL', 'https://api.starsender.online/api/send');

// HELPER FUNCTIONS
function e($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function rupiah($n) { return 'Rp ' . number_format((float)$n, 0, ',', '.'); }
function toFloat($v) { return (float)preg_replace('/[^0-9\.\-]/', '', (string)$v); }
function makePublicToken() { return bin2hex(random_bytes(32)); }
function waNormalize($no) {
    $no = preg_replace('/[^0-9]/', '', (string)$no);
    if ($no === '') return '';
    if (substr($no, 0, 1) === '0') $no = '62' . substr($no, 1);
    return $no;
}

function sendWAStarSenderText($to, $body) {
    $to = waNormalize($to);
    if ($to === '' || $body === '') return ['ok' => false];
    $payload = ["messageType" => "text", "to" => $to, "body" => $body];
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => STARSENDER_URL,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Authorization: ' . STARSENDER_API_KEY],
    ]);
    $resp = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ['ok' => ($code >= 200 && $code < 300)];
}

// CSRF TOKEN
if (empty($_SESSION['csrf_gaji'])) $_SESSION['csrf_gaji'] = bin2hex(random_bytes(32));
$csrfToken = $_SESSION['csrf_gaji'];

// LOGIC PEMBANTU (Filter & Waktu)
$bulan_ini = (int)date('m');
$tahun_ini = (int)date('Y');
$success_message = $_SESSION['success_gaji'] ?? '';
$error_message = '';
unset($_SESSION['success_gaji']);

// PROSES POST: BAYAR GAJI
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_bayar'])) {
    if (!hash_equals($csrfToken, $_POST['csrf_token'] ?? '')) {
        $error_message = "CSRF Token Invalid.";
    } else {
        $nik = trim($_POST['karyawan_nik'] ?? '');
        $tgl = $_POST['tanggal_bayar'] ?? date('Y-m-d');
        $bonus = toFloat($_POST['bonus'] ?? 0);
        $potongan = toFloat($_POST['potongan'] ?? 0);
        $ket = trim($_POST['keterangan'] ?? '');

        // Ambil data karyawan (gunakan kolom baru)
        $stmtEmp = $conn_bbm->prepare("
            SELECT
                id, nik, nama, no_telp, bank, rekening,
                COALESCE(gaji_pokok,0) AS gaji_pokok,
                COALESCE(tunjangan_jabatan,0) AS tunjangan_jabatan,
                COALESCE(tunjangan_operasional,0) AS tunjangan_operasional
            FROM karyawan
            WHERE nik=? LIMIT 1
        ");
        $stmtEmp->bind_param("s", $nik);
        $stmtEmp->execute();
        $emp = $stmtEmp->get_result()->fetch_assoc();

        if ($emp) {
            $gp = (float)$emp['gaji_pokok'];
            $tj = (float)$emp['tunjangan_jabatan'];
            $to = (float)$emp['tunjangan_operasional'];
            $gaji_dasar = $gp + $tj + $to;

            $total = $gaji_dasar + $bonus - $potongan;

            if ($total < 0) {
                $error_message = "Total gaji tidak boleh minus!";
            } else {
                $token = makePublicToken();

                // Simpan slip (kolom gaji_pokok di slip_gaji kita isi = total gaji dasar (gp+tj+to))
                $sqlIns = "INSERT INTO slip_gaji
                    (karyawan_nik, nama_karyawan, tanggal_bayar, gaji_pokok, bonus, potongan, total_dibayar,
                     nama_bank, no_rekening, nama_rekening, keterangan, public_token)
                    VALUES (?,?,?,?,?,?,?,?,?,?,?,?)";
                $stmtIns = $conn->prepare($sqlIns);

                // nama_rekening kita pakai nama karyawan (sesuai versi Anda sebelumnya)
                $namaRek = $emp['nama'];

                $stmtIns->bind_param(
                    "sssddddsssss",
                    $nik,
                    $emp['nama'],
                    $tgl,
                    $gaji_dasar,
                    $bonus,
                    $potongan,
                    $total,
                    $emp['bank'],
                    $emp['rekening'],
                    $namaRek,
                    $ket,
                    $token
                );

                if ($stmtIns->execute()) {
                    // Kirim WA
                    $slipUrl = APP_BASE_URL . "/slip_gaji_pdf.php?token=" . $token;

                    $msg = "✅ *Slip Gaji PT REAL DATA SOLUSINDO*\n\n"
                         . "👤 Nama: *{$emp['nama']}*\n"
                         . "📅 Tgl: " . date('d-m-Y', strtotime($tgl)) . "\n\n"
                         . "🧾 Rincian:\n"
                         . "- Gaji Pokok: " . rupiah($gp) . "\n"
                         . "- Tunj. Jabatan: " . rupiah($tj) . "\n"
                         . "- Tunj. Operasional: " . rupiah($to) . "\n"
                         . "- Bonus: " . rupiah($bonus) . "\n"
                         . "- Potongan: " . rupiah($potongan) . "\n\n"
                         . "💰 Total Dibayar: *" . rupiah($total) . "*\n"
                         . "📄 Slip: $slipUrl";

                    sendWAStarSenderText($emp['no_telp'], $msg);

                    $_SESSION['success_gaji'] = "Gaji {$emp['nama']} berhasil dibayar!";
                    header("Location: gaji_karyawan.php");
                    exit;
                } else {
                    $error_message = "Gagal simpan slip: " . e($stmtIns->error);
                }
            }
        } else {
            $error_message = "Karyawan dengan NIK tersebut tidak ditemukan.";
        }
    }
}

// PROSES POST: DELETE
if (isset($_POST['delete_slip'])) {
    // (opsional) tambah CSRF delete kalau mau, sekarang mengikuti pola lama Anda
    $slipId = (int)($_POST['slip_id'] ?? 0);
    if ($slipId > 0) {
        $stmtDel = $conn->prepare("DELETE FROM slip_gaji WHERE id=?");
        $stmtDel->bind_param("i", $slipId);
        $stmtDel->execute();
    }
    header("Location: gaji_karyawan.php");
    exit;
}

// AMBIL DATA UNTUK TAMPILAN (karyawan + kasbon bulan ini)
$karyawan_list = [];
$resK = $conn_bbm->query("
    SELECT
        k.*,
        COALESCE(k.gaji_pokok,0) AS gaji_pokok,
        COALESCE(k.tunjangan_jabatan,0) AS tunjangan_jabatan,
        COALESCE(k.tunjangan_operasional,0) AS tunjangan_operasional,
        (COALESCE(k.gaji_pokok,0) + COALESCE(k.tunjangan_jabatan,0) + COALESCE(k.tunjangan_operasional,0)) AS gaji_dasar,
        (SELECT COALESCE(SUM(jumlah),0)
            FROM kasbon
            WHERE id_karyawan=k.id
              AND status='selesai'
              AND MONTH(tanggal)=$bulan_ini
              AND YEAR(tanggal)=$tahun_ini
        ) AS kasbon_ini
    FROM karyawan k
    ORDER BY nama ASC
");
while($row = $resK->fetch_assoc()) $karyawan_list[] = $row;

// Ambil Riwayat & Statistik
$f_bulan = (int)($_GET['bulan'] ?? $bulan_ini);
$f_tahun = (int)($_GET['tahun'] ?? $tahun_ini);
$f_search = $_GET['search'] ?? '';

$sqlR = "SELECT * FROM slip_gaji
         WHERE MONTH(tanggal_bayar)=? AND YEAR(tanggal_bayar)=? AND nama_karyawan LIKE ?
         ORDER BY id DESC";
$stmtR = $conn->prepare($sqlR);
$search_param = "%$f_search%";
$stmtR->bind_param("iis", $f_bulan, $f_tahun, $search_param);
$stmtR->execute();
$riwayat = $stmtR->get_result()->fetch_all(MYSQLI_ASSOC);

// Hitung Statistik Simple
$stats = ['total' => 0, 'bonus' => 0, 'potongan' => 0, 'count' => count($riwayat)];
foreach($riwayat as $r) {
    $stats['total'] += (float)$r['total_dibayar'];
    $stats['bonus'] += (float)$r['bonus'];
    $stats['potongan'] += (float)$r['potongan'];
}

require_once 'templates/header.php';
?>

<style>
    .stat-card { border-left: 4px solid #667eea; transition: 0.3s; }
    .stat-card:hover { transform: translateY(-3px); box-shadow: 0 5px 15px rgba(0,0,0,0.1); }
    .form-gaji-container { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border-radius: 15px; padding: 25px; }
    .btn-bayar { background: #11998e; border: none; font-weight: bold; color: white; }
    .btn-bayar:hover { background: #0e8178; }
</style>

<div class="container-fluid mt-4 px-md-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="bi bi-cash-stack"></i> Payroll System</h2>
        <div class="badge bg-primary p-2">Periode: <?= e(date('F Y')) ?></div>
    </div>

    <?php if (!empty($error_message)): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <?= e($error_message) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if ($success_message): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <?= e($success_message) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card stat-card p-3 shadow-sm">
                <small class="text-muted">Total Pembayaran</small>
                <h4 class="fw-bold mb-0"><?= rupiah($stats['total']) ?></h4>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stat-card p-3 shadow-sm" style="border-left-color: #11998e">
                <small class="text-muted">Karyawan Terbayar</small>
                <h4 class="fw-bold mb-0"><?= (int)$stats['count'] ?> Orang</h4>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stat-card p-3 shadow-sm" style="border-left-color: #f093fb">
                <small class="text-muted">Total Bonus</small>
                <h4 class="fw-bold mb-0 text-success">+<?= rupiah($stats['bonus']) ?></h4>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stat-card p-3 shadow-sm" style="border-left-color: #fa709a">
                <small class="text-muted">Total Potongan</small>
                <h4 class="fw-bold mb-0 text-danger">-<?= rupiah($stats['potongan']) ?></h4>
            </div>
        </div>
    </div>

    <div class="form-gaji-container shadow-lg mb-5">
        <h4 class="mb-4"><i class="bi bi-pencil-square"></i> Input Pembayaran Gaji</h4>
        <form id="formGaji" method="POST" action="">
            <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Karyawan</label>
                    <select id="karyawan" name="karyawan_nik" class="form-select select2" required>
                        <option value="">-- Pilih Karyawan --</option>
                        <?php foreach ($karyawan_list as $k):
                            $gp = (float)$k['gaji_pokok'];
                            $tj = (float)$k['tunjangan_jabatan'];
                            $to = (float)$k['tunjangan_operasional'];
                            $gaji_dasar = (float)$k['gaji_dasar'];
                            $kasbon = (float)($k['kasbon_ini'] ?? 0);
                        ?>
                            <option
                                value="<?= e($k['nik']) ?>"
                                data-gp="<?= $gp ?>"
                                data-tj="<?= $tj ?>"
                                data-to="<?= $to ?>"
                                data-gaji="<?= $gaji_dasar ?>"
                                data-kasbon="<?= $kasbon ?>"
                                data-bank="<?= e($k['bank'] ?? '-') ?>"
                                data-rek="<?= e($k['rekening'] ?? '-') ?>"
                            >
                                <?= e($k['nama']) ?> (<?= e($k['nik']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label">Gaji Dasar</label>
                    <input type="text" id="gaji_pokok" class="form-control" readonly>
                    <small class="text-white-50">Pokok+Tunjangan</small>
                </div>

                <div class="col-md-2">
                    <label class="form-label">Bonus</label>
                    <input type="number" name="bonus" id="bonus" class="form-control" value="0" step="1000">
                </div>

                <div class="col-md-2">
                    <label class="form-label">Potongan (Kasbon)</label>
                    <input type="number" name="potongan" id="potongan" class="form-control" value="0" step="1000">
                    <small class="text-white-50">Auto isi kasbon</small>
                </div>

                <div class="col-md-2">
                    <label class="form-label">Tgl Bayar</label>
                    <input type="date" name="tanggal_bayar" class="form-control" value="<?= e(date('Y-m-d')) ?>" required>
                </div>

                <div class="col-md-9">
                    <label class="form-label">Keterangan</label>
                    <input type="text" name="keterangan" class="form-control" placeholder="Catatan tambahan...">
                </div>

                <div class="col-md-3 d-flex align-items-end">
                    <button type="submit" name="submit_bayar" class="btn btn-bayar w-100 p-2" id="btnSubmit">
                        <i class="bi bi-check-all"></i> BAYAR SEKARANG
                    </button>
                </div>
            </div>

            <div class="mt-3 p-3 bg-white bg-opacity-10 rounded">
                <div class="row g-2 align-items-center">
                    <div class="col-md-8">
                        <div class="small">
                            <span class="me-3">Bank: <strong id="txtBank">-</strong></span>
                            <span class="me-3">Rek: <strong id="txtRek">-</strong></span>
                        </div>
                        <div class="small mt-1">
                            <span class="me-3">Gaji Pokok: <strong id="txtGP">Rp 0</strong></span>
                            <span class="me-3">Tunj. Jabatan: <strong id="txtTJ">Rp 0</strong></span>
                            <span class="me-3">Tunj. Operasional: <strong id="txtTO">Rp 0</strong></span>
                        </div>
                    </div>
                    <div class="col-md-4 text-md-end">
                        <div class="small">Total Dibayar:</div>
                        <h3 class="d-inline fw-bold" id="totalText">Rp 0</h3>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-body">
            <div class="d-flex justify-content-between mb-3 align-items-center">
                <h5 class="fw-bold m-0">Riwayat Pembayaran</h5>
                <form class="d-flex gap-2">
                    <select name="bulan" class="form-select form-select-sm">
                        <?php for($i=1;$i<=12;$i++) echo "<option value='$i' ".($f_bulan==$i?'selected':'').">".date('F', mktime(0,0,0,$i,1))."</option>"; ?>
                    </select>
                    <input type="text" name="search" class="form-control form-control-sm" placeholder="Cari nama..." value="<?= e($f_search) ?>">
                    <button class="btn btn-sm btn-primary">Filter</button>
                </form>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Tgl</th>
                            <th>Nama Karyawan</th>
                            <th>Gaji Dasar</th>
                            <th>Bonus</th>
                            <th>Potongan</th>
                            <th>Total</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($riwayat as $r): ?>
                        <tr>
                            <td><?= e(date('d/m/y', strtotime($r['tanggal_bayar']))) ?></td>
                            <td>
                                <strong><?= e($r['nama_karyawan']) ?></strong><br>
                                <small class="text-muted"><?= e($r['nama_bank']) ?> - <?= e($r['no_rekening']) ?></small>
                            </td>
                            <td><?= rupiah($r['gaji_pokok']) ?></td>
                            <td class="text-success">+<?= rupiah($r['bonus']) ?></td>
                            <td class="text-danger">-<?= rupiah($r['potongan']) ?></td>
                            <td class="fw-bold"><?= rupiah($r['total_dibayar']) ?></td>
                            <td class="text-center">
                                <a href="slip_gaji_pdf.php?token=<?= e($r['public_token']) ?>" target="_blank" class="btn btn-sm btn-outline-danger"><i class="bi bi-file-pdf"></i></a>
                                <button onclick="hapus(<?= (int)$r['id'] ?>, '<?= e($r['nama_karyawan']) ?>')" class="btn btn-sm btn-outline-secondary"><i class="bi bi-trash"></i></button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (count($riwayat) === 0): ?>
                        <tr><td colspan="7" class="text-center text-muted py-4">Belum ada pembayaran di periode ini.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<form id="formDelete" method="POST" style="display:none;">
    <input type="hidden" name="delete_slip" value="1">
    <input type="hidden" name="slip_id" id="del_id">
</form>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const sel = document.getElementById('karyawan');
    const bns = document.getElementById('bonus');
    const pot = document.getElementById('potongan');
    const btn = document.getElementById('btnSubmit');

    const elGaji = document.getElementById('gaji_pokok');
    const elBank = document.getElementById('txtBank');
    const elRek  = document.getElementById('txtRek');
    const elTot  = document.getElementById('totalText');

    const elGP = document.getElementById('txtGP');
    const elTJ = document.getElementById('txtTJ');
    const elTO = document.getElementById('txtTO');

    function rupiah(n){
        n = Number(n) || 0;
        return 'Rp ' + n.toLocaleString('id-ID');
    }

    function hitung() {
        const opt = sel.options[sel.selectedIndex];
        if(!opt || !opt.value) {
            elGaji.value = '';
            elBank.innerText = '-';
            elRek.innerText = '-';
            elGP.innerText = 'Rp 0';
            elTJ.innerText = 'Rp 0';
            elTO.innerText = 'Rp 0';
            elTot.innerText = 'Rp 0';
            btn.disabled = true;
            return;
        }

        const gp = parseFloat(opt.dataset.gp) || 0;
        const tj = parseFloat(opt.dataset.tj) || 0;
        const to = parseFloat(opt.dataset.to) || 0;
        const gajiDasar = parseFloat(opt.dataset.gaji) || (gp + tj + to);

        const bn = parseFloat(bns.value) || 0;
        const pt = parseFloat(pot.value) || 0;

        const total = gajiDasar + bn - pt;

        elGaji.value = rupiah(gajiDasar);
        elBank.innerText = opt.dataset.bank || '-';
        elRek.innerText = opt.dataset.rek || '-';

        elGP.innerText = rupiah(gp);
        elTJ.innerText = rupiah(tj);
        elTO.innerText = rupiah(to);

        elTot.innerText = rupiah(total);

        btn.disabled = (total < 0);
    }

    sel.addEventListener('change', function() {
        const opt = sel.options[sel.selectedIndex];
        pot.value = (opt && opt.value) ? (opt.dataset.kasbon || 0) : 0;
        hitung();
    });

    bns.addEventListener('input', hitung);
    pot.addEventListener('input', hitung);

    // init
    hitung();
});

function hapus(id, nama) {
    if(confirm('Hapus slip gaji ' + nama + '?')) {
        document.getElementById('del_id').value = id;
        document.getElementById('formDelete').submit();
    }
}
</script>

<?php require_once 'templates/footer.php'; ?>
