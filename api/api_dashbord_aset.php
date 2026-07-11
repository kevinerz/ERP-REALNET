<?php
// Set header untuk output JSON
header('Content-Type: application/json');

// Sertakan file konfigurasi database
include '../aset/config.php';

$response = array();

// 1. "Kamus" atau pemetaan manual dari Nama Lengkap ke Username Aset.
$user_mapping = [
    'MUHAMAD GOFUR'     => 'Gofur',
    'RAMDANI'           => 'Ramdani',
    'ARISTA DWI CANDRA' => 'ARIES',
    'BASIR'             => 'basir'
    // Jika ada teknisi baru, cukup tambahkan di sini.
];

// 2. Ambil nama lengkap yang dikirim dari Flutter
if (!isset($_GET['username']) || empty($_GET['username'])) {
    $response['status'] = 'error';
    $response['message'] = 'Parameter nama karyawan (username) tidak disediakan.';
    echo json_encode($response);
    $conn->close();
    exit();
}
$nama_lengkap = $_GET['username'];

// 3. Cari username yang sesuai dari "kamus" di atas.
$target_username = isset($user_mapping[$nama_lengkap]) ? $user_mapping[$nama_lengkap] : $nama_lengkap;

// 4. Gunakan username target untuk mencari aset di tabel 'aset_spv'
$stmt = $conn->prepare("SELECT id, kode_aset, nama_aset, kondisi, foto, pemilik_username FROM aset_spv WHERE pemilik_username = ? ORDER BY id DESC");

if ($stmt) {
    $stmt->bind_param("s", $target_username);
    $stmt->execute();
    $result = $stmt->get_result();
    $asets = array();

    while ($row = $result->fetch_assoc()) {
        // ===== PERUBAHAN URL FOTO DI SINI =====
        if (!empty($row['foto'])) {
            // URL diubah untuk menyertakan /aset/
            $row['foto_url'] = "https://datarealsolution.net/aset/uploads/" . htmlspecialchars($row['foto']);
        } else {
            $row['foto_url'] = null;
        }
        $asets[] = $row;
    }

    $response['status'] = 'success';
    $response['username'] = htmlspecialchars($target_username);
    $response['data'] = $asets;
    $stmt->close();

} else {
    $response['status'] = 'error';
    $response['message'] = 'Gagal menyiapkan query untuk mencari aset: ' . $conn->error;
}

// Tutup koneksi database
$conn->close();

// Cetak respons dalam format JSON
echo json_encode($response);
?>