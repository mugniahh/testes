<?php
require_once('includes/config.php');
require_once 'vendor/autoload.php';

try {
    $downloadType = $_GET['download'] ?? '';

    // Fungsi untuk mendapatkan nama bulan
    function namaBulan($bulan) {
        $nama_bulan = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni',
            7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
        ];
        return isset($nama_bulan[$bulan]) ? $nama_bulan[$bulan] : '';
    }

    $data = [];
    $sheetTitle = 'Arsip_' . ucfirst(str_replace('_', '', $downloadType));

    // Menentukan jenis data berdasarkan downloadType
    if ($downloadType === 'surat_masuk') {
        $stmt = $pdo->query("SELECT MONTH(tanggal_masuk) AS bulan, YEAR(tanggal_masuk) AS tahun, tujuan_surat, COUNT(*) AS jumlah 
                             FROM surat 
                             WHERE tanggal_masuk IS NOT NULL 
                             GROUP BY bulan, tahun, tujuan_surat");
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $headers = ['Bulan', 'Tahun', 'Tujuan Surat', 'Jumlah Surat'];
    } elseif ($downloadType === 'surat_keluar') {
        $stmt = $pdo->query("SELECT MONTH(tanggal_keluar) AS bulan, YEAR(tanggal_keluar) AS tahun, 
                             SUBSTRING_INDEX(nama_instansi, '(', 1) AS nama_instansi_bersih, COUNT(*) AS jumlah 
                             FROM surat 
                             WHERE tanggal_keluar IS NOT NULL 
                             GROUP BY bulan, tahun, nama_instansi_bersih");
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $headers = ['Bulan', 'Tahun', 'Nama Instansi', 'Jumlah Surat'];
    } elseif ($downloadType === 'total_surat') {
        $masuk = $pdo->query("SELECT MONTH(tanggal_masuk) AS bulan, YEAR(tanggal_masuk) AS tahun, COUNT(*) AS jumlah 
                              FROM surat 
                              WHERE tanggal_masuk IS NOT NULL 
                              GROUP BY bulan, tahun")->fetchAll(PDO::FETCH_ASSOC);
        $keluar = $pdo->query("SELECT MONTH(tanggal_keluar) AS bulan, YEAR(tanggal_keluar) AS tahun, COUNT(*) AS jumlah 
                               FROM surat 
                               WHERE tanggal_keluar IS NOT NULL 
                               GROUP BY bulan, tahun")->fetchAll(PDO::FETCH_ASSOC);

        $data = array_merge(
            array_map(fn($r) => $r + ['jenis' => 'Masuk'], $masuk),
            array_map(fn($r) => $r + ['jenis' => 'Keluar'], $keluar)
        );
        $headers = ['Jenis', 'Bulan', 'Tahun', 'Jumlah Surat'];
    } else {
        throw new Exception("Jenis arsip tidak valid.");
    }

    // Debugging (hapus setelah selesai testing)
    if (empty($data)) {
        throw new Exception("Data kosong. Cek kembali hasil query.");
    }

    // Setup Google Sheets API
    $client = new Google_Client();
    $client->setAuthConfig('C:\laragon\www\sipatracloud\Credentials.json');
    $client->addScope(Google_Service_Sheets::SPREADSHEETS);

    $service = new Google_Service_Sheets($client);
    $spreadsheetId = '1j7lETXXFogHENEyKfSlJlnGjqsvtFq2PQ5mppxWseJE'; // Ganti dengan ID Spreadsheet kamu
    $range = "$sheetTitle!A1";

    // Cek dan buat sheet jika belum ada
    $spreadsheet = $service->spreadsheets->get($spreadsheetId);
    $sheetExists = false;
    foreach ($spreadsheet->getSheets() as $sheet) {
        if ($sheet->getProperties()->getTitle() === $sheetTitle) {
            $sheetExists = true;
            break;
        }
    }

    if (!$sheetExists) {
        $batchUpdateRequest = new Google_Service_Sheets_BatchUpdateSpreadsheetRequest([
            'requests' => [[
                'addSheet' => ['properties' => ['title' => $sheetTitle]]
            ]]
        ]);
        $service->spreadsheets->batchUpdate($spreadsheetId, $batchUpdateRequest);
    }

    // Kosongkan sheet sebelum isi ulang
    $clear = new Google_Service_Sheets_ClearValuesRequest();
    $service->spreadsheets_values->clear($spreadsheetId, $range, $clear);

    // Siapkan data untuk dikirim
    $values = [];
    $values[] = ["Ekspor Arsip - " . ucfirst(str_replace('_', ' ', $downloadType))];
    $values[] = $headers;

    // Proses data baris demi baris
    foreach ($data as $row) {
        $rowData = [];
        if ($downloadType === 'surat_masuk') {
            $rowData = [
                namaBulan($row['bulan']),
                $row['tahun'],
                $row['tujuan_surat'],
                $row['jumlah']
            ];
        } elseif ($downloadType === 'surat_keluar') {
            $rowData = [
                namaBulan($row['bulan']),
                $row['tahun'],
                empty($row['nama_instansi_bersih']) ? 'Instansi Tidak Diketahui' : $row['nama_instansi_bersih'],
                $row['jumlah']
            ];
        } elseif ($downloadType === 'total_surat') {
            $rowData = [
                $row['jenis'],
                namaBulan($row['bulan']),
                $row['tahun'],
                $row['jumlah']
            ];
        }

        if (count($rowData) == count($headers)) {
            $values[] = $rowData;
        } else {
            throw new Exception("Data tidak sesuai dengan format yang diharapkan.");
        }
    }

    // Kirim data ke Google Sheets
    $body = new Google_Service_Sheets_ValueRange([
        'values' => $values
    ]);
    $params = ['valueInputOption' => 'RAW'];
    $service->spreadsheets_values->update($spreadsheetId, $range, $body, $params);

    // Redirect ke Google Sheets (tanpa sheetName yang undefined)
    $spreadsheetUrl = "https://docs.google.com/spreadsheets/d/$spreadsheetId/edit";
    header("Location: $spreadsheetUrl");
    exit();
} catch (Exception $e) {
    echo "❌ Gagal kirim ke kirim spreadsheet: " . htmlspecialchars($e->getMessage());
}
?>
