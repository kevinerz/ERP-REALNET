<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once 'libraries/dompdf/autoload.inc.php';
use Dompdf\Dompdf;

$nama_pelanggan = isset($_GET['pelanggan']) ? $_GET['pelanggan'] : 'NAMA PELANGGAN';

$html = '
<style>
    body { font-family: sans-serif; font-size:13px; }
    .judul { text-align: center; font-size: 19px; font-weight: bold; margin-bottom: 10px;}
    .subjudul { text-align: center; margin-bottom: 20px;}
    table { border-collapse: collapse; width: 70%; margin: 20px auto 30px auto;}
    th, td { border: 1px solid #333; padding: 8px 4px; }
    th { background: #eee; }
</style>
<div class="judul">SURAT PERSETUJUAN AKTIVASI PELANGGAN BARU</div>
<div class="subjudul">
    Dengan ini kami menyatakan bahwa proses pembuatan data pelanggan baru:<br>
    <b>Nama Pelanggan:</b> '.htmlspecialchars($nama_pelanggan).'<br>
    <b>Bagian Create Pelanggan:</b> SOLIHIN
</div>

<table>
  <tr>
    <th>Tahapan</th>
    <th>Nama Penanggung Jawab</th>
    <th>Status</th>
  </tr>
  <tr>
    <td>QC SPV Teknis</td>
    <td>Kevin, Aby, Aria, Sujono</td>
    <td>Disetujui</td>
  </tr>
  <tr>
    <td>Approve Manager</td>
    <td>Murdat Saepudin</td>
    <td>Disetujui</td>
  </tr>
  <tr>
    <td>Data SPV Admin</td>
    <td>SITI ROBIATUL ADAWIYAH</td>
    <td>Disetujui</td>
  </tr>
</table>

<br><br>
<table style="width:90%;margin:auto;">
<tr>
    <td style="text-align:center;">
        <br><br>
        <b>QC SPV Teknis</b><br><br><br>
        Kevin / Aby / Aria / Sujono
    </td>
    <td style="text-align:center;">
        <br><br>
        <b>Manager</b><br><br><br>
        Murdat Saepudin
    </td>
    <td style="text-align:center;">
        <br><br>
        <b>SPV Admin</b><br><br><br>
        SITI ROBIATUL ADAWIYAH
    </td>
</tr>
</table>
';

$dompdf = new Dompdf();
$dompdf->loadHtml($html);
$dompdf->setPaper('A4');
$dompdf->render();
$dompdf->stream('persetujuan_aktivasi_'.str_replace(' ','_',$nama_pelanggan).'.pdf', ['Attachment'=>false]);
exit;
?>
