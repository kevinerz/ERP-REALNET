<?php

// ======================================================
//  LOAD SERVICE ACCOUNT JSON
// ======================================================
$jsonPath = __DIR__ . "/myrealtek-cd329-75a5adc66f2e.json";

if (!file_exists($jsonPath)) {
    file_put_contents("log_fcm_error.txt", "JSON FILE NOT FOUND\n", FILE_APPEND);
    die("JSON_NOT_FOUND");
}

$serviceAccount = json_decode(file_get_contents($jsonPath), true);

if (!$serviceAccount) {
    file_put_contents("log_fcm_error.txt", "JSON DECODE FAILED\n", FILE_APPEND);
    die("JSON_DECODE_ERROR");
}

$projectId   = $serviceAccount["project_id"];
$clientEmail = $serviceAccount["client_email"];
$privateKey  = $serviceAccount["private_key"];   
$tokenUri    = $serviceAccount["token_uri"];


// ======================================================
//  BASE64 URL SAFE ENCODER
// ======================================================
function base64url_encode($data) {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}


// ======================================================
//  FUNGSI UTAMA KIRIM FCM V1
// ======================================================
function sendFCM($deviceToken, $title, $body) {
    global $clientEmail, $privateKey, $tokenUri, $projectId;

    // =========== 1. JWT HEADER ===========
    $header = base64url_encode(json_encode([
        "alg" => "RS256",
        "typ" => "JWT"
    ]));

    // =========== 2. JWT PAYLOAD ===========
    $now = time();
    $payload = base64url_encode(json_encode([
        "iss"   => $clientEmail,
        "scope" => "https://www.googleapis.com/auth/firebase.messaging",
        "aud"   => $tokenUri,
        "iat"   => $now,
        "exp"   => $now + 3600
    ]));

    // =========== 3. SIGN JWT ===========
    $dataToSign = $header . "." . $payload;
    $signature = "";

    $signSuccess = openssl_sign($dataToSign, $signature, $privateKey, OPENSSL_ALGO_SHA256);

    if (!$signSuccess) {
        file_put_contents("log_fcm_error.txt", "OPENSSL SIGN FAILED\n", FILE_APPEND);
        return false;
    }

    $jwt = $dataToSign . "." . base64url_encode($signature);


    // =========== 4. REQUEST ACCESS TOKEN GOOGLE ===========
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $tokenUri);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
        "grant_type" => "urn:ietf:params:oauth:grant-type:jwt-bearer",
        "assertion"  => $jwt
    ]));

    $result = curl_exec($ch);
    $tokenResponse = json_decode($result, true);
    curl_close($ch);

    if (!isset($tokenResponse["access_token"])) {
        file_put_contents("log_fcm_error.txt", "TOKEN ERROR: $result\n", FILE_APPEND);
        return false;
    }

    $accessToken = $tokenResponse["access_token"];


    // =========== 5. KIRIM FCM NOTIFICATION ===========
    $url = "https://fcm.googleapis.com/v1/projects/$projectId/messages:send";

    $message = [
        "message" => [
            "token" => $deviceToken,
            "notification" => [
                "title" => $title,
                "body"  => $body
            ],
            "android" => [
                "priority" => "high"
            ]
        ]
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer $accessToken",
        "Content-Type: application/json"
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($message));

    $response = curl_exec($ch);
    $curlError = curl_error($ch);
    curl_close($ch);


    // =========== 6. LOGGING ===========
    if ($curlError) {
        file_put_contents("log_fcm_error.txt", "SEND ERROR: $curlError\n", FILE_APPEND);
    } else {
        file_put_contents("log_fcm.txt",
            "To: $deviceToken\nResult: $response\n\n",
            FILE_APPEND
        );
    }

    return $response;
}
