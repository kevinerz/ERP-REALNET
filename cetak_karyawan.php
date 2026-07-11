<?php
require 'libraries/dompdf/autoload.inc.php'; // jika install via composer
// require_once 'vendor/dompdf/autoload.inc.php'; // jika manual

use Dompdf\Dompdf;
use Dompdf\Options;

// Koneksi database
$servername = "localhost";
$username = "u272457353_kevinsamsung99";
$password = "Admionkevin99";
$database = "u272457353_umumdata";
$conn = new mysqli($servername, $username, $password, $database);
if ($conn->connect_error) die("Koneksi gagal: " . $conn->connect_error);

$sql = "SELECT * FROM karyawan";
$result = $conn->query($sql);

// Siapkan HTML untuk PDF
$html = '
<html>
<head>
    <style>
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 12px; }
        h2 { text-align: center; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px;}
        th, td { border: 1px solid #444; padding: 4px; text-align: center; font-size: 11px; }
        th { background: #f2f2f2; }
    </style>
</head>
<body>
    <h2>Data Karyawan</h2>
    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Nama</th>
                <th>NIK</th>
                <th>Nomor KK</th>
                <th>Tipe & Nomor SIM</th>
                <th>Jenis Kelamin</th>
                <th>Tempat & Tanggal Lahir</th>
                <th>Umur</th>
                <th>Agama</th>
                <th>Status Pernikahan</th>
                <th>Nomor Telepon</th>
                <th>Email</th>
                <th>Alamat</th>
                <th>Status Kepegawaian</th>
                <th>Divisi</th>
                <th>Gaji</th>
            </tr>
        </thead>
        <tbody>
';

$no = 1;
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $html .= '<tr>
            <td>'.$no++.'</td>
            <td>'.htmlspecialchars($row['nama']).'</td>
            <td>'.htmlspecialchars($row['nik']).'</td>
            <td>'.htmlspecialchars($row['nomor_kk']).'</td>
            <td>'.htmlspecialchars($row['tipe_nomor_sim']).'</td>
            <td>'.htmlspecialchars($row['jenis_kelamin']).'</td>
            <td>'.htmlspecialchars($row['tempat_tanggal_lahir']).'</td>
            <td>'.htmlspecialchars($row['umur']).'</td>
            <td>'.htmlspecialchars($row['agama']).'</td>
            <td>'.htmlspecialchars($row['status_pernikahan']).'</td>
            <td>'.htmlspecialchars($row['no_telp']).'</td>
            <td>'.htmlspecialchars($row['email']).'</td>
            <td>'.htmlspecialchars($row['alamat']).'</td>
            <td>'.htmlspecialchars($row['status_kepegawaian']).'</td>
            <td>'.htmlspecialchars($row['divisi']).'</td>
            <td>'.number_format($row['gaji'], 2, ',', '.').'</td>
        </tr>';
    }
} else {
    $html .= '<tr><td colspan="16">Belum ada data karyawan.</td></tr>';
}

$html .= '
        </tbody>
    </table>
</body>
</html>
';

$conn->close();

// Konfigurasi dompdf
$options = new Options();
$options->set('isRemoteEnabled', true); // Untuk load gambar dari URL jika perlu
$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A3', 'landscape');
$dompdf->render();

// Output ke browser
$dompdf->stream("data_karyawan.pdf", array("Attachment" => false)); // false untuk preview di browser
exit;
?>
