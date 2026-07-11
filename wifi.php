<?php

// Konfigurasi
$genieacs_host = '103.211.27.22:7557';
$genieacs_username = 'admin';
$genieacs_password = 'admin';

// Fungsi untuk mengganti SSID dan Password
function change_wifi($device_id, $ssid_index, $new_ssid, $new_password) {
    global $genieacs_host, $genieacs_username, $genieacs_password;

    $encoded_device_id = urlencode($device_id);
    $url = "http://" . $genieacs_host . "/devices/" . $encoded_device_id . "/tasks";

    $data = [
        "name" => "setParameterValues",
        "parameterValues" => [
            ["InternetGatewayDevice.LANDevice.1.WLANConfiguration." . $ssid_index . ".SSID", $new_ssid, "xsd:string"],
            ["InternetGatewayDevice.LANDevice.1.WLANConfiguration." . $ssid_index . ".PreSharedKey.1.KeyPassphrase", $new_password, "xsd:string"]
        ]
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json',
        'Authorization: Basic ' . base64_encode($genieacs_username . ':' . $genieacs_password)
    ));

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if ($response === false) {
        $error = curl_error($ch);
        curl_close($ch);
        return ['error' => 'cURL Error: ' . $error];
    }

    curl_close($ch);

    if ($http_code >= 400) {
        return ['error' => 'HTTP Error: ' . $http_code, 'response' => $response];
    }

    return json_decode($response, true);
}

// Mendapatkan device_id dari URL
$device_id = isset($_GET['device_id']) ? $_GET['device_id'] : '';

// Menangani penggantian SSID dan Password untuk WLAN 1-6
if (isset($_POST['submit_wifi']) && isset($_POST['ssid_index']) && isset($_POST['new_ssid']) && isset($_POST['new_password'])) {
    $ssid_index = $_POST['ssid_index'];
    $new_ssid = $_POST['new_ssid'];
    $new_password = $_POST['new_password'];

    $result = change_wifi($device_id, $ssid_index, $new_ssid, $new_password);

    if (isset($result['error'])) {
        echo "Error change SSID and password for WLAN " . $ssid_index . ": " . $result['error'] . "<br>";
    } else {
        echo "SSID and password for WLAN " . $ssid_index . " successfully updated.<br>";
    }
}
?>

<h3>Change SSID and Password for WLAN Configurations (1-6)</h3>

<form method="POST">
    <label for="ssid_index">WLAN Configuration:</label>
    <select id="ssid_index" name="ssid_index">
        <option value="1">WLAN SSID 1</option>
        <option value="2">WLAN SSID 2</option>
        <option value="3">WLAN SSID 3</option>
        <option value="4">WLAN SSID 4</option>
        <option value="5">WLAN SSID 5</option>
        <option value="6">WLAN SSID 6</option>
    </select><br><br>

    <label for="new_ssid">New SSID:</label>
    <input type="text" id="new_ssid" name="new_ssid" required><br><br>

    <label for="new_password">New Password:</label>
    <input type="password" id="new_password" name="new_password" required><br><br>

    <input type="hidden" name="device_id" value="<?php echo $device_id; ?>">
    <input type="submit" name="submit_wifi" value="Change SSID and Password">
</form>
