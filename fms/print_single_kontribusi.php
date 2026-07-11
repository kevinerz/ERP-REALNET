<?php
session_start();
// Cek apakah user sudah login atau belum
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: login.php');
    exit;
}

// Memuat koneksi DB FMS
require_once 'config/database.php'; // Pastikan path ini benar

// Pastikan ID kontribusi diberikan
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("ID Kontribusi tidak valid.");
}

$kontribusi_id = $_GET['id'];

// Mengambil data kontribusi berdasarkan ID
$stmt = $conn->prepare("SELECT * FROM keu_pembayaran_kontribusi WHERE id = ?");
$stmt->bind_param("i", $kontribusi_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Data kontribusi tidak ditemukan.");
}

$data = $result->fetch_assoc();
$stmt->close();
$conn->close();

// Memuat Dompdf
require_once 'dompdf/autoload.inc.php'; // Pastikan path ini benar ke folder Dompdf Anda

use Dompdf\Dompdf;
use Dompdf\Options;

// Buat instance Dompdf
$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', true);
$dompdf = new Dompdf($options);

// Output Buffering untuk menangkap HTML yang akan di-render
ob_start();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bukti Pembayaran Kontribusi - #<?= htmlspecialchars($data['id']) ?></title>
    <style>
        body {
            font-family: 'Times New Roman', serif;
            margin: 25mm 20mm; /* Margin atas/bawah 25mm, kiri/kanan 20mm */
            font-size: 11pt;
            line-height: 1.5;
        }
        .header {
            text-align: center;
            margin-bottom: 25px;
        }
        .header h1 {
            font-size: 18pt;
            margin-bottom: 3px;
        }
        .header h2 {
            font-size: 14pt;
            margin-top: 0;
            margin-bottom: 5px;
            text-transform: uppercase;
        }
        .header p {
            font-size: 10pt;
            margin: 0;
        }
        .line-separator {
            border: none;
            border-top: 2px solid #000;
            margin: 15px 0 25px 0;
        }
        .document-title {
            text-align: center;
            font-size: 16pt;
            font-weight: bold;
            text-decoration: underline;
            margin-bottom: 30px;
        }
        .document-info {
            width: 100%;
            margin-bottom: 20px;
        }
        .document-info td {
            padding: 2px 0;
            vertical-align: top;
        }
        .document-info td:first-child {
            width: 25%;
        }
        .content-section {
            margin-bottom: 25px;
        }
        .detail-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        .detail-table td {
            padding: 6px 0;
            vertical-align: top;
        }
        .detail-table td:first-child {
            width: 35%;
            font-weight: bold;
        }
        .signature-area {
            position: relative; /* Untuk memposisikan tanda tangan di bagian bawah */
            margin-top: 60px; /* Jarak dari konten atas */
        }
        .signature-col {
            width: 48%; /* Hampir setengah lebar */
            display: inline-block; /* Agar bisa berdampingan */
            vertical-align: top; /* Rata atas jika ada perbedaan tinggi */
        }
        .signature-left {
            text-align: center;
            float: left; /* Posisikan ke kiri */
        }
        .signature-right {
            text-align: center;
            float: right; /* Posisikan ke kanan */
        }
        .signature-line {
            margin-top: 50px; /* Ruang untuk tanda tangan */
            border-bottom: 1px solid #000;
            width: 70%; /* Lebar garis tanda tangan */
            margin-left: auto;
            margin-right: auto;
        }
        .date-location {
            text-align: right;
            margin-bottom: 10px;
        }
        .clear-fix::after {
            content: "";
            clear: both;
            display: table;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>PT. MEDIA GRASI INTERNET</h1>
        <h2>INTERNET SERVICE PROVIDER</h2>
        <p>Perum Puri Rajeg, Jl. Arjuna 2, Lembangsari, Kec. Rajeg, Kabupaten Tangerang, Banten 15540</p>
    <hr class="line-separator">

    <div class="document-title">
        BUKTI PEMBAYARAN KONTRIBUSI
    </div>

    <div class="document-info">
        <table>
            <tr>
                <td>Nomor Dokumen</td>
                <td>: KTB/MGI/<?= date('Ym') ?>/<?= sprintf('%04d', $data['id']) ?></td>
            </tr>
            <tr>
                <td>Tanggal Cetak</td>
                <td>: <?= date("d F Y", strtotime('now')) ?></td> </tr>
        </table>
    </div>

    <div class="content-section">
        <p>Dengan hormat,</p>
        <p>Bersama ini kami sampaikan bahwa telah diterima pembayaran kontribusi dari:</p>

        <table class="detail-table">
            <tr>
                <td>Nama Penerima</td>
                <td>: <?= htmlspecialchars($data['nama_penerima']) ?></td>
            </tr>
            <tr>
                <td>Nomor WA Penerima</td>
                <td>: <?= htmlspecialchars($data['no_wa_penerima']) ?></td>
            </tr>
            <tr>
                <td>Nama/Tujuan Kontribusi</td>
                <td>: <strong><?= htmlspecialchars($data['nama_kontribusi']) ?></strong></td>
            </tr>
            <tr>
                <td>Tanggal Pembayaran</td>
                <td>: <?= date("d F Y", strtotime($data['tanggal_bayar'])) ?></td>
            </tr>
            <tr>
                <td>Nominal</td>
                <td>: <strong>Rp <?= number_format($data['nominal'], 0, ',', '.') ?>,-</strong></td>
            </tr>
            <tr>
                <td>Keterangan</td>
                <td>: <?= nl2br(htmlspecialchars($data['keterangan'])) ?></td>
            </tr>
        </table>

        <p style="margin-top: 25px;">Demikian bukti pembayaran kontribusi ini dibuat untuk digunakan sebagaimana mestinya. Atas perhatian dan kerjasamanya, kami mengucapkan terima kasih.</p>
    </div>

    <div class="signature-area clear-fix">
        <div class="date-location">
            Tangerang, <?= date("d F Y", strtotime('now')) ?>
        </div>

        <div class="signature-col signature-left">
            <p>Penerima Kontribusi,</p>
            <div class="signature-line"></div>
            <p>(_________________________)</p>
            <p>Nama Lengkap Penerima</p>
        </div>

        <div class="signature-col signature-right">
            <p>Hormat kami,</p>
            <p>Direktur PT. Media Grasi Internet</p>
            <div class="signature-line"></div> <p><strong>(Kudhori)</strong></p>
        </div>
    </div>

</body>
</html>
<?php
$html = ob_get_clean(); // Ambil semua HTML yang di-buffer

$dompdf->loadHtml($html);

// Atur ukuran kertas dan orientasi
$dompdf->setPaper('A4', 'portrait');

// Render HTML ke PDF
$dompdf->render();

// Output PDF ke browser
$filename = "Bukti_Pembayaran_Kontribusi_" . htmlspecialchars($data['id']) . ".pdf";
$dompdf->stream($filename, array("Attachment" => false)); // "Attachment" => true untuk langsung download
exit();
?>