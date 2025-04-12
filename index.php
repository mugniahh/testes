<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit();
}
require_once('includes/config.php');

// Ambil tahun yang tersedia di database
// Ambil daftar tahun berdasarkan tanggal_terima
$sql_tahun = "SELECT DISTINCT YEAR(tanggal_terima) AS tahun FROM surat ORDER BY tahun DESC";
$tahun_list = $pdo->query($sql_tahun)->fetchAll(PDO::FETCH_COLUMN);// Tahun yang dipilih (default: tahun sekarang)
$tahun_pilih = isset($_GET['tahun']) && in_array((int)$_GET['tahun'], $tahun_list) 
    ? (int)$_GET['tahun'] 
    : (int)$tahun_list[0];

// Query jumlah surat masuk dan keluar
$sql_masuk = "SELECT COUNT(*) AS jumlah FROM surat WHERE YEAR(tanggal_terima) = ? AND tanggal_masuk IS NOT NULL";
$sql_keluar = "SELECT COUNT(*) AS jumlah FROM surat WHERE YEAR(tanggal_terima) = ? AND tanggal_keluar IS NOT NULL";
$sql_total_surat = "SELECT COUNT(DISTINCT id) AS jumlah FROM surat WHERE YEAR(tanggal_terima) = ?";

$stmt_masuk = $pdo->prepare($sql_masuk);
$stmt_keluar = $pdo->prepare($sql_keluar);
$stmt_total = $pdo->prepare($sql_total_surat);

$stmt_masuk->execute([$tahun_pilih]);
$stmt_keluar->execute([ $tahun_pilih]);
$stmt_total->execute([$tahun_pilih]);

$jumlah_masuk = $stmt_masuk->fetchColumn();
$jumlah_keluar = $stmt_keluar->fetchColumn();
$total_surat = $stmt_total->fetchColumn();
// Fungsi untuk mendapatkan nama bulan dari angka
function getNamaBulan($bulan)
{
    $bulanArray = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
        5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
    ];
    return isset($bulanArray[$bulan]) ? $bulanArray[$bulan] : 'Tidak Diketahui';
}

// Statistik surat masuk dan keluar per bulan berdasarkan tahun yang dipilih
$sql_masuk_perbulan = "SELECT MONTH(tanggal_terima) AS bulan, COUNT(*) AS jumlah FROM surat WHERE YEAR(tanggal_masuk) = :tahun GROUP BY bulan";
$sql_keluar_perbulan = "SELECT MONTH(tanggal_terima) AS bulan, COUNT(*) AS jumlah FROM surat WHERE YEAR(tanggal_keluar) = :tahun GROUP BY bulan";

$stmt_masuk_perbulan = $pdo->prepare($sql_masuk_perbulan);
$stmt_masuk_perbulan->execute(['tahun' => $tahun_pilih]);
$statistik_masuk = $stmt_masuk_perbulan->fetchAll(PDO::FETCH_ASSOC);

$stmt_keluar_perbulan = $pdo->prepare($sql_keluar_perbulan);
$stmt_keluar_perbulan->execute(['tahun' => $tahun_pilih]);
$statistik_keluar = $stmt_keluar_perbulan->fetchAll(PDO::FETCH_ASSOC);

// Siapkan data untuk visualisasi per bulan
$bulan_labels = array_map('getNamaBulan', range(1, 12));
$masuk_per_bulan = array_fill(0, 12, 0);
$keluar_per_bulan = array_fill(0, 12, 0);

foreach ($statistik_masuk as $data) {
    $masuk_per_bulan[$data['bulan'] - 1] = $data['jumlah'];
}

foreach ($statistik_keluar as $data) {
    $keluar_per_bulan[$data['bulan'] - 1] = $data['jumlah'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - SIPAS Biro Umum</title>
    <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .dashboard-card {
            margin-top: 20px;
            text-align: center;
            padding: 20px;
            border-radius: 10px;
            color: white;
        }
        .card-text {
            font-size: 1.5rem;
        }
        .btn-dashboard {
            margin-top: 10px;
        }
        .logo-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .logo-kota-kendari {
            width: 120px;
            height: auto;
        }
        .logo-sultra {
            width: 125px;
            height: auto;
        }
        .header-text {
            text-align: center;
            flex-grow: 1;
        }
    </style>
</head>
<body>
<?php include('includes/header.php'); ?>

<div class="container mt-5">
<h2 class="text-center">Pilih Tahun</h2>
<form method="GET" class="mb-3">
        <label for="tahun">Pilih Tahun:</label>
        <select name="tahun" id="tahun" class="form-select" onchange="this.form.submit()">
            <?php foreach ($tahun_list as $tahun): ?>
                <option value="<?= $tahun ?>" <?= ($tahun == $tahun_pilih) ? 'selected' : '' ?>><?= $tahun ?></option>
            <?php endforeach; ?>
        </select>
    </form>
    <div class="logo-container">
        <img src="assets/img/logo sultra.png" alt="Logo Kota Kendari" class="logo-kota-kendari">
        <div class="header-text">
            <h1 class="text-center">Selamat Datang, <?= htmlspecialchars($_SESSION['user']) ?></h1>
            <p class="text-center">Sistem Informasi Pengarsipan Surat</p>
        </div>
        <img src="assets/img/logo sultra.png" alt="Logo Sultra" class="logo-sultra">
    </div>
    <div class="row mt-4">
        <!-- Surat Masuk -->
        <div class="col-md-6">
            <div class="card bg-success text-white dashboard-card">
                <h3>Surat Masuk</h3>
                <p class="card-text"><?= $jumlah_masuk ?> Surat</p>
                <a href="daftar_surat.php?jenis_surat=Masuk&tahun=<?= $tahun_pilih ?>" class="btn btn-light btn-dashboard">Lihat Surat Masuk</a>
            </div>
        </div>
        <!-- Surat Keluar -->
        <div class="col-md-6">
            <div class="card bg-primary text-white dashboard-card">
                <h3>Surat Keluar</h3>
                <p class="card-text"><?= $jumlah_keluar ?> Surat</p>
                <a href="daftar_surat.php?jenis_surat=Keluar&tahun=<?= $tahun_pilih ?>" class="btn btn-light btn-dashboard">Lihat Surat Keluar</a>
            </div>
        </div>
    </div>

    <!-- Total Surat Keseluruhan -->
    <div class="row mt-4">
        <div class="col-md-12">
            <div class="card bg-info text-white dashboard-card">
                <h3>Daftar Surat </h3>
                <p class="card-text"><?= $total_surat ?> Surat</p>
                <a href="daftar_surat.php?tahun=<?= $tahun_pilih ?>" class="btn btn-light btn-dashboard">Lihat Semua Surat</a>
            </div>
        </div>
    </div>

    <!-- Statistik Surat Masuk dan Keluar -->
    <div class="container mt-5">
        <h2 class="text-center">Statistik Surat Masuk dan Keluar Per Bulan</h2>
        <canvas id="suratChart"></canvas>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const ctx = document.getElementById('suratChart').getContext('2d');
    const suratChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: <?= json_encode($bulan_labels) ?>,
            datasets: [
                {
                    label: 'Surat Masuk',
                    data: <?= json_encode($masuk_per_bulan) ?>,
                    backgroundColor: 'rgba(75, 192, 192, 0.6)',
                    borderColor: 'rgba(75, 192, 192, 1)',
                    borderWidth: 1
                },
                {
                    label: 'Surat Keluar',
                    data: <?= json_encode($keluar_per_bulan) ?>,
                    backgroundColor: 'rgba(54, 162, 235, 0.6)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 1
                }
            ]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'top'
                },
                title: {
                    display: true,
                    text: 'Jumlah Surat Masuk dan Keluar Per Bulan'
                }
            },
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
</script>

<?php include('includes/footer.php'); ?>
</body>
</html>
