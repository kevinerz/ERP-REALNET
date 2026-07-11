<?php
// Set header untuk respons JSON
header('Content-Type: application/json');

// Array respons standar untuk dikirimkan kembali ke JavaScript
$response = [
    'success' => false,
    'message' => 'Terjadi kesalahan tidak diketahui.'
];

// Pastikan request menggunakan metode POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['message'] = 'Metode request tidak diizinkan. Hanya POST yang diperbolehkan.';
    echo json_encode($response);
    exit;
}

// --- Sanitasi dan Validasi Input dari POST ---
// Menggunakan filter_input untuk keamanan dan kejelasan
$id             = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
$odp            = trim(filter_input(INPUT_POST, 'odp', FILTER_SANITIZE_STRING));
$vlan           = trim(filter_input(INPUT_POST, 'vlan', FILTER_SANITIZE_STRING));
$modem          = trim(filter_input(INPUT_POST, 'modem', FILTER_SANITIZE_STRING));
$dropcore       = trim(filter_input(INPUT_POST, 'dropcore', FILTER_SANITIZE_STRING));
// Pastikan teknisi_arr adalah array dan trim setiap elemennya
$teknisi_arr    = isset($_POST['teknisi']) && is_array($_POST['teknisi']) ? array_map('trim', $_POST['teknisi']) : [];

// Validasi input wajib
if ($id === false || $id <= 0) {
    $response['message'] = 'ID pemasangan tidak valid.';
    echo json_encode($response);
    exit;
}
if ($odp === '') {
    $response['message'] = 'Field ODP wajib diisi.';
    echo json_encode($response);
    exit;
}
if ($vlan === '') {
    $response['message'] = 'Field VLAN wajib diisi.';
    echo json_encode($response);
    exit;
}
if (empty($teknisi_arr)) {
    $response['message'] = 'Minimal pilih 1 Teknisi / Leader Area.';
    echo json_encode($response);
    exit;
}

// Gabungkan array teknisi menjadi string dipisahkan koma
$teknisi = implode(',', $teknisi_arr);

// --- Konfigurasi Koneksi Database ---
// Detail koneksi untuk db_pemasangan
$db_pemasangan_host = "localhost";
$db_pemasangan_user = "u272457353_kevinsamsung9";
$db_pemasangan_pass = "Admionkevin99";
$db_pemasangan_name = "u272457353_db_pemasangan";

// Detail koneksi untuk db_umum
$db_umum_host = "localhost";
$db_umum_user = "u272457353_kevinsamsung99";
$db_umum_pass = "Admionkevin99";
$db_umum_name = "u272457353_umumdata";

// Detail koneksi untuk db_crm
$db_crm_host = 'localhost';
$db_crm_name = 'u272457353_crm';
$db_crm_user = 'u272457353_kevinsamsung90';
$db_crm_pass = 'Kevinsamsung999';
$db_crm_charset = 'utf8mb4';

// Inisialisasi variabel koneksi menjadi null
$conn_pemasangan = null;
$conn_umum = null;
$pdo_crm = null;

// --- Logika Pembaruan Data ---
try {
    // 1. Koneksi ke Database Pemasangan
    $conn_pemasangan = new mysqli($db_pemasangan_host, $db_pemasangan_user, $db_pemasangan_pass, $db_pemasangan_name);
    if ($conn_pemasangan->connect_error) {
        throw new Exception("Koneksi ke database pemasangan gagal: " . $conn_pemasangan->connect_error);
    }

    // 2. Koneksi ke Database Umum
    $conn_umum = new mysqli($db_umum_host, $db_umum_user, $db_umum_pass, $db_umum_name);
    if ($conn_umum->connect_error) {
        throw new Exception("Koneksi ke database umum gagal: " . $conn_umum->connect_error);
    }

    // Mulai transaksi untuk memastikan semua operasi berhasil atau gagal bersamaan
    $conn_pemasangan->begin_transaction();
    $conn_umum->begin_transaction();

    // 3. Ambil Data Pemasangan Lama (untuk informasi CRM & modem lama)
    // Kolom 'ktp' di tabel 'pemasangan' akan diambil.
    // Kolom 'harga' DIHAPUS karena tidak ada di tabel 'pemasangan' sesuai error.
    $stmt_get_pemasangan = $conn_pemasangan->prepare(
        "SELECT modem, userppp, nama, pop, ktp, alamat, telp, email, paket, tanggal, marketing, url_maps
         FROM pemasangan WHERE id = ?"
    );
    if (!$stmt_get_pemasangan) {
        throw new Exception("Gagal menyiapkan statement SELECT dari db_pemasangan: " . $conn_pemasangan->error);
    }
    $stmt_get_pemasangan->bind_param("i", $id);
    $stmt_get_pemasangan->execute();
    $result_get_pemasangan = $stmt_get_pemasangan->get_result();
    $data_pemasangan = $result_get_pemasangan->fetch_assoc();
    $stmt_get_pemasangan->close();

    if (!$data_pemasangan) {
        throw new Exception("Data pemasangan dengan ID $id tidak ditemukan.");
    }

    // Data yang akan digunakan untuk CRM dan update modem
    $modem_lama = $data_pemasangan['modem'];
    $nama_pelanggan = $data_pemasangan['nama'];
    $pop_pelanggan = $data_pemasangan['pop'];
    $username_ppp = $data_pemasangan['userppp'];
    // Mengambil dari kolom 'ktp' di tabel 'pemasangan'
    $no_ktp_pelanggan = $data_pemasangan['ktp'];
    $alamat_pelanggan = $data_pemasangan['alamat'];
    $telp_pelanggan = $data_pemasangan['telp'];
    $email_pelanggan = $data_pemasangan['email'];
    $paket_pelanggan = $data_pemasangan['paket'];
    // Inisialisasi harga ke null karena tidak ada di tabel 'pemasangan'
    $harga_pelanggan = null; // Set ke null, atau '' jika kolom di CRM adalah VARCHAR

    $tanggal_pemasangan = $data_pemasangan['tanggal']; // Digunakan sebagai tanggal_tagihan di CRM
    $marketing_pelanggan = $data_pemasangan['marketing'];
    $koordinat_pelanggan = $data_pemasangan['url_maps']; // Asumsi ini adalah koordinat

    // 4. Update Tabel `pemasangan` di `db_pemasangan`
    $stmt_update_pemasangan = $conn_pemasangan->prepare(
        "UPDATE pemasangan SET odp = ?, vlan = ?, modem = ?, dropcore = ?, teknisi = ?, status = 'di proses' WHERE id = ?"
    );
    if (!$stmt_update_pemasangan) {
        throw new Exception("Gagal menyiapkan statement UPDATE pemasangan: " . $conn_pemasangan->error);
    }
    $stmt_update_pemasangan->bind_param("sssssi", $odp, $vlan, $modem, $dropcore, $teknisi, $id);
    if (!$stmt_update_pemasangan->execute()) {
        throw new Exception("Gagal memperbarui data pemasangan: " . $stmt_update_pemasangan->error);
    }
    $stmt_update_pemasangan->close();
    $conn_pemasangan->commit(); // Commit transaksi untuk db_pemasangan

    // 5. Update Status Modem di `db_umum`
    if (!empty($modem)) { // Jika modem baru dipilih
        $stmt_update_modem = $conn_umum->prepare(
            "UPDATE modem SET status = 'dipasang', lokasi_penyimpanan = ? WHERE id_modem = ?"
        );
        if (!$stmt_update_modem) {
            throw new Exception("Gagal menyiapkan statement UPDATE modem (baru): " . $conn_umum->error);
        }
        $stmt_update_modem->bind_param("ss", $nama_pelanggan, $modem);
        if (!$stmt_update_modem->execute()) {
            throw new Exception("Gagal memperbarui status modem baru: " . $stmt_update_modem->error);
        }
        $stmt_update_modem->close();

        // Jika ada modem lama dan berbeda dengan modem baru, kembalikan modem lama ke status 'tersedia'
        if (!empty($modem_lama) && $modem_lama !== $modem) {
            $empty_lokasi = ''; // Kosongkan lokasi penyimpanan modem lama
            $stmt_reset_modem_lama = $conn_umum->prepare(
                "UPDATE modem SET status = 'tersedia', lokasi_penyimpanan = ? WHERE id_modem = ?"
            );
            if (!$stmt_reset_modem_lama) {
                throw new Exception("Gagal menyiapkan statement UPDATE modem (lama): " . $conn_umum->error);
            }
            $stmt_reset_modem_lama->bind_param("ss", $empty_lokasi, $modem_lama);
            if (!$stmt_reset_modem_lama->execute()) {
                throw new Exception("Gagal mengembalikan status modem lama: " . $stmt_reset_modem_lama->error);
            }
            $stmt_reset_modem_lama->close();
        }
    }
    $conn_umum->commit(); // Commit transaksi untuk db_umum

    // 6. Koneksi dan Sinkronisasi Data ke CRM (`db_crm`)
    $dsn_crm = "mysql:host=$db_crm_host;dbname=$db_crm_name;charset=$db_crm_charset";
    $pdo_crm = new PDO($dsn_crm, $db_crm_user, $db_crm_pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false, // Sangat penting untuk keamanan dan penanganan tipe data
    ]);

    // Cek apakah pelanggan sudah ada di CRM berdasarkan username PPPoE
    $stmt_check_crm = $pdo_crm->prepare("SELECT id FROM customers WHERE username = ?");
    $stmt_check_crm->execute([$username_ppp]);
    $existing_crm_customer = $stmt_check_crm->fetch();

    // Data yang akan dimasukkan/diperbarui ke CRM
    // Pastikan nama kunci di array ini cocok persis dengan nama kolom di tabel 'customers' CRM
    $data_crm = [
        'pop'             => $pop_pelanggan,
        'username'        => $username_ppp,
        'nama'            => $nama_pelanggan,
        'no_ktp'          => $no_ktp_pelanggan, // Ini akan masuk ke kolom 'no_ktp' di CRM
        'alamat'          => $alamat_pelanggan,
        'telepon'         => $telp_pelanggan,
        'email'           => $email_pelanggan,
        'paket'           => $paket_pelanggan,
        'harga'           => $harga_pelanggan, // Menggunakan nilai null yang sudah diset di atas
        'tanggal_tagihan' => $tanggal_pemasangan,
        'foto_ktp'        => '', // Sesuaikan jika Anda punya kolom ini dan datanya di db_pemasangan
        'marketing'       => $marketing_pelanggan,
        'koordinat'       => $koordinat_pelanggan,
    ];

    if ($existing_crm_customer) {
        // UPDATE data pelanggan yang sudah ada di CRM
        $sql_crm = "UPDATE customers SET
            pop = :pop, nama = :nama, no_ktp = :no_ktp, alamat = :alamat, telepon = :telepon, email = :email,
            paket = :paket, harga = :harga, tanggal_tagihan = :tanggal_tagihan, foto_ktp = :foto_ktp,
            marketing = :marketing, koordinat = :koordinat, updated_at = NOW()
            WHERE username = :username"; // Update berdasarkan username
    } else {
        // INSERT pelanggan baru ke CRM
        $sql_crm = "INSERT INTO customers
            (pop, username, nama, no_ktp, alamat, telepon, email, paket, harga, tanggal_tagihan, foto_ktp, marketing, created_at, updated_at, koordinat)
            VALUES
            (:pop, :username, :nama, :no_ktp, :alamat, :telepon, :email, :paket, :harga, :tanggal_tagihan, :foto_ktp, :marketing, NOW(), NOW(), :koordinat)";
    }

    $stmt_crm = $pdo_crm->prepare($sql_crm);
    if (!$stmt_crm->execute($data_crm)) {
        throw new Exception("Gagal menyimpan data ke CRM.");
    }

    // Jika semua berhasil, set respons sukses
    $response['success'] = true;
    $response['message'] = 'Data pemasangan berhasil diperbarui dan disinkronkan ke CRM.';

} catch (Exception $e) {
    // Tangani error: rollback transaksi jika ada yang gagal
    if ($conn_pemasangan && $conn_pemasangan->in_transaction) {
        $conn_pemasangan->rollback();
    }
    if ($conn_umum && $conn_umum->in_transaction) {
        $conn_umum->rollback();
    }
    $response['message'] = "Error: " . $e->getMessage();
} finally {
    // Tutup semua koneksi database di akhir skrip
    if ($conn_pemasangan) $conn_pemasangan->close();
    if ($conn_umum) $conn_umum->close();
    // PDO akan otomatis menutup koneksi saat skrip berakhir, tidak perlu unset secara eksplisit
    $pdo_crm = null;
}

// Kirim respons JSON kembali ke klien
echo json_encode($response);
?>