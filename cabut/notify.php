<?php
// notify.php
// Helper kirim notifikasi WhatsApp ke GRUP via StarSender

if (!defined('STARSENDER_API_KEY')) {
  // Simpan API Key di .env / config sebenarnya. Untuk contoh ini kita pakai yang Anda kirim.
  define('STARSENDER_API_KEY', 'e9c50247-3b8d-4cd8-924a-024a4d2b3124');
}

if (!defined('STARSENDER_ENDPOINT')) {
  define('STARSENDER_ENDPOINT', 'https://api.starsender.online/api/send');
}

/**
 * Map POP ke Group ID WhatsApp
 */
function getGroupIdForPop(string $pop): ?string {
  $key = strtolower(trim($pop));
  $groups = [
    'rajeg'  => '6281293958590-1587210420@g.us',
    'kemeri' => '6287770366015-1628875457@g.us',
    'mauk'   => '120363419348224895@g.us',
  ];
  return $groups[$key] ?? null;
}

/**
 * Kirim pesan ke grup (text only)
 * @return array [success(bool), http_code(int|null), response(string|null), error(string|null)]
 */
function sendWaGroupMessage(string $groupId, string $message): array {
  $payload = [
    'messageType' => 'text',
    'to'          => $groupId,
    'body'        => $message,
  ];

  $ch = curl_init(STARSENDER_ENDPOINT);
  curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => json_encode($payload),
    CURLOPT_HTTPHEADER     => [
      'Content-Type: application/json',
      'Authorization: ' . STARSENDER_API_KEY,
    ],
    CURLOPT_TIMEOUT        => 20,
  ]);

  $resp = curl_exec($ch);
  $errno = curl_errno($ch);
  $error = $errno ? curl_error($ch) : null;
  $http  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
  curl_close($ch);

  $ok = !$errno && $http >= 200 && $http < 300;
  return [
    'success'   => $ok,
    'http_code' => $http ?: null,
    'response'  => $resp ?: null,
    'error'     => $error,
  ];
}

/**
 * Template pesan buat tiket baru
 */
function formatNewTicketMessage(array $t): string {
  // Hindari karakter WA formatting tak diinginkan
  $safe = fn($v) => trim((string)$v);

  $lines = [
    "*[TIKET CABUT MODEM]*",
    "POP: *" . $safe($t['pop'] ?? '-') . "*",
    "Nama: " . $safe($t['nama'] ?? '-'),
    "Alamat: " . $safe($t['alamat'] ?? '-'),
    "WA: " . $safe($t['wa'] ?? '-'),
    "Alasan: " . $safe($t['alasan'] ?? '-'),
    "SN: " . $safe($t['sn_modem'] ?? '-'),
    "Status: *" . $safe($t['status'] ?? '-') . "*",
    "Waktu: " . ($t['created_at'] ?? date('Y-m-d H:i:s')),
  ];
  return implode("\n", $lines);
}

/**
 * Template pesan saat status berubah
 */
function formatStatusChangeMessage(array $t, string $oldStatus, string $newStatus): string {
  $safe = fn($v) => trim((string)$v);

  $lines = [
    "*[UPDATE STATUS TIKET CABUT]*",
    "POP: *" . $safe($t['pop'] ?? '-') . "*",
    "Nama: " . $safe($t['nama'] ?? '-'),
    "SN: " . $safe($t['sn_modem'] ?? '-'),
    "Status: *$oldStatus -> $newStatus*",
    "Waktu: " . date('Y-m-d H:i:s'),
  ];
  return implode("\n", $lines);
}
