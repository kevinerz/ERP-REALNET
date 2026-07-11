<?php
require_once __DIR__ . '/config/database.php';
date_default_timezone_set('Asia/Jakarta');

// ===================
// DATABASE
// ===================
$servername = "localhost";
$username   = "u272457353_kevinsamsung99";
$password   = "Admionkevin99";
$database   = "u272457353_umumdata";

$conn = getErpDbConnection();
if ($conn->connect_error) die("DB Error: " . $conn->connect_error);
$conn->set_charset("utf8mb4");

// ===================
// FUNGSI LOG
// ===================
function addLog($c, $sn, $aksi, $idk, $nama, $ket) {
    $w = date("Y-m-d H:i:s");
    $l = $c->prepare("
        INSERT INTO jaringan_modem_log(serial_number, aksi, id_karyawan, nama_karyawan, waktu, keterangan)
        VALUES(?, ?, ?, ?, ?, ?)");
    $l->bind_param("ssisss", $sn, $aksi, $idk, $nama, $w, $ket);
    $l->execute();
    $l->close();
}

// ===================
// AJAX HANDLER
// ===================
if (isset($_POST["ajax"])) {
    header('Content-Type: application/json');
    
    $sn   = strtoupper(trim($_POST["sn"] ?? ''));
    $aksi = $_POST["aksi"] ?? '';
    $idk  = intval($_POST["idk"] ?? 0);
    $now  = date("Y-m-d H:i:s");

    if (empty($sn)) {
        echo json_encode(['status' => 'error', 'message' => 'Serial Number kosong']);
        exit;
    }

    // Ambil nama teknisi
    $q = $conn->prepare("SELECT nama FROM hr_karyawan WHERE id=? LIMIT 1");
    $q->bind_param("i", $idk);
    $q->execute();
    $q->bind_result($nama_karyawan);
    $q->fetch();
    $q->close();

    if (!$nama_karyawan) {
        echo json_encode(['status' => 'error', 'message' => 'Karyawan tidak ditemukan']);
        exit;
    }

    // Cek modem
    $cek = $conn->prepare("SELECT id_modem FROM jaringan_modem WHERE serial_number=?");
    $cek->bind_param("s", $sn);
    $cek->execute();
    $result = $cek->get_result();
    $modem = $result->fetch_assoc();
    $cek->close();

    // =============== AMBIL ===============
    if ($aksi === "ambil") {
        if ($modem) {
            $u = $conn->prepare("
                UPDATE jaringan_modem SET 
                    status='dibawa',
                    tanggal_keluar=?,
                    id_karyawan_keluar=?,
                    lokasi_penyimpanan=?
                WHERE serial_number=?
            ");
            $u->bind_param("siss", $now, $idk, $nama_karyawan, $sn);
            $u->execute();
            $u->close();

            addLog($conn, $sn, "ambil", $idk, $nama_karyawan, "Modem diambil");

            echo json_encode([
                'status' => 'success',
                'message' => "✅ $sn → Diambil oleh $nama_karyawan",
                'type' => 'out'
            ]);
        } else {
            $model = "GPON";
            $merk  = (substr($sn, 0, 2) === "ZT") ? "ZTE" : "";

            $i = $conn->prepare("
                INSERT INTO jaringan_modem
                (serial_number, model, merk, status, tanggal_keluar, id_karyawan_keluar, lokasi_penyimpanan)
                VALUES (?, ?, ?, 'dibawa', ?, ?, ?)
            ");
            $i->bind_param("ssssis", $sn, $model, $merk, $now, $idk, $nama_karyawan);
            $i->execute();
            $i->close();

            addLog($conn, $sn, "ambil", $idk, $nama_karyawan, "Modem baru");

            echo json_encode([
                'status' => 'success',
                'message' => "🆕 $sn (BARU) → $nama_karyawan",
                'type' => 'out'
            ]);
        }
        exit;
    }

    // =============== KEMBALIKAN ===============
    if ($aksi === "kembalikan") {
        if (!$modem) {
            echo json_encode(['status' => 'error', 'message' => '❌ SN tidak ditemukan']);
            exit;
        }

        $u = $conn->prepare("
            UPDATE jaringan_modem SET
                status='tersedia',
                tanggal_masuk=?,
                lokasi_penyimpanan='GUDANG',
                id_karyawan_keluar=NULL
            WHERE serial_number=?
        ");
        $u->bind_param("ss", $now, $sn);
        $u->execute();
        $u->close();

        addLog($conn, $sn, "kembalikan", $idk, $nama_karyawan, "Kembali gudang");

        echo json_encode([
            'status' => 'success',
            'message' => "✅ $sn → Kembali ke GUDANG",
            'type' => 'in'
        ]);
        exit;
    }

    echo json_encode(['status' => 'error', 'message' => 'Aksi tidak valid']);
    exit;
}

// ===================
// LIST TEKNISI
// ===================
$teknisi = $conn->query("
    SELECT id, nama
    FROM hr_karyawan
    WHERE status_aktif = 1
      AND (divisi='Teknisi'
           OR divisi='Leader Area'
           OR jabatan LIKE '%Teknisi%'
           OR jabatan LIKE '%Lapangan%')
    ORDER BY nama ASC
");

$conn->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Modem Scanner</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

<style>
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    height: 100vh;
    overflow: hidden;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}

.container-fluid {
    height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
}

.main-grid {
    display: grid;
    grid-template-columns: 450px 1fr;
    gap: 20px;
    width: 100%;
    max-width: 1600px;
    height: calc(100vh - 40px);
}

.control-panel {
    background: white;
    border-radius: 20px;
    padding: 30px;
    box-shadow: 0 20px 60px rgba(0,0,0,0.3);
    display: flex;
    flex-direction: column;
}

.terminal {
    background: #0d1117;
    border: 3px solid #30363d;
    border-radius: 20px;
    padding: 25px;
    color: #00ff00;
    font-family: 'Consolas', 'Monaco', monospace;
    font-size: 16px;
    overflow-y: auto;
    box-shadow: 0 20px 60px rgba(0,0,0,0.5);
    position: relative;
}

.terminal::-webkit-scrollbar {
    width: 12px;
}

.terminal::-webkit-scrollbar-track {
    background: #1a1e24;
    border-radius: 10px;
}

.terminal::-webkit-scrollbar-thumb {
    background: #30363d;
    border-radius: 10px;
}

.header {
    text-align: center;
    margin-bottom: 25px;
}

.header h2 {
    font-size: 1.8rem;
    font-weight: 700;
    color: #667eea;
    margin-bottom: 5px;
}

.live-badge {
    display: inline-block;
    padding: 5px 15px;
    background: #10b981;
    color: white;
    border-radius: 20px;
    font-size: 0.85rem;
    font-weight: 600;
}

.live-dot {
    display: inline-block;
    width: 8px;
    height: 8px;
    background: white;
    border-radius: 50%;
    margin-right: 5px;
    animation: blink 1.5s infinite;
}

@keyframes blink {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.3; }
}

.form-group {
    margin-bottom: 20px;
}

.form-label {
    font-weight: 600;
    color: #333;
    margin-bottom: 8px;
    display: block;
    font-size: 0.95rem;
}

.form-select, .form-control {
    width: 100%;
    padding: 14px;
    border: 2px solid #e2e8f0;
    border-radius: 10px;
    font-size: 1.05rem;
    transition: all 0.3s;
}

.form-select:focus, .form-control:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.scan-input {
    font-family: 'Consolas', monospace;
    font-size: 1.3rem;
    text-transform: uppercase;
    letter-spacing: 1px;
    font-weight: 600;
}

.btn-action {
    width: 100%;
    padding: 16px;
    font-size: 1.1rem;
    font-weight: 600;
    border: none;
    border-radius: 12px;
    cursor: pointer;
    transition: all 0.3s;
    margin-top: auto;
}

.btn-ambil {
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
}

.btn-kembalikan {
    background: linear-gradient(135deg, #10b981, #059669);
    color: white;
}

.btn-action:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 30px rgba(0,0,0,0.3);
}

.log-line {
    padding: 12px 15px;
    margin: 8px 0;
    border-left: 4px solid transparent;
    border-radius: 5px;
    animation: slideIn 0.3s ease-out;
}

@keyframes slideIn {
    from {
        opacity: 0;
        transform: translateX(-20px);
    }
    to {
        opacity: 1;
        transform: translateX(0);
    }
}

.log-success {
    color: #00ff00;
    border-left-color: #00ff00;
    background: rgba(0, 255, 0, 0.1);
}

.log-error {
    color: #ff4444;
    border-left-color: #ff4444;
    background: rgba(255, 68, 68, 0.1);
}

.log-info {
    color: #87cefa;
    border-left-color: #87cefa;
}

.log-out {
    color: #fbbf24;
    border-left-color: #fbbf24;
    background: rgba(251, 191, 36, 0.1);
}

.log-in {
    color: #34d399;
    border-left-color: #34d399;
    background: rgba(52, 211, 153, 0.1);
}

.terminal-header {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    background: #21262d;
    padding: 12px 20px;
    border-radius: 20px 20px 0 0;
    display: flex;
    align-items: center;
    gap: 8px;
    color: #8b949e;
    font-size: 0.9rem;
}

.terminal-dot {
    width: 12px;
    height: 12px;
    border-radius: 50%;
}

.dot-red { background: #ff5f56; }
.dot-yellow { background: #ffbd2e; }
.dot-green { background: #27c93f; }

.terminal-body {
    margin-top: 50px;
}

.status-toast {
    position: fixed;
    top: 30px;
    right: 30px;
    padding: 20px 30px;
    border-radius: 15px;
    font-weight: 600;
    font-size: 1.1rem;
    box-shadow: 0 10px 40px rgba(0,0,0,0.3);
    z-index: 9999;
    animation: slideInRight 0.3s ease-out;
    display: none;
}

@keyframes slideInRight {
    from {
        opacity: 0;
        transform: translateX(100px);
    }
    to {
        opacity: 1;
        transform: translateX(0);
    }
}

.toast-success {
    background: #10b981;
    color: white;
}

.toast-error {
    background: #ef4444;
    color: white;
}

/* Responsive */
@media (max-width: 1024px) {
    .main-grid {
        grid-template-columns: 1fr;
        grid-template-rows: auto 1fr;
        height: calc(100vh - 40px);
    }
    
    .control-panel {
        padding: 20px;
    }
    
    .terminal {
        min-height: 400px;
    }
}

@media (max-width: 768px) {
    .container-fluid {
        padding: 10px;
    }
    
    .main-grid {
        gap: 10px;
        height: calc(100vh - 20px);
    }
    
    .control-panel {
        padding: 15px;
    }
    
    .header h2 {
        font-size: 1.4rem;
    }
    
    .scan-input {
        font-size: 1.1rem;
    }
    
    .terminal {
        padding: 15px;
        font-size: 14px;
    }
}
</style>

</head>
<body>

<div class="container-fluid">
    <div class="main-grid">
        
        <!-- Control Panel -->
        <div class="control-panel">
            <div class="header">
                <h2><i class="bi bi-upc-scan"></i> MODEM SCANNER</h2>
                <div class="live-badge">
                    <span class="live-dot"></span>LIVE MODE
                </div>
            </div>

            <div class="form-group">
                <label class="form-label"><i class="bi bi-toggles"></i> Aktivitas</label>
                <select class="form-select" id="aksi" onchange="updateButton()">
                    <option value="ambil">📤 Ambil Modem (Keluar)</option>
                    <option value="kembalikan">📥 Kembalikan Modem (Masuk)</option>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label"><i class="bi bi-person"></i> Teknisi</label>
                <select class="form-select" id="id_teknisi">
                    <?php while($t = $teknisi->fetch_assoc()): ?>
                        <option value="<?= $t['id'] ?>"><?= $t['nama'] ?></option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label"><i class="bi bi-upc"></i> Serial Number</label>
                <input type="text" 
                       class="form-control scan-input" 
                       id="serial_number" 
                       placeholder="SCAN..."
                       autocomplete="off"
                       autofocus>
            </div>

            <button class="btn-action btn-ambil" id="btnAction" onclick="manualProcess()">
                <i class="bi bi-send"></i> PROSES
            </button>
        </div>

        <!-- Terminal -->
        <div class="terminal">
            <div class="terminal-header">
                <span class="terminal-dot dot-red"></span>
                <span class="terminal-dot dot-yellow"></span>
                <span class="terminal-dot dot-green"></span>
                <span class="ms-2">terminal@warehouse:~$</span>
            </div>
            <div class="terminal-body" id="logBox">
                <div class="log-info">🚀 System ready. Scan barcode to start...</div>
            </div>
        </div>

    </div>
</div>

<!-- Toast Notification -->
<div class="status-toast" id="toast"></div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
let buffer = "";
let lastKeyTime = 0;
let scanCount = 0;

// Update button style based on action
function updateButton() {
    const aksi = document.getElementById('aksi').value;
    const btn = document.getElementById('btnAction');
    
    if (aksi === 'ambil') {
        btn.className = 'btn-action btn-ambil';
        btn.innerHTML = '<i class="bi bi-box-arrow-right"></i> AMBIL MODEM';
    } else {
        btn.className = 'btn-action btn-kembalikan';
        btn.innerHTML = '<i class="bi bi-box-arrow-in-left"></i> KEMBALIKAN MODEM';
    }
}

// Enter key on input
document.getElementById("serial_number").addEventListener("keyup", function(e) {
    if (e.key === "Enter") {
        let sn = this.value.trim().toUpperCase();
        if (sn) {
            this.value = "";
            processScan(sn);
        }
    }
});

// Barcode scanner auto-detect
document.addEventListener("keydown", function(e) {
    const activeEl = document.activeElement;
    
    if (activeEl.tagName === 'INPUT' || 
        activeEl.tagName === 'SELECT') {
        return;
    }

    const now = Date.now();

    if (now - lastKeyTime > 100) {
        buffer = "";
    }
    lastKeyTime = now;

    if (e.key === "Enter") {
        let sn = buffer.trim().toUpperCase();
        buffer = "";
        if (sn.length > 3) {
            processScan(sn);
        }
    } else if (e.key.length === 1) {
        buffer += e.key;
    }
});

// Process scan
function processScan(sn) {
    if (!sn) return;

    const aksi = document.getElementById("aksi").value;
    const idk = document.getElementById("id_teknisi").value;

    scanCount++;
    appendLog(`[#${scanCount}] Processing ${sn}...`, 'info');
    playBeep();

    fetch("", {
        method: "POST",
        headers: {"Content-Type": "application/x-www-form-urlencoded"},
        body: new URLSearchParams({
            ajax: 1,
            sn: sn,
            aksi: aksi,
            idk: idk
        })
    })
    .then(r => r.json())
    .then(data => {
        if (data.status === 'success') {
            appendLog(data.message, data.type === 'out' ? 'out' : 'in');
            showToast(data.message, 'success');
        } else {
            appendLog(data.message, 'error');
            showToast(data.message, 'error');
        }
        document.getElementById('serial_number').focus();
    })
    .catch(err => {
        appendLog(`❌ Error: ${err.message}`, 'error');
        showToast('Connection Error', 'error');
    });
}

// Manual process
function manualProcess() {
    const sn = document.getElementById('serial_number').value.trim().toUpperCase();
    if (sn) {
        document.getElementById('serial_number').value = '';
        processScan(sn);
    } else {
        showToast('⚠️ Serial Number kosong', 'error');
    }
}

// Append log
function appendLog(text, type = 'info') {
    const log = document.getElementById("logBox");
    const timestamp = new Date().toLocaleTimeString('id-ID');
    const div = document.createElement('div');
    div.className = `log-line log-${type}`;
    div.textContent = `[${timestamp}] ${text}`;
    
    log.insertBefore(div, log.firstChild.nextSibling);
    
    while (log.children.length > 50) {
        log.removeChild(log.lastChild);
    }
}

// Show toast
function showToast(message, type) {
    const toast = document.getElementById('toast');
    toast.textContent = message;
    toast.className = `status-toast toast-${type}`;
    toast.style.display = 'block';
    
    setTimeout(() => {
        toast.style.display = 'none';
    }, 3000);
}

// Play beep
function playBeep() {
    try {
        const context = new (window.AudioContext || window.webkitAudioContext)();
        const oscillator = context.createOscillator();
        const gainNode = context.createGain();
        
        oscillator.connect(gainNode);
        gainNode.connect(context.destination);
        
        oscillator.frequency.value = 800;
        oscillator.type = 'sine';
        
        gainNode.gain.setValueAtTime(0.3, context.currentTime);
        gainNode.gain.exponentialRampToValueAtTime(0.01, context.currentTime + 0.1);
        
        oscillator.start(context.currentTime);
        oscillator.stop(context.currentTime + 0.1);
    } catch(e) {}
}

// Auto focus
document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('serial_number').focus();
    updateButton();
});

document.addEventListener('click', function(e) {
    if (e.target.tagName !== 'INPUT' && 
        e.target.tagName !== 'SELECT' && 
        e.target.tagName !== 'BUTTON') {
        document.getElementById('serial_number').focus();
    }
});
</script>

</body>
</html>