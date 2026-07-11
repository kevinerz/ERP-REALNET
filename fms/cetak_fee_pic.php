<?php
require_once 'config/database.php';
require_once 'dompdf/autoload.inc.php';

use Dompdf\Dompdf;
use Dompdf\Options;

// 1. Validasi Input dari URL
$pic_nama = isset($_GET['pic_nama']) ? trim($_GET['pic_nama']) : '';
$periode_input = isset($_GET['periode']) ? $_GET['periode'] : '';

if (empty($pic_nama) || empty($periode_input)) {
    die("Error: Nama PIC dan Periode harus dipilih.");
}

// Proses periode (contoh: dari "2025-06" menjadi tanggal akhir bulan "2025-06-30")
$periode_date = new DateTime($periode_input . '-01');
$periode_akhir_bulan = $periode_date->format('Y-m-t');
$periode_tampil = $periode_date->format('F Y');

// 2. Definisi Aturan Bisnis
$id_paket_valid = [25, 28, 31, 32];
$paket_in_clause = implode(',', $id_paket_valid);
$fee_per_pelanggan = 5000;

// 3. Ambil data pelanggan untuk PIC dan periode tersebut
$pelanggan_list = [];
$stmt = $conn_pasang->prepare(
    "SELECT nama, alamat, paket, pop, tanggal FROM pemasangan 
     WHERE marketing = ? 
     AND paket IN ($paket_in_clause) 
     AND status IN ('selesai', 'on')
     AND tanggal <= ?"
);
$stmt->bind_param("ss", $pic_nama, $periode_akhir_bulan);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $pelanggan_list[] = $row;
}
$jumlah_pelanggan = count($pelanggan_list);
$total_fee = $jumlah_pelanggan * $fee_per_pelanggan;

// Ambil nama paket
$paket_map = [];
$rp = $conn_bbm->query("SELECT id_paket, nama_paket FROM paket");
while ($p = $rp->fetch_assoc()) {
    $paket_map[$p['id_paket']] = $p['nama_paket'];
}

$conn_pasang->close();
$conn_bbm->close();

// 4. Membuat Konten HTML untuk PDF
$html = '
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Fee PIC - ' . htmlspecialchars($pic_nama) . '</title>
    <style>
        body { font-family: sans-serif; margin: 25px; font-size: 12px; }
        .header h1 { margin: 0; font-size: 20px; text-align:center; }
        .header p { margin: 5px 0; text-align:center;}
        .details { margin: 20px 0; }
        .summary-table, .details-table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        .summary-table td { padding: 8px; font-size: 14px; }
        .details-table th, .details-table td { border: 1px solid #ccc; padding: 6px; }
        .details-table th { background-color: #f2f2f2; text-align:center; }
        .total { font-weight: bold; font-size: 16px; background-color: #e8f5e9; }
        .footer { margin-top: 40px; text-align: center; font-size: 10px; color: #777; }
    </style>
</head>
<body>
    <div class="header">
        <h1>LAPORAN FEE PIC BULANAN</h1>
        <p>PT. Real Data Solusindo</p>
    </div>

    <div class="details">
        <table class="summary-table">
            <tr>
                <td width="15%"><b>Nama PIC</b></td>
                <td width="35%">: ' . htmlspecialchars($pic_nama) . '</td>
                <td width="15%"><b>Periode</b></td>
                <td width="35%">: ' . $periode_tampil . '</td>
            </tr>
        </table>
        <hr>
        <table class="summary-table">
            <tr>
                <td width="50%"><b>Total Pelanggan Aktif (Paket Khusus)</b></td>
                <td>: ' . $jumlah_pelanggan . ' Pelanggan</td>
            </tr>
            <tr>
                <td><b>Fee per Pelanggan</b></td>
                <td>: Rp ' . number_format($fee_per_pelanggan, 0, ',', '.') . '</td>
            </tr>
            <tr class="total">
                <td><b>TOTAL FEE DITERIMA</b></td>
                <td>: Rp ' . number_format($total_fee, 0, ',', '.') . '</td>
            </tr>
        </table>
    </div>

    <h4>Rincian Pelanggan:</h4>
    <table class="details-table">
        <thead>
            <tr>
                <th>No</th>
                <th>Nama Pelanggan</th>
                <th>Alamat</th>
                <th>Paket</th>
                <th>Tanggal Pasang</th>
            </tr>
        </thead>
        <tbody>';
$no = 1;
if ($jumlah_pelanggan > 0) {
    foreach ($pelanggan_list as $pelanggan) {
        $html .= '
            <tr>
                <td style="text-align:center;">' . $no++ . '</td>
                <td>' . htmlspecialchars($pelanggan['nama']) . '</td>
                <td>' . htmlspecialchars($pelanggan['alamat']) . '</td>
                <td style="text-align:center;">' . htmlspecialchars($paket_map[$pelanggan['paket']] ?? 'N/A') . '</td>
                <td style="text-align:center;">' . date("d-m-Y", strtotime($pelanggan['tanggal'])) . '</td>
            </tr>';
    }
} else {
    $html .= '<tr><td colspan="5" style="text-align:center; padding: 20px;">Tidak ada pelanggan aktif pada periode ini.</td></tr>';
}
$html .= '
        </tbody>
    </table>

    <div class="footer">
        Dicetak oleh FMS pada ' . date("d F Y, H:i:s") . '
    </div>
</body>
</html>
';

// 5. Generate PDF
$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();
$dompdf->stream("laporan-fee-pic-" . str_replace(' ', '-', $pic_nama) . "-" . $periode_input . ".pdf", ["Attachment" => false]);
exit();
?>