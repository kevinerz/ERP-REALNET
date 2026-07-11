<?php
// config.php - Konfigurasi Aplikasi RealNet (sinkron dengan daftar.php)
//
// DIMIGRASIKAN: DB_*_PEMASANGAN dan DB_*_UMUMDATA dulu menunjuk ke 2 database
// terpisah (db_pemasangan & umumdata). Sekarang keduanya sudah digabung jadi
// satu database `erprealnet`, jadi kedua set konstanta ini sengaja dibuat
// SAMA NILAINYA supaya kode lama yang masih pakai
// `new mysqli(DB_HOST, DB_USER_PEMASANGAN, ...)` dkk tetap jalan tanpa
// perlu diubah satu per satu.
//
// DB_*_MARKETING TIDAK diubah -- itu database `u272457353_market` yang
// terpisah (dipakai folder market/), belum ikut dikonsolidasi karena belum
// ada skema/dump-nya.

declare(strict_types=1);

require_once __DIR__ . '/config/database.php';

// --- Error Reporting (disarankan: ON saat development, OFF saat produksi) ---
ini_set('display_errors', '0'); // ubah ke '1' jika sedang debug
error_reporting(E_ALL);

// --- Zona Waktu ---
date_default_timezone_set('Asia/Jakarta');

// --- URL Dasar Aplikasi ---
define('BASE_APP_URL', 'https://datarealsolution.net');

// =============================
// KONFIGURASI DATABASE (erprealnet, dipetakan dari config/database.php)
// =============================
// DB_HOST sudah didefinisikan oleh config/database.php

define('DB_USER_PEMASANGAN', DB_USER);
define('DB_PASS_PEMASANGAN', DB_PASS);
define('DB_NAME_PEMASANGAN', DB_NAME);

define('DB_USER_UMUMDATA', DB_USER);
define('DB_PASS_UMUMDATA', DB_PASS);
define('DB_NAME_UMUMDATA', DB_NAME);

// DB: marketing (database u272457353_market -- BELUM dikonsolidasi, dibiarkan apa adanya)
define('DB_USER_MARKETING', 'u272457353_kevinsamsungku');
define('DB_PASS_MARKETING', 'Admionkevin99');
define('DB_NAME_MARKETING', 'u272457353_market');

// =============================
// WHATSAPP API (Starsender)
// daftar.php membutuhkan 2 token:
// - WA_API_TOKEN_CUSTOMER: untuk customer & marketing (individual)
// - WA_API_TOKEN_GROUP: untuk group
// =============================
define('WA_API_URL', 'https://api.starsender.online/api/send');

// Token customer/individual (customer + marketing)
define('WA_API_TOKEN_CUSTOMER', env('WA_API_TOKEN_CUSTOMER', 'e9c50247-3b8d-4cd8-924a-024a4d2b3124'));

// Token group
define('WA_API_TOKEN_GROUP', env('WA_API_TOKEN_GROUP', 'e9c50247-3b8d-4cd8-924a-024a4d2b3124'));

// (opsional) kompatibilitas jika file lama masih pakai konstanta ini
define('WA_API_TOKEN', WA_API_TOKEN_GROUP);
define('WA_API_CS', WA_API_TOKEN_CUSTOMER);

// =============================
// GOOGLE MAPS
// =============================
define('GOOGLE_MAPS_API_KEY', env('GOOGLE_MAPS_API_KEY', 'AIzaSyDH4s_S0mOhLisPV_3e3SRXai11dZwA7dY'));

// =============================
// TRIPAY (jika dipakai modul lain)
// =============================
define('TRIPAY_API_KEY', env('TRIPAY_API_KEY', '20Tc8Y8CUXT8BWNCnv8RYDnfQa98cC2mE9DfOgwo'));
define('TRIPAY_PRIVATE_KEY', env('TRIPAY_PRIVATE_KEY', 'AG53I-w5uBC-Ijmak-RAeXV-Kuvpn'));
define('TRIPAY_MERCHANT_CODE', env('TRIPAY_MERCHANT_CODE', 'T41755'));
define('TRIPAY_BASE_URL', 'https://tripay.co.id/api');

// =============================
// URL Shortener (jika dipakai modul lain)
// =============================
define('SHORTENER_BASE_URL', 'https://api.tinyurl.com');
define('SHORTENER_API_KEY', env('SHORTENER_API_KEY', 'QTUkIeoehJ53UXcI6YFtzyaL8iq8Cn9CNB7XlmYCP1PnnVxryb24uiKE25CQ'));

// =============================
// ID Paket Prorata (jika dipakai modul lain)
// =============================
define('PRORATA_PACKAGE_IDS', [25, 28, 31, 32]);
