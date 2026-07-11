<?php

// Konfigurasi
$genieacs_host = '103.211.27.22:7557';
$genieacs_username = 'admin';
$genieacs_password = 'admin';

// Fungsi untuk mendapatkan daftar seluruh device
function get_all_devices() {
    global $genieacs_host, $genieacs_username, $genieacs_password;
    $url = 'http://' . $genieacs_host . '/devices/';
    $auth_header = 'Authorization: Basic ' . base64_encode($genieacs_username . ':' . $genieacs_password);

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array($auth_header, 'Content-Type: application/json'));

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

// Fungsi untuk mengambil SSID dan password
function get_wifi_info($device_id) {
    global $genieacs_host, $genieacs_username, $genieacs_password;

    $encoded_device_id = urlencode($device_id);
    $url = "http://" . $genieacs_host . "/devices/" . $encoded_device_id . "/parameters";

    $auth_header = 'Authorization: Basic ' . base64_encode($genieacs_username . ':' . $genieacs_password);
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array($auth_header, 'Content-Type: application/json'));

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

    $decoded_response = json_decode($response, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        return ['error' => 'JSON Decode Error: ' . json_last_error_msg()];
    }

    // Menyiapkan data SSID dan password untuk SSID 1 dan SSID 5
    $ssid1 = isset($decoded_response['InternetGatewayDevice']['LANDevice'][1]['WLANConfiguration'][1]['SSID']) ? $decoded_response['InternetGatewayDevice']['LANDevice'][1]['WLANConfiguration'][1]['SSID'] : 'N/A';
    $ssid5 = isset($decoded_response['InternetGatewayDevice']['LANDevice'][1]['WLANConfiguration'][5]['SSID']) ? $decoded_response['InternetGatewayDevice']['LANDevice'][1]['WLANConfiguration'][5]['SSID'] : 'N/A';

    $password1 = isset($decoded_response['InternetGatewayDevice']['LANDevice'][1]['WLANConfiguration'][1]['PreSharedKey'][1]['KeyPassphrase']) ? $decoded_response['InternetGatewayDevice']['LANDevice'][1]['WLANConfiguration'][1]['PreSharedKey'][1]['KeyPassphrase'] : 'N/A';
    $password5 = isset($decoded_response['InternetGatewayDevice']['LANDevice'][1]['WLANConfiguration'][5]['PreSharedKey'][1]['KeyPassphrase']) ? $decoded_response['InternetGatewayDevice']['LANDevice'][1]['WLANConfiguration'][5]['PreSharedKey'][1]['KeyPassphrase'] : 'N/A';

    return [
        'ssid1' => $ssid1,
        'ssid5' => $ssid5,
        'password1' => $password1,
        'password5' => $password5
    ];
}

// Mendapatkan list semua device.
$all_devices = get_all_devices();

if (isset($all_devices['error'])) {
    echo "Error get all devices: " . $all_devices['error'] . "<br>";
    if (isset($all_devices['response'])) {
        echo "Response Error: " . $all_devices['response'] . "<br>";
    }
} else {
    echo "<table border='1'>";
    echo "<tr><th>Device ID</th><th>Hardware Version</th><th>Provisioning Code</th><th>WLAN SSID 1</th><th>WLAN Password 1</th><th>WLAN SSID 5</th><th>WLAN Password 5</th><th>Actions</th></tr>";

    foreach ($all_devices as $device) {
        $deviceId = $device['_id'];
        $hardwareVersion = isset($device['InternetGatewayDevice']['DeviceInfo']['HardwareVersion']['_value']) ? $device['InternetGatewayDevice']['DeviceInfo']['HardwareVersion']['_value'] : 'N/A';
        $provisioningCode = isset($device['InternetGatewayDevice']['DeviceInfo']['ProvisioningCode']['_value']) ? $device['InternetGatewayDevice']['DeviceInfo']['ProvisioningCode']['_value'] : 'N/A';

        // Mendapatkan SSID dan password untuk SSID 1 dan 5
        $wifi_info = get_wifi_info($deviceId);
        $ssid1 = $wifi_info['ssid1'];
        $password1 = $wifi_info['password1'];
        $ssid5 = $wifi_info['ssid5'];
        $password5 = $wifi_info['password5'];

        echo "<tr>";
        echo "<td>" . $deviceId . "</td>";
        echo "<td>" . $hardwareVersion . "</td>";
        echo "<td>" . $provisioningCode . "</td>";
        echo "<td>" . $ssid1 . "</td>";
        echo "<td>" . $password1 . "</td>";
        echo "<td>" . $ssid5 . "</td>";
        echo "<td>" . $password5 . "</td>";
        echo "<td><a href='?action=delete&device_id=" . $deviceId . "' onclick=\"return confirm('Apakah Anda yakin ingin menghapus perangkat ini?')\">Delete</a> | 
              <a href='?action=reboot&device_id=" . $deviceId . "' onclick=\"return confirm('Apakah Anda yakin ingin melakukan reboot perangkat ini?')\">Reboot</a> |
              <a href='wifi.php?device_id=" . $deviceId . "'>Change SSID/Password</a></td>";
        echo "</tr>";
    }

    echo "</table><br>";
}

// Menangani permintaan penghapusan
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['device_id'])) {
    $device_id_to_delete = $_GET['device_id'];
    $delete_result = delete_device($device_id_to_delete);

    if (isset($delete_result['error'])) {
        echo "Error delete device: " . $delete_result['error'] . "<br>";
        if (isset($delete_result['response'])) {
            echo "Response Error: " . $delete_result['response'] . "<br>";
        }
    } else {
        echo "Device " . $device_id_to_delete . " berhasil dihapus.<br>";
        echo "<script>window.location.href = window.location.pathname;</script>";
    }
}

// Menangani permintaan reboot
if (isset($_GET['action']) && $_GET['action'] == 'reboot' && isset($_GET['device_id'])) {
    $device_id_to_reboot = $_GET['device_id'];
    $reboot_result = reboot_device_curl($device_id_to_reboot);

    if (isset($reboot_result['error'])) {
        echo "Error reboot device: " . $reboot_result['error'] . "<br>";
        if (isset($reboot_result['response'])) {
            echo "Response Error: " . $reboot_result['response'] . "<br>";
        }
    } else {
        echo "Perangkat " . $device_id_to_reboot . " berhasil di-reboot.<br>";
        echo "<script>window.location.href = window.location.pathname;</script>";
    }
}
?>
