<?php
header("Content-Type: application/json");

// Ambil data dari request body
$input = json_decode(file_get_contents("php://input"), true);

// Periksa apakah data "body" dan "pop" diterima
if (!isset($input['body']) || empty($input['body']) || !isset($input['pop']) || empty($input['pop'])) {
    file_put_contents('log_kirim.txt', date('Y-m-d H:i:s') . " - ERROR: Data tidak lengkap\n", FILE_APPEND);
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Data tidak lengkap"]);
    exit;
}

// Menentukan nomor tujuan berdasarkan POP
$pop = strtolower(trim($input['pop']));
$nomor_tujuan = "";

switch ($pop) {
    case "rajeg":
        $nomor_tujuan = "6281293958590-1587210420@g.us";
        break;
    case "kemeri":
        $nomor_tujuan = "6287770366015-1628875457@g.us";
        break;
    case "cianjur":
        $nomor_tujuan = "120363399972363054@g.us";
        break;
    case "brebes":
        $nomor_tujuan = "120363297070607107@g.us";
        break;
        case "sengon":
        $nomor_tujuan = "120363399972363054@g.us";
        break;
        case "grinting":
        $nomor_tujuan = "120363399972363054@g.us";
        break;
    default:
        file_put_contents('log_kirim.txt', date('Y-m-d H:i:s') . " - ERROR: POP tidak valid\n", FILE_APPEND);
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "POP tidak valid"]);
        exit;
}

// Menentukan API Key berdasarkan POP (Brebes pakai API Key berbeda)
$api_key = ($pop === "brebes") ? "7c1552e6-b220-48b3-a948-ec26253386e7" : "e9c50247-3b8d-4cd8-924a-024a4d2b3124";

// Siapkan data pesan untuk API StarSender
$pesan = [
    "messageType" => "text",
    "to" => $nomor_tujuan,
    "body" => $input['body'],
    "delay" => 5,
    "schedule" => null
];

// Kirim permintaan ke API StarSender
$curl = curl_init();
curl_setopt_array($curl, [
    CURLOPT_URL => 'https://api.starsender.online/api/send',
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING => '',
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => 'POST',
    CURLOPT_POSTFIELDS => json_encode($pesan),
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'Authorization: ' . $api_key
    ],
]);

$response = curl_exec($curl);
$http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
curl_close($curl);

// Simpan log hasil pengiriman
file_put_contents('log_kirim.txt', date('Y-m-d H:i:s') . " - HTTP Code: $http_code - Response: $response\n", FILE_APPEND);

// Berikan respons ke index.php
echo $response;
?>
