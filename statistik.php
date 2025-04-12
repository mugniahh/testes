<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit();
}
require_once('includes/config.php');

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
// Ambil daftar tahun berdasarkan tanggal_terima
$sql_tahun = "SELECT DISTINCT YEAR(tanggal_terima) AS tahun FROM surat ORDER BY tahun DESC";
$tahun_list = $pdo->query($sql_tahun)->fetchAll(PDO::FETCH_COLUMN);// Tahun yang dipilih (default: tahun sekarang)
$tahun_pilih = isset($_GET['tahun']) ? (int)$_GET['tahun'] : (int)$tahun_list[0];

// Query jumlah surat masuk per bulan berdasarkan tanggal_masuk
$sql_masuk = "SELECT MONTH(tanggal_masuk) AS bulan, COUNT(*) AS jumlah FROM surat WHERE YEAR(tanggal_masuk) = :tahun
              GROUP BY bulan";
$stmt = $pdo->prepare($sql_masuk);
$stmt->execute(['tahun' => $tahun_pilih]);
$statistik_masuk = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Query jumlah surat keluar per bulan berdasarkan tanggal_keluar
$sql_keluar = "SELECT MONTH(tanggal_keluar) AS bulan, COUNT(*) AS jumlah FROM surat WHERE YEAR(tanggal_keluar) = :tahun
               GROUP BY bulan";
$stmt = $pdo->prepare($sql_keluar);
$stmt->execute(['tahun' => $tahun_pilih]);
$statistik_keluar = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Query total surat masuk berdasarkan tanggal_masuk
$sql_total_masuk = "SELECT COUNT(*) AS total FROM surat WHERE YEAR (tanggal_masuk) = :tahun";
$stmt = $pdo->prepare($sql_total_masuk);
$stmt->execute(['tahun' => $tahun_pilih]);
$result = $stmt->fetch(PDO::FETCH_ASSOC);
$total_masuk = isset($result['total']) ? $result['total'] : 0;
// Query total surat keluar berdasarkan tanggal_keluar
$sql_total_keluar = "SELECT COUNT(*) AS total FROM surat WHERE YEAR (tanggal_keluar) = :tahun";
$stmt = $pdo->prepare($sql_total_keluar);
$stmt->execute(['tahun' => $tahun_pilih]);
$result = $stmt->fetch(PDO::FETCH_ASSOC);
$total_keluar = isset($result['total']) ? $result['total'] : 0;

// Query tujuan surat masuk berdasarkan tanggal_masuk
$sql_tujuan_masuk = "SELECT tujuan_surat, COUNT(*) AS jumlah FROM surat WHERE tanggal_masuk IS NOT NULL AND YEAR(tanggal_masuk) = :tahun GROUP BY tujuan_surat ORDER BY jumlah DESC ";
$stmt = $pdo->prepare($sql_tujuan_masuk);
$stmt->execute(['tahun' => $tahun_pilih]);
$statistik_tujuan_masuk = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Query tujuan surat keluar berdasarkan tanggal_keluar
$sql_tujuan_keluar = "SELECT tujuan_surat, COUNT(*) AS jumlah FROM surat WHERE tanggal_keluar IS NOT NULL AND YEAR(tanggal_keluar) = :tahun
GROUP BY tujuan_surat 
ORDER BY jumlah DESC";
$stmt = $pdo->prepare($sql_tujuan_keluar);
$stmt->execute(['tahun' => $tahun_pilih]);
$statistik_tujuan_keluar = $stmt->fetchAll(PDO::FETCH_ASSOC);

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

// Statistik nama_instansi untuk surat keluar
$sql_instansi_keluar = "SELECT TRIM(SUBSTRING_INDEX(nama_instansi, '(', 1)) AS nama_instansi, COUNT(*) AS jumlah FROM surat WHERE tanggal_keluar IS NOT NULL 
                        AND YEAR(tanggal_keluar) = :tahun
                        GROUP BY nama_instansi
                        ORDER BY nama_instansi ASC";
$stmt = $pdo->prepare($sql_instansi_keluar);
$stmt->execute(['tahun' => $tahun_pilih]);
$instansi_keluar = $stmt->fetchAll(PDO::FETCH_ASSOC);
$labelsInstansiKeluar = array_column($instansi_keluar, 'nama_instansi');
$dataInstansiKeluar = array_column($instansi_keluar, 'jumlah');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Statistik Surat</title>
    <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .statistik-header {
            margin-top: 30px;
            margin-bottom: 20px;
            text-align: center;
        }
        .statistik-table {
            margin-top: 20px;
        }
        .chart-container {
            margin-top: 30px;
        }
        .highlight {
            font-weight: bold;
            color: #007bff;
        }
    </style>
</head>
<body>
<?php include('includes/header.php'); ?>

<div class="container mt-5">
    <h1 class="statistik-header">Statistik Surat</h1>
    <form method="GET" class="mb-3">
    <label for="tahun">Pilih Tahun:</label>
    <select name="tahun" id="tahun" class="form-select" onchange="this.form.submit()">
        <?php foreach ($tahun_list as $tahun): ?>
            <option value="<?= $tahun ?>" <?= ($tahun == $tahun_pilih) ? 'selected' : '' ?>><?= $tahun ?></option>
        <?php endforeach; ?>
    </select>
</form>
    <!-- Total Surat -->
    <div class="row">
        <div class="col-md-6">
            <div class="alert alert-success">
                <h4>Total Surat Masuk</h4>
                <p class="highlight"><?= $total_masuk ?> Surat</p>
            </div>
        </div>
        <div class="col-md-6">
            <div class="alert alert-primary">
                <h4>Total Surat Keluar</h4>
                <p class="highlight"><?= $total_keluar ?> Surat</p>
            </div>
        </div>
    </div>

    <!-- Visualisasi Surat Masuk dan Keluar per Bulan -->
    <div class="chart-container">
        <canvas id="chartPerBulan"></canvas>
    </div>

     <!-- Statistik Tujuan Surat -->
     <h3 class="mt-5">Statistik Tujuan Surat</h3>
    <div class="row">
        <div class="col-md-6">
            <h4>Tujuan Surat Masuk</h4>
            <canvas id="chartTujuanMasuk"></canvas>
        </div>
        <div class="col-md-6">
            <h4>Instansi Surat Keluar</h4>
            <canvas id="chartInstansiKeluar"></canvas>
        </div>
    </div>
</div>

<script>
    // Data dari PHP
    const labelsBulan = <?= json_encode($bulan_labels) ?>;
    const dataMasukBulan = <?= json_encode($masuk_per_bulan) ?>;
    const dataKeluarBulan = <?= json_encode($keluar_per_bulan) ?>;

    const labelsTujuanMasuk = <?= json_encode(array_column($statistik_tujuan_masuk, 'tujuan_surat')) ?>;
    const dataTujuanMasuk = <?= json_encode(array_column($statistik_tujuan_masuk, 'jumlah')) ?>;

    const labelsInstansiKeluar = <?= json_encode($labelsInstansiKeluar) ?>;
    const dataInstansiKeluar = <?= json_encode($dataInstansiKeluar) ?>;

    // Chart: Surat Masuk dan Keluar per Bulan
    const ctxBulan = document.getElementById('chartPerBulan').getContext('2d');
    new Chart(ctxBulan, {
        type: 'bar',
        data: {
            labels: labelsBulan,
            datasets: [
                {
                    label: 'Surat Masuk',
                    data: dataMasukBulan,
                    backgroundColor: 'rgba(75, 192, 192, 0.6)',
                    borderColor: 'rgba(75, 192, 192, 1)',
                    borderWidth: 1
                },
                {
                    label: 'Surat Keluar',
                    data: dataKeluarBulan,
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
                    text: 'Jumlah Surat per Bulan'
                }
            }
        }
    });

    // Chart: Tujuan Surat Masuk
    const ctxTujuanMasuk = document.getElementById('chartTujuanMasuk').getContext('2d');
    new Chart(ctxTujuanMasuk, {
        type: 'pie',
        data: {
            labels: labelsTujuanMasuk,
            datasets: [{
                data: dataTujuanMasuk,
                backgroundColor: [
                    'rgba(255, 99, 132, 0.6)',
                    'rgba(54, 162, 235, 0.6)',
                    'rgba(255, 206, 86, 0.6)',
                    'rgba(75, 192, 192, 0.6)',
                    'rgba(153, 102, 255, 0.6)'
                ],
                borderColor: [
                    'rgba(255, 99, 132, 1)',
                    'rgba(54, 162, 235, 1)',
                    'rgba(255, 206, 86, 1)',
                    'rgba(75, 192, 192, 1)',
                    'rgba(153, 102, 255, 1)'
                ],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'top'
                },
                title: {
                    display: true,
                    text: 'Tujuan Surat Masuk'
                }
            }
        }
    });

     // Chart: Instansi Surat Keluar
     const ctxInstansiKeluar = document.getElementById('chartInstansiKeluar').getContext('2d');
    new Chart(ctxInstansiKeluar, {
        type: 'pie',
        data: {
            labels: labelsInstansiKeluar,
            datasets: [{
                data: dataInstansiKeluar,
                backgroundColor: [
                    'rgba(255, 159, 64, 0.6)',
                    'rgba(255, 99, 132, 0.6)',
                    'rgba(75, 192, 192, 0.6)',
                    'rgba(153, 102, 255, 0.6)',
                    'rgba(54, 162, 235, 0.6)'
                ],
                borderColor: [
                    'rgba(255, 159, 64, 1)',
                    'rgba(255, 99, 132, 1)',
                    'rgba(75, 192, 192, 1)',
                    'rgba(153, 102, 255, 1)',
                    'rgba(54, 162, 235, 1)'
                ],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'top'
                },
                title: {
                    display: true,
                    text: 'Instansi Surat Keluar'
                }
            }
        }
    });
</script>

<?php include('includes/footer.php'); ?>
</body>
</html>
