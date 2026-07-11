<?php
require_once 'routeros_api.class.php';

header('Content-Type: application/json');
$username = isset($_GET['username']) ? trim($_GET['username']) : '';

$API = new RouterosAPI();
if ($API->connect('103.68.214.126', '1234', 'abcd1234')) {
    $API->write('/ppp/active/print');
    $result = $API->read();
    $API->disconnect();

    $user_found = false;
    $data_found = [];
    $all_active = [];

    foreach ($result as $row) {
        if (isset($row['name'])) {
            $all_active[] = $row['name'];
            if ($username && $row['name'] === $username) {
                $user_found = true;
                $data_found = $row;
            }
        }
    }

    if ($username) {
        if ($user_found) {
            echo json_encode(['status'=>'online', 'data'=>$data_found]);
        } else {
            echo json_encode(['status'=>'offline', 'message'=>'User not connected', 'input'=>$username, 'all_active'=>$all_active]);
        }
    } else {
        // tampilkan semua user aktif
        echo json_encode(['all_active'=>$all_active, 'raw'=>$result]);
    }
} else {
    echo json_encode(['status'=>'error','message'=>'Gagal koneksi ke Mikrotik']);
}
