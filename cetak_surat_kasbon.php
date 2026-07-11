<?php
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once 'libraries/dompdf/autoload.inc.php';
use Dompdf\Dompdf;

include 'kasbon/koneksi.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("ID kasbon tidak valid.");
}
$kasbon_id = (int)$_GET['id'];

// Ambil kasbon berdasarkan ID
$stmt = $conn->prepare("
    SELECT k.*, u.nama, u.divisi, u.username 
    FROM kasbon k 
    JOIN karyawan u ON k.id_karyawan = u.id 
    WHERE k.id = ? AND k.status = 'selesai'
");
$stmt->bind_param("i", $kasbon_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Tidak ada kasbon yang sudah disetujui (status selesai).");
}

$data = $result->fetch_assoc();

// Ambil nama approver dari divisi tertentu
function getApprover($conn, $divisi) {
    $stmt = $conn->prepare("SELECT nama FROM karyawan WHERE divisi = ? LIMIT 1");
    $stmt->bind_param("s", $divisi);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res->fetch_assoc();
    return $row ? $row['nama'] : '-';
}

// Tentukan alur persetujuan berdasarkan divisi pengaju
function getApprovalFlow($pengaju_divisi) {
    switch ($pengaju_divisi) {
        case 'Teknisi':
            // Langsung ke SPV Teknis, lewati Leader Area
            return ['SPV Teknis', 'Manager', 'Admin', 'Finance'];
        case 'Leader Area':
            return ['SPV Teknis', 'Manager', 'Admin', 'Finance'];
        case 'SPV Teknis':
            return ['Manager', 'Admin', 'Finance'];
        case 'Manager':
            return ['Admin', 'Finance'];
        case 'Finance':
            return ['Manager', 'Admin', 'Finance'];
        case 'Admin':
            return ['Manager', 'Finance'];
        default:
            return [];
    }
}



// Siapkan data
$nama           = $data['nama'];
$divisi_user    = $data['divisi'];
$tanggal_kasbon = date("d-m-Y", strtotime($data['tanggal']));
$tanggal_surat  = date("d-m-Y", strtotime($data['tanggal_dibuat']));
$jumlah_rp      = 'Rp ' . number_format($data['jumlah'], 0, ',', '.');
$keperluan      = htmlspecialchars($data['keperluan']);

// Ambil nama approvers sesuai alur
$flow = getApprovalFlow($divisi_user);
$approvers = [];
foreach ($flow as $step) {
    $divisi_lookup = ($step === 'Admin') ? 'Admin' : $step;
    $approvers[$step] = getApprover($conn, $divisi_lookup);
}

// Buat HTML
$html = '
<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12pt; }
        .header { text-align: center; margin-bottom: 30px; }
        .isi { margin: 20px 0; }
        .ttd { margin-top: 60px; text-align: left; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        td { padding: 6px; border: 1px solid #000; }
        ul { padding-left: 16px; margin-top: 10px; }
    </style>
</head>
<body>
<div class="header">
    <h2>SURAT PENGAMBILAN KASBON</h2>
    <p>Tanggal: '.$tanggal_surat.'</p>
</div>

<div class="isi">
    <p>Kepada Yth:</p>
    <p><strong>Bagian Keuangan</strong></p>
    <p>Dengan ini kami menginformasikan bahwa kasbon berikut telah disetujui oleh seluruh pihak dan dapat dicairkan:</p>

    <table>
        <tr><td><strong>Nama Pengaju</strong></td><td>'.$nama.'</td></tr>
        <tr><td><strong>Divisi</strong></td><td>'.$divisi_user.'</td></tr>
        <tr><td><strong>Tanggal Kasbon</strong></td><td>'.$tanggal_kasbon.'</td></tr>
        <tr><td><strong>Jumlah</strong></td><td>'.$jumlah_rp.'</td></tr>
        <tr><td><strong>Keperluan</strong></td><td>'.$keperluan.'</td></tr>
    </table>

    <p style="margin-top: 20px;">Disetujui oleh:</p>
    <ul>';
foreach ($approvers as $div => $nama_approver) {
    $html .= '<li>' . htmlspecialchars($div) . ': <strong>' . htmlspecialchars($nama_approver) . '</strong></li>';
}
$html .= '</ul>

    <p>Demikian surat ini dibuat untuk digunakan sebagai dasar pencairan kasbon.</p>
</div>

<div class="ttd">
    <p>Hormat Kami,</p>
    <p style="margin-top: 60px;"><strong>'.$nama.'</strong><br>'.$divisi_user.'</p>
</div>
</body>
</html>';

// Cetak PDF
$dompdf = new Dompdf();
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();
$dompdf->stream("Surat_Kasbon_" . $nama . ".pdf", ["Attachment" => false]);
?>
