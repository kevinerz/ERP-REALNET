<?php
date_default_timezone_set('Asia/Jakarta');
require_once 'koneksi.php';

// Ambil data POP dari database POP
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

if (isset($_POST['submit'])) {
    $nama     = htmlspecialchars($_POST['nama'], ENT_QUOTES);
    $alamat   = htmlspecialchars($_POST['alamat'], ENT_QUOTES);
    $whatsapp = htmlspecialchars($_POST['whatsapp'], ENT_QUOTES);
    $pop      = htmlspecialchars($_POST['pop'], ENT_QUOTES);
    $keluhan  = htmlspecialchars($_POST['keluhan'], ENT_QUOTES);
    $maps_url = htmlspecialchars($_POST['maps_url'], ENT_QUOTES);

    // VALIDASI NOMOR WHATSAPP (lebih fleksibel)
    if (!preg_match("/^\+?0?\d{9,15}$/", $whatsapp)) {
        $error_message = "Nomor WhatsApp tidak valid!";
    }

    if (!isset($error_message)) {
        $tanggal_sekarang = date('Y-m-d H:i:s');

        switch ($pop) {
            case "rajeg":    $nomor_tujuan = "6281293958590-1587210420@g.us"; break;
            case "kemeri":   $nomor_tujuan = "6287770366015-1628875457@g.us"; break;
            case "cianjur":  $nomor_tujuan = "120363399972363054@g.us"; break;
            case "brebes":   $nomor_tujuan = "120363297070607107@g.us"; break;
            case "sengon":   $nomor_tujuan = "120363366069803212@g.us"; break;
            case "grinting": $nomor_tujuan = "120363399972363054@g.us"; break;
            case "mauk":     $nomor_tujuan = "120363419348224895@g.us"; break;
            default:         $nomor_tujuan = "";
        }

        $stmt = $conn_utama->prepare("
            INSERT INTO tiket 
            (nama_pelanggan, alamat, whatsapp, pop, keluhan, maps_url, tanggal_dibuat)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param("sssssss", $nama, $alamat, $whatsapp, $pop, $keluhan, $maps_url, $tanggal_sekarang);

        if ($stmt->execute()) {
            $success_message = "Tiket berhasil dibuat!<br>Tanggal dan Jam WIB: " . $tanggal_sekarang;

            // === PERBAIKAN PANGGILAN FUNGSI NOTIFIKASI ===

            // Notif ke customer
            sendNotification(
                $whatsapp,         // to
                $keluhan,          // keluhan
                'customer',        // type
                $nama,             // nama
                $alamat,           // alamat
                '',                // whatsapp (untuk customer tidak perlu)
                $maps_url,         // maps
                '',                // pop
                $tanggal_sekarang  // timestamp
            );

            // Notif ke group
            sendNotification(
                $nomor_tujuan,     // to
                $keluhan,
                'group',
                $nama,
                $alamat,
                $whatsapp,         // whatsapp pelanggan
                $maps_url,
                $pop,
                $tanggal_sekarang
            );
        } else {
            $error_message = "Error: " . $stmt->error;
        }

        $stmt->close();
    }
}

$conn_pop->close();
$conn_utama->close();


// =========================
// FUNGSI KIRIM NOTIF WA
// =========================
function sendNotification($recipient, $keluhan, $type, $nama, $alamat, $whatsapp = '', $maps_url = '', $pop = '', $tanggal_sekarang = '')
{
    $customerIcon = "👋";
    $checkIcon    = "❌";
    $alertIcon    = "🚨";
    $mapIcon      = "🗺️";
    $ticketIcon   = "🎫";

    if ($type == 'customer') {
        $messageBody  = "$customerIcon Pelanggan Yth, $nama,\n\n";
        $messageBody .= "$ticketIcon Tiket gangguan Anda telah kami terima.\n";
        $messageBody .= "$alertIcon Keluhan: $keluhan\n";
        $messageBody .= "Mohon ditunggu untuk ditindaklanjuti. $checkIcon\n";
        $messageBody .= "Terima kasih,\nGRASI NET";
    }

    elseif ($type == 'group') {
        $messageBody  = "$ticketIcon Tiket Gangguan Baru:\n\n";
        $messageBody .= "Nama: $nama\nAlamat: $alamat\nWhatsApp: $whatsapp\n";
        $messageBody .= "$mapIcon Maps URL: $maps_url\nPOP: $pop\n";
        $messageBody .= "$alertIcon Keluhan: $keluhan\n";
        $messageBody .= "Tanggal Dibuat: $tanggal_sekarang\n";
        $messageBody .= "Status: Belum Ditindaklanjuti $checkIcon";
    }

    $pesan = [
        "messageType" => "text",
        "to"          => $recipient,
        "body"        => $messageBody,
        "delay" => 10,
        "schedule" => (time() + 10) * 1000   // format milisecond (13 digit)

    ];

    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL            => 'https://api.starsender.online/api/send',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POSTFIELDS     => json_encode($pesan),
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'Authorization: e9c50247-3b8d-4cd8-924a-024a4d2b3124'
        ],
    ]);

    $response = curl_exec($curl);
    $err      = curl_error($curl);
    curl_close($curl);

    // Log file WA (untuk debug)
    file_put_contents("log_wa.txt", 
        "To: $recipient\nResponse: $response\nError: $err\n\n",
        FILE_APPEND
    );
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
</html>
