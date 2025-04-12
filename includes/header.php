<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>SIPAS Biro Umum</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">

    <style>
        /* Konsistensi untuk elemen ikon dan teks */
        .nav-link i {
            font-size: 16px;
            color: white;
        }

        .navbar-brand img {
            margin-right: 8px;
        }

        .navbar-brand span {
            font-size: 16px;
            font-weight: bold;
            color: white;
        }

        .nav-link {
            font-size: 14px;
            font-weight: bold;
            color: white;
        }

        /* Gaya untuk tombol Logout */
        .nav-link.btn.btn-danger {
            background-color: #dc3545;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: bold;
            display: inline-flex;
            align-items: center;
            padding: 6px 12px;
            gap: 8px;
            text-decoration: none;
        }

        .nav-link.btn.btn-danger i {
            font-size: 16px;
            color: white;
        }

        .nav-link.btn.btn-danger:hover {
            background-color: #c82333;
            text-decoration: none;
        }

        /* Menyesuaikan navbar pada mode mobile */
        @media (max-width: 992px) {
            .navbar-nav {
                text-align: center;
            }
            .nav-link {
                padding: 10px;
            }
        }
    </style>
</head>
<body>

<header>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container-fluid">
            <!-- Logo dan Nama Aplikasi -->
            <a class="navbar-brand d-flex align-items-center" href="index.php" style="gap: 8px;">
                <img src="assets/img/logo sipas.png" alt="Logo" style="width: 50px; height: 50px; border-radius: 50%;">
                <span>TU Biro Umum</span>
            </a>
            
            <!-- Tombol Toggler -->
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link d-flex align-items-center" href="index.php" style="gap: 6px;">
                            <i class="fas fa-home"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link d-flex align-items-center" href="daftar_surat.php" style="gap: 6px;">
                            <i class="fas fa-envelope"></i> Daftar Surat
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link d-flex align-items-center" href="arsip.php" style="gap: 6px;">
                            <i class="fas fa-archive"></i> Arsip
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link d-flex align-items-center" href="statistik.php" style="gap: 6px;">
                            <i class="fas fa-chart-bar"></i> Statistik
                        </a>
                    </li>
                </ul>

                <!-- Tombol Logout -->
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link btn btn-danger d-flex align-items-center text-white px-3" href="logout.php" style="gap: 8px; font-weight: bold;">
                            <i class="fas fa-sign-out-alt"></i> Logout
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
</header>

<!-- Bootstrap 5 JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
