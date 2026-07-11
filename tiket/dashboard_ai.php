<?php
require_once __DIR__ . '/../config/database.php';
// dashboard_ai.php (Versi Terbaik - Real-time & Cerdas)
$db = getErpDbConnection();
if ($db->connect_error) die("Koneksi gagal: " . $db->connect_error);

date_default_timezone_set("Asia/Jakarta");

function time_ago($datetime) {
    $now = new DateTime;
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);
    $w = floor($diff->d / 7);
    $diff->d -= $w * 7;
    $string = ['y' => 'tahun','m' => 'bulan','w' => 'minggu','d' => 'hari','h' => 'jam','i' => 'menit','s' => 'detik'];
    foreach ($string as $k => &$v) {
        if ($diff->$k) $v = $diff->$k . ' ' . $v;
        else unset($string[$k]);
    }
    return $string ? implode(', ', array_slice($string, 0, 1)) . ' yang lalu' : 'baru saja';
}

$result = $db->query("SELECT * FROM tiket_ai WHERE status = 'pending' OR status LIKE 'awaiting_%' ORDER BY id DESC");
$initial_tickets = [];
$latest_id = 0;
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $initial_tickets[] = $row;
    }
    $latest_id = $initial_tickets[0]['id']; // Ambil ID paling baru untuk auto-refresh
}
$pops = ['rajeg', 'kemeri', 'mauk', 'cianjur', 'brebes', 'sengon', 'grinting'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dasbor Tiket Real-Time</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
        body { background-color: #f0f2f5; }
        .ticket-form-row { background-color: #fff; border: 1px solid #e9ecef; border-radius: .5rem; transition: all .2s ease-in-out; }
        .ticket-form-row.awaiting-data { border-left: 5px solid #ffc107; }
        .ticket-form-row:hover { box-shadow: 0 .5rem 1.5rem rgba(0,0,0,.08); transform: translateY(-2px); }
        .info-item { margin-top: 8px; font-size: 0.9rem; }
        .copy-btn { cursor: pointer; color: #6c757d; }
        .copy-btn:hover { color: #0d6efd; }
        .keluhan-box { background-color: #f8f9fa; border: 1px solid #e9ecef; padding: 10px; border-radius: 5px; font-size: 0.9rem; white-space: pre-wrap; }
        .header-row { font-weight: 600; color: #495057; background-color: transparent; border-bottom: 2px solid #dee2e6; }
        .form-control-sm, .form-select-sm { font-size: 0.9rem; }
        .new-ticket-animation { animation: fadeInAndHighlight 1.5s ease-out; }
        @keyframes fadeInAndHighlight { 0% { background-color: #fff3cd; opacity: 0; transform: translateY(-20px); } 50% { background-color: #fff3cd; opacity: 1; transform: translateY(0); } 100% { background-color: #ffffff; } }
        .toast-container { z-index: 1100; }
    </style>
</head>
<body>
<div class="toast-container position-fixed bottom-0 end-0 p-3">
  <div id="copyToast" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
    <div class="toast-header">
      <i class="fa-solid fa-check-circle text-success me-2"></i>
      <strong class="me-auto">Berhasil</strong>
      <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
    </div>
    <div class="toast-body">
      Teks berhasil disalin!
    </div>
  </div>
</div>

<div class="container-fluid p-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="mb-0 text-primary-emphasis"><i class="fa-solid fa-bolt me-2"></i> Dasbor Tiket Real-Time</h3>
        <div class="text-muted d-flex align-items-center">
            <div id="loading-indicator" class="spinner-grow spinner-grow-sm text-success me-2" role="status" style="display: none;"></div>
            <span id="last-updated" class="small">Terhubung</span>
        </div>
    </div>
    
    <div id="notification-area">
        <?php if (isset($_GET['success'])): ?><div class="alert alert-success alert-dismissible fade show" role="alert"><?= htmlspecialchars($_GET['success']) ?><button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div><?php endif; ?>
        <?php if (isset($_GET['error'])): ?><div class="alert alert-danger alert-dismissible fade show" role="alert"><?= htmlspecialchars($_GET['error']) ?><button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div><?php endif; ?>
    </div>

    <div class="row header-row d-none d-md-flex p-2 mb-2">
        <div class="col-md-3">Pelanggan (Bisa Diedit)</div>
        <div class="col-md-3">Keluhan</div>
        <div class="col-md-2">Waktu</div>
        <div class="col-md-2">POP</div>
        <div class="col-md-2">Aksi</div>
    </div>
    
    <div id="ticket-list">
        <?php if (empty($initial_tickets)): ?>
            <div id="no-tickets" class="text-center p-5 bg-white rounded shadow-sm mt-3">
                <i class="fa-solid fa-check-circle fa-3x text-success mb-3"></i>
                <h4>Semua tiket sudah diproses!</h4>
                <p class="text-muted">Menunggu tiket baru masuk secara otomatis...</p>
            </div>
        <?php else: ?>
            <?php foreach ($initial_tickets as $row): ?>
                <?= generate_ticket_html($row, $pops) ?>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?php
function generate_ticket_html($row, $pops) {
    $row_class = (strpos($row['status'], 'awaiting') !== false) ? 'awaiting-data' : '';
    $time_ago = time_ago($row['created_at']);
    $pop_options_html = '';
    foreach ($pops as $pop) {
        $selected = (isset($row['pop']) && $row['pop'] == $pop) ? 'selected' : '';
        $pop_options_html .= "<option value='" . htmlspecialchars($pop) . "' " . $selected . ">" . ucfirst(htmlspecialchars($pop)) . "</option>";
    }
    
    $html = "
    <form class='ticket-form-row p-3 mb-3 {$row_class}' method='post' action='submit_ai.php'>
        <input type='hidden' name='ai_id' value='{$row['id']}'>
        <input type='hidden' name='whatsapp' value='" . htmlspecialchars($row['whatsapp']) . "'>
        <input type='hidden' name='keluhan' value='" . htmlspecialchars($row['keluhan']) . "'>
        <input type='hidden' name='maps_url' value='" . htmlspecialchars($row['maps_url']) . "'>
        
        <div class='row align-items-center'>
            <div class='col-md-3'>
                <input type='text' name='nama' class='form-control form-control-sm mb-1' value='" . htmlspecialchars($row['nama']) . "' placeholder='Nama Pelanggan' required>
                <textarea name='alamat' class='form-control form-control-sm' rows='2' placeholder='Alamat Pelanggan' required>" . htmlspecialchars($row['alamat']) . "</textarea>
                <div class='info-item'>
                    <i class='fa-brands fa-whatsapp me-1'></i>
                    <a href='https://wa.me/" . preg_replace('/[^0-9]/', '', $row['whatsapp']) . "' target='_blank'>" . htmlspecialchars($row['whatsapp']) . "</a>
                    <i class='fa-regular fa-copy copy-btn ms-1' title='Salin WA' onclick='copyToClipboard(\"" . htmlspecialchars($row['whatsapp']) . "\")'></i>
                </div>
            </div>
            <div class='col-md-3'><div class='keluhan-box'>" . nl2br(htmlspecialchars($row['keluhan'])) . "</div></div>
            <div class='col-md-2'><span class='text-muted small' title='{$row['created_at']}'>{$time_ago}</span></div>
            <div class='col-md-2'>
                <select name='pop' required class='form-select form-select-sm'>
                    <option value=''>- Pilih POP -</option>
                    {$pop_options_html}
                </select>
            </div>
            <div class='col-md-2'>
                <button type='submit' name='action' value='process' class='btn btn-sm btn-success w-100 mb-1'><i class='fa-solid fa-share-from-square me-1'></i> Kirim</button>
                <a href='submit_ai.php?action=reject&id={$row['id']}' onclick='return confirm(\"Anda yakin ingin menolak tiket ini?\");' class='btn btn-sm btn-outline-danger w-100'><i class='fa-solid fa-trash-can me-1'></i> Tolak</a>
            </div>
        </div>
    </form>";
    return $html;
}
?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    let latestId = <?= $latest_id ?>;
    const pops = <?= json_encode($pops) ?>;
    const toastEl = document.getElementById('copyToast');
    const toast = new bootstrap.Toast(toastEl);

    function copyToClipboard(text) {
        navigator.clipboard.writeText(text).then(() => {
            toast.show();
        });
    }

    function timeAgo(dateString) {
        // Implementasi sederhana time_ago di JS untuk tiket baru
        const seconds = Math.floor((new Date() - new Date(dateString)) / 1000);
        if(seconds < 60) return "baru saja";
        if(seconds < 3600) return Math.floor(seconds / 60) + " menit yang lalu";
        // ... bisa ditambahkan untuk jam, hari, dst.
        return new Date(dateString).toLocaleTimeString('id-ID');
    }
    
    function generateTicketHTML(row) {
        const rowClass = row.status.includes('awaiting') ? 'awaiting-data' : '';
        let popOptionsHtml = '';
        pops.forEach(pop => {
            const selected = row.pop === pop ? 'selected' : '';
            popOptionsHtml += `<option value="${pop}" ${selected}>${pop.charAt(0).toUpperCase() + pop.slice(1)}</option>`;
        });

        // Menggunakan backticks (`) untuk multi-line string di JavaScript
        return `
        <form class="ticket-form-row p-3 mb-3 ${rowClass} new-ticket-animation" method="post" action="submit_ai.php">
            <input type="hidden" name="ai_id" value="${row.id}">
            <input type="hidden" name="whatsapp" value="${row.whatsapp}">
            <input type="hidden" name="keluhan" value="${row.keluhan}">
            <input type="hidden" name="maps_url" value="${row.maps_url || ''}">
            <div class="row align-items-center">
                <div class="col-md-3">
                    <input type="text" name="nama" class="form-control form-control-sm mb-1" value="${row.nama}" placeholder="Nama Pelanggan" required>
                    <textarea name="alamat" class="form-control form-control-sm" rows="2" placeholder="Alamat Pelanggan" required>${row.alamat}</textarea>
                    <div class="info-item">
                        <i class="fa-brands fa-whatsapp me-1"></i>
                        <a href="https://wa.me/${row.whatsapp.replace(/[^0-9]/g, '')}" target="_blank">${row.whatsapp}</a>
                        <i class="fa-regular fa-copy copy-btn ms-1" title="Salin WA" onclick='copyToClipboard("${row.whatsapp}")'></i>
                    </div>
                </div>
                <div class="col-md-3"><div class="keluhan-box">${row.keluhan.replace(/\n/g, '<br>')}</div></div>
                <div class="col-md-2"><span class="text-muted small" title="${row.created_at}">${timeAgo(row.created_at)}</span></div>
                <div class="col-md-2">
                    <select name="pop" required class="form-select form-select-sm">
                        <option value="">- Pilih POP -</option>
                        ${popOptionsHtml}
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" name="action" value="process" class="btn btn-sm btn-success w-100 mb-1"><i class="fa-solid fa-share-from-square me-1"></i> Kirim</button>
                    <a href="submit_ai.php?action=reject&id=${row.id}" onclick="return confirm('Anda yakin ingin menolak tiket ini?');" class="btn btn-sm btn-outline-danger w-100"><i class="fa-solid fa-trash-can me-1"></i> Tolak</a>
                </div>
            </div>
        </form>`;
    }

    async function fetchNewTickets() {
        document.getElementById('loading-indicator').style.display = 'inline-block';
        try {
            const response = await fetch(`fetch_tickets.php?since=${latestId}`);
            const newTickets = await response.json();

            if (newTickets.length > 0) {
                const ticketList = document.getElementById('ticket-list');
                const noTicketsEl = document.getElementById('no-tickets');
                if(noTicketsEl) noTicketsEl.remove();

                newTickets.forEach(ticket => {
                    const ticketHtml = generateTicketHTML(ticket);
                    ticketList.insertAdjacentHTML('afterbegin', ticketHtml);
                    if (ticket.id > latestId) {
                        latestId = ticket.id;
                    }
                });
                document.title = `(${newTickets.length}) Tiket Baru! - Dasbor Real-Time`;
            } else {
                 document.title = "Dasbor Tiket Real-Time";
            }
        } catch (error) {
            console.error("Gagal mengambil tiket baru:", error);
        } finally {
            document.getElementById('loading-indicator').style.display = 'none';
        }
    }
    
    // Cek tiket baru setiap 15 detik
    setInterval(fetchNewTickets, 15000);

</script>
</body>
</html>