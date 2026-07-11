<?php
$apiKey = '20Tc8Y8CUXT8BWNCnv8RYDnfQa98cC2mE9DfOgwo';
$curl = curl_init();
curl_setopt_array($curl, [
    CURLOPT_URL => 'https://tripay.co.id/api/merchant/payment-channel',
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => ['Authorization: Bearer '.$apiKey],
]);
$response = curl_exec($curl);
curl_close($curl);

print_r($response); // Lihat channel code yang tersedia
?>
