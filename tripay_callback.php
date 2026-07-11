<?php
// =============== KONFIGURASI ===============
$privateKey = 'AG53I-w5uBC-Ijmak-RAeXV-Kuvpn'; // Ganti dengan Private Key Tripay kamu
// =================== KONFIGURASI ===================
$adminGroup = "120363162491513453@g.us"; // Grup WhatsApp Administrasi

function sendNotif($to, $msg) {
    $payload = json_encode([
        "messageType" => "text",
        "to"          => $to,
        "body"        => $msg,
        "delay"       => 3,
        "schedule"    => time() + 3
    ]);
    $ch = curl_init("https://api.starsender.online/api/send");
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_HTTPHEADER     => [
            "Content-Type: application/json",
            "Authorization: " . "7106aa0b-0eb0-4673-aaf6-470ccc1f2390" // <-- Ganti token API kamu
        ]
    ]);
    curl_exec($ch);
    curl_close($ch);
}

// ================ CALLBACK LOGIC ================
$json = file_get_contents('php://input');
$data = json_decode($json, true);

// Validasi signature Tripay
$callbackSignature = $_SERVER['HTTP_X_CALLBACK_SIGNATURE'] ?? '';
$ourSignature = hash_hmac('sha256', $json, $privateKey);
if ($callbackSignature !== $ourSignature) {
    http_response_code(403);
    exit('Invalid signature');
}

// Ambil data dari payload Tripay
$order_id       = $data['merchant_ref'] ?? '';
$reference      = $data['reference'] ?? '';
$payment_method = $data['payment_method'] ?? '';
$amount         = number_format($data['total_amount'] ?? 0, 0, ',', '.');
$fee_merchant   = number_format($data['fee_merchant'] ?? 0, 0, ',', '.');
$fee_customer   = number_format($data['fee_customer'] ?? 0, 0, ',', '.');
$total_fee      = number_format($data['total_fee'] ?? 0, 0, ',', '.');
$amount_received= number_format($data['amount_received'] ?? 0, 0, ',', '.');
$status         = $data['status'] ?? '';
$paid_at        = isset($data['paid_at']) ? date('d-m-Y H:i:s', $data['paid_at']) : '-';
$note           = $data['note'] ?? '-';

// Hanya notif jika status PAID
if (strtolower($status) == "paid") {
    $msg = "✅ *Pembayaran Berhasil (Tripay)*\n\n"
         . "• Invoice: *$order_id*\n"
         . "• Ref: $reference\n"
         . "• Metode: $payment_method\n"
         . "• Jumlah Bayar: Rp$amount\n"
         . "• Fee Merchant: Rp$fee_merchant\n"
         . "• Fee Customer: Rp$fee_customer\n"
         . "• Total Fee: Rp$total_fee\n"
         . "• Diterima Bersih: Rp$amount_received\n"
         . "• Waktu: $paid_at\n"
         . "• Note: $note\n"
         . "• Status: *LUNAS*";
    sendNotif($adminGroup, $msg);
}

// Balas OK biar Tripay tidak retry
echo json_encode(["success"=>true]);