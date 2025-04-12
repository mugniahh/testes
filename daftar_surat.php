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
$tahun_pilih = isset($_GET['tahun']) ? (int)$_GET['tahun'] : (int)$tahun_list[0];


// Fungsi untuk menghapus surat
if (isset($_GET['delete_id'])) {
    $id = $_GET['delete_id'];

    try {
        $delete_sql = "DELETE FROM surat WHERE id = :id";
        $stmt = $pdo->prepare($delete_sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);

        if ($stmt->execute()) {
            $_SESSION['message'] = "Data berhasil dihapus.";
        } else {
            $_SESSION['error'] = "Gagal menghapus data.";
        }
    } catch (Exception $e) {
        $_SESSION['error'] = "Error: " . $e->getMessage();
    }

    header('Location: daftar_surat.php');
    exit();
}

// Inisialisasi variabel pencarian dan filter
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$jenis_surat = isset($_GET['jenis_surat']) ? $_GET['jenis_surat'] : '';
$tujuan_surat = isset($_GET['tujuan_surat']) ? $_GET['tujuan_surat'] : '';
$tanggal_masuk = isset($_GET['tanggal_masuk']) ? $_GET['tanggal_masuk'] : '';
$tanggal_keluar = isset($_GET['tanggal_keluar']) ? $_GET['tanggal_keluar'] : '';

// Query utama
$sql = "
SELECT 
    id, 
    tanggal_terima, 
    alamat_pengirim, 
    tanggal_surat, 
    nomor_surat, 
    perihal, 
    tujuan_surat, 
    tanggal_masuk, 
    diteruskan,
    tanggal_turun,
    tanggal_keluar, 
    nama_instansi 
FROM surat
WHERE 1=1
";
// Tambahkan filter berdasarkan tahun
if (!empty($tahun_pilih)) {
    $sql .= " AND YEAR(tanggal_terima) = :tahun";
}

$stmt = $pdo->prepare($sql);

// Filter berdasarkan tujuan surat
if (!empty($tujuan_surat)) {
    $sql .= " AND tujuan_surat = :tujuan_surat";
}

// Filter berdasarkan jenis surat
if (!empty($jenis_surat)) {
    if ($jenis_surat === 'Masuk') {
        $sql .= " AND tanggal_masuk IS NOT NULL";
    } elseif ($jenis_surat === 'Keluar') {
        $sql .= " AND tanggal_keluar IS NOT NULL";
    }
}

// Filter berdasarkan pencarian
if (!empty($search)) {
    $sql .= " AND (perihal LIKE :search OR nomor_surat LIKE :search OR alamat_pengirim LIKE :search)";
}

$sql .= " ORDER BY tanggal_terima ASC";

$stmt = $pdo->prepare($sql);
// Bind parameter tahun jika dipilih
if (!empty($tahun_pilih)) {
    $stmt->bindValue(':tahun', $tahun_pilih, PDO::PARAM_INT);
}
// Bind parameter jika ada
if (!empty($tujuan_surat)) {
    $stmt->bindValue(':tujuan_surat', $tujuan_surat);
}
if (!empty($search)) {
    $stmt->bindValue(':search', "%$search%");
}

$stmt->execute();
$surat = $stmt->fetchAll(PDO::FETCH_ASSOC);


?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Surat</title>
    <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .table-blue-header th {
            background-color: #a0c4ff; /* Warna biru soft */
            color: #000; /* Warna teks hitam untuk kontras */
        }
        .table-responsive {
            overflow-x: auto;
            white-space: nowrap;
        }
        .perihal-column {
    white-space: normal; /* Memungkinkan teks turun ke baris berikutnya */
    word-wrap: break-word;
    overflow-wrap: break-word;
    max-width: 800px; /* Atur batas lebar kolom */
}
.alamatpengirim-column {
    white-space: normal; /* Memungkinkan teks turun ke baris berikutnya */
    word-wrap: break-word;
    overflow-wrap: break-word;
    max-width: 800px; /* Atur batas lebar kolom */
}
        
        
    </style>
</head>
<body>
<?php include('includes/header.php'); ?>

<div class="container mt-5">
    <h1 class="mb-4 text-center">Daftar Surat</h1>
    <a href="input_surat.php" class="btn btn-primary mb-3">Tambah Surat</a>
  
    <form method="GET" class="mb-3">
    <!-- Search di atas -->
    <div class="row mb-3">
        <div class="col-md-9 mb-2">
            <input type="text" name="search" class="form-control" placeholder="Cari berdasarkan perihal, nomor surat, atau alamat pengirim..." value="<?= htmlspecialchars($search) ?>">
        </div>
        <div class="col-md-3 mb-2">
            <button type="submit" class="btn btn-primary w-100">Cari</button>
        </div>
    </div>

    <!-- Filter di bawah search -->
    <div class="row mb-3">
        <div class="col-md-3">
            <select name="tahun" class="form-select">
                <?php foreach ($tahun_list as $tahun): ?>
                    <option value="<?= $tahun ?>" <?= ($tahun == $tahun_pilih) ? 'selected' : '' ?>><?= $tahun ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3">
            <select name="tujuan_surat" class="form-select">
                <option value="">Semua Tujuan</option>
                <option value="Gubernur" <?= $tujuan_surat === 'Gubernur' ? 'selected' : '' ?>>Disposisi Gubernur</option>
                <option value="Wakil Gubernur" <?= $tujuan_surat === 'Wakil Gubernur' ? 'selected' : '' ?>>Disposisi Wakil Gubernur</option>
                <option value="SETDA" <?= $tujuan_surat === 'SETDA' ? 'selected' : '' ?>>Disposisi SETDA</option>
                <option value="PLH_SETDA" <?= $tujuan_surat === 'PLH_SETDA' ? 'selected' : '' ?>>Disposisi PLH SETDA</option>
                <option value="Staf_Ahli" <?= $tujuan_surat === 'Staf_Ahli' ? 'selected' : '' ?>>Disposisi Staf Ahli</option>
                <option value="Instansi Lain" <?= $tujuan_surat === 'Instansi Lain' ? 'selected' : '' ?>>Pengelola Instansi</option>
            </select>
        </div>
        <div class="col-md-3">
            <select name="jenis_surat" class="form-select">
                <option value="">Semua Jenis</option>
                <option value="Masuk" <?= $jenis_surat === 'Masuk' ? 'selected' : '' ?>>Surat Masuk</option>
                <option value="Keluar" <?= $jenis_surat === 'Keluar' ? 'selected' : '' ?>>Surat Keluar</option>
            </select>
        </div>
        <div class="col-md-3">
            <button type="submit" class="btn btn-secondary w-100">Terapkan Filter</button>
        </div>
    </div>

    <a href="suratsheetapi.php?tahun=<?= urlencode($tahun_pilih) ?>&tujuan_surat=<?= urlencode($tujuan_surat) ?>&jenis_surat=<?= urlencode($jenis_surat) ?>&search=<?= urlencode($search) ?>" target="_blank" class="btn btn-success mb-3">
    <img src="https://www.gstatic.com/images/branding/product/1x/sheets_2020q4_48dp.png" alt="Google Sheets" style="height:20px; vertical-align:middle; margin-right:5px;">
    Ekspor ke Google Sheets
</a>


</a>
    <div class="table-responsive">
    <table class="table table-hover table-striped table-bordered">
        <thead class="table-blue-header">
            <tr>
                <th>No</th>
                <th>No Agenda</th>
                <th>Tanggal Terima</th>
                <th>Alamat Pengirim</th>
                <th>Tanggal Surat</th>
                <th>Nomor Surat</th>
                <th>Perihal</th>
                <th>Tujuan Surat</th>
                <th>Tanggal Masuk</th>
                <th>Diteruskan</th>
                <th>Tanggal Turun</th>
                <th>Tanggal Keluar</th>
                <th>Nama Instansi</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($surat)): ?>
                <?php $no = 1;             
                $nomor_agenda = 1; 
                $tanggal_sebelumnya = null; ?>
                <?php foreach ($surat as $s): ?>
                    <?php
                        // Jika tanggal berubah, reset nomor agenda ke 1
                        if ($s['tanggal_terima'] !== $tanggal_sebelumnya) {
                            $nomor_agenda = 1;
                            $tanggal_sebelumnya = $s['tanggal_terima'];
                        }
                        ?>
                    <tr>
                        <td><?= $no++ ?></td>
                        <td><?= $nomor_agenda++ ?></td>
                        <td><?= htmlspecialchars($s['tanggal_terima']?? '') ?></td>
                        <td class="alamatpengirim-column"><?=($s['alamat_pengirim']?? '') ?></td>
                        <td><?= htmlspecialchars($s['tanggal_surat'] ?? '') ?></td>
                        <td><?= htmlspecialchars($s['nomor_surat']) ?></td>
                        <td class="perihal-column"><?= htmlspecialchars($s['perihal'] ?? '') ?></td>
                        <td><?= htmlspecialchars($s['tujuan_surat'] ?? '') ?></td>
                        <td><?= htmlspecialchars($s['tanggal_masuk'] ?? '') ?></td>
                        <td><?= htmlspecialchars($s['diteruskan'] ?? '') ?></td>
                        <td><?= htmlspecialchars($s['tanggal_turun'] ?? '') ?></td>
                        <td><?= htmlspecialchars($s['tanggal_keluar'] ?? '') ?></td>
                        <td><?= htmlspecialchars($s['nama_instansi'] ?? '') ?></td>
                        <td>
                            <a href="input_surat.php?edit_id=<?= $s['id'] ?>" class="btn btn-warning btn-sm">Edit</a>
                            <a href="daftar_surat.php?delete_id=<?= $s['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Yakin ingin menghapus surat ini?')">Hapus</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="11" class="text-center">Tidak ada data yang ditemukan.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
<a href="suratsheetapi.php?tahun=<?= urlencode($tahun_pilih) ?>&tujuan_surat=<?= urlencode($tujuan_surat) ?>&jenis_surat=<?= urlencode($jenis_surat) ?>&search=<?= urlencode($search) ?>" target="_blank" class="btn btn-success mb-3">
    <img src="https://www.gstatic.com/images/branding/product/1x/sheets_2020q4_48dp.png" alt="Google Sheets" style="height:20px; vertical-align:middle; margin-right:5px;">
    Ekspor ke Google Sheets
</a>
</div>
<?php include('includes/footer.php'); ?>
</body>
</html>
