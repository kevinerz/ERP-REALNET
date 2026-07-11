<?php
// Dimigrasikan: database utama (tiket_helpdesk) dan POP (db_pemasangan)
// sudah digabung jadi satu database `erprealnet`.

require_once __DIR__ . '/../config/database.php';

// Koneksi ke database utama (tempat tabel tiket berada)
$conn_utama = getErpDbConnection();

// Koneksi ke database POP (tempat tabel POP berada)
$table_pop = 'jaringan_pop';
$kolom_pop = 'name';
$conn_pop  = getErpDbConnection();
