<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

date_default_timezone_set('Asia/Jakarta');
require_once 'koneksi.php';
require_once __DIR__ . '/../fcm_v1_send.php';

// =====================================================
// AMBIL DATA POP DARI DATABASE PEMASANGAN
// =====================================================
$sql_pop = "SELECT DISTINCT $kolom_pop FROM $table_pop ORDER BY $kolom_pop ASC";
$result_pop = $conn_pop->query($sql_pop);
$pops = [];

if ($result_pop && $result_pop->num_rows > 0) {
    while ($row = $result_pop->fetch_assoc()) {
        $pops[] = htmlspecialchars($row[$kolom_pop], ENT_QUOTES);
    }
} else {
    $error_message = "Data POP tidak tersedia.";
}


// =====================================================
// FORM SUBMIT — INPUT TIKET
// =====================================================
if (isset($_POST['submit'])) {

    $nama     = htmlspecialchars($_POST['nama'], ENT_QUOTES);
    $alamat   = htmlspecialchars($_POST['alamat'], ENT_QUOTES);
    $whatsapp = htmlspecialchars($_POST['whatsapp'], ENT_QUOTES);
    $pop      = htmlspecialchars($_POST['pop'], ENT_QUOTES);
    $keluhan  = htmlspecialchars($_POST['keluhan'], ENT_QUOTES);
    $maps_url = htmlspecialchars($_POST['maps_url'], ENT_QUOTES);

    // VALIDASI NOMOR WA
    if (!preg_match("/^\+?0?\d{9,15}$/", $whatsapp)) {
        $error_message = "Nomor WhatsApp tidak valid!";
    }

    if (!isset($error_message)) {

        $tanggal_sekarang = date('Y-m-d H:i:s');

        // =====================================================
        // MAPPING POP → id_pop_penempatan (untuk teknisi)
        // =====================================================
        $popMap = [
            'rajeg'  => 1,
            'mauk'   => 2,
            'kemeri' => 3
        ];
        $idPopTeknisi = $popMap[strtolower($pop)] ?? 0;

        // =====================================================
        // TARGET GRUP WHATSAPP POP
        // =====================================================
        switch ($pop) {
            case "rajeg":  $nomor_tujuan = "120363424064802149@g.us"; break;
            case "kemeri": $nomor_tujuan = "120363423460663827@g.us"; break;
            case "muncung":   $nomor_tujuan = "120363424070641923@g.us"; break;
             case "kelapa":   $nomor_tujuan = "120363423157487069@g.us"; break;
            case "panggang": $nomor_tujuan = "120363422971129799@g.us"; break;
            case "mauk":   $nomor_tujuan = "120363419348224895@g.us"; break;
            default:       $nomor_tujuan = "";
        }

        // =====================================================
        // INSERT TIKET KE DATABASE TIKET (conn_utama)
        // =====================================================
        $stmt = $conn_utama->prepare("
            INSERT INTO tiket 
            (nama_pelanggan, alamat, whatsapp, pop, keluhan, maps_url, tanggal_dibuat)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param("sssssss",
            $nama, $alamat, $whatsapp,
            $pop, $keluhan, $maps_url, $tanggal_sekarang
        );

        if ($stmt->execute()) {

            // =====================================================
            // SEND WA — CUSTOMER
            // =====================================================
            sendNotification(
                $whatsapp,
                $keluhan,
                'customer',
                $nama,
                $alamat,
                '',
                $maps_url,
                '',
                $tanggal_sekarang
            );

            // =====================================================
            // SEND WA — GROUP TEKNISI POP
            // =====================================================
            sendNotification(
                $nomor_tujuan,
                $keluhan,
                'group',
                $nama,
                $alamat,
                $whatsapp,
                $maps_url,
                $pop,
                $tanggal_sekarang
            );

            // =====================================================
            // KIRIM FCM TEKNISI — BERDASARKAN id_pop_penempatan
            // =====================================================
            if ($idPopTeknisi > 0) {

                $sql_fcm = "
                    SELECT fcm_token, nama 
                    FROM karyawan
                    WHERE id_pop_penempatan = $idPopTeknisi
                      AND fcm_token IS NOT NULL
                      AND fcm_token != ''
                ";

                $res_fcm = $conn_umum->query($sql_fcm);

                if ($res_fcm && $res_fcm->num_rows > 0) {

                    while ($tk = $res_fcm->fetch_assoc()) {

                        $fcmToken = $tk['fcm_token'];

                        $title = "Gangguan Baru di POP " . strtoupper($pop);
                        $body  = "$nama | $alamat\nKeluhan: $keluhan";

                        $result_fcm = sendFCM($fcmToken, $title, $body);

                        file_put_contents("log_fcm.txt",
                            "To: $fcmToken\nPOP: $pop\nResult: $result_fcm\n\n",
                            FILE_APPEND
                        );
                    }
                }
            }

            $success_message = "Tiket berhasil dibuat!<br>Tanggal & Jam: " . $tanggal_sekarang;

        } else {
            $error_message = "Gagal menyimpan tiket: " . $stmt->error;
        }

        $stmt->close();
    }
}


// =====================================================
// CLOSE DATABASE CONNECTION
// =====================================================
$conn_pop->close();
$conn_utama->close();
$conn_umum->close();


// =====================================================
// FUNGSI SEND WHATSAPP (StarSender)
// =====================================================
function sendNotification(
    $recipient,
    $keluhan,
    $type,
    $nama,
    $alamat,
    $whatsapp = '',
    $maps_url = '',
    $pop = '',
    $tanggal_sekarang = ''
) {
    $customerIcon = "👋";
    $ticketIcon   = "🎫";
    $alertIcon    = "🚨";
    $mapIcon      = "🗺️";

    if ($type == 'customer') {
    $messageBody  = "👋 Halo *$nama*,\n\n";
    $messageBody .= "🎫 Tiket gangguan Anda telah kami terima.\n";
    $messageBody .= "🔍 Keluhan: *$keluhan*\n\n";
    $messageBody .= "Tim teknisi RealNet akan segera menindaklanjuti.\n";
    $messageBody .= "Terima kasih telah menghubungi RealNet.";
}

elseif ($type == 'group') {
    $messageBody  = "🎫 *TIKET GANGGUAN BARU*\n\n";
    $messageBody .= "👤 Nama: $nama\n";
    $messageBody .= "🏠 Alamat: $alamat\n";
    $messageBody .= "📱 WA: $whatsapp\n\n";
    $messageBody .= "🗺️ Maps: $maps_url\n";
    $messageBody .= "🌐 POP: $pop\n\n";
    $messageBody .= "🚨 Keluhan:\n$keluhan\n\n";
    $messageBody .= "⏱️ Waktu: $tanggal_sekarang\n";
    $messageBody .= "📌 Status: Belum Ditindaklanjuti";
}


    $payload = [
        "messageType" => "text",
        "to"          => $recipient,
        "body"        => $messageBody,
        "delay"       => 10,
        "schedule"    => (time() + 10) * 1000
    ];

    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL            => "https://api.starsender.online/api/send",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POSTFIELDS     => json_encode($payload),
        CURLOPT_HTTPHEADER     => [
            "Content-Type: application/json",
            "Authorization: e9c50247-3b8d-4cd8-924a-024a4d2b3124"
        ],
    ]);

    $response = curl_exec($curl);
    $err      = curl_error($curl);
    curl_close($curl);

    file_put_contents("log_wa.txt",
        "To: $recipient\nMessage: $messageBody\nResponse: $response\nError: $err\n\n",
        FILE_APPEND
    );

    // ================
    // LOG WhatsApp TXT
    // ================
    $logIsi  = "========================================\n";
    $logIsi .= "Waktu     : " . date('Y-m-d H:i:s') . "\n";
    $logIsi .= "Kepada    : $recipient\n";
    $logIsi .= "Tipe      : $type\n";
    $logIsi .= "Pesan     : $messageBody\n";
    $logIsi .= "Response  : $response\n";
    $logIsi .= "Error     : $err\n";
    $logIsi .= "========================================\n\n";

    file_put_contents("whatsapp.txt", $logIsi, FILE_APPEND);
}

?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>🛠️ Tiket Gangguan Realnet</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- Google Fonts (Poppins) -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@500;700&display=swap" rel="stylesheet">
    <!-- Icon -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
body {
    font-family: 'Poppins', Arial, sans-serif;
    background: linear-gradient(135deg,#feeecc 0%,#e3e5ff 100%);
    margin: 0;
    min-height: 100vh;
}
.ticket-container {
    max-width: 390px;
    margin: 28px auto 0 auto;
    background: #fff9fc;
    border-radius: 1.3em;
    box-shadow: 0 8px 24px 0 rgba(120,80,180,0.10), 0 1.5px 4px 0 rgba(255,140,80,0.08);
    padding: 1.1em 1.1em 1.4em 1.1em; /* padding kiri-kanan lebih lebar */
    border: 3px solid #ffd447;
    animation: popin 0.7s cubic-bezier(.33,1.61,.74,.91);
    box-sizing: border-box;
}
@keyframes popin {
    0% {transform: scale(0.92) translateY(40px); opacity:0;}
    100% {transform: scale(1) translateY(0); opacity:1;}
}
.title-fun {
    text-align: center;
    font-size: 1.7em;
    font-weight: 700;
    margin-bottom: 4px;
    color: #6a1b9a;
    letter-spacing: -1px;
}
.subtitle-fun {
    text-align: center;
    color: #0d47a1;
    font-size: 1.1em;
    margin-bottom: 16px;
}
.fun-form-group {
    margin-bottom: 1.1em;
}
label {
    display: block;
    font-size: 1em;
    margin-bottom: 5px;
    font-weight: 600;
    color: #222;
    letter-spacing: -0.2px;
}
.label-emoji {
    font-size: 1.15em;
    margin-right: 5px;
}
input[type="text"], input[type="tel"], textarea, select {
    width: 92%;
    margin: 0 auto 15px auto; /* bawah lebih gede */
    display: block;
    border: 2px solid #d1c4e9;
    border-radius: 0.8em;
    padding: 0.7em 1em;
    font-size: 1em;
    background: #fff;
    outline: none;
    transition: border .2s, box-shadow .2s;
    box-shadow: 0 1px 7px 0 #7e57c215;
    box-sizing: border-box;
}
input[type="text"]:focus, input[type="tel"]:focus, textarea:focus, select:focus {
    border: 2px solid #7e57c2;
}
textarea {
    resize: vertical;
    min-height: 52px;
}
select {
    background: linear-gradient(90deg,#fff,#ffe082 100%);
    color: #373737;
    font-weight: 600;
}
.btn-submit-fun {
    width: 100%;
    background: linear-gradient(92deg,#ffd447 35%,#43e97b 100%);
    color: #2d207a;
    border: none;
    border-radius: 1em;
    font-weight: 700;
    padding: 0.95em 0;
    font-size: 1.18em;
    box-shadow: 0 4px 18px 0 rgba(60,80,120,.09);
    cursor: pointer;
    margin-top: 3px;
    transition: transform .15s, box-shadow .2s;
}
.btn-submit-fun:hover {
    background: linear-gradient(96deg,#f44336 0%,#ffd447 65%,#43e97b 100%);
    color: #fff;
    transform: scale(1.03);
    box-shadow: 0 7px 30px 0 rgba(120,60,180,0.19);
}
.success, .error {
    text-align: center;
    font-size: 1.06em;
    border-radius: 0.9em;
    margin-bottom: 14px;
    padding: 9px 0;
}
.success {
    background: #e8fce8;
    color: #1b6b31;
    border: 2px solid #38b000;
    box-shadow: 0 2px 12px #38b00022;
}
.error {
    background: #fff0f1;
    color: #a6162d;
    border: 2px solid #f44336;
    box-shadow: 0 2px 12px #f443361b;
}
@media (max-width: 550px) {
    .ticket-container { max-width: 98vw; margin-top: 7vw; }
    .title-fun { font-size: 1.25em; }
}

    </style>
</head>
<body>
    <div class="ticket-container">
        <div class="title-fun">🛠️ Tiket Gangguan Realnet</div>
        <div class="subtitle-fun">Isi data dengan benar, tim kami siap membantu!</div>
        <?php if (isset($success_message)): ?>
            <div class="success animate__animated animate__fadeInDown"><?php echo $success_message; ?></div>
        <?php endif; ?>
        <?php if (isset($error_message)): ?>
            <div class="error animate__animated animate__shakeX"><?php echo $error_message; ?></div>
        <?php endif; ?>
        <form method="post" autocomplete="off">
            <div class="fun-form-group">
                <label for="nama"><span class="label-emoji">👤</span>Nama Lengkap</label>
                <input type="text" id="nama" name="nama" maxlength="40" required>
            </div>
            <div class="fun-form-group">
                <label for="alamat"><span class="label-emoji">🏠</span>Alamat Lengkap</label>
                <textarea id="alamat" name="alamat" rows="2" maxlength="140" required></textarea>
            </div>
            <div class="fun-form-group">
                <label for="whatsapp"><span class="label-emoji">📱</span>Nomor WhatsApp</label>
                <input type="tel" id="whatsapp" name="whatsapp" maxlength="16" pattern="^\+?\d{7,15}$" placeholder="+628123456789" required>
            </div>
            <div class="fun-form-group">
                <label for="pop"><span class="label-emoji">🌐</span>Pilih POP/Area</label>
                <select id="pop" name="pop" required>
                    <option value="">- Pilih Area -</option>
                    <?php foreach ($pops as $pop_item): ?>
                        <option value="<?php echo htmlspecialchars($pop_item); ?>">
                            <?php echo htmlspecialchars(ucwords($pop_item)); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="fun-form-group">
                <label for="keluhan"><span class="label-emoji">💬</span>Keluhan Gangguan</label>
                <textarea id="keluhan" name="keluhan" rows="3" maxlength="140" required></textarea>
            </div>
            <div class="fun-form-group">
                <label for="maps_url"><span class="label-emoji">🗺️</span>URL Google Maps <span style="color:#a35ef7;font-weight:400;">(Opsional)</span></label>
                <input type="text" id="maps_url" name="maps_url" maxlength="160" placeholder="https://goo.gl/maps/xxx">
            </div>
            <button class="btn-submit-fun" type="submit" name="submit">
                <i class="bi bi-send-fill"></i> Kirim Tiket
            </button>
        </form>
    </div>
</body>
<script>
document.addEventListener("DOMContentLoaded", function () {

    // Jika ada pesan sukses, kosongkan form
    const successBox = document.querySelector(".success");
    if (successBox) {
        const form = document.querySelector("form");
        form.reset();
    }

});
</script>

</html>

