<?php
require_once 'db.php';

function ambilModem($id_modem, $id_karyawan) {
    global $conn;

    $q1 = $conn->prepare("
        UPDATE jaringan_modem 
        SET status='Diambil', tanggal_keluar=NOW(), id_karyawan_keluar=? 
        WHERE id_modem=?
    ");
    $q1->bind_param("ii", $id_karyawan, $id_modem);
    $q1->execute();

    $q2 = $conn->prepare("
        INSERT INTO jaringan_modem_log(id_modem,id_karyawan,aksi,waktu) 
        VALUES(?, ?, 'AMBIL', NOW())
    ");
    $q2->bind_param("ii", $id_modem, $id_karyawan);
    $q2->execute();
}

function kembalikanModem($id_modem, $id_karyawan, $lokasi) {
    global $conn;

    $q1 = $conn->prepare("
        UPDATE jaringan_modem SET 
            status='Tersedia',
            lokasi_penyimpanan=?, 
            id_karyawan_keluar=NULL, 
            tanggal_keluar=NULL
        WHERE id_modem=?
    ");
    $q1->bind_param("si", $lokasi, $id_modem);
    $q1->execute();

    $q2 = $conn->prepare("
        INSERT INTO jaringan_modem_log(id_modem,id_karyawan,aksi,waktu,lokasi_tujuan) 
        VALUES(?, ?, 'KEMBALIKAN', NOW(), ?)
    ");
    $q2->bind_param("iis", $id_modem, $id_karyawan, $lokasi);
    $q2->execute();
}
?>
