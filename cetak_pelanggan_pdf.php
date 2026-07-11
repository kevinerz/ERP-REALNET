<?php
require_once __DIR__ . '/config/database.php';
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once 'libraries/dompdf/autoload.inc.php';
use Dompdf\Dompdf;

if (!isset($_GET['id'])) {
    die('ID pelanggan tidak ditemukan.');
}
$id = intval($_GET['id']);

// Koneksi
$servername = "localhost";
$username = "u272457353_kevinsamsung9";
$password = "Admionkevin99";
$database = "u272457353_db_pemasangan";
$conn = getErpDbConnection();
if ($conn->connect_error) die("DB error");

$stmt = $conn->prepare("SELECT * FROM pelanggan_instalasi WHERE id=?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) die('Data tidak ditemukan.');
$row = $result->fetch_assoc();

// Data paket
$paket_label = $row['paket'];
$conn_umum = getErpDbConnection();
if (!$conn_umum->connect_error) {
    $rp = $conn_umum->query("SELECT * FROM jaringan_paket WHERE id_paket='".intval($row['paket'])."'");
    if ($paket = $rp->fetch_assoc()) {
        $paket_label = htmlspecialchars($paket['nama_paket']) . ' (' . htmlspecialchars($paket['kecepatan']) . ') - Rp ' . number_format($paket['harga'],0,',','.');
    }
}

$html = '
<style>
    body { font-family: sans-serif; font-size:12px; }
    .head { text-align:center; margin-bottom:12px; }
    table { border-collapse:collapse; width:100%; margin:auto; }
    th, td { border:1px solid #333; padding:6px 4px; text-align:left; }
    th { width:38%; background:#eee; }
</style>
<h3 class="head">Data Pemasangan Pelanggan</h3>
<table>
<tr><th>Nama</th><td>'.htmlspecialchars($row['nama']).'</td></tr>
<tr><th>User PPPoE</th><td>'.htmlspecialchars($row['userppp']).'</td></tr>
<tr><th>Password PPPoE</th><td>'.htmlspecialchars($row['passwordppp']).'</td></tr>
<tr><th>Paket</th><td>'.$paket_label.'</td></tr>
<tr><th>VLAN</th><td>'.htmlspecialchars($row['vlan']).'</td></tr>
<tr><th>POP</th><td>'.htmlspecialchars($row['pop']).'</td></tr>
<tr><th>ODP</th><td>'.htmlspecialchars($row['odp']).'</td></tr>
<tr><th>URL Maps</th><td>'.htmlspecialchars($row['url_maps']).'</td></tr>
<tr><th>Teknisi</th><td>'.htmlspecialchars($row['teknisi']).'</td></tr>
<tr><th>Alamat</th><td>'.htmlspecialchars($row['alamat']).'</td></tr>
<tr><th>KTP</th><td>'.htmlspecialchars($row['ktp']).'</td></tr>
<tr><th>Telp</th><td>'.htmlspecialchars($row['telp']).'</td></tr>
<tr><th>Email</th><td>'.htmlspecialchars($row['email']).'</td></tr>
<tr><th>Marketing</th><td>'.htmlspecialchars($row['marketing']).'</td></tr>
<tr><th>Tanggal</th><td>'.date('d-m-Y', strtotime($row['tanggal'])).'</td></tr>
<tr><th>Status</th><td>'.htmlspecialchars($row['status']).'</td></tr>
<tr><th>Modem</th><td>'.htmlspecialchars($row['modem']).'</td></tr>
<tr><th>Dropcore</th><td>'.htmlspecialchars($row['dropcore']).'</td></tr>
<tr><th>Last Updated By</th><td>'.htmlspecialchars($row['last_updated_by']).'</td></tr>
</table>
';

$dompdf = new Dompdf();
$dompdf->loadHtml($html);
$dompdf->setPaper('A4');
$dompdf->render();
$dompdf->stream('data_pelanggan_'.$row['nama'].'.pdf', ['Attachment'=>false]);
exit;
?>
