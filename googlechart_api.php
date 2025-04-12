<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit();
}
require_once('includes/config.php');

function getNamaBulan($bulan) {
    $bulanArray = [1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'];
    return $bulanArray[$bulan] ?? 'Tidak Diketahui';
}

$sql_tahun = "SELECT DISTINCT YEAR(tanggal_terima) AS tahun FROM surat ORDER BY tahun DESC";
$tahun_list = $pdo->query($sql_tahun)->fetchAll(PDO::FETCH_COLUMN);
$tahun_pilih = isset($_GET['tahun']) ? (int)$_GET['tahun'] : (int)$tahun_list[0];

$sql_masuk = "SELECT MONTH(tanggal_masuk) AS bulan, COUNT(*) AS jumlah FROM surat WHERE YEAR(tanggal_masuk) = :tahun GROUP BY bulan";
$stmt = $pdo->prepare($sql_masuk);
$stmt->execute(['tahun' => $tahun_pilih]);
$statistik_masuk = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

$sql_keluar = "SELECT MONTH(tanggal_keluar) AS bulan, COUNT(*) AS jumlah FROM surat WHERE YEAR(tanggal_keluar) = :tahun GROUP BY bulan";
$stmt = $pdo->prepare($sql_keluar);
$stmt->execute(['tahun' => $tahun_pilih]);
$statistik_keluar = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

$sql_total_masuk = "SELECT COUNT(*) AS total FROM surat WHERE YEAR(tanggal_masuk) = :tahun";
$stmt = $pdo->prepare($sql_total_masuk);
$stmt->execute(['tahun' => $tahun_pilih]);
$total_masuk = $stmt->fetchColumn();

$sql_total_keluar = "SELECT COUNT(*) AS total FROM surat WHERE YEAR(tanggal_keluar) = :tahun";
$stmt = $pdo->prepare($sql_total_keluar);
$stmt->execute(['tahun' => $tahun_pilih]);
$total_keluar = $stmt->fetchColumn();

$sql_tujuan_masuk = "SELECT tujuan_surat, COUNT(*) AS jumlah FROM surat WHERE tanggal_masuk IS NOT NULL AND YEAR(tanggal_masuk) = :tahun GROUP BY tujuan_surat ORDER BY jumlah DESC";
$stmt = $pdo->prepare($sql_tujuan_masuk);
$stmt->execute(['tahun' => $tahun_pilih]);
$statistik_tujuan_masuk = $stmt->fetchAll(PDO::FETCH_ASSOC);

$sql_instansi_keluar = "SELECT TRIM(SUBSTRING_INDEX(nama_instansi, '(', 1)) AS nama_instansi, COUNT(*) AS jumlah FROM surat WHERE tanggal_keluar IS NOT NULL AND YEAR(tanggal_keluar) = :tahun GROUP BY nama_instansi ORDER BY nama_instansi ASC";
$stmt = $pdo->prepare($sql_instansi_keluar);
$stmt->execute(['tahun' => $tahun_pilih]);
$instansi_keluar = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Statistik Surat - Google Charts</title>
    <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>

    <script>
        google.charts.load('current', {'packages':['corechart', 'bar']});
        google.charts.setOnLoadCallback(drawAllCharts);

        function drawAllCharts() {
            drawSuratBulan();
            drawTujuanMasuk();
            drawInstansiKeluar();
        }

        function drawSuratBulan() {
            const data = google.visualization.arrayToDataTable([
                ['Bulan', 'Surat Masuk', 'Surat Keluar'],
                <?php
                for ($i = 1; $i <= 12; $i++) {
                    $masuk = $statistik_masuk[$i] ?? 0;
                    $keluar = $statistik_keluar[$i] ?? 0;
                    echo "['" . getNamaBulan($i) . "', $masuk, $keluar],\n";
                }
                ?>
            ]);

            const options = {
                title: 'Jumlah Surat Masuk dan Keluar per Bulan',
                chartArea: {width: '70%'},
                hAxis: {title: 'Jumlah Surat'},
                vAxis: {title: 'Bulan'}
            };

            const chart = new google.visualization.ColumnChart(document.getElementById('chartPerBulan'));
            chart.draw(data, options);
        }

        function drawTujuanMasuk() {
            const data = google.visualization.arrayToDataTable([
                ['Tujuan', 'Jumlah'],
                <?php foreach ($statistik_tujuan_masuk as $row): ?>
                    ['<?= addslashes($row['tujuan_surat']) ?>', <?= $row['jumlah'] ?>],
                <?php endforeach; ?>
            ]);

            const options = {
                title: 'Tujuan Surat Masuk',
                pieHole: 0.4
            };

            const chart = new google.visualization.PieChart(document.getElementById('chartTujuanMasuk'));
            chart.draw(data, options);
        }

        function drawInstansiKeluar() {
            const data = google.visualization.arrayToDataTable([
                ['Instansi', 'Jumlah'],
                <?php foreach ($instansi_keluar as $row): ?>
                    ['<?= addslashes($row['nama_instansi']) ?>', <?= $row['jumlah'] ?>],
                <?php endforeach; ?>
            ]);

            const options = {
                title: 'Instansi Surat Keluar'
            };

            const chart = new google.visualization.PieChart(document.getElementById('chartInstansiKeluar'));
            chart.draw(data, options);
        }
    </script>
</head>
<body>
<?php include('includes/header.php'); ?>

<div class="container mt-5">
    <h1 class="text-center mb-4">Statistik Surat (Google Charts)</h1>
    <form method="GET" class="mb-3">
        <label for="tahun">Pilih Tahun:</label>
        <select name="tahun" id="tahun" class="form-select" onchange="this.form.submit()">
            <?php foreach ($tahun_list as $tahun): ?>
                <option value="<?= $tahun ?>" <?= ($tahun == $tahun_pilih) ? 'selected' : '' ?>><?= $tahun ?></option>
            <?php endforeach; ?>
        </select>
    </form>

    <!-- Total Surat -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="alert alert-success text-center">
                <h4>Total Surat Masuk</h4>
                <p><strong><?= $total_masuk ?></strong> Surat</p>
            </div>
        </div>
        <div class="col-md-6">
            <div class="alert alert-primary text-center">
                <h4>Total Surat Keluar</h4>
                <p><strong><?= $total_keluar ?></strong> Surat</p>
            </div>
        </div>
    </div>

    <!-- Chart Area -->
    <div id="chartPerBulan" style="height: 400px;"></div>
    <div class="row mt-5">
        <div class="col-md-6">
            <div id="chartTujuanMasuk" style="height: 400px;"></div>
        </div>
        <div class="col-md-6">
            <div id="chartInstansiKeluar" style="height: 400px;"></div>
        </div>
    </div>
</div>

<?php include('includes/footer.php'); ?>
</body>
</html>
