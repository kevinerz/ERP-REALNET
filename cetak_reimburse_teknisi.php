<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once 'libraries/dompdf/autoload.inc.php';
use Dompdf\Dompdf;

if (!isset($_GET['id'])) {
    die('ID pemasangan tidak ditemukan.');
}
$id = intval($_GET['id']);

// Koneksi database pemasangan
$host_pemasangan = "localhost";
$user_pemasangan = "u272457353_kevinsamsung9";
$pass_pemasangan = "Admionkevin99";
$db_pemasangan = "u272457353_db_pemasangan";
$conn = new mysqli($host_pemasangan, $user_pemasangan, $pass_pemasangan, $db_pemasangan);
if ($conn->connect_error) die("Koneksi gagal: " . $conn->connect_error);

// Ambil data pemasangan
$stmt = $conn->prepare("SELECT * FROM pemasangan WHERE id=?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) die('Data pemasangan tidak ditemukan.');
$row = $result->fetch_assoc();

if (strtolower($row['status']) != 'selesai') die('Pemasangan belum selesai, tidak dapat mengajukan rembes.');

// Data field
$nama_teknisi   = $row['teknisi'];
$nama_pelanggan = $row['nama'];
$userppp        = $row['userppp'];
$passwordppp    = $row['passwordppp'];
$ktp            = $row['ktp'];
$alamat         = $row['alamat'];
$pop            = $row['pop'];
$modem          = $row['modem'];
$telepon        = $row['telp']; // <-- ini bagian penting
$tanggal        = date('d-m-Y', strtotime($row['tanggal']));

// Nominal tetap
$nominal_teknisi = 100000;

// Koneksi ke DB umumdata untuk info paket
$host_umum = "localhost";
$user_umum = "u272457353_kevinsamsung99";
$pass_umum = "Admionkevin99";
$db_umum   = "u272457353_umumdata";
$conn_umum = new mysqli($host_umum, $user_umum, $pass_umum, $db_umum);

$paket_label = $row['paket'];
if (!$conn_umum->connect_error) {
    $paket_id = intval($row['paket']);
    $rp = $conn_umum->query("SELECT * FROM paket WHERE id_paket='$paket_id' LIMIT 1");
    if ($paket = $rp->fetch_assoc()) {
        $paket_label = htmlspecialchars($paket['nama_paket']) . ' (' . htmlspecialchars($paket['kecepatan']) . ') - Rp ' . number_format($paket['harga'],0,',','.');
    }
}

// Mapping Serial Number Modem
$serial_number_modem = '-';
if (!$conn_umum->connect_error) {
    $modem_id = intval($row['modem']);
    $modem_query = $conn_umum->query("SELECT serial_number FROM modem WHERE id_modem='$modem_id' LIMIT 1");
    if ($modem_data = $modem_query->fetch_assoc()) {
        $serial_number_modem = htmlspecialchars($modem_data['serial_number']);
    }
}

// HTML untuk PDF
$html = '
<style>
    body { font-family: sans-serif; font-size:12px; }
    .kop-container { border-bottom: 4px solid #ef233c; padding-bottom: 10px; margin-bottom: 18px; }
    .kop-title { flex:1; }
    .kop-nama { font-size: 22px; font-weight: 900; color: #ef233c; letter-spacing:2px; }
    .kop-alamat { font-size:12px; color:#222; font-weight: 500; }
    .judul { text-align:center; font-size:18px; font-weight:bold; margin-bottom:12px;}
    .tabel { border-collapse:collapse; width:90%; margin:24px auto;}
    th, td { border:1px solid #333; padding:8px 4px; text-align:left; }
    th { background: #fbeee0; width:40%; }
    .ttd { margin-top:60px; text-align:right; margin-right: 60px; }
</style>

<div class="kop-container">
    <div class="kop-title">
      <div class="kop-nama">PT. REAL DATA SOLUSINDO</div>
      <div class="kop-alamat">
        Jalan Kartini Gang Cempaka, Desa/Kelurahan Sengon, Kec. Tanjung, Kab. Brebes, Provinsi Jawa Tengah, Kode Pos: 52254<br>
        Telp. (021) 222-333, Email: info@realdatasolution.co.id
      </div>
    </div>
</div>

<div class="judul">Formulir Reimburse Pemasangan</div>
<table class="tabel">
    <tr><th>Nama Teknisi</th><td>'.htmlspecialchars($nama_teknisi).'</td></tr>
    <tr><th>Nama Pelanggan</th><td>'.htmlspecialchars($nama_pelanggan).'</td></tr>
    <tr><th>Tanggal Pemasangan</th><td>'.$tanggal.'</td></tr>
    <tr><th>KTP</th><td>'.htmlspecialchars($ktp).'</td></tr>
    <tr><th>Telp</th><td>'.htmlspecialchars($telepon).'</td></tr>
    <tr><th>WhatsApp</th><td><a href="https://wa.me/'.preg_replace('/[^0-9]/', '', $telepon).'" target="_blank">https://wa.me/'.preg_replace('/[^0-9]/', '', $telepon).'</a></td></tr>
    <tr><th>User PPPoE</th><td>'.htmlspecialchars($userppp).'</td></tr>
    <tr><th>Password PPPoE</th><td>'.htmlspecialchars($passwordppp).'</td></tr>
    <tr><th>Modem (Serial Number)</th><td>'.$serial_number_modem.'</td></tr>
    <tr><th>Paket</th><td>'.$paket_label.'</td></tr>
    <tr><th>Alamat</th><td>'.htmlspecialchars($alamat).'</td></tr>
    <tr><th>POP</th><td>'.htmlspecialchars($pop).'</td></tr>
    <tr><th>Status</th><td>Selesai</td></tr>
    <tr><th>Fee Teknisi</th><td>Rp '.number_format($nominal_teknisi,0,',','.').',-</td></tr>
    <tr><th>Total</th><td>Rp '.number_format($nominal_teknisi,0,',','.').',-</td></tr>
</table>
<div class="ttd">
    Tangerang, '.$tanggal.'<br>
    <br><br><br><br>
    <u>'.htmlspecialchars($nama_teknisi).'</u><br>
    Teknisi Lapangan
</div>
';

$dompdf = new Dompdf();
$dompdf->loadHtml($html);
$dompdf->setPaper('A4');
$dompdf->render();
$dompdf->stream('reimburse_teknisi_'.$row['nama'].'.pdf', ['Attachment'=>false]);
exit;
?>
