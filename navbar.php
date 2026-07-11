<?php
// ==========================================
// KONFIGURASI MENU NAVIGASI & LOGIKA AKTIF
// ==========================================
$requestUri   = $_SERVER['REQUEST_URI'] ?? $_SERVER['PHP_SELF'] ?? '';
$path         = parse_url($requestUri, PHP_URL_PATH);
$currentPage  = basename($path);

$menus = [
    ['label' => 'Dashboard',    'file' => 'dashboard.php',          'icon' => '<i class="bi bi-speedometer2"></i>'],
    ['label' => 'Aktivasi',     'file' => 'aktivasi_pelanggan.php', 'icon' => '<i class="bi bi-person-check"></i>'],
    ['label' => 'Proses PSB',   'file' => 'prosesaktivasi.php',     'icon' => '<i class="bi bi-hourglass-split"></i>'],
    ['label' => 'Selesai PSB',  'file' => 'selesai_aktivasi.php',   'icon' => '<i class="bi bi-check2-circle"></i>'],
    ['label' => 'Gangguan',     'file' => 'gangguan.php',           'icon' => '<i class="bi bi-exclamation-triangle"></i>'],
    ['label' => 'CABUT',        'file' => 'cabut.php',              'icon' => '<i class="bi bi-plug"></i>'],

    // ✅ TAMBAHAN: Remote Modem (link ke subdomain remot.datarealsolution.net)
    // Dibuat pakai URL penuh supaya tidak tergantung lokasi folder ERP.
    ['label' => 'Remote Modem', 'url'  => 'https://remot.datarealsolution.net/', 'icon' => '<i class="bi bi-router"></i>'],

    ['label' => 'HRIS',         'file' => 'maintenance.php',       'icon' => '<i class="bi bi-people"></i>'],
    ['label' => 'BBM',          'file' => 'list_reimburse.php',     'icon' => '<i class="bi bi-receipt"></i>'],
    ['label' => 'Kasbon',       'file' => 'list_kasbon.php',        'icon' => '<i class="bi bi-wallet2"></i>'],
    [
        'label'   => 'Logistik',
        'icon'    => '<i class="bi bi-box-seam"></i>',
        'submenu' => [
            ['label' => 'AMS (Aset)',      'file' => 'pengajuan_aset.php', 'icon' => '<i class="bi bi-archive"></i>'],
            ['label' => 'IMS (Inventory)', 'file' => 'dashims.php',        'icon' => '<i class="bi bi-kanban-fill"></i>'],
        ]
    ],
    [
        'label'   => 'Pengaturan',
        'icon'    => '<i class="bi bi-gear"></i>',
        'submenu' => [
            ['label' => 'POP',   'file' => 'pop.php',         'icon' => '<i class="bi bi-hdd-network"></i>'],
            ['label' => 'ODP',   'file' => 'dashodp.php',     'icon' => '<i class="bi bi-diagram-3"></i>'],
            ['label' => 'PAKET', 'file' => 'dashpaketku.php', 'icon' => '<i class="bi bi-wifi"></i>'],
        ],
    ],
];

function isActive(string $currentPage, string $file): bool {
    return $currentPage === $file;
}

// ✅ Active untuk menu eksternal (Remote Modem)
function isActiveUrl(string $requestUri, string $url): bool {
    $host = parse_url($url, PHP_URL_HOST);
    if (!$host) return false;
    $reqHost = $_SERVER['HTTP_HOST'] ?? '';
    return strcasecmp($reqHost, $host) === 0;
}
?>

<style>
    .navbar-custom {
        background: #ffffff;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        padding: 0.8rem 1rem;
        transition: all 0.3s ease;
        z-index: 1050;
    }

    .navbar-brand {
        font-weight: 800;
        font-size: 1.25rem;
        color: #0d6efd !important;
        letter-spacing: -0.5px;
    }

    .nav-link i,
    .dropdown-item i {
        margin-right: 8px;
        font-size: 1.1em;
        color: #0d6efd;
        vertical-align: middle;
    }

    /* Logout desktop */
    .btn-logout-desktop {
        border-radius: 999px;
        font-weight: 500;
        border: 1px solid #ffebeb;
        background-color: #fff5f5;
        color: #dc3545 !important;
        padding: 0.35rem 0.9rem;
        font-size: 0.85rem;
        transition: all 0.2s ease;
        white-space: nowrap;
    }
    .btn-logout-desktop:hover {
        background-color: #ffe4e4;
        border-color: #ffc2c2;
        text-decoration: none;
    }

    /* Logout mobile */
    .btn-logout-mobile {
        margin-top: 15px;
        width: 100%;
        text-align: center;
        background-color: #fff5f5;
        border: 1px solid #ffebeb;
        color: #dc3545 !important;
        border-radius: 10px;
        padding: 0.6rem 1rem;
        font-weight: 500;
    }

    @media (min-width: 992px) {
        .navbar-nav.main-menu {
            flex: 1 1 auto;
            justify-content: center;
            flex-wrap: wrap;
        }

        .nav-link {
            color: #555;
            font-weight: 500;
            font-size: 0.88rem;
            padding: 0.4rem 0.8rem !important;
            border-radius: 999px;
            transition: all 0.2s ease;
            margin: 0 2px;
        }

        .nav-link:hover {
            background-color: #f0f7ff;
            color: #0d6efd;
            transform: translateY(-1px);
        }

        .nav-link.active {
            background-color: #0d6efd;
            color: #fff !important;
            box-shadow: 0 4px 10px rgba(13, 110, 253, 0.3);
        }

        .nav-link.active i {
            color: #fff !important;
        }

        .dropdown-menu {
            border: none;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
            border-radius: 12px;
            margin-top: 15px;
            animation: fadeIn 0.3s ease;
        }
    }

    @media (max-width: 991.98px) {
        .navbar-collapse {
            background: #ffffff;
            margin-top: 15px;
            padding: 15px;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.05);
            border: 1px solid #f1f1f1;
            max-height: 80vh;
            overflow-y: auto;
        }

        .nav-link {
            padding: 12px 15px !important;
            border-bottom: 1px solid #f8f9fa;
            border-radius: 8px;
            color: #444;
        }

        .nav-link:hover,
        .nav-link.active {
            background-color: #f0f7ff;
            color: #0d6efd !important;
            padding-left: 20px !important;
        }
    }

    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(10px); }
        to   { opacity: 1; transform: translateY(0); }
    }
</style>

<nav class="navbar navbar-expand-lg fixed-top navbar-custom">
    <div class="container-fluid">
        <a class="navbar-brand d-flex align-items-center" href="dashboard.php">
            <div class="bg-primary text-white rounded p-1 me-2 d-flex align-items-center justify-content-center" style="width:32px;height:32px;">
                <i class="bi bi-diagram-3-fill fs-6"></i>
            </div>
            ERP REALNET
        </a>

        <button class="navbar-toggler border-0 shadow-none p-0"
                type="button"
                data-bs-toggle="collapse"
                data-bs-target="#navbarContent"
                aria-controls="navbarContent"
                aria-expanded="false"
                aria-label="Toggle navigation">
            <span class="bi bi-list fs-1 text-primary"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarContent">
            <!-- MENU UTAMA (tengah) -->
            <ul class="navbar-nav main-menu mb-2 mb-lg-0 align-items-lg-center">
                <?php foreach ($menus as $menu): ?>
                    <?php if (isset($menu['submenu'])): ?>
                        <?php
                        $isActiveDropdown = false;
                        foreach ($menu['submenu'] as $sub) {
                            if (isActive($currentPage, $sub['file'])) {
                                $isActiveDropdown = true;
                                break;
                            }
                        }
                        ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle <?= $isActiveDropdown ? 'active' : '' ?>"
                               href="#"
                               role="button"
                               data-bs-toggle="dropdown"
                               aria-expanded="false">
                                <?= $menu['icon'] ?>
                                <span><?= htmlspecialchars($menu['label'], ENT_QUOTES, 'UTF-8') ?></span>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <?php foreach ($menu['submenu'] as $sub): ?>
                                    <li>
                                        <a class="dropdown-item <?= isActive($currentPage, $sub['file']) ? 'active' : '' ?>"
                                           href="<?= htmlspecialchars($sub['file'], ENT_QUOTES, 'UTF-8') ?>">
                                            <?= $sub['icon'] ?>
                                            <?= htmlspecialchars($sub['label'], ENT_QUOTES, 'UTF-8') ?>
                                        </a>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </li>

                    <?php else: ?>
                        <?php
                        // ✅ dukung menu internal (file) dan eksternal (url)
                        $href = $menu['file'] ?? ($menu['url'] ?? '#');
                        $active = false;

                        if (isset($menu['file'])) {
                            $active = isActive($currentPage, $menu['file']);
                        } elseif (isset($menu['url'])) {
                            $active = isActiveUrl($requestUri, $menu['url']);
                        }
                        ?>
                        <li class="nav-item">
                            <a class="nav-link <?= $active ? 'active' : '' ?>"
                               href="<?= htmlspecialchars($href, ENT_QUOTES, 'UTF-8') ?>"
                               <?= isset($menu['url']) ? 'target="_blank" rel="noopener noreferrer"' : '' ?>>
                                <?= $menu['icon'] ?>
                                <span><?= htmlspecialchars($menu['label'], ENT_QUOTES, 'UTF-8') ?></span>
                            </a>
                        </li>
                    <?php endif; ?>
                <?php endforeach; ?>

                <!-- Logout untuk MOBILE (di dalam menu) -->
                <li class="nav-item d-lg-none my-2">
                    <hr class="dropdown-divider">
                </li>
                <li class="nav-item d-lg-none">
                    <a href="logout.php" class="btn-logout-mobile">
                        <i class="bi bi-power"></i> Logout
                    </a>
                </li>
            </ul>

            <!-- Logout untuk DESKTOP (selalu di ujung kanan) -->
            <div class="d-none d-lg-flex align-items-center ms-lg-3">
                <a href="logout.php" class="btn-logout-desktop">
                    <i class="bi bi-power"></i>
                    <span class="ms-1">Logout</span>
                </a>
            </div>
        </div>
    </div>
</nav>

<div style="height: 85px;"></div>
