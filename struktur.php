<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Struktur Organisasi ISP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
    /* Styling untuk container dan box */
    .org-chart {
        display: flex;
        justify-content: center;
        flex-wrap: wrap;
        margin-top: 30px;
        position: relative;
    }

    .level {
        display: flex;
        justify-content: center;
        gap: 40px;
        flex-wrap: wrap;
        width: 100%;
        position: relative; /* Untuk positioning pseudo-element */
        padding-top: 30px; /* Ruang untuk garis dari level atas */
    }

    /* Garis vertikal dari tengah level atas */
    .org-chart > .level:first-child::before {
        content: '';
        position: absolute;
        top: 0;
        left: 50%;
        width: 2px;
        height: 30px; /* Panjang garis ke level berikutnya */
        background-color: #007bff;
        transform: translateX(-50%);
        z-index: -1;
    }

    /* Garis vertikal dari tengah level ke garis horizontal di bawahnya */
    .level:not(:first-child)::before {
        content: '';
        position: absolute;
        top: 0;
        left: 50%;
        width: 2px;
        height: 30px; /* Panjang garis ke garis horizontal */
        background-color: #007bff;
        transform: translateX(-50%);
        z-index: -1;
    }

    /* Garis horizontal yang menghubungkan ke setiap kotak di level yang sama */
    .level:not(:first-child)::after {
        content: '';
        position: absolute;
        top: 30px; /* Sesuaikan dengan panjang garis vertikal di atas */
        left: 50%;
        width: calc(100% + 40px); /* Lebar agar mencakup semua kotak */
        height: 2px;
        background-color: #007bff;
        z-index: -2;
        transform: translateX(-50%);
    }

    /* Garis vertikal dari garis horizontal ke setiap kotak */
    .box {
        width: 180px;
        height: 100px;
        border: 2px solid #007bff;
        margin: 10px;
        text-align: center;
        line-height: 30px;
        border-radius: 5px;
        background-color: #f8f9fa;
        position: relative;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        transition: transform 0.3s ease;
    }

    .box:hover {
        transform: scale(1.1);
        box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
    }

    .box .title {
        font-weight: bold;
        color: white;
        background-color: #007bff;
        padding: 5px 0;
    }

    .box .content {
        padding: 5px;
        font-size: 14px;
    }

    .box::before {
        content: '';
        position: absolute;
        top: -30px; /* Sesuaikan dengan posisi garis horizontal */
        left: 50%;
        width: 2px;
        height: 30px; /* Panjang garis ke kotak */
        background-color: #007bff;
        transform: translateX(-50%);
        z-index: -1;
    }

    /* Sembunyikan elemen connector yang lama */
    .connector, .connector-child {
        display: none;
    }

    /* Styling untuk level */
    .level-2 .box, .level-3 .box, .level-4 .box {
        width: 180px;
        height: 100px;
    }

    /* Hilangkan garis vertikal di atas level pertama */
    .org-chart > .level:first-child::before {
        /* Sudah sesuai */
    }

    /* Hilangkan garis horizontal di level terakhir */
    .org-chart > .level:last-child::after {
        display: none;
    }

    /* Atur agar garis vertikal di atas kotak level pertama tidak muncul */
    .org-chart > .level:first-child .box::before {
        display: none;
    }
</style>
</head>
<body>

    <div class="container">
        <h1 class="text-center mb-4">Struktur Organisasi ISP</h1>

        <div class="org-chart">
            <div class="level">
                <div class="box">
                    <div class="title">DIREKSI</div>
                    <div class="content">CEO</div>
                </div>
            </div>
        </div>

        <div class="org-chart">
            <div class="level">
                <div class="box">
                    <div class="title">DIREKTUR UTAMA</div>
                    <div class="content">John Doe</div>
                </div>
                <div class="box">
                    <div class="title">DIREKTUR KEUANGAN</div>
                    <div class="content">Jane Smith</div>
                </div>
            </div>
        </div>

        <div class="org-chart">
            <div class="level">
                <div class="box">
                    <div class="title">DIREKTUR PERSONALIA</div>
                    <div class="content">Michael Brown</div>
                </div>
                <div class="box">
                    <div class="title">DIREKTUR</div>
                    <div class="content">Olivia Martin</div>
                </div>
                <div class="box">
                    <div class="title">DIREKTUR</div>
                    <div class="content">Ethan Harris</div>
                </div>
            </div>
        </div>

        <div class="org-chart">
            <div class="level">
                <div class="box">
                    <div class="title">MANAGER PERSONAL</div>
                    <div class="content">Sophia Clark</div>
                </div>
                <div class="box">
                    <div class="title">MANAGER PEMASARAN</div>
                    <div class="content">Lucas White</div>
                </div>
                <div class="box">
                    <div class="title">MANAGER PABRIK</div>
                    <div class="content">Ava Johnson</div>
                </div>
                <div class="box">
                    <div class="title">ADMIN GUDANG</div>
                    <div class="content">Emma Davis</div>
                </div>
            </div>
        </div>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>