<?php
// privacy.php
header('Content-Type: text/html; charset=UTF-8');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Kebijakan Privasi - MyRealtek</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Kebijakan Privasi aplikasi MyRealtek.">
    <style>
        :root {
            color-scheme: light dark;
        }
        body {
            font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            margin: 0;
            padding: 0;
            line-height: 1.6;
            background: #f5f5f5;
            color: #222;
        }
        .container {
            max-width: 900px;
            margin: 0 auto;
            padding: 24px 16px 40px;
        }
        .card {
            background: #ffffff;
            border-radius: 12px;
            padding: 24px 20px;
            box-shadow: 0 4px 16px rgba(0,0,0,0.06);
        }
        h1, h2, h3 {
            margin-top: 0;
            color: #1a237e;
        }
        h1 {
            font-size: 26px;
            margin-bottom: 8px;
        }
        h2 {
            font-size: 18px;
            margin-top: 24px;
        }
        h3 {
            font-size: 16px;
            margin-top: 18px;
        }
        p {
            margin: 8px 0;
            font-size: 14px;
        }
        ul {
            padding-left: 18px;
            margin: 6px 0 12px;
            font-size: 14px;
        }
        li {
            margin: 4px 0;
        }
        .badge {
            display: inline-block;
            font-size: 11px;
            padding: 4px 10px;
            border-radius: 999px;
            background: #e8eaf6;
            color: #1a237e;
            margin-bottom: 16px;
        }
        .section-meta {
            font-size: 12px;
            color: #666;
            margin-bottom: 16px;
        }
        .highlight {
            background: #e3f2fd;
            border-left: 3px solid #1e88e5;
            padding: 10px 12px;
            border-radius: 8px;
            font-size: 13px;
            margin: 12px 0 16px;
        }
        .footer {
            margin-top: 18px;
            font-size: 12px;
            color: #777;
            text-align: center;
        }
        @media (prefers-color-scheme: dark) {
            body {
                background: #121212;
                color: #e0e0e0;
            }
            .card {
                background: #1e1e1e;
                box-shadow: 0 4px 16px rgba(0,0,0,0.7);
            }
            h1, h2, h3 {
                color: #bbdefb;
            }
            .badge {
                background: #283593;
                color: #e8eaf6;
            }
            .section-meta {
                color: #b0bec5;
            }
            .highlight {
                background: #1a237e;
                border-left-color: #64b5f6;
            }
            .footer {
                color: #90a4ae;
            }
        }
        a {
            color: #1e88e5;
            text-decoration: none;
        }
        a:hover {
            text-decoration: underline;
        }
        strong {
            font-weight: 600;
        }
    </style>
</head>
<body>
<div class="container">
    <div class="card">
        <span class="badge">Kebijakan Privasi</span>
        <h1>MyRealtek</h1>
        <p class="section-meta">
            Terakhir diperbarui: <strong>27 November 2025</strong><br>
            Berlaku untuk aplikasi <strong>MyRealtek</strong> dan layanan terkait di bawah pengelolaan
            <strong>Data Real Solution / REALNET</strong>.
        </p>

        <div class="highlight">
            <strong>Penting:</strong> Dengan menggunakan aplikasi MyRealtek, Anda menyetujui pengumpulan
            dan penggunaan informasi sesuai dengan Kebijakan Privasi ini.
        </div>

        <h2>1. Informasi yang Kami Kumpulkan</h2>
        <p>Dalam penggunaan aplikasi MyRealtek, jenis data yang dapat kami kumpulkan antara lain:</p>
        <h3>a. Data Akun dan Identitas</h3>
        <ul>
            <li>Nama teknisi atau pengguna internal.</li>
            <li>Username atau ID login.</li>
            <li>Informasi hak akses (POP/area kerja, role, divisi).</li>
        </ul>

        <h3>b. Data Operasional dan Tiket</h3>
        <ul>
            <li>Informasi tiket gangguan pelanggan (nama pelanggan, alamat, keluhan, POP, VLAN, SN perangkat).</li>
            <li>Status penanganan tiket (belum dikerjakan, di proses, selesai) dan histori tindakan teknisi.</li>
            <li>Catatan aktivitas teknisi terkait penanganan gangguan.</li>
        </ul>

        <h3>c. Data Teknis</h3>
        <ul>
            <li>Informasi perangkat (tipe device, sistem operasi, versi aplikasi).</li>
            <li>Log error atau crash untuk keperluan debugging dan peningkatan kualitas aplikasi.</li>
            <li>Data analitik penggunaan aplikasi (melalui layanan pihak ketiga seperti Firebase Analytics, jika diaktifkan).</li>
        </ul>

        <h3>d. Data Tambahan</h3>
        <ul>
            <li>Nomor telepon/WhatsApp pelanggan yang digunakan untuk komunikasi teknisi melalui aplikasi.</li>
            <li>Tautan lokasi (Google Maps) jika disimpan dalam tiket untuk memudahkan kunjungan lapangan.</li>
        </ul>

        <h2>2. Cara Kami Menggunakan Informasi</h2>
        <p>Data yang dikumpulkan digunakan untuk tujuan:</p>
        <ul>
            <li>Mengelola tiket gangguan dan aktivitas teknisi di lapangan.</li>
            <li>Mempermudah komunikasi antara teknisi dan pelanggan (misalnya melalui WhatsApp dan Maps).</li>
            <li>Meningkatkan kualitas layanan internet dan operasional internal REALNET / Data Real Solution.</li>
            <li>Melakukan pemantauan performa aplikasi, perbaikan bug, dan pengembangan fitur baru.</li>
            <li>Memastikan keamanan sistem dan mencegah penyalahgunaan akun.</li>
        </ul>

        <h2>3. Dasar Pemrosesan Data</h2>
        <p>Kami memproses data berdasarkan:</p>
        <ul>
            <li><strong>Pelaksanaan kontrak/layanan</strong>: untuk menjalankan layanan teknis dan helpdesk.</li>
            <li><strong>Kepentingan sah (legitimate interest)</strong>: untuk meningkatkan layanan dan keamanan sistem.</li>
            <li><strong>Persetujuan</strong> (jika diwajibkan oleh hukum tertentu), khususnya untuk beberapa jenis analitik atau integrasi pihak ketiga.</li>
        </ul>

        <h2>4. Berbagi Informasi dengan Pihak Ketiga</h2>
        <p>Kami tidak menjual data pribadi kepada pihak ketiga. Namun, data dapat dibagikan dengan:</p>
        <ul>
            <li><strong>Tim internal REALNET / Data Real Solution</strong> yang berwenang (misalnya tim NOC, helpdesk, manajemen).</li>
            <li><strong>Penyedia layanan pihak ketiga</strong> yang membantu operasional sistem, seperti:
                <ul>
                    <li>Firebase (Firebase Cloud Messaging, Firebase Analytics).</li>
                    <li>Penyedia hosting dan server.</li>
                </ul>
            </li>
        </ul>
        <p>Kami akan berupaya memastikan pihak ketiga tersebut menjaga kerahasiaan dan keamanan data sesuai standar yang berlaku.</p>

        <h2>5. Penyimpanan dan Keamanan Data</h2>
        <ul>
            <li>Data disimpan di server yang dikelola atau ditunjuk oleh REALNET / Data Real Solution.</li>
            <li>Kami menerapkan upaya yang wajar secara teknis dan organisasional untuk melindungi data dari akses tidak sah, penyalahgunaan, atau kebocoran.</li>
            <li>Meski demikian, tidak ada sistem yang sepenuhnya bebas risiko. Pengguna tetap disarankan menjaga kerahasiaan kredensial login masing-masing.</li>
        </ul>

        <h2>6. Hak Pengguna (Teknisi/Internal)</h2>
        <p>Jika diperbolehkan oleh hukum yang berlaku, Anda dapat mengajukan permintaan:</p>
        <ul>
            <li>Melihat ringkasan data akun yang tersimpan.</li>
            <li>Memperbaiki data akun yang tidak akurat (melalui admin sistem).</li>
            <li>Meminta penghapusan atau pembatasan pemrosesan data tertentu, sepanjang tidak bertentangan dengan kebutuhan operasional dan kewajiban hukum.</li>
        </ul>
        <p>Permintaan dapat diajukan melalui kontak yang tercantum di bagian <strong>Kontak</strong>.</p>

        <h2>7. Data Pelanggan di Dalam Tiket</h2>
        <p>
            MyRealtek digunakan oleh teknisi/internal untuk mengelola tiket gangguan pelanggan.
            Data pelanggan (nama, alamat, nomor kontak, keluhan, dll.) hanya digunakan untuk keperluan
            penanganan gangguan dan peningkatan layanan ISP.
        </p>
        <p>
            Akses ke data ini dibatasi berdasarkan peran dan area kerja (POP), serta hanya boleh digunakan
            sesuai prosedur operasional yang berlaku di REALNET / Data Real Solution.
        </p>

        <h2>8. Penggunaan Layanan Pihak Ketiga</h2>
        <p>MyRealtek dapat menggunakan layanan pihak ketiga, misalnya:</p>
        <ul>
            <li><strong>Firebase Cloud Messaging (FCM)</strong> untuk notifikasi.</li>
            <li><strong>Firebase Analytics</strong> untuk analitik penggunaan aplikasi.</li>
            <li><strong>Google Maps</strong> untuk membuka lokasi pelanggan (via tautan).</li>
        </ul>
        <p>
            Layanan-layanan tersebut dapat mengumpulkan informasi tertentu sesuai kebijakan privasi mereka
            masing-masing. Kami menganjurkan Anda untuk meninjau kebijakan privasi dari layanan-layanan tersebut.
        </p>

        <h2>9. Penyimpanan Data dan Jangka Waktu</h2>
        <ul>
            <li>Data tiket dan log operasional dapat disimpan selama masih dibutuhkan untuk kepentingan operasional, audit, atau kewajiban hukum.</li>
            <li>Data dapat dianonimkan atau dihapus jika sudah tidak diperlukan lagi.</li>
        </ul>

        <h2>10. Perubahan Kebijakan Privasi</h2>
        <p>
            Kebijakan Privasi ini dapat diperbarui dari waktu ke waktu. Perubahan akan diberitahukan melalui:
        </p>
        <ul>
            <li>Pembaruan halaman ini di website/aplikasi.</li>
            <li>Informasi internal kepada teknisi atau admin terkait jika perubahan bersifat signifikan.</li>
        </ul>
        <p>
            Dengan terus menggunakan aplikasi MyRealtek setelah perubahan diberlakukan, Anda dianggap menyetujui versi terbaru dari Kebijakan Privasi ini.
        </p>

        <h2>11. Kontak</h2>
        <p>Jika Anda memiliki pertanyaan, permintaan, atau keluhan terkait Kebijakan Privasi ini, Anda dapat menghubungi:</p>
        <ul>
            <li><strong>REALNET / Data Real Solution</strong></li>
            <li>Email: <a href="mailto:support@datarealsolution.net">support@datarealsolution.net</a> (contoh, sesuaikan)</li>
            <li>Website: <a href="https://datarealsolution.net" target="_blank">https://datarealsolution.net</a></li>
        </ul>

        <div class="footer">
            &copy; <?php echo date('Y'); ?> MyRealtek &middot; REALNET / Data Real Solution. Semua hak dilindungi.
        </div>
    </div>
</div>
</body>
</html>
