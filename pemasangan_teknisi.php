<?php
require_once __DIR__ . '/config/database.php';
session_start();

// Inisialisasi variabel sesi
$username = $_SESSION['username'] ?? '';
$_SESSION['lokasi_penyimpanan'] = $username;

// Mapping username ke POP
$usernameToPop = [
    "Gofur"   => "rajeg",
    "jihan"   => "rajeg",
    "ALFARIZ" => "rajeg",
    "ARIES"   => "mauk",
    "SARANI"  => "mauk",
    "Fzr41"   => "kemeri",
    "Ramdani" => "kemeri",
    "sopi"    => "kemeri"
];
$pop_filter = $usernameToPop[$username] ?? null;

// Koneksi Database
$conn_pemasangan = null;
$conn_umum = null;

try {
    $conn_pemasangan = getErpDbConnection();
    if ($conn_pemasangan->connect_error) {
        throw new Exception("Koneksi ke database pemasangan gagal: " . $conn_pemasangan->connect_error);
    }

    $conn_umum = getErpDbConnection();
    if ($conn_umum->connect_error) {
        throw new Exception("Koneksi ke database umum gagal: " . $conn_umum->connect_error);
    }
} catch (Exception $e) {
    die($e->getMessage());
}

// Pengaturan Paginasi
$rows_per_page = 5;
$cari = trim($_GET['cari'] ?? '');
$page = max(1, intval($_GET['page_pemasangan'] ?? 1));
$start_row = ($page - 1) * $rows_per_page;

// Membangun klausa WHERE untuk query
$where = [];
$params = [];
$types = '';

if ($pop_filter) {
    $where[] = "pop = ?";
    $params[] = $pop_filter;
    $types .= "s";
}

if ($cari) {
    $where[] = "(nama LIKE ? OR alamat LIKE ? OR telp LIKE ? OR ktp LIKE ?)";
    $params[] = "%$cari%";
    $params[] = "%$cari%";
    $params[] = "%$cari%";
    $params[] = "%$cari%";
    $types .= "ssss";
}

$where[] = "(status = ? OR status = ?)";
$params[] = "aktivasi";
$params[] = "di proses";
$types .= "ss";

$where_sql = $where ? "WHERE " . implode(" AND ", $where) : "";

// Query untuk menghitung total baris
$sql_count = "SELECT COUNT(*) AS total FROM pelanggan_instalasi $where_sql";
$stmt_count = $conn_pemasangan->prepare($sql_count);
if ($params) {
    $stmt_count->bind_param($types, ...$params);
}
$stmt_count->execute();
$total_rows = $stmt_count->get_result()->fetch_assoc()['total'] ?? 0;
$stmt_count->close();
$total_pages = max(1, ceil($total_rows / $rows_per_page));

// Query untuk mengambil data pemasangan
$sql_data = "SELECT id, nama, user, userppp, paket, vlan, sn, pop, odp, url_maps, teknisi, alamat, ktp, telp, email, marketing, tanggal, status, modem, dropcore, passwordppp
             FROM pelanggan_instalasi $where_sql ORDER BY tanggal DESC LIMIT ?, ?";

$params2 = $params;
$types2 = $types . "ii";
$params2[] = $start_row;
$params2[] = $rows_per_page;

$stmt = $conn_pemasangan->prepare($sql_data);
if (!$stmt) {
    die("Error preparing statement: " . $conn_pemasangan->error);
}
$stmt->bind_param($types2, ...$params2);
$stmt->execute();
$result = $stmt->get_result();

// Mengambil daftar ODP
$ODP_LIST = [];
$res_odp = $conn_umum->query("SELECT DISTINCT nama_odp FROM jaringan_odp ORDER BY nama_odp ASC");
if ($res_odp) {
    while ($d = $res_odp->fetch_assoc()) {
        $ODP_LIST[] = $d['nama_odp'];
    }
}

// Mengambil daftar Modem berdasarkan lokasi user
$lokasi_user = $_SESSION['lokasi_penyimpanan'] ?? '';
$MODEM_LIST = [];
if ($lokasi_user) {
    $stmt_modem = $conn_umum->prepare(
        "SELECT id_modem AS id, serial_number, model, merk
         FROM jaringan_modem
         WHERE status = 'tersedia'
         AND lokasi_penyimpanan = ?"
    );
    if ($stmt_modem) {
        $stmt_modem->bind_param("s", $lokasi_user);
        $stmt_modem->execute();
        $res_modem = $stmt_modem->get_result();
        while ($d = $res_modem->fetch_assoc()) {
            $MODEM_LIST[] = $d;
        }
        $stmt_modem->close();
    }
}

// Mengambil daftar Dropcore
$DROPCORE_LIST = [];
$res_dropcore = $conn_umum->query("SELECT id_kabel_dropcore AS id, kode_kabel, panjang_meter FROM jaringan_kabel_dropcore WHERE status = 'tersedia'");
if ($res_dropcore) {
    while ($d = $res_dropcore->fetch_assoc()) {
        $DROPCORE_LIST[] = $d;
    }
}

// Mengambil daftar Teknisi
$TEKNISI_LIST = [];
$res_teknisi = $conn_umum->query("SELECT username, divisi FROM hr_karyawan WHERE divisi IN ('Teknisi','Leader Area') ORDER BY divisi, username ASC");
if ($res_teknisi) {
    while ($d = $res_teknisi->fetch_assoc()) {
        $TEKNISI_LIST[] = $d;
    }
}

/**
 * Mengembalikan badge status HTML berdasarkan status yang diberikan.
 * @param string $status
 * @return string
 */
function getStatusBadge($status)
{
    $status = strtolower($status);
    switch ($status) {
        case 'aktivasi':
            return '<span class="badge bg-primary"><i class="fas fa-bolt status-icon"></i> Aktivasi</span>';
        case 'belum diproses':
            return '<span class="badge bg-danger text-dark"><i class="fas fa-circle-notch fa-spin status-icon"></i> Belum Diproses</span>';
        case 'di proses':
            return '<span class="badge bg-warning text-dark"><i class="fas fa-circle-notch fa-spin status-icon"></i> Sedang Diproses</span>';
        case 'selesai':
            return '<span class="badge bg-success"><i class="fas fa-check-circle status-icon"></i> Selesai</span>';
        default:
            return '<span class="badge bg-secondary"><i class="fas fa-question-circle status-icon"></i> Tidak Diketahui</span>';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Data Pemasangan - Status Aktivasi / Di Proses</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet" />
    <style>
        .accordion-button:not(.collapsed) { color:#fff; background:#007bff; }
        .status-icon { margin-right:5px; }
        .modal-lg { max-width: 700px; }
        dt { width: 140px; float: left; clear: left; font-weight: bold; }
        dd { margin-left: 150px; margin-bottom: 8px; }
        @media (max-width: 600px) {
            dt, dd { float: none; width: auto; margin-left: 0; }
        }
    </style>
</head>
<body>
<div class="container mt-4">
    <h2 class="text-center">Data Pemasangan <span class="text-primary">Aktivasi / Di Proses</span></h2>
    <div class="d-flex justify-content-end mb-2">
        <a href="menu_teknisi.php" class="btn btn-secondary">
            <i class="fa fa-arrow-left"></i> Kembali
        </a>
    </div>
    <form class="row g-3 mb-3" method="get">
        <div class="col-md-6">
            <input type="text" class="form-control" name="cari" placeholder="Cari nama/alamat/dll"
                value="<?php echo htmlspecialchars($cari); ?>">
        </div>
        <div class="col-md-2">
            <button class="btn btn-primary w-100" type="submit">Cari</button>
        </div>
    </form>
    <div class="accordion" id="accordionPemasangan">
    <?php
    $idx = ($page - 1) * $rows_per_page + 1; // Sesuaikan indeks dengan halaman saat ini
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            echo '<div class="accordion-item">';
            echo '<h2 class="accordion-header" id="head' . $idx . '">';
            echo '<button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse' . $idx . '" aria-expanded="false" aria-controls="collapse' . $idx . '">';
            echo getStatusBadge($row['status']) . ' - ID: ' . $row['id'] . ' - Nama: ' . htmlspecialchars($row['nama']);
            echo '</button></h2>';
            echo '<div id="collapse' . $idx . '" class="accordion-collapse collapse" aria-labelledby="head' . $idx . '" data-bs-parent="#accordionPemasangan">';
            echo '<div class="accordion-body">';
            // Tombol Modal Detail & Update & Status
            echo '<button type="button" class="btn btn-primary btn-sm me-1" data-bs-toggle="modal" data-bs-target="#modalDetail' . $idx . '"><i class="fa fa-eye"></i> Lihat Detail</button>';
            echo '<button type="button" class="btn btn-warning btn-sm me-1" data-bs-toggle="modal" data-bs-target="#modalUpdate' . $idx . '"><i class="fa fa-edit"></i> Update</button>';
            echo '<button type="button" class="btn btn-info btn-sm btn-status-pppoe"
                     data-bs-toggle="modal"
                     data-bs-target="#modalStatusPPPoE"
                     data-username="' . htmlspecialchars($row['userppp']) . '"
                     data-nama="' . htmlspecialchars($row['nama']) . '"
                     data-pop="' . htmlspecialchars($row['pop']) . '"
                     ><i class="fa fa-signal"></i> Status</button>';
            echo '</div></div></div>';

            // Modal Detail
            echo '<div class="modal fade" id="modalDetail' . $idx . '" tabindex="-1" aria-labelledby="modalDetailLabel' . $idx . '" aria-hidden="true">';
            echo '  <div class="modal-dialog modal-lg">';
            echo '    <div class="modal-content">';
            echo '      <div class="modal-header">';
            echo '        <h5 class="modal-title" id="modalDetailLabel' . $idx . '">Detail Pemasangan ID: ' . $row['id'] . '</h5>';
            echo '        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>';
            echo '      </div>';
            echo '      <div class="modal-body">';
            echo '      <dl>';
            echo '<dt>ID</dt><dd>' . $row['id'] . '</dd>';
            echo '<dt>Nama</dt><dd>' . htmlspecialchars($row['nama']) . '</dd>';
            echo '<dt>User PPPoE</dt><dd>' . htmlspecialchars($row['userppp']) . '</dd>';
            echo '<dt>Password PPPoE</dt><dd>' . htmlspecialchars($row['passwordppp']) . '</dd>';
            echo '<dt>Paket</dt><dd>' . htmlspecialchars($row['paket']) . '</dd>';
            echo '<dt>VLAN</dt><dd>' . htmlspecialchars($row['vlan']) . '</dd>';
            echo '<dt>POP</dt><dd>' . htmlspecialchars($row['pop']) . '</dd>';
            echo '<dt>ODP</dt><dd>' . htmlspecialchars($row['odp']) . '</dd>';
            echo '<dt>Teknisi</dt><dd>' . htmlspecialchars($row['teknisi']) . '</dd>';
            echo '<dt>Alamat</dt><dd>' . htmlspecialchars($row['alamat']) . '</dd>';
            echo '<dt>No KTP</dt><dd>' . htmlspecialchars($row['ktp']) . '</dd>'; // Menampilkan 'ktp' dari tabel pemasangan
            echo '<dt>No Telepon</dt><dd>' . htmlspecialchars($row['telp']) . '</dd>';
            echo '<dt>Email</dt><dd>' . htmlspecialchars($row['email']) . '</dd>';
            echo '<dt>Marketing</dt><dd>' . htmlspecialchars($row['marketing']) . '</dd>';
            echo '<dt>Tanggal</dt><dd>' . htmlspecialchars($row['tanggal']) . '</dd>';
            echo '<dt>Status</dt><dd>' . getStatusBadge($row['status']) . '</dd>';
            echo '<dt>URL Maps</dt><dd>';
            if ($row['url_maps']) {
                echo '<a href="' . htmlspecialchars($row['url_maps']) . '" target="_blank" rel="noopener">Lihat Lokasi</a>';
            } else {
                echo '-';
            }
            echo '</dd>';
            echo '<dt>Modem</dt><dd>' . htmlspecialchars($row['modem']) . '</dd>';
            echo '<dt>Dropcore</dt><dd>' . htmlspecialchars($row['dropcore']) . '</dd>';
            echo '</dl>';
            echo '      </div>';
            echo '      <div class="modal-footer">';
            echo '        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>';
            echo '      </div>';
            echo '    </div>';
            echo '  </div>';
            echo '</div>';

            // MODAL UPDATE
            echo '<div class="modal fade" id="modalUpdate' . $idx . '" tabindex="-1" aria-labelledby="modalUpdateLabel' . $idx . '" aria-hidden="true">';
            echo '  <div class="modal-dialog">';
            echo '    <div class="modal-content">';
            echo '      <div class="modal-header">';
            echo '        <h5 class="modal-title" id="modalUpdateLabel' . $idx . '">Update Data Pemasangan</h5>';
            echo '        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>';
            echo '      </div>';
            echo '<form class="form-update-pemasangan" data-id="' . $row['id'] . '">';
            echo '<div class="modal-body">';
            echo '<input type="hidden" name="id" value="' . $row['id'] . '">';

            // ODP
            echo '<div class="mb-3"><label for="odp' . $idx . '" class="form-label">ODP</label>';
            echo '<select class="form-control" id="odp' . $idx . '" name="odp" required>';
            echo '<option value="">Pilih ODP</option>';
            foreach ($ODP_LIST as $odp) {
                $selected = ($row['odp'] == $odp) ? 'selected' : '';
                echo '<option value="' . htmlspecialchars($odp) . '" ' . $selected . '>' . htmlspecialchars($odp) . '</option>';
            }
            echo '</select></div>';

            // VLAN
            echo '<div class="mb-3"><label for="vlan' . $idx . '" class="form-label">VLAN</label>';
            echo '<input type="text" class="form-control" id="vlan' . $idx . '" name="vlan" value="' . htmlspecialchars($row['vlan']) . '" required></div>';

            // Modem
            echo '<div class="mb-3"><label for="modem' . $idx . '" class="form-label">Modem</label>';
            echo '<select class="form-control" id="modem' . $idx . '" name="modem" required>';
            echo '<option value="">Pilih Modem</option>';
            foreach ($MODEM_LIST as $modem) {
                $sel = ($row['modem'] == $modem['id']) ? 'selected' : '';
                echo '<option value="' . $modem['id'] . '" ' . $sel . '>' . htmlspecialchars($modem['serial_number'] . ' - ' . $modem['model'] . ' - ' . $modem['merk']) . '</option>';
            }
            echo '</select></div>';

            // Dropcore
            echo '<div class="mb-3"><label for="dropcore' . $idx . '" class="form-label">Dropcore</label>';
            echo '<select class="form-control" id="dropcore' . $idx . '" name="dropcore">';
            echo '<option value="">Pilih Dropcore</option>';
            foreach ($DROPCORE_LIST as $drop) {
                $sel = ($row['dropcore'] == $drop['id']) ? 'selected' : '';
                echo '<option value="' . $drop['id'] . '" ' . $sel . '>' . htmlspecialchars($drop['kode_kabel'] . ' - ' . $drop['panjang_meter'] . ' m') . '</option>';
            }
            echo '</select></div>';

            // Teknisi multi select
            $selected_teknisi = array_map('trim', explode(',', $row['teknisi'] ?? ''));
            echo '<div class="mb-3">';
            echo '<label for="teknisi' . $idx . '" class="form-label">Teknisi / Leader Area</label>';
            echo '<select class="form-control" id="teknisi' . $idx . '" name="teknisi[]" multiple required style="height:90px;">';
            foreach ($TEKNISI_LIST as $tk) {
                $sel = in_array($tk['username'], $selected_teknisi) ? 'selected' : '';
                echo '<option value="' . htmlspecialchars($tk['username']) . '" ' . $sel . '>' . htmlspecialchars($tk['username'] . ' - ' . $tk['divisi']) . '</option>';
            }
            echo '</select>';
            echo '<div style="font-size:12px;color:#999;">(Tekan CTRL / Command untuk memilih lebih dari satu)</div>';
            echo '</div>';

            echo '</div>'; // modal-body
            echo '<div class="modal-footer">';
            echo '<button type="submit" class="btn btn-warning">Update &amp; Proses</button>';
            // Tombol "Selesai" disembunyikan secara default menggunakan kelas 'd-none'
            // dan hanya akan muncul jika statusnya 'di proses' (setelah update) atau memang sudah 'selesai'
            $hide_selesai_btn = (strtolower($row['status']) === 'aktivasi') ? 'd-none' : '';
            echo '<button type="button" class="btn btn-success btn-selesai ' . $hide_selesai_btn . '" data-id="' . $row['id'] . '">Selesai</button>';
            echo '<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>';
            echo '</div>';
            echo '</form>';
            echo '    </div>';
            echo '  </div>';
            echo '</div>';

            $idx++;
        }
    } else {
        echo "<p class='text-center'>Tidak ada data pemasangan status <b>aktivasi</b> atau <b>di proses</b> ditemukan.</p>";
    }
    ?>
    </div>

    <div class="modal fade" id="modalStatusPPPoE" tabindex="-1" aria-labelledby="modalStatusPPPoELabel" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="modalStatusPPPoELabel">Status Modem (PPPoE)</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <div id="pppoe-status-loading" style="display:none;">
              <div class="text-center"><div class="spinner-border text-info" role="status"></div><br>Loading...</div>
            </div>
            <div id="pppoe-status-result"></div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
          </div>
        </div>
      </div>
    </div>

    <nav aria-label="Page navigation">
        <ul class="pagination justify-content-center mt-4">
            <li class="page-item <?php if($page<=1)echo 'disabled';?>">
                <a class="page-link" href="?page_pemasangan=<?php echo $page-1;?>&cari=<?php echo urlencode($cari);?>">Previous</a>
            </li>
            <?php for($i=1;$i<=$total_pages;$i++){
                echo '<li class="page-item '.($i==$page?'active':'').'"><a class="page-link" href="?page_pemasangan='.$i.'&cari='.urlencode($cari).'">'.$i.'</a></li>';
            }?>
            <li class="page-item <?php if($page>=$total_pages)echo 'disabled';?>">
                <a class="page-link" href="?page_pemasangan=<?php echo $page+1;?>&cari=<?php echo urlencode($cari);?>">Next</a>
            </li>
        </ul>
    </nav>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script>
$(function(){
    // TOMBOL STATUS (userppp)
    $('.btn-status-pppoe').on('click', function(){
        const username = $(this).data('username');
        const nama = $(this).data('nama') || '';
        const pop = ($(this).data('pop') || '').toLowerCase();
        $('#modalStatusPPPoELabel').text(`Status Modem (PPPoE)${nama ? " - " + nama : ""}`);
        const loading = $('#pppoe-status-loading');
        const result = $('#pppoe-status-result');
        loading.show();
        result.html('');
        if (!username) {
            loading.hide();
            result.html('<div class="alert alert-warning">Username PPPoE tidak ditemukan.</div>');
            return;
        }
        const url = (pop === 'rajeg')
            ? `https://datarealsolution.net/pppoe_status_rajeg.php?username=${encodeURIComponent(username)}`
            : `https://datarealsolution.net/pppoe_status.php?username=${encodeURIComponent(username)}`;

        fetch(url)
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                loading.hide();
                if (data.status === 'online') {
                    const d = data.data;
                    const html = `
                        <div class="alert alert-success mb-2"><b>ONLINE</b></div>
                        <table class="table table-bordered table-sm">
                            <tr><th>Username</th><td>${d.name}</td></tr>
                            <tr><th>IP Address</th><td>${d.address}</td></tr>
                            <tr><th>MAC (Caller ID)</th><td>${d['caller-id']}</td></tr>
                            <tr><th>Uptime</th><td>${d.uptime}</td></tr>
                            <tr><th>Service</th><td>${d.service}</td></tr>
                            <tr><th>Session ID</th><td>${d['session-id']}</td></tr>
                            <tr><th>Limit In</th><td>${d['limit-bytes-in']}</td></tr>
                            <tr><th>Limit Out</th><td>${d['limit-bytes-out']}</td></tr>
                            <tr><th>Radius</th><td>${d.radius}</td></tr>
                        </table>`;
                    result.html(html);
                } else {
                    result.html('<div class="alert alert-danger"><b>OFFLINE</b></div>');
                }
            })
            .catch(error => {
                loading.hide();
                console.error("Error fetching PPPoE status:", error);
                result.html('<div class="alert alert-danger">Gagal mengambil data status modem. Periksa koneksi atau URL.</div>');
            });
    });

    // Reset modal setiap kali dibuka
    $('#modalStatusPPPoE').on('show.bs.modal', function () {
        $('#pppoe-status-loading').show();
        $('#pppoe-status-result').html('');
    });

    // ========== UPDATE HANDLER ==========
    $(document).on('submit', '.form-update-pemasangan', function(e){
        e.preventDefault();
        const form = $(this);
        const formId = form.data('id'); // Ambil ID formulir untuk referensi
        const updateButton = form.find('button[type="submit"]');
        const completeButton = form.find('.btn-selesai'); // Tombol selesai di dalam modal ini

        // Nonaktifkan tombol untuk mencegah pengiriman ganda
        updateButton.prop('disabled', true).text('Memproses...');

        const odp = form.find('select[name="odp"]').val();
        const vlan = form.find('input[name="vlan"]').val().trim();
        const teknisi = form.find('select[name="teknisi[]"]').val();

        // Validasi sisi klien
        if (!odp) {
            alert('Field ODP wajib diisi.');
            updateButton.prop('disabled', false).text('Update & Proses'); // Aktifkan kembali tombol
            return false;
        }
        if (!vlan) {
            alert('Field VLAN wajib diisi.');
            updateButton.prop('disabled', false).text('Update & Proses');
            return false;
        }
        if (!teknisi || teknisi.length === 0) {
            alert('Minimal pilih 1 Teknisi / Leader Area.');
            updateButton.prop('disabled', false).text('Update & Proses');
            return false;
        }

        const formData = form.serialize();

        $.post('update_pemasangan.php', formData, function(res){
            console.log("Response from update_pemasangan.php:", res); // Debugging: lihat respons dari server
            if(res.success){
                alert("Berhasil update: " + res.message);
                // Tampilkan tombol Selesai di modal yang sama
                completeButton.removeClass('d-none'); // Menghapus kelas 'd-none' untuk menampilkan tombol
                // Opsional: Perbarui status di header akordeon tanpa reload halaman penuh
                const accordionHeader = $('#head' + formId).find('.accordion-button');
                accordionHeader.html(`<?php echo getStatusBadge('di proses'); ?> - ID: ${formId} - Nama: ${res.nama || ''}`); // Asumsi res.nama ada
            } else {
                alert("Gagal update data: " + res.message);
            }
        }, 'json') // <<< PENTING: Mengharapkan respons JSON
        .fail(function(jqXHR, textStatus, errorThrown) {
            console.error("AJAX Error (update_pemasangan.php):", textStatus, errorThrown, jqXHR.responseText); // Debugging
            let errorMessage = "Terjadi kesalahan pada server saat update. Respons tidak valid.";
            try {
                const errorRes = JSON.parse(jqXHR.responseText);
                if (errorRes.message) {
                    errorMessage = "Terjadi kesalahan pada server saat update: " + errorRes.message;
                }
            } catch (e) {
                // Biarkan errorMessage default
            }
            alert(errorMessage);
        })
        .always(function() {
            // Aktifkan kembali tombol update setelah selesai, terlepas dari sukses atau gagal
            updateButton.prop('disabled', false).text('Update & Proses');
        });
    });

    // ========== SELESAI HANDLER ==========
    $(document).on('click', '.btn-selesai', function(){
        if (!confirm("Yakin tandai selesai? Pemasangan akan dipindahkan ke riwayat.")) return;
        const button = $(this);
        button.prop('disabled', true).text('Memproses...'); // Nonaktifkan tombol
        const id = button.data('id');

        $.post('selesai_pemasangan.php', {id: id}, function(res){
            console.log("Response from selesai_pemasangan.php:", res); // Debugging: lihat respons dari server
            if(res.success){
                alert("Berhasil tandai selesai: " + res.message);
                location.reload(); // Muat ulang halaman setelah berhasil
            } else {
                alert("Gagal update status: " + (res.message || "Error tidak diketahui"));
            }
        }, 'json') // <<< PENTING: Mengharapkan respons JSON
        .fail(function(jqXHR, textStatus, errorThrown) {
            console.error("AJAX Error (selesai_pemasangan.php):", textStatus, errorThrown, jqXHR.responseText); // Debugging
            let errorMessage = "Terjadi kesalahan pada server saat menandai selesai. Respons tidak valid.";
            try {
                const errorRes = JSON.parse(jqXHR.responseText);
                if (errorRes.message) {
                    errorMessage = "Terjadi kesalahan pada server saat menandai selesai: " + errorRes.message;
                }
            } catch (e) {
                // Biarkan errorMessage default
            }
            alert(errorMessage);
        })
        .always(function() {
            // Aktifkan kembali tombol jika masih di halaman (meskipun akan reload)
            button.prop('disabled', false).text('Selesai');
        });
    });
});
</script>
</body>
</html>
<?php
// Tutup koneksi database
if ($conn_pemasangan) {
    $conn_pemasangan->close();
}
if ($conn_umum) {
    $conn_umum->close();
}
?>