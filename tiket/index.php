<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

date_default_timezone_set('Asia/Jakarta');
require_once 'koneksi.php';
require_once __DIR__ . '/../fcm_v1_send.php';

$conn_customer = new mysqli('localhost', 'u272457353_kevinsamsungda', 'Admionkevin99', 'u272457353_dapel');
if ($conn_customer->connect_error) die("Koneksi customer gagal: " . $conn_customer->connect_error);

$sql_customers = "SELECT fullname, phonenumber, address FROM tbl_customers ORDER BY fullname ASC";
$result_customers = $conn_customer->query($sql_customers);
$customers = [];
if ($result_customers && $result_customers->num_rows > 0) {
    while ($row = $result_customers->fetch_assoc()) {
        $customers[] = [
            'fullname'    => htmlspecialchars($row['fullname'], ENT_QUOTES),
            'phonenumber' => htmlspecialchars($row['phonenumber'], ENT_QUOTES),
            'address'     => htmlspecialchars($row['address'], ENT_QUOTES)
        ];
    }
}

$sql_pop    = "SELECT DISTINCT $kolom_pop FROM $table_pop ORDER BY $kolom_pop ASC";
$result_pop = $conn_pop->query($sql_pop);
$pops = [];
if ($result_pop && $result_pop->num_rows > 0) {
    while ($row = $result_pop->fetch_assoc())
        $pops[] = htmlspecialchars($row[$kolom_pop], ENT_QUOTES);
} else {
    $error_message = "Data POP tidak tersedia.";
}

if (isset($_POST['submit'])) {
    $nama     = htmlspecialchars($_POST['nama'], ENT_QUOTES);
    $alamat   = htmlspecialchars($_POST['alamat'], ENT_QUOTES);
    $whatsapp = htmlspecialchars($_POST['whatsapp'], ENT_QUOTES);
    $pop      = htmlspecialchars($_POST['pop'], ENT_QUOTES);
    $keluhan  = htmlspecialchars($_POST['keluhan'], ENT_QUOTES);
    $maps_url = htmlspecialchars($_POST['maps_url'], ENT_QUOTES);

    if (!preg_match("/^\+?0?\d{9,15}$/", $whatsapp))
        $error_message = "Nomor WhatsApp tidak valid!";

    if (!isset($error_message)) {
        $tanggal_sekarang = date('Y-m-d H:i:s');
        $popMap = ['rajeg' => 1, 'mauk' => 2, 'kemeri' => 3];
        $idPopTeknisi = $popMap[strtolower($pop)] ?? 0;
        switch ($pop) {
            case "rajeg":    $nomor_tujuan = "120363424064802149@g.us"; break;
            case "kemeri":   $nomor_tujuan = "120363423460663827@g.us"; break;
            case "muncung":  $nomor_tujuan = "120363424070641923@g.us"; break;
            case "kelapa":   $nomor_tujuan = "120363423157487069@g.us"; break;
            case "panggang": $nomor_tujuan = "120363422971129799@g.us"; break;
            case "badakanom": $nomor_tujuan = "120363409600702809@g.us"; break;
            case "mauk":     $nomor_tujuan = "120363405820721170@g.us"; break;
            default:         $nomor_tujuan = "";
        }
        $stmt = $conn_utama->prepare("INSERT INTO tiket_gangguan (nama_pelanggan, alamat, whatsapp, pop, keluhan, maps_url, tanggal_dibuat) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssss", $nama, $alamat, $whatsapp, $pop, $keluhan, $maps_url, $tanggal_sekarang);
        if ($stmt->execute()) {
            sendNotificationCustomer($whatsapp, $keluhan, 'customer', $nama, $alamat, '', $maps_url, '', $tanggal_sekarang);
            sendNotificationGroup($nomor_tujuan, $keluhan, 'group', $nama, $alamat, $whatsapp, $maps_url, $pop, $tanggal_sekarang);
            if ($idPopTeknisi > 0) {
                $res_fcm = $conn_umum->query("SELECT fcm_token, nama FROM hr_karyawan WHERE id_pop_penempatan = $idPopTeknisi AND fcm_token IS NOT NULL AND fcm_token != ''");
                if ($res_fcm && $res_fcm->num_rows > 0) {
                    while ($tk = $res_fcm->fetch_assoc()) {
                        $result_fcm = sendFCM($tk['fcm_token'], "Gangguan Baru di POP " . strtoupper($pop), "$nama | $alamat\nKeluhan: $keluhan");
                        file_put_contents("log_fcm.txt", "To: {$tk['fcm_token']}\nPOP: $pop\nResult: $result_fcm\n\n", FILE_APPEND);
                    }
                }
            }
            $success_message = "Tiket berhasil dibuat! &nbsp;&bull;&nbsp; " . date('d M Y, H:i', strtotime($tanggal_sekarang));
        } else {
            $error_message = "Gagal menyimpan tiket: " . $stmt->error;
        }
        $stmt->close();
    }
}

$conn_pop->close(); $conn_utama->close(); $conn_umum->close(); $conn_customer->close();

function sendNotificationCustomer($recipient,$keluhan,$type,$nama,$alamat,$whatsapp='',$maps_url='',$pop='',$tanggal_sekarang='') {
    $msg = "👋 Halo *$nama*,\n\n🎫 Tiket gangguan Anda telah kami terima.\n🔍 Keluhan: *$keluhan*\n\nTim teknisi RealNet akan segera menindaklanjuti.\nTerima kasih telah menghubungi RealNet.";
    $curl = curl_init();
    curl_setopt_array($curl,[CURLOPT_URL=>"https://api.starsender.online/api/send",CURLOPT_RETURNTRANSFER=>true,CURLOPT_POSTFIELDS=>json_encode(["messageType"=>"text","to"=>$recipient,"body"=>$msg,"delay"=>10,"schedule"=>(time()+10)*1000]),CURLOPT_HTTPHEADER=>["Content-Type: application/json","Authorization: 7106aa0b-0eb0-4673-aaf6-470ccc1f2390"]]);
    $response=curl_exec($curl);$err=curl_error($curl);curl_close($curl);
    file_put_contents("whatsapp.txt","[".date('Y-m-d H:i:s')."] CUSTOMER to $recipient\nResp: $response\n\n",FILE_APPEND);
}
function sendNotificationGroup($recipient,$keluhan,$type,$nama,$alamat,$whatsapp='',$maps_url='',$pop='',$tanggal_sekarang='') {
    $msg = "🎫 *TIKET GANGGUAN BARU*\n\n👤 Nama: $nama\n🏠 Alamat: $alamat\n📱 WA: $whatsapp\n\n🗺️ Maps: $maps_url\n🌐 POP: $pop\n\n🚨 Keluhan:\n$keluhan\n\n⏱️ Waktu: $tanggal_sekarang\n📌 Status: Belum Ditindaklanjuti";
    $curl = curl_init();
    curl_setopt_array($curl,[CURLOPT_URL=>"https://api.starsender.online/api/send",CURLOPT_RETURNTRANSFER=>true,CURLOPT_POSTFIELDS=>json_encode(["messageType"=>"text","to"=>$recipient,"body"=>$msg,"delay"=>10,"schedule"=>(time()+10)*1000]),CURLOPT_HTTPHEADER=>["Content-Type: application/json","Authorization: e9c50247-3b8d-4cd8-924a-024a4d2b3124"]]);
    $response=curl_exec($curl);$err=curl_error($curl);curl_close($curl);
    file_put_contents("whatsapp.txt","[".date('Y-m-d H:i:s')."] GROUP to $recipient\nResp: $response\n\n",FILE_APPEND);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
<title>Tiket Gangguan — RealNet</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{
  --white:#ffffff;
  --gray-50:#f8fafc;
  --gray-100:#f1f5f9;
  --gray-200:#e2e8f0;
  --gray-300:#cbd5e1;
  --gray-400:#94a3b8;
  --gray-500:#64748b;
  --gray-700:#334155;
  --gray-900:#0f172a;
  --blue:#2563eb;
  --blue-lt:#eff6ff;
  --blue-mid:#dbeafe;
  --blue-dk:#1d4ed8;
  --green:#16a34a;
  --green-lt:#f0fdf4;
  --red:#dc2626;
  --red-lt:#fef2f2;
  --border:#e2e8f0;
  --r:8px;--rl:12px;
  --sh-sm:0 1px 3px rgba(15,23,42,.08),0 1px 2px rgba(15,23,42,.05);
  --sh:0 4px 16px rgba(15,23,42,.12),0 1px 4px rgba(15,23,42,.06);
  --font:'Plus Jakarta Sans',system-ui,sans-serif;
}
html{font-size:15px;-webkit-text-size-adjust:100%}
body{font-family:var(--font);background:var(--gray-50);color:var(--gray-900);min-height:100vh;padding-bottom:48px}

/* TOPBAR */
.topbar{
  background:var(--white);border-bottom:1px solid var(--border);
  padding:0 20px;height:56px;
  display:flex;align-items:center;gap:12px;
  position:sticky;top:0;z-index:200;box-shadow:var(--sh-sm);
}
.topbar-ico{
  width:32px;height:32px;background:var(--blue);border-radius:8px;
  display:flex;align-items:center;justify-content:center;flex-shrink:0;
}
.topbar-ico svg{width:17px;height:17px;fill:#fff}
.tb-brand{font-size:.9rem;font-weight:700;color:var(--gray-900);line-height:1.1}
.tb-sub{font-size:.7rem;color:var(--gray-500)}
.tb-pill{
  margin-left:auto;font-size:.68rem;font-weight:600;
  padding:3px 10px;border-radius:20px;
  background:var(--green-lt);color:var(--green);
  border:1px solid #bbf7d0;
  display:flex;align-items:center;gap:5px;white-space:nowrap;
}
.dot{width:6px;height:6px;border-radius:50%;background:var(--green);animation:blink 2s infinite}
@keyframes blink{0%,100%{opacity:1}50%{opacity:.3}}

/* PAGE */
.page{max-width:640px;margin:0 auto;padding:22px 16px}

/* ALERT */
.alert{
  display:flex;align-items:flex-start;gap:10px;
  padding:12px 16px;border-radius:var(--r);
  font-size:.875rem;font-weight:500;
  margin-bottom:16px;border:1px solid transparent;
  animation:drop .3s ease;
}
@keyframes drop{from{opacity:0;transform:translateY(-8px)}to{opacity:1;transform:none}}
.alert-ok{background:var(--green-lt);color:#15803d;border-color:#bbf7d0}
.alert-err{background:var(--red-lt);color:#b91c1c;border-color:#fecaca}
.alert svg{width:16px;height:16px;fill:currentColor;flex-shrink:0;margin-top:1px}

/* STEP CARD */
.card{
  background:var(--white);border:1px solid var(--border);
  border-radius:var(--rl);box-shadow:var(--sh-sm);
  margin-bottom:10px;
  /* PENTING: overflow visible agar dropdown tidak terpotong */
  overflow:visible;
}
.card-head{
  display:flex;align-items:center;gap:9px;
  padding:12px 18px;border-bottom:1px solid var(--border);
  background:var(--gray-50);
  border-radius:var(--rl) var(--rl) 0 0;
}
.c-num{
  width:22px;height:22px;background:var(--blue);color:#fff;
  border-radius:6px;font-size:.68rem;font-weight:700;
  display:flex;align-items:center;justify-content:center;flex-shrink:0;
}
.c-ttl{font-size:.72rem;font-weight:700;color:var(--gray-500);text-transform:uppercase;letter-spacing:.07em}
.card-body{padding:16px 18px}

/* LABEL */
label.lbl{
  display:flex;align-items:center;gap:5px;
  font-size:.72rem;font-weight:700;
  color:var(--gray-500);text-transform:uppercase;letter-spacing:.07em;
  margin-bottom:6px;
}
label.lbl svg{width:12px;height:12px;fill:currentColor;opacity:.7}
.opt{font-size:.63rem;padding:1px 6px;background:var(--gray-100);border:1px solid var(--gray-200);border-radius:4px;color:var(--gray-400);text-transform:none;letter-spacing:0;font-weight:500}

/* FIELDS */
.inp,.txta,.sel{
  width:100%;padding:10px 12px;
  border:1.5px solid var(--gray-200);border-radius:var(--r);
  font-family:var(--font);font-size:.875rem;
  color:var(--gray-900);background:var(--white);
  outline:none;transition:border-color .15s,box-shadow .15s;
  appearance:none;-webkit-appearance:none;
}
.inp::placeholder,.txta::placeholder{color:var(--gray-300)}
.inp:focus,.txta:focus,.sel:focus{border-color:var(--blue);box-shadow:0 0 0 3px rgba(37,99,235,.12)}
.inp[readonly],.txta[readonly]{background:var(--gray-50);color:var(--gray-400);cursor:not-allowed;border-color:var(--gray-200)}
.txta{resize:vertical;min-height:64px;line-height:1.6}
.sel-w{position:relative}
.sel-w::after{content:'';position:absolute;right:12px;top:50%;transform:translateY(-50%);border:5px solid transparent;border-top-color:var(--gray-400);border-bottom:none;pointer-events:none}
.sel{padding-right:32px;cursor:pointer}
.fld{margin-bottom:13px}
.fld:last-child{margin-bottom:0}
.g2{display:grid;grid-template-columns:1fr 1fr;gap:12px}
@media(max-width:480px){.g2{grid-template-columns:1fr}}

/* SEARCH */
.sw{position:relative}
.sw .si{position:absolute;left:12px;top:50%;transform:translateY(-50%);width:14px;height:14px;fill:var(--gray-400);pointer-events:none;transition:fill .15s;z-index:1}
.sw:focus-within .si{fill:var(--blue)}
#sc{
  width:100%;padding:10px 36px 10px 36px;
  border:1.5px solid var(--gray-200);border-radius:var(--r);
  font-family:var(--font);font-size:.875rem;color:var(--gray-900);
  background:var(--white);outline:none;transition:border-color .15s,box-shadow .15s;
}
#sc::placeholder{color:var(--gray-300)}
#sc:focus{border-color:var(--blue);box-shadow:0 0 0 3px rgba(37,99,235,.12)}
#bc{
  position:absolute;right:9px;top:50%;transform:translateY(-50%);
  width:20px;height:20px;border-radius:50%;
  background:var(--gray-200);border:none;
  color:var(--gray-600);font-size:13px;line-height:1;
  cursor:pointer;display:none;align-items:center;justify-content:center;
  transition:background .15s,color .15s;z-index:1;
}
#bc:hover{background:var(--red);color:#fff}
#bc.show{display:flex}

/* DROPDOWN — fixed, keluar dari overflow apapun */
#dl{
  position:fixed;
  background:var(--white);
  border:1.5px solid var(--blue);
  border-top:none;
  border-radius:0 0 var(--r) var(--r);
  box-shadow:var(--sh);
  max-height:250px;overflow-y:auto;
  z-index:9999;display:none;
}
#dl.open{display:block}
.ci{
  display:flex;align-items:center;gap:10px;
  padding:9px 13px;cursor:pointer;
  border-bottom:1px solid var(--gray-100);
  transition:background .1s;
}
.ci:last-child{border-bottom:none}
.ci:hover{background:var(--blue-lt)}
.ci-av{
  width:32px;height:32px;border-radius:7px;
  background:var(--blue-mid);border:1px solid #bfdbfe;
  color:var(--blue);font-size:.75rem;font-weight:700;
  display:flex;align-items:center;justify-content:center;flex-shrink:0;
}
.ci-n{font-size:.85rem;font-weight:600;color:var(--gray-900);line-height:1.3}
.ci-p{font-size:.73rem;color:var(--gray-500);margin-top:1px}
.ci-0{padding:16px;text-align:center;color:var(--gray-400);font-size:.82rem}
#dl::-webkit-scrollbar{width:4px}
#dl::-webkit-scrollbar-track{background:var(--gray-100)}
#dl::-webkit-scrollbar-thumb{background:var(--gray-300);border-radius:4px}

/* CHIP */
.chip{
  display:none;align-items:center;gap:10px;
  margin-top:9px;padding:9px 12px;
  background:var(--blue-lt);border:1px solid #bfdbfe;
  border-radius:var(--r);animation:fi .2s ease;
}
.chip.show{display:flex}
@keyframes fi{from{opacity:0}to{opacity:1}}
.ch-av{
  width:34px;height:34px;border-radius:7px;
  background:var(--blue);color:#fff;
  font-size:.78rem;font-weight:700;
  display:flex;align-items:center;justify-content:center;flex-shrink:0;
}
.ch-n{font-size:.875rem;font-weight:600;color:var(--gray-900)}
.ch-d{font-size:.73rem;color:var(--gray-500);margin-top:1px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.ch-i{flex:1;min-width:0}
.ch-x{background:none;border:none;cursor:pointer;color:var(--gray-400);font-size:1.15rem;padding:2px 4px;line-height:1;border-radius:4px;transition:color .15s,background .15s}
.ch-x:hover{color:var(--red);background:var(--red-lt)}

/* SUBMIT */
.btn{
  width:100%;padding:13px;
  background:var(--blue);color:#fff;
  font-family:var(--font);font-size:.93rem;font-weight:700;
  border:none;border-radius:var(--r);cursor:pointer;
  display:flex;align-items:center;justify-content:center;gap:8px;
  transition:background .15s,transform .1s,box-shadow .15s;
}
.btn svg{width:16px;height:16px;fill:#fff;flex-shrink:0}
.btn:hover{background:var(--blue-dk);box-shadow:0 4px 14px rgba(37,99,235,.35);transform:translateY(-1px)}
.btn:active{transform:none;box-shadow:none}

.foot{text-align:center;font-size:.68rem;color:var(--gray-400);margin-top:16px}
</style>
</head>
<body>

<header class="topbar">
  <div class="topbar-ico">
    <svg viewBox="0 0 24 24"><path d="M12 2a10 10 0 1 0 0 20A10 10 0 0 0 12 2zm1 14.93V15a1 1 0 0 0-2 0v1.93A8.001 8.001 0 0 1 4.07 11H6a1 1 0 0 0 0-2H4.07A8.001 8.001 0 0 1 11 4.07V6a1 1 0 0 0 2 0V4.07A8.001 8.001 0 0 1 19.93 11H18a1 1 0 0 0 0 2h1.93A8.001 8.001 0 0 1 13 16.93z"/></svg>
  </div>
  <div>
    <div class="tb-brand">RealNet Helpdesk</div>
    <div class="tb-sub">Sistem Tiket Gangguan</div>
  </div>
  <div class="tb-pill"><span class="dot"></span>ONLINE</div>
</header>

<div class="page">

<?php if (isset($success_message)): ?>
<div class="alert alert-ok">
  <svg viewBox="0 0 24 24"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41L9 16.17z"/></svg>
  <span><?= $success_message ?></span>
</div>
<?php endif; ?>
<?php if (isset($error_message)): ?>
<div class="alert alert-err">
  <svg viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/></svg>
  <span><?= $error_message ?></span>
</div>
<?php endif; ?>

<form method="post" autocomplete="off" id="mf">

  <!-- 1. CARI CUSTOMER -->
  <div class="card">
    <div class="card-head"><div class="c-num">1</div><div class="c-ttl">Pilih Customer</div></div>
    <div class="card-body">
      <div class="fld">
        <label class="lbl" for="sc">
          <svg viewBox="0 0 24 24"><path d="M15.5 14h-.79l-.28-.27A6.47 6.47 0 0 0 16 9.5 6.5 6.5 0 1 0 9.5 16a6.47 6.47 0 0 0 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/></svg>
          Cari Nama atau Nomor
        </label>
        <div class="sw" id="sw">
          <svg class="si" viewBox="0 0 24 24"><path d="M15.5 14h-.79l-.28-.27A6.47 6.47 0 0 0 16 9.5 6.5 6.5 0 1 0 9.5 16a6.47 6.47 0 0 0 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/></svg>
          <input type="text" id="sc" placeholder="Ketik nama atau nomor telepon…" autocomplete="off">
          <button type="button" id="bc">×</button>
        </div>
        <div class="chip" id="chip">
          <div class="ch-av" id="chav"></div>
          <div class="ch-i">
            <div class="ch-n" id="chn"></div>
            <div class="ch-d" id="chd"></div>
          </div>
          <button type="button" class="ch-x" id="chx">×</button>
        </div>
      </div>
    </div>
  </div>

  <!-- 2. DATA PELANGGAN -->
  <div class="card">
    <div class="card-head"><div class="c-num">2</div><div class="c-ttl">Data Pelanggan</div></div>
    <div class="card-body">
      <div class="fld">
        <label class="lbl" for="nama">
          <svg viewBox="0 0 24 24"><path d="M12 12c2.7 0 4.8-2.1 4.8-4.8S14.7 2.4 12 2.4 7.2 4.5 7.2 7.2 9.3 12 12 12zm0 2.4c-3.2 0-9.6 1.6-9.6 4.8v2.4h19.2v-2.4c0-3.2-6.4-4.8-9.6-4.8z"/></svg>
          Nama Lengkap
        </label>
        <input type="text" class="inp" id="nama" name="nama" maxlength="40" required readonly>
      </div>
      <div class="g2">
        <div class="fld" style="margin-bottom:0">
          <label class="lbl" for="wa">
            <svg viewBox="0 0 24 24"><path d="M6.62 10.79a15.05 15.05 0 0 0 6.59 6.59l2.2-2.2c.27-.27.67-.36 1.02-.24 1.12.37 2.33.57 3.57.57.55 0 1 .45 1 1V20c0 .55-.45 1-1 1C10.28 21 3 13.72 3 4.5c0-.55.45-1 1-1H7.5c.55 0 1 .45 1 1 0 1.25.2 2.45.57 3.57.11.35.03.74-.24 1.02l-2.21 2.2z"/></svg>
            Nomor WhatsApp
          </label>
          <input type="tel" class="inp" id="wa" name="whatsapp" maxlength="16" placeholder="+628xxx" required readonly>
        </div>
        <div class="fld" style="margin-bottom:0">
          <label class="lbl" for="pop">
            <svg viewBox="0 0 24 24"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5S10.62 6.5 12 6.5s2.5 1.12 2.5 2.5S13.38 11.5 12 11.5z"/></svg>
            POP / Area
          </label>
          <div class="sel-w">
            <select class="sel" id="pop" name="pop" required>
              <option value="">— Pilih —</option>
              <?php foreach ($pops as $p): ?>
              <option value="<?= htmlspecialchars($p) ?>"><?= htmlspecialchars(ucwords($p)) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
      </div>
      <div class="fld" style="margin-top:13px">
        <label class="lbl" for="alamat">
          <svg viewBox="0 0 24 24"><path d="M10 20v-6h4v6h5v-8h3L12 3 2 12h3v8z"/></svg>
          Alamat Lengkap
        </label>
        <textarea class="txta" id="alamat" name="alamat" rows="2" maxlength="140" required readonly></textarea>
      </div>
    </div>
  </div>

  <!-- 3. KELUHAN -->
  <div class="card">
    <div class="card-head"><div class="c-num">3</div><div class="c-ttl">Detail Gangguan</div></div>
    <div class="card-body">
      <div class="fld">
        <label class="lbl" for="keluhan">
          <svg viewBox="0 0 24 24"><path d="M20 2H4c-1.1 0-2 .9-2 2v18l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2z"/></svg>
          Deskripsi Keluhan
        </label>
        <textarea class="txta" id="keluhan" name="keluhan" rows="3" maxlength="140" placeholder="Contoh: Internet tidak bisa connect, lampu ONU merah…" required></textarea>
      </div>
      <div class="fld">
        <label class="lbl" for="maps_url">
          <svg viewBox="0 0 24 24"><path d="M20.5 3l-.16.03L15 5.1 9 3 3.36 4.9c-.21.07-.36.25-.36.48V20.5c0 .28.22.5.5.5l.16-.03L9 18.9l6 2.1 5.64-1.9c.21-.07.36-.25.36-.48V3.5c0-.28-.22-.5-.5-.5zM15 19l-6-2.11V5l6 2.11V19z"/></svg>
          URL Google Maps
          <span class="opt">Opsional</span>
        </label>
        <input type="text" class="inp" id="maps_url" name="maps_url" maxlength="160" placeholder="https://goo.gl/maps/…">
      </div>
    </div>
  </div>

  <button class="btn" type="submit" name="submit">
    <svg viewBox="0 0 24 24"><path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/></svg>
    Kirim Tiket Sekarang
  </button>
</form>

<div class="foot">RealNet ISP &nbsp;·&nbsp; Helpdesk System</div>
</div>

<!-- Dropdown di luar semua container, position:fixed via JS -->
<div id="dl"></div>

<script>
(function(){
  var SC  = document.getElementById('sc');
  var SW  = document.getElementById('sw');
  var DL  = document.getElementById('dl');
  var BC  = document.getElementById('bc');
  var CHIP= document.getElementById('chip');
  var CHAV= document.getElementById('chav');
  var CHN = document.getElementById('chn');
  var CHD = document.getElementById('chd');
  var CHX = document.getElementById('chx');
  var N   = document.getElementById('nama');
  var WA  = document.getElementById('wa');
  var AL  = document.getElementById('alamat');

  var DATA = <?php echo json_encode($customers); ?>;

  function pos(){
    var r = SC.getBoundingClientRect();
    DL.style.left  = r.left+'px';
    DL.style.top   = r.bottom+'px';
    DL.style.width = r.width+'px';
  }

  function render(arr){
    if(!arr.length){ DL.innerHTML='<div class="ci-0">Customer tidak ditemukan</div>'; return; }
    DL.innerHTML = arr.map(function(c){
      var av = c.fullname.trim().slice(0,2).toUpperCase();
      return '<div class="ci" data-fn="'+c.fullname+'" data-ph="'+c.phonenumber+'" data-ad="'+c.address+'">'
        +'<div class="ci-av">'+av+'</div>'
        +'<div><div class="ci-n">'+c.fullname+'</div>'
        +'<div class="ci-p">'+c.phonenumber+'</div></div></div>';
    }).join('');
    DL.querySelectorAll('.ci').forEach(function(el){
      el.addEventListener('mousedown', function(e){
        e.preventDefault(); // jangan blur dulu
        pick(this.dataset.fn, this.dataset.ph, this.dataset.ad);
      });
    });
  }

  function open(){  pos(); DL.classList.add('open'); }
  function close(){ DL.classList.remove('open'); }

  SC.addEventListener('input', function(){
    var q = this.value.toLowerCase().trim();
    BC.classList.toggle('show', q.length>0);
    if(!q){ close(); return; }
    var f = DATA.filter(function(c){
      return c.fullname.toLowerCase().includes(q)||c.phonenumber.includes(q);
    });
    render(f); open();
  });

  SC.addEventListener('focus', function(){
    if(this.value.trim().length>0){ render(DATA.filter(function(c){
      var q=SC.value.toLowerCase().trim();
      return c.fullname.toLowerCase().includes(q)||c.phonenumber.includes(q);
    })); open(); }
  });

  SC.addEventListener('blur', function(){
    setTimeout(close, 150);
  });

  window.addEventListener('scroll', function(){ if(DL.classList.contains('open')) pos(); }, true);
  window.addEventListener('resize', function(){ if(DL.classList.contains('open')) pos(); });

  BC.addEventListener('click', function(){
    SC.value=''; this.classList.remove('show'); close(); SC.focus();
  });

  function pick(fn, ph, ad){
    N.value=fn; WA.value=ph; AL.value=ad; unlock();
    CHAV.textContent=fn.trim().slice(0,2).toUpperCase();
    CHN.textContent=fn;
    CHD.textContent=ph+' · '+ad;
    CHIP.classList.add('show');
    SC.value=''; BC.classList.remove('show'); close();
    document.getElementById('pop').focus();
  }

  CHX.addEventListener('click', function(){
    CHIP.classList.remove('show');
    N.value=WA.value=AL.value=''; lock();
    SC.value=''; BC.classList.remove('show'); SC.focus();
  });

  function lock(){[N,WA,AL].forEach(function(e){e.setAttribute('readonly','');})}
  function unlock(){[N,WA,AL].forEach(function(e){e.removeAttribute('readonly');})}

  if(document.querySelector('.alert-ok')){
    document.getElementById('mf').reset();
    CHIP.classList.remove('show'); lock();
  }
})();
</script>
</body>
</html>