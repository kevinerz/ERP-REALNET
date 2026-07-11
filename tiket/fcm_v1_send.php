<?php
$serviceAccount = json_decode(file_get_contents(__DIR__ . "/myrealtek-cd329-75a5adc66f2e.json"), true);

$projectId      = $serviceAccount["project_id"];
$clientEmail    = $serviceAccount["client_email"];
$privateKey     = $serviceAccount["private_key"];
$tokenUri       = $serviceAccount["token_uri"];

function base64url_encode($data) {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

function sendFCM($deviceToken, $title, $body) {
    global $clientEmail, $privateKey, $tokenUri, $projectId;

    // HEADER
    $header = base64url_encode(json_encode([
        "alg" => "RS256",
        "typ" => "JWT"
    ]));

    // PAYLOAD
    $now = time();
    $payload = base64url_encode(json_encode([
        "iss"   => $clientEmail,
        "scope" => "https://www.googleapis.com/auth/firebase.messaging",
        "aud"   => $tokenUri,
        "iat"   => $now,
        "exp"   => $now + 3600
    ]));

    // SIGN JWT
    $data_to_sign = $header . "." . $payload;
    openssl_sign($data_to_sign, $signature, $privateKey, "SHA256");
    $jwt = $data_to_sign . "." . base64url_encode($signature);

    // GET ACCESS TOKEN GOOGLE
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $tokenUri);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
        "grant_type" => "urn:ietf:params:oauth:grant-type:jwt-bearer",
        "assertion"  => $jwt
    ]));

    $tokenResponse = json_decode(curl_exec($ch), true);
    curl_close($ch);

    if (!isset($tokenResponse["access_token"])) {
        return false;
    }

    $accessToken = $tokenResponse["access_token"];

    // SEND FCM
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

    $result = curl_exec($ch);
    curl_close($ch);

    return $result;
}
