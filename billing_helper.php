<?php

// billing_helper.php

// Memuat file konfigurasi utama
require_once __DIR__ . "/config.php";

/**
 * Memeriksa apakah ID paket termasuk dalam kategori prorata.
 *
 * @param int $idPaket ID paket yang akan diperiksa.
 * @return bool True jika paket adalah prorata, false jika tidak.
 */
function isPaketProrata(int $idPaket): bool {
    // Menggunakan konstanta PRORATA_PACKAGE_IDS dari config.php
    return in_array($idPaket, PRORATA_PACKAGE_IDS);
}

/**
 * Menghitung biaya prorata berdasarkan harga bulanan.
 * Perhitungan ini mempertimbangkan sisa hari di bulan berjalan dan 5 hari di bulan berikutnya
 * untuk periode prorata hingga tanggal 5 bulan berikutnya.
 *
 * @param float $hargaBulanan Harga bulanan paket.
 * @return float Harga prorata yang dibulatkan ke atas.
 */
function hitungProrata(float $hargaBulanan): float {
    $today = new DateTime();
    $dayOfMonth = (int)$today->format('d');
    $month = (int)$today->format('m');
    $year = (int)$today->format('Y');

    // Tanggal 5 bulan ini
    $tgl5 = new DateTime("$year-$month-05");

    $hariAktif = 0;
    if ($today < $tgl5) {
        // Jika hari ini sebelum tanggal 5 bulan ini,
        // hitung sisa hari bulan ini + 5 hari bulan depan.
        $lastDayOfCurrentMonth = (int)$today->format('t'); // Total hari di bulan ini
        $remainingDaysCurrentMonth = $lastDayOfCurrentMonth - $dayOfMonth + 1; // Termasuk hari ini
        $hariAktif = $remainingDaysCurrentMonth + 5; // Tambah 5 hari di bulan berikutnya (sampai tgl 5)

    } else {
        // Jika hari ini tanggal 5 atau setelahnya,
        // hitung hari aktif dari hari ini sampai tanggal 5 bulan depan.
        $nextMonthDT = (clone $today)->modify('+1 month');
        $nextMonthYear = (int)$nextMonthDT->format('Y');
        $nextMonthNum = (int)$nextMonthDT->format('m');

        $tgl5NextMonth = new DateTime("$nextMonthYear-$nextMonthNum-05");

        $interval = $today->diff($tgl5NextMonth);
        $hariAktif = $interval->days + 1; // +1 karena perhitungan diff tidak termasuk hari terakhir
    }

    // Pastikan hari aktif minimal 1 untuk menghindari pembagian atau perkalian nol
    if ($hariAktif <= 0) {
        $hariAktif = 1;
    }

    // Jumlah hari di bulan ini (untuk basis perhitungan harian)
    $totalHariBulanIni = (int)$today->format('t');
    if ($totalHariBulanIni <= 0) {
        $totalHariBulanIni = 30; // Fallback jika somehow hari di bulan nol
    }

    // Pastikan harga bulanan tidak nol untuk menghindari pembagian dengan nol
    if ($hargaBulanan <= 0) {
        return 0;
    }

    // Hitung harga prorata dan bulatkan ke atas
    $hargaProrata = ceil(($hargaBulanan / $totalHariBulanIni) * $hariAktif);

    return $hargaProrata;
}

/**
 * Mengirim notifikasi WhatsApp untuk pembayaran prorata kepada pelanggan.
 *
 * @param string $telp Nomor telepon pelanggan (format 62...).
 * @param string $nama Nama pelanggan.
 * @param string $namaPaket Nama paket internet.
 * @param float $hargaProrata Jumlah tagihan prorata.
 * @param string $periodeAwal Tanggal awal periode prorata (format Y-m-d).
 * @param string $periodeAkhir Tanggal akhir periode prorata (format Y-m-d).
 * @param string $urlPembayaran URL halaman pembayaran yang sudah dipendekkan.
 * @return bool True jika berhasil mengirim, false jika gagal.
 */
function waNotifProrata(string $telp, string $nama, string $namaPaket, float $hargaProrata, string $periodeAwal, string $periodeAkhir, string $urlPembayaran): bool {
    $waToken = WA_API_CS;
    $apiUrl = WA_API_URL;

    $body = "
👋 Halo *{$nama}*,

*Tagihan Pemasangan Prorata (Pembayaran Online)*

Paket: *{$namaPaket}*
Periode aktif: *{$periodeAwal}* s/d *{$periodeAkhir}*
Tagihan PRORATA: *Rp" . number_format($hargaProrata, 0, ',', '.') . "*

Silakan lakukan pembayaran melalui link berikut:

*{$urlPembayaran}*

Di halaman tersebut, Anda dapat memilih metode pembayaran yang diinginkan (QRIS, Virtual Account Bank, OVO, DANA, Indomaret, Alfamart, dll).

Setelah pembayaran terverifikasi otomatis, layanan akan aktif selama periode tersebut.

Terima kasih 🙏
";

    $curl = curl_init($apiUrl);
    curl_setopt_array($curl, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode([
            "messageType" => "text",
            "to"          => $telp,
            "body"        => $body
        ]),
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'Authorization: ' . $waToken
        ],
        CURLOPT_TIMEOUT        => 10,
    ]);

    $resp = curl_exec($curl);
    $error = curl_error($curl);
    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);

    file_put_contents('log_wa_prorata.txt', date('Y-m-d H:i:s') . " TELP:$telp HTTP:$httpCode RESP:{$resp} ERROR:{$error}\n", FILE_APPEND);

    if ($error) {
        error_log("WA Notification (Prorata) cURL Error: " . $error);
        return false;
    }

    $result = json_decode($resp, true);
    // Periksa status 'success' atau kode spesifik dari Starsender untuk keberhasilan
    return $result && isset($result['status']) && $result['status'] === 'success';
}

/**
 * Membuat invoice pembayaran melalui Tripay.
 * Menggunakan kredensial API dari config.php.
 *
 * @param string $orderId ID pesanan unik.
 * @param string $nama Nama pelanggan.
 * @param string $email Email pelanggan.
 * @param string $telp Nomor telepon pelanggan (format 62...).
 * @param float $jumlah Total jumlah pembayaran.
 * @param string $namaPaket Nama paket untuk deskripsi item pembayaran.
 * @param string $paymentMethodCode Kode metode pembayaran Tripay yang dipilih.
 * @return string|false URL checkout Tripay jika berhasil, false jika gagal.
 */
function buatInvoiceTripay(string $orderId, string $nama, string $email, string $telp, float $jumlah, string $namaPaket, string $paymentMethodCode) {
    $tripayUrl = TRIPAY_BASE_URL . "/transaction/create";
    $apiKey = TRIPAY_API_KEY;
    $privateKey = TRIPAY_PRIVATE_KEY;
    $merchantCode = TRIPAY_MERCHANT_CODE;

    $payload = [
        "method"         => $paymentMethodCode,
        "merchant_ref"   => $orderId,
        "amount"         => (int)ceil($jumlah), // Amount must be an integer
        "customer_name"  => $nama,
        "customer_email" => $email,
        "customer_phone" => $telp,
        "order_items"    => [
            [
                "sku"         => "INSTALL-PRORATA",
                "name"        => "Biaya PRORATA Pemasangan Awal ({$namaPaket})",
                "price"       => (int)ceil($jumlah),
                "quantity"    => 1,
                "product_url" => "", // Opsional: URL produk Anda
                "image_url"   => "", // Opsional: URL gambar produk
                "description" => "Tagihan pemasangan awal PRORATA untuk paket {$namaPaket}, hanya membayar sisa hari berjalan hingga tanggal 5 bulan depan. Setelahnya, tagihan bulanan normal."
            ]
        ],
        "expired_time"   => time() + (3 * 60 * 60), // Kadaluarsa dalam 3 jam
        "signature"      => hash_hmac('sha256', $merchantCode . $orderId . (int)ceil($jumlah), $privateKey)
    ];

    $curl = curl_init($tripayUrl);
    curl_setopt_array($curl, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode($payload),
        CURLOPT_HTTPHEADER     => [
            "Authorization: Bearer $apiKey",
            "Content-Type: application/json"
        ],
        CURLOPT_TIMEOUT        => 15, // Timeout untuk permintaan
    ]);

    $response = curl_exec($curl);
    $error = curl_error($curl);
    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);

    // Logging respon Tripay untuk debugging
    file_put_contents('log_tripay.txt', date('Y-m-d H:i:s') . " order_id: $orderId, HTTP:$httpCode RESP: $response ERROR:$error\n", FILE_APPEND);

    if ($error) {
        error_log("Tripay Invoice cURL Error: " . $error);
        return false;
    }

    $result = json_decode($response, true);

    if (!$result) {
        error_log("Tripay Invoice: Respon JSON tidak valid atau kosong. HTTP Code: {$httpCode}");
        return false;
    }

    if (!isset($result['success']) || !$result['success']) {
        $errorMessage = $result['message'] ?? 'Unknown error from Tripay API';
        error_log("Tripay Invoice API Error: " . $errorMessage . " | HTTP Code: {$httpCode}");
        return false;
    }

    if (isset($result['data']['checkout_url'])) {
        return $result['data']['checkout_url'];
    }

    error_log("Tripay Invoice: 'checkout_url' tidak ditemukan dalam respon sukses. Data: " . print_r($result, true));
    return false;
}

/**
 * Mempersingkat URL menggunakan API TinyURL.
 * Akan mengembalikan URL asli jika proses pemendekan gagal.
 *
 * @param string $longUrl URL yang ingin dipersingkat.
 * @return string URL yang sudah dipersingkat atau URL asli jika gagal.
 */
function shortenUrl(string $longUrl): string {
    $apiUrl = SHORTENER_BASE_URL . '/create'; // Endpoint API modern TinyURL
    $apiToken = SHORTENER_API_KEY;

    $ch = curl_init();

    // Payload dalam format JSON untuk API TinyURL modern
    $payload = json_encode(['url' => $longUrl]);

    curl_setopt_array($ch, [
        CURLOPT_URL            => $apiUrl,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_HTTPHEADER     => [
            'Authorization: Bearer ' . $apiToken, // Menggunakan API key untuk otorisasi
            'Content-Type: application/json'      // Menentukan tipe konten payload
        ],
        CURLOPT_TIMEOUT        => 5, // Timeout 5 detik untuk permintaan
    ]);

    $response = curl_exec($ch);
    $error = curl_error($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    // Periksa error cURL atau kode HTTP selain 200 (OK)
    if ($error || $httpCode !== 200) {
        error_log("Pemendekan URL TinyURL gagal. Error: {$error}, Kode HTTP: {$httpCode}, Respon: {$response}");
        return $longUrl; // Kembalikan URL asli jika gagal
    }

    $result = json_decode($response, true);

    // Periksa apakah JSON berhasil di-decode dan 'tiny_url' ada di dalam 'data'
    if (json_last_error() === JSON_ERROR_NONE && isset($result['data']['tiny_url']) && filter_var($result['data']['tiny_url'], FILTER_VALIDATE_URL)) {
        return $result['data']['tiny_url'];
    } else {
        error_log("Respon API TinyURL tidak memiliki 'tiny_url' atau JSON tidak valid: " . $response);
        return $longUrl; // Kembalikan URL asli jika data yang diharapkan tidak ditemukan
    }
}

?>