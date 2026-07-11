<?php
// config.php - Konfigurasi Aplikasi RealNet (sinkron dengan daftar.php)

declare(strict_types=1);

// --- Error Reporting (disarankan: ON saat development, OFF saat produksi) ---
ini_set('display_errors', '0'); // ubah ke '1' jika sedang debug
error_reporting(E_ALL);

// --- Zona Waktu ---
date_default_timezone_set('Asia/Jakarta');

// --- URL Dasar Aplikasi ---
define('BASE_APP_URL', 'https://datarealsolution.net');

// =============================
// KONFIGURASI DATABASE
// =============================
define('DB_HOST', 'localhost');

// DB: pemasangan
define('DB_USER_PEMASANGAN', 'u272457353_kevinsamsung9');
define('DB_PASS_PEMASANGAN', 'Admionkevin99');
define('DB_NAME_PEMASANGAN', 'u272457353_db_pemasangan');

// DB: umumdata (paket)
define('DB_USER_UMUMDATA', 'u272457353_kevinsamsung99');
define('DB_PASS_UMUMDATA', 'Admionkevin99');
define('DB_NAME_UMUMDATA', 'u272457353_umumdata');

// DB: marketing (mitra)
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
define('WA_API_TOKEN_CUSTOMER', 'e9c50247-3b8d-4cd8-924a-024a4d2b3124');

// Token group
define('WA_API_TOKEN_GROUP', 'e9c50247-3b8d-4cd8-924a-024a4d2b3124');

// (opsional) kompatibilitas jika file lama masih pakai konstanta ini
define('WA_API_TOKEN', WA_API_TOKEN_GROUP);
define('WA_API_CS', WA_API_TOKEN_CUSTOMER);

// =============================
// GOOGLE MAPS
// =============================
define('GOOGLE_MAPS_API_KEY', 'AIzaSyDH4s_S0mOhLisPV_3e3SRXai11dZwA7dY');

// =============================
// TRIPAY (jika dipakai modul lain)
// =============================
define('TRIPAY_API_KEY', '20Tc8Y8CUXT8BWNCnv8RYDnfQa98cC2mE9DfOgwo');
define('TRIPAY_PRIVATE_KEY', 'AG53I-w5uBC-Ijmak-RAeXV-Kuvpn');
define('TRIPAY_MERCHANT_CODE', 'T41755');
define('TRIPAY_BASE_URL', 'https://tripay.co.id/api');

// =============================
// URL Shortener (jika dipakai modul lain)
// =============================
define('SHORTENER_BASE_URL', 'https://api.tinyurl.com');
define('SHORTENER_API_KEY', 'QTUkIeoehJ53UXcI6YFtzyaL8iq8Cn9CNB7XlmYCP1PnnVxryb24uiKE25CQ');

// =============================
// ID Paket Prorata (jika dipakai modul lain)
// =============================
define('PRORATA_PACKAGE_IDS', [25, 28, 31, 32]);

?>
