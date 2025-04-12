<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit();
}
require_once('includes/config.php');

// Query untuk arsip surat masuk (berdasarkan tanggal_masuk)
$sql_masuk = "
    SELECT MONTH(tanggal_masuk) AS bulan, YEAR(tanggal_masuk) AS tahun, tujuan_surat, COUNT(*) AS jumlah 
    FROM surat 
    WHERE tanggal_masuk IS NOT NULL 
    GROUP BY bulan, tahun, tujuan_surat
";
$surat_masuk = $pdo->query($sql_masuk)->fetchAll(PDO::FETCH_ASSOC);

// Query untuk arsip surat keluar (berdasarkan tanggal_keluar)
$sql_keluar = "
    SELECT MONTH(tanggal_keluar) AS bulan, YEAR(tanggal_keluar) AS tahun, nama_instansi, COUNT(*) AS jumlah 
    FROM surat 
    WHERE tanggal_keluar IS NOT NULL 
    GROUP BY bulan, tahun, nama_instansi
";
$surat_keluar = $pdo->query($sql_keluar)->fetchAll(PDO::FETCH_ASSOC);

// Query untuk total surat masuk
$sql_total_masuk = "
    SELECT MONTH(tanggal_masuk) AS bulan, YEAR(tanggal_masuk) AS tahun, COUNT(*) AS jumlah 
    FROM surat 
    WHERE tanggal_masuk IS NOT NULL 
    GROUP BY bulan, tahun
";
$total_surat_masuk = $pdo->query($sql_total_masuk)->fetchAll(PDO::FETCH_ASSOC);

// Query untuk total surat masuk
$sql_total_keluar = "
    SELECT MONTH(tanggal_keluar) AS bulan, YEAR(tanggal_keluar) AS tahun, COUNT(*) AS jumlah 
    FROM surat 
    WHERE tanggal_keluar IS NOT NULL 
    GROUP BY bulan, tahun
";
$total_surat_keluar = $pdo->query($sql_total_keluar)->fetchAll(PDO::FETCH_ASSOC);

// Query untuk arsip surat keluar (berdasarkan tanggal_keluar dan nama_instansi yang sudah dibersihkan)
$sql_keluar = "
    SELECT MONTH(tanggal_keluar) AS bulan, YEAR(tanggal_keluar) AS tahun, 
           SUBSTRING_INDEX(nama_instansi, '(', 1) AS nama_instansi_bersih, 
           COUNT(*) AS jumlah 
    FROM surat 
    WHERE tanggal_keluar IS NOT NULL 
    GROUP BY bulan, tahun, nama_instansi_bersih
";
$surat_keluar = $pdo->query($sql_keluar)->fetchAll(PDO::FETCH_ASSOC);




// Gabungkan total surat masuk dan keluar
$total_surat = array_merge(
    array_map(function ($row) {
        $row['jenis'] = 'Masuk';
        return $row;
    }, $total_surat_masuk),
    array_map(function ($row) {
        $row['jenis'] = 'Keluar';
        return $row;
    }, $total_surat_keluar)
);

// Fungsi untuk konversi bulan angka ke nama
function namaBulan($bulan)
{
    $nama_bulan = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni',
        7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
    ];
    return isset($nama_bulan[$bulan]) ? $nama_bulan[$bulan] : '';
}


// Menambahkan perhitungan total surat masuk dan keluar
$total_masuk_keluar = 0;
foreach ($total_surat as $ts) {
    $total_masuk_keluar += $ts['jumlah'];
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Arsip Surat</title>
    <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .table-section {
            margin-top: 30px;
        }

        .section-title {
            margin-bottom: 20px;
            text-align: center;
        }

        .card-header {
            font-size: 1.2rem;
            font-weight: bold;
            text-align: center;
        }

        .card {
            margin-bottom: 20px;
        }

        .btn-lihat {
            margin-top: 10px;
        }

        .btn-download {
            margin-top: 10px;
        }

        .total-row {
            font-weight: bold;
            background-color: rgba(75, 192, 192, 0.5); /* Warna latar belakang untuk total surat masuk */
        }

        .total-row-keluar {
            font-weight: bold;
            background-color: rgba(54, 162, 235, 0.5); /* Warna latar belakang untuk total surat keluar */
        }

        .total-row-all {
            font-weight: bold;
            background-color: rgba(255, 159, 64, 0.5); /* Warna latar belakang untuk total semua surat */
        }
    </style>
</head>

<body>
    <?php include('includes/header.php'); ?>

    <div class="container mt-5">
        <h1 class="text-center mb-4">Arsip Surat</h1>

        <div class="row">
            <!-- Arsip Surat Masuk -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-success text-white">Arsip Surat Masuk</div>
                    <div class="card-body">
                        <table class="table table-striped table-hover table-bordered">
                            <thead class="table-success">
                                <tr>
                                    <th>Bulan</th>
                                    <th>Tahun</th>
                                    <th>Tujuan</th>
                                    <th>Jumlah Surat</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($surat_masuk as $sm) : ?>
                                    <tr>
                                        <td><?= namaBulan($sm['bulan']) ?></td>
                                        <td><?= htmlspecialchars($sm['tahun']) ?></td>
                                        <td><?= htmlspecialchars($sm['tujuan_surat']) ?></td>
                                        <td><?= htmlspecialchars($sm['jumlah']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <a href="Arsipsheetapi.php?download=surat_masuk" target="_blank" class="btn btn-success btn-download">
    <img src="https://www.gstatic.com/images/branding/product/1x/sheets_2020q4_48dp.png" alt="Google Sheets" style="height:20px; vertical-align:middle; margin-right:5px;">
    Ekspor ke Google Sheets
</a>
                                            </div>
                </div>
            </div>

         <!-- Arsip Surat Keluar -->
<div class="col-md-6">
    <div class="card">
        <div class="card-header bg-primary text-white">Arsip Surat Keluar</div>
        <div class="card-body">
            <table class="table table-striped table-hover table-bordered">
                <thead class="table-primary">
                    <tr>
                        <th>Bulan</th>
                        <th>Tahun</th>
                        <th>Nama Instansi</th>
                        <th>Jumlah Surat</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($surat_keluar as $sk) : ?>
                        <tr>
                            <td><?= namaBulan($sk['bulan']) ?></td>
                            <td><?= htmlspecialchars($sk['tahun']) ?></td>
                            <td><?= htmlspecialchars($sk['nama_instansi_bersih']) ?></td>
                            <td><?= htmlspecialchars($sk['jumlah']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <a href="Arsipsheetapi.php?download=surat_keluar" target="_blank" class="btn btn-primary btn-download">
    <img src="https://www.gstatic.com/images/branding/product/1x/sheets_2020q4_48dp.png" alt="Google Sheets" style="height:20px; vertical-align:middle; margin-right:5px;">
    Ekspor ke Google Sheets
</a>
            </div>
    </div>
</div>


        <!-- Total Surat Masuk dan Keluar -->
        <div class="card mt-5">
            <div class="card-header bg-info text-white">Total Surat Masuk dan Keluar</div>
            <div class="card-body">
                <table class="table table-striped table-hover table-bordered">
                    <thead class="table-info">
                        <tr>
                            <th>Jenis</th>
                            <th>Bulan</th>
                            <th>Tahun</th>
                            <th>Jumlah Surat</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($total_surat as $ts) : ?>
                            <tr>
                                <td><?= htmlspecialchars($ts['jenis']) ?></td>
                                <td><?= namaBulan($ts['bulan']) ?></td>
                                <td><?= htmlspecialchars($ts['tahun']) ?></td>
                                <td><?= htmlspecialchars($ts['jumlah']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <!-- Menampilkan Total Surat Masuk dan Keluar -->
                        <tr class="total-row-all">
                            <td colspan="3" class="text-center">Total Semua Surat</td>
                            <td><?= $total_masuk_keluar ?></td>
                        </tr>
                    </tbody>
                </table>
                <a href="Arsipsheetapi.php?download=total_surat" target="_blank" class="btn btn-info btn-download">
    <img src="https://www.gstatic.com/images/branding/product/1x/sheets_2020q4_48dp.png" alt="Google Sheets" style="height:20px; vertical-align:middle; margin-right:5px;">
    Ekspor ke Google Sheets
</a>
            </div>
        </div>
    </div>

    <?php include('includes/footer.php'); ?>
</body>

</html>
