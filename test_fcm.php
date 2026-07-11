<?php
require_once __DIR__ . '/fcm_v1_send.php';

// Masukkan 1 token FCM teknisi dari database karyawan
$testToken = "cQoXQifxTKmMTisxhYt0v9:APA91bGsYBKJMTPd9QO9TjEZJ7SuBW5LjKHniYrHIvJzKmyCoJY6rDclWfa9fYJIX7_K9io8NAfEKQ_lpqlcS-w6UkCh0L-H2OEgiFkT4JKdPaxkuFpOFHM";

$title = "🔥 Test FCM Berhasil";
$body  = "Ini pesan test dari server RealNet.";

$result = sendFCM($testToken, $title, $body);

echo "<pre>";
echo "Hasil Test FCM:\n\n";
var_dump($result);
echo "</pre>";
