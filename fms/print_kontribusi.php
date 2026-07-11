<?php
session_start();
// Cek apakah user sudah login atau belum
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: login.php');
    exit;
}

// Memuat koneksi DB FMS
require_once 'config/db_connect.php'; // Sesuaikan dengan lokasi file koneksi DB Anda
                                    // Asumsi $conn sudah tersedia di sini

// Memuat Dompdf
require_once 'vendor/autoload.php'; // Jika menggunakan Composer
// Jika tidak menggunakan Composer, sesuaikan path ke folder dompdf yang Anda unduh
// require_once 'dompdf/autoload.inc.php';

use Dompdf\Dompdf;
use Dompdf\Options;

// Buat instance Dompdf
$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options.set('isRemoteEnabled', true); // Penting jika Anda punya gambar eksternal atau CSS dari CDN
$dompdf = new Dompdf($options);

// Output Buffering untuk menangkap HTML yang akan di-render
ob_start();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Kontribusi</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20mm;
            font-size: 10pt;
        }
        h1 {
            text-align: center;
            margin-bottom: 20px;
            font-size: 16pt;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            border: 1px solid #000;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
        .text-center {
            text-align: center;
        }
        .text-muted {
            color: #666;
        }
    </style>
</head>
<body>
    <h1>Laporan Riwayat Kontribusi</h1>
    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Tanggal Bayar</th>
                <th>Nama Kontribusi</th>
                <th>Penerima</th>
                <th>No. WA Penerima</th>
                <th>Nominal</th>
                <th>Keterangan</th>
            </tr>
        </thead>
        <tbody>
            <?php
            // Query untuk mengambil data riwayat
            // Pastikan $conn tersedia dari config/db_connect.php
            $result = $conn->query("SELECT * FROM keu_pembayaran_kontribusi ORDER BY tanggal_bayar DESC, id DESC LIMIT 100");
            $no = 1;
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    echo "<tr>";
                    echo "<td>" . $no++ . "</td>";
                    echo "<td>" . date("d M Y", strtotime($row['tanggal_bayar'])) . "</td>";
                    echo "<td>" . htmlspecialchars($row['nama_kontribusi']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['nama_penerima']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['no_wa_penerima']) . "</td>";
                    echo "<td>Rp " . number_format($row['nominal'], 0, ',', '.') . "</td>";
                    echo "<td>" . nl2br(htmlspecialchars($row['keterangan'])) . "</td>";
                    echo "</tr>";
                }
            } else {
                echo "<tr><td colspan='7' class='text-center text-muted p-4'>Belum ada riwayat kontribusi.</td></tr>";
            }
            ?>
        </tbody>
    </table>
</body>
</html>
<?php
$html = ob_get_clean(); // Ambil semua HTML yang di-buffer

$dompdf->loadHtml($html);

// (Opsional) Atur ukuran kertas dan orientasi
$dompdf->setPaper('A4', 'portrait');

// Render HTML ke PDF
$dompdf->render();

// Output PDF ke browser
$dompdf->stream("Laporan_Kontribusi_" . date('Ymd_His') . ".pdf", array("Attachment" => false)); // "Attachment" => true untuk langsung download
exit();
?>