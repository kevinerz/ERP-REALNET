<?php
require_once __DIR__ . '/config/database.php';
// ==========================================
// CETAK KARTU NAMA - NO QR VERSION
// ==========================================
ini_set('display_errors', 1);
error_reporting(E_ALL);

// 1. KONEKSI DATABASE
$conn = getErpDbConnection();
if ($conn->connect_error) die("Koneksi Gagal: " . $conn->connect_error);
$conn->set_charset("utf8mb4");

// 2. AMBIL PARAMETER
$id       = (int)($_GET['id'] ?? 0);
$multiple = max(1, min(10, (int)($_GET['multiple'] ?? 10))); 

// 3. QUERY DATA
$stmt = $conn->prepare("SELECT * FROM hr_karyawan WHERE id=?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$k = $result->fetch_assoc();

if (!$k) die("Data tidak ditemukan.");

// 4. PREPARE DATA
function e($val) { return htmlspecialchars((string)$val, ENT_QUOTES, 'UTF-8'); }

$data = [
    'nama'      => e($k['nama']),
    'jabatan'   => e($k['jabatan']),
    'divisi'    => e($k['divisi']),
    'nik'       => e($k['nik']),
    'email'     => e($k['email']),
    'telp'      => e($k['no_telp']),
    'avatar'    => $k['avatar'] 
];

// Fallback Avatar
$avatarUrl = !empty($data['avatar']) ? $data['avatar'] : "https://ui-avatars.com/api/?name=".urlencode($data['nama'])."&background=f8fafc&color=1e293b&size=300&bold=true";

?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cetak ID Card - <?= $data['nama'] ?></title>
    
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&family=Poppins:wght@600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

    <style>
        /* --- CONFIG --- */
        :root {
            --card-width: 90mm;
            --card-height: 54mm;
            --navy-dark: #0f172a; 
            --navy-light: #1e293b;
            --accent: #3b82f6;
            --text-dark: #334155;
            --text-muted: #64748b;
        }
        
        * { box-sizing: border-box; -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
        
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f1f5f9;
            margin: 0;
            display: flex;
            flex-direction: column;
            align-items: center;
            min-height: 100vh;
        }

        /* --- CONTROL BAR --- */
        .no-print-bar {
            width: 100%;
            background: white;
            padding: 15px 20px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.05);
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 999;
            margin-bottom: 20px;
        }
        .btn { padding: 8px 20px; border-radius: 50px; border: none; font-weight: 600; cursor: pointer; transition: 0.2s; font-size: 14px; }
        .btn-print { background: var(--navy-dark); color: white; }
        .btn-print:hover { background: var(--accent); }

        /* --- SHEET CONTAINER --- */
        .sheet-container {
            background: white;
            width: 210mm; /* A4 */
            min-height: 297mm; 
            padding: 10mm; 
            box-shadow: 0 0 25px rgba(0,0,0,0.1);
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            grid-auto-rows: max-content;
            gap: 5mm; 
            justify-items: center;
        }

        /* --- ID CARD LAYOUT --- */
        .id-card {
            width: var(--card-width);
            height: var(--card-height);
            background: white;
            border-radius: 12px;
            overflow: hidden;
            display: flex;
            position: relative;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            border: 1px solid #e2e8f0;
        }

        /* SIDEBAR (KIRI) */
        .card-sidebar {
            width: 35%;
            background: linear-gradient(160deg, var(--navy-dark) 0%, var(--navy-light) 100%);
            display: flex;
            align-items: center;     /* Center Vertikal */
            justify-content: center; /* Center Horizontal */
            padding: 10px;
            position: relative;
        }
        
        /* Pattern Overlay */
        .card-sidebar::before {
            content: "";
            position: absolute;
            top: 0; left: 0; width: 100%; height: 100%;
            background-image: radial-gradient(rgba(255,255,255,0.1) 1px, transparent 1px);
            background-size: 10px 10px;
            opacity: 0.3;
        }

        /* Avatar Container - Diperbesar sedikit karena QR hilang */
        .avatar-container {
            width: 75px;  /* Lebih besar dari sebelumnya (65px) */
            height: 75px;
            border-radius: 50%;
            border: 4px solid rgba(255,255,255,0.2);
            padding: 3px;
            z-index: 2;
        }

        .card-avatar {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            object-fit: cover;
            background: #fff;
        }

        /* CONTENT (KANAN) */
        .card-content {
            flex: 1;
            padding: 15px 18px; /* Padding sedikit ditambah */
            display: flex;
            flex-direction: column;
        }

        .header-company {
            border-bottom: 2px solid #f1f5f9;
            padding-bottom: 6px;
            margin-bottom: 10px;
        }
        .company-name {
            font-family: 'Poppins', sans-serif;
            font-size: 9px;
            font-weight: 800;
            color: var(--navy-dark);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .company-tagline {
            font-size: 6px;
            color: var(--accent);
            font-weight: 600;
            text-transform: uppercase;
        }

        .info-main {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .karyawan-nama {
            font-family: 'Poppins', sans-serif;
            font-size: 14px;
            font-weight: 700;
            color: var(--navy-dark);
            line-height: 1.2;
            margin-bottom: 4px;
            text-transform: uppercase;
        }

        .karyawan-jabatan {
            font-size: 8px;
            color: white;
            background: var(--accent);
            padding: 3px 10px;
            border-radius: 12px;
            display: inline-block;
            font-weight: 600;
            margin-bottom: 8px;
        }

        .karyawan-nik {
            font-family: monospace;
            font-size: 9px;
            color: var(--text-muted);
            letter-spacing: 1px;
        }

        .footer-contact {
            margin-top: auto;
            display: flex;
            flex-direction: column;
            gap: 3px;
            font-size: 7.5px;
            color: var(--text-dark);
        }
        .contact-item { display: flex; align-items: center; gap: 6px; }
        .contact-item i { color: var(--accent); font-size: 9px; }

        /* --- PRINT --- */
        @media print {
            body { background: white; }
            .no-print-bar { display: none !important; }
            .sheet-container {
                width: 100%;
                box-shadow: none;
                margin: 0;
                padding: 10mm;
            }
            .id-card {
                border: 1px dashed #ccc;
                page-break-inside: avoid;
                box-shadow: none;
            }
            @page { size: A4 portrait; margin: 0; }
        }
    </style>
</head>
<body>

    <div class="no-print-bar">
        <div>
            <h3 style="margin:0; color:#0f172a">Preview ID Card</h3>
            <span style="font-size:12px; color:#64748b">PT Real Data Solusindo (No QR)</span>
        </div>
        <div>
            <label style="font-size:12px; margin-right:5px">Jumlah:</label>
            <input type="number" id="qty" value="<?= $multiple ?>" min="1" max="10" style="padding:5px; border:1px solid #ccc; width:50px; border-radius:5px; text-align:center">
            <button onclick="window.print()" class="btn btn-print"><i class="bi bi-printer-fill me-2"></i> Cetak</button>
        </div>
    </div>

    <div class="sheet-container" id="container"></div>

<script>
    const data = {
        nama: "<?= $data['nama'] ?>",
        jabatan: "<?= $data['jabatan'] ?>",
        nik: "<?= $data['nik'] ?>",
        telp: "<?= $data['telp'] ?>",
        email: "<?= $data['email'] ?>",
        avatar: "<?= $avatarUrl ?>"
    };

    function cardTemplate() {
        return `
        <div class="id-card">
            <div class="card-sidebar">
                <div class="avatar-container">
                    <img src="${data.avatar}" class="card-avatar" alt="Foto">
                </div>
            </div>
            
            <div class="card-content">
                <div class="header-company">
                    <div class="company-name">PT Real Data Solusindo</div>
                    <div class="company-tagline">Internet Service Provider</div>
                </div>

                <div class="info-main">
                    <div class="karyawan-nama">${data.nama}</div>
                    <div><span class="karyawan-jabatan">${data.jabatan}</span></div>
                    <div class="karyawan-nik">ID: ${data.nik}</div>
                </div>

                <div class="footer-contact">
                    <div class="contact-item">
                        <i class="bi bi-telephone-fill"></i> ${data.telp}
                    </div>
                    <div class="contact-item">
                        <i class="bi bi-envelope-at-fill"></i> ${data.email}
                    </div>
                    <div class="contact-item">
                        <i class="bi bi-geo-alt-fill"></i> Madiun, Jawa Timur
                    </div>
                </div>
            </div>
        </div>
        `;
    }

    function render() {
        const qty = document.getElementById('qty').value;
        const container = document.getElementById('container');
        let html = '';
        for(let i=0; i<qty; i++) {
            html += cardTemplate();
        }
        container.innerHTML = html;
    }

    render();
    document.getElementById('qty').addEventListener('change', render);
</script>

</body>
</html>

<?php $conn->close(); ?>