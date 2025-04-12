<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit();
}

require_once('includes/config.php');

// Periksa apakah ini edit atau tambah data
$is_edit = isset($_GET['edit_id']) ? true : false;

// Jika edit, ambil data dari database berdasarkan ID
if ($is_edit) {
    $edit_id = $_GET['edit_id'];

    $stmt = $pdo->prepare("SELECT * FROM surat WHERE id = :id");
    $stmt->execute(['id' => $edit_id]);
    $data = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$data) {
        die("Data tidak ditemukan.");
    }
}

// Jika form disubmit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tanggal_terima = !empty($_POST['tanggal_terima']) ? $_POST['tanggal_terima'] : null;
    $alamat_pengirim = $_POST['alamat_pengirim'];
    $tanggal_surat = $_POST['tanggal_surat'];
    $nomor_surat = $_POST['nomor_surat'];
    $perihal = $_POST['perihal'];
    $tujuan_surat = $_POST['tujuan_surat'];
    $tanggal_masuk = !empty($_POST['tanggal_masuk']) ? $_POST['tanggal_masuk'] : null;
    $diteruskan = $_POST['diteruskan'] ;
    $tanggal_turun = !empty($_POST['tanggal_turun']) ? $_POST['tanggal_turun'] : null;
    $tanggal_keluar = !empty($_POST['tanggal_keluar']) ? $_POST['tanggal_keluar'] : null;
    $nama_instansi = !empty($_POST['nama_instansi']) ? $_POST['nama_instansi'] : null;

    
    if (!$is_edit) {
        // Mengambil nomor agenda berdasarkan tanggal surat yang terakhir
        $nomor_agenda_sql = "SELECT MAX(no_agenda) FROM surat WHERE tanggal_terima = :tanggal_terima";
        $stmt = $pdo->prepare($nomor_agenda_sql);
        $stmt->bindValue(':tanggal_terima', $tanggal_terima);
        $stmt->execute();
        $nomor_agenda = $stmt->fetchColumn();

        // Jika tidak ada nomor agenda, mulai dari 1
        if (!$nomor_agenda) {
            $nomor_agenda = 1;
        } else {
            // Jika ada, increment nomor agenda
            $nomor_agenda++;
        }
    }

    try {
        if ($is_edit) {
            // Update data jika ini edit
            $stmt = $pdo->prepare("UPDATE surat SET 
                tanggal_terima = :tanggal_terima,
                alamat_pengirim = :alamat_pengirim,
                tanggal_surat = :tanggal_surat,
                nomor_surat = :nomor_surat,
                perihal = :perihal,
                tujuan_surat = :tujuan_surat,
                tanggal_masuk = :tanggal_masuk,
                diteruskan = :diteruskan,
                tanggal_turun = :tanggal_turun,
                tanggal_keluar = :tanggal_keluar,
                nama_instansi = :nama_instansi
                WHERE id = :id");

            $stmt->execute([
                'tanggal_terima' => $tanggal_terima,
                'alamat_pengirim' => $alamat_pengirim,
                'tanggal_surat' => $tanggal_surat,
                'nomor_surat' => $nomor_surat,
                'perihal' => $perihal,
                'tujuan_surat' => $tujuan_surat,
                'tanggal_masuk' => $tanggal_masuk,
                'diteruskan' => $diteruskan,
                'tanggal_turun' => $tanggal_turun,
                'tanggal_keluar' => $tanggal_keluar,
                'nama_instansi' => $nama_instansi,
                'id' => $edit_id
            ]);

            $_SESSION['message'] = "Data berhasil diperbarui.";
        } else {
            // Tambah data baru jika ini tambah
            $stmt = $pdo->prepare("INSERT INTO surat 
                (tanggal_terima, alamat_pengirim, tanggal_surat, nomor_surat, perihal, tujuan_surat, 
                tanggal_masuk,diteruskan, tanggal_turun,  tanggal_keluar, nama_instansi, no_agenda) 
            VALUES 
                (:tanggal_terima, :alamat_pengirim, :tanggal_surat, :nomor_surat, :perihal, :tujuan_surat, 
                :tanggal_masuk, :diteruskan, :tanggal_turun, :tanggal_keluar, :nama_instansi, :no_agenda)");

            $stmt->execute([
                'tanggal_terima' => $tanggal_terima,
                'alamat_pengirim' => $alamat_pengirim,
                'tanggal_surat' => $tanggal_surat,
                'nomor_surat' => $nomor_surat,
                'perihal' => $perihal,
                'tujuan_surat' => $tujuan_surat,
                'tanggal_masuk' => $tanggal_masuk,
                'diteruskan' => $diteruskan,
                'tanggal_turun' => $tanggal_turun,
                'tanggal_keluar' => $tanggal_keluar,
                'nama_instansi' => $nama_instansi,
                'no_agenda' => $nomor_agenda
            ]);

            $_SESSION['message'] = "Data berhasil ditambahkan.";
        }

        // Redirect ke daftar_surat.php
        header('Location: daftar_surat.php');
        exit();
    } catch (Exception $e) {
        $_SESSION['error'] = "Terjadi kesalahan: " . $e->getMessage();
        header('Location: daftar_surat.php');
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $is_edit ? 'Edit' : 'Tambah' ?> Surat</title>
    <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<?php include('includes/header.php'); ?>

<div class="container mt-4">
    <h1><?= $is_edit ? 'Edit' : 'Tambah' ?> Surat</h1>
    <form method="POST">
        <div class="mb-3">
            <label for="tanggal_terima">Tanggal Terima</label>
            <input type="date" id="tanggal_terima" name="tanggal_terima" class="form-control" value="<?= isset($data['tanggal_terima']) ? htmlspecialchars($data['tanggal_terima']) : '' ?>" >
        </div>
        <div class="mb-3">
            <label for="alamat_pengirim">Alamat Pengirim</label>
            <input type="text" id="alamat_pengirim" name="alamat_pengirim" class="form-control" value="<?= isset($data['alamat_pengirim']) ? htmlspecialchars($data['alamat_pengirim']) : '' ?>" >
        </div>
        <div class="mb-3">
            <label for="tanggal_surat">Tanggal Surat</label>
            <input type="date" id="tanggal_surat" name="tanggal_surat" class="form-control" value="<?= isset($data['tanggal_surat']) ? htmlspecialchars($data['tanggal_surat']) : '' ?>" >
        </div>
        <div class="mb-3">
            <label for="nomor_surat">Nomor Surat</label>
            <input type="text" id="nomor_surat" name="nomor_surat" class="form-control" value="<?= isset($data['nomor_surat']) ? htmlspecialchars($data['nomor_surat']) : '' ?>" >
        </div>
        <div class="mb-3">
            <label for="perihal">Perihal</label>
            <input type="text" id="perihal" name="perihal" class="form-control" value="<?= isset($data['perihal']) ? htmlspecialchars($data['perihal']) : '' ?>" >
        </div>
        <div class="mb-3">
            <label for="tujuan_surat">Tujuan Surat</label>
            <select id="tujuan_surat" name="tujuan_surat" class="form-control" required>
                <option value="Gubernur" <?= isset($data['tujuan_surat']) && $data['tujuan_surat'] == 'Gubernur' ? 'selected' : '' ?>>Disposisi  Gubernur</option>
                <option value="SETDA" <?= isset($data['tujuan_surat']) && $data['tujuan_surat'] == 'SETDA' ? 'selected' : '' ?>>Disposisi SETDA</option>
                <option value="PLH_SETDA" <?= isset($data['tujuan_surat']) && $data['tujuan_surat'] == 'PLH_SETDA' ? 'selected' : '' ?>>Disposisi PLH SETDA</option>
                <option value="Staf_Ahli" <?= isset($data['tujuan_surat']) && $data['tujuan_surat'] == 'Staf_Ahli' ? 'selected' : '' ?>>Disposisi Staf Ahli</option>
                <option value="Instansi Lain" <?= isset($data['tujuan_surat']) && $data['tujuan_surat'] == 'Instansi Lain' ? 'selected' : '' ?>>Pengelola Instansi</option>
            </select>
        </div>
        <div class="mb-3">
            <label for="tanggal_masuk">Tanggal Masuk</label>
            <input type="date" id="tanggal_masuk" name="tanggal_masuk" class="form-control" value="<?= isset($data['tanggal_masuk']) ? htmlspecialchars($data['tanggal_masuk']) : '' ?>">
        </div>
        <div class="mb-3">
            <label for="diteruskan">Diteruskan</label>
            <input type="text" id="diteruskan" name="diteruskan" class="form-control" value="<?= isset($data['diteruskan']) ? htmlspecialchars($data['diteruskan']) : '' ?>">
        </div>
        <div class="mb-3">
            <label for="tanggal_turun">Tanggal Turun</label>
            <input type="date" id="tanggal_turun" name="tanggal_turun" class="form-control" value="<?= isset($data['tanggal_turun']) ? htmlspecialchars($data['tanggal_turun']) : '' ?>">
        </div>
        <div class="mb-3">
            <label for="tanggal_keluar">Tanggal Keluar</label>
            <input type="date" id="tanggal_keluar" name="tanggal_keluar" class="form-control" value="<?= isset($data['tanggal_keluar']) ? htmlspecialchars($data['tanggal_keluar']) : '' ?>">
        </div>
        <div class="mb-3">
            <label for="nama_instansi">Nama Instansi</label>
            <input type="text" id="nama_instansi" name="nama_instansi" class="form-control" value="<?= isset($data['nama_instansi']) ? htmlspecialchars($data['nama_instansi']) : '' ?>">
        </div>
        <button type="submit" class="btn btn-primary"><?= $is_edit ? 'Simpan' : 'Tambah' ?></button>
    </form>
</div>

<?php include('includes/footer.php'); ?>
</body>
</html>
