<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ERP PT.REAL DATA SOLUSINDO</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Your custom CSS -->
    <link rel="stylesheet" href="nav.css">
    <style>
        /* Ensure dropdown is visible */
        .dropdown-menu {
            display: block;
            visibility: hidden;
            opacity: 0;
            transition: visibility 0s, opacity 0.3s linear;
        }
        .dropdown:hover .dropdown-menu {
            visibility: visible;
            opacity: 1;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light bg-light">
        <div class="container-fluid">
            <a class="navbar-brand text-red" href="#">ERP PT.REAL DATA SOLUSINDO</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNavDropdown" aria-controls="navbarNavDropdown" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNavDropdown">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item">
                        <a class="nav-link" href="pemasangan.php">Pemasangan</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="gangguan.php">Gangguan</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="dashkaryawan.php">HRIS</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="dashaset.php">AMS</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="dashims.php">IMS</a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdownMenuLink" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            Pengaturan
                        </a>
                        <ul class="dropdown-menu" aria-labelledby="navbarDropdownMenuLink">
                            <li><a class="dropdown-item" href="pop.php">POP</a></li>
                            <li><a class="dropdown-item" href="dashodp.php">ODP</a></li>
                            <li><a class="dropdown-item" href="dashpaket.php">PAKET</a></li>
                        </ul>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php">
                            <i class="bi bi-box-arrow-right"></i> Logout
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Ensure dropdowns work on hover and click
        document.addEventListener("DOMContentLoaded", function(){
            const dropdowns = document.querySelectorAll('.dropdown');
            dropdowns.forEach(dropdown => {
                ['click', 'mouseover', 'mouseout'].forEach(evt => 
                    dropdown.addEventListener(evt, function(e) {
                        if (evt === 'mouseover') {
                            this.classList.add('show');
                            this.querySelector('.dropdown-menu').classList.add('show');
                        } else if (evt === 'mouseout') {
                            this.classList.remove('show');
                            this.querySelector('.dropdown-menu').classList.remove('show');
                        } else if (evt === 'click') {
                            e.stopPropagation();
                            this.classList.toggle('show');
                            this.querySelector('.dropdown-menu').classList.toggle('show');
                        }
                    })
                );
            });
        });
    </script>
</body>
</html>