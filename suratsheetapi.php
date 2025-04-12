<?php
require_once('includes/config.php');
require_once 'vendor/autoload.php';

try {
    // Ambil filter dari parameter GET
    $tahun_pilih = $_GET['tahun'] ?? date('Y');
    $tujuan_surat = $_GET['tujuan_surat'] ?? '';
    $jenis_surat = $_GET['jenis_surat'] ?? '';
    $search = $_GET['search'] ?? '';

    // Log debug
    error_log("====== EXPORT SURAT ======");
    error_log("Tahun dipilih: " . $tahun_pilih);

    // SQL query dasar
    $sql = "SELECT * FROM surat WHERE YEAR(tanggal_terima) = :tahun";
    if (!empty($tujuan_surat)) $sql .= " AND tujuan_surat = :tujuan_surat";
    if (!empty($jenis_surat)) {
        if ($jenis_surat === 'Masuk') $sql .= " AND tanggal_masuk IS NOT NULL";
        elseif ($jenis_surat === 'Keluar') $sql .= " AND tanggal_keluar IS NOT NULL";
    }
    if (!empty($search)) {
        $sql .= " AND (perihal LIKE :search OR nomor_surat LIKE :search OR alamat_pengirim LIKE :search)";
    }
    $sql .= " ORDER BY tanggal_terima ASC";

    error_log("SQL final: $sql");

    // Prepare statement
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':tahun', $tahun_pilih, PDO::PARAM_INT);
    if (!empty($tujuan_surat)) $stmt->bindValue(':tujuan_surat', $tujuan_surat);
    if (!empty($search)) $stmt->bindValue(':search', "%$search%");
    $stmt->execute();
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    error_log("Jumlah data ditemukan: " . count($data));

    // === Google Sheets ===
    $client = new Google_Client();
    $client->setAuthConfig('C:\laragon\www\sipatracloud\Credentials.json');
    $client->addScope(Google_Service_Sheets::SPREADSHEETS);

    $service = new Google_Service_Sheets($client);
    $spreadsheetId = '1U-afgipFazIVCFu5Vz-HIrls2yQ0BBKfHMzM7W5oG0k';
    $sheetName = $tahun_pilih;
    $range = $sheetName;

    // Cek apakah sheet dengan nama tahun sudah ada
    $spreadsheet = $service->spreadsheets->get($spreadsheetId);
    $sheetExists = false;
    foreach ($spreadsheet->getSheets() as $sheet) {
        if ($sheet->getProperties()->getTitle() === $sheetName) {
            $sheetExists = true;
            break;
        }
    }

    // Jika belum ada, buat sheet baru
    if (!$sheetExists) {
        $batchUpdateRequest = new Google_Service_Sheets_BatchUpdateSpreadsheetRequest([
            'requests' => [[
                'addSheet' => [
                    'properties' => ['title' => $sheetName]
                ]
            ]]
        ]);
        $service->spreadsheets->batchUpdate($spreadsheetId, $batchUpdateRequest);
        error_log("Sheet '$sheetName' berhasil dibuat.");
    } else {
        error_log("Sheet '$sheetName' sudah ada.");
    }

    // Buat data yang dikirim ke Google Sheets
    $values = [];

    // Baris pertama: info tahun
    $values[] = ["Tahun Data: $tahun_pilih"];

    // Header
    $values[] = [
        'No', 'No Agenda', 'Tanggal Terima', 'Alamat Pengirim', 'Tanggal Surat',
        'Nomor Surat', 'Perihal', 'Tujuan Surat', 'Tanggal Masuk', 'Diteruskan',
        'Tanggal Turun', 'Tanggal Keluar', 'Nama Instansi'
    ];

    // Isi data
    $no = 1;
    $nomor_agenda = 1;
    $tanggal_sebelumnya = null;

    foreach ($data as $s) {
        if ($s['tanggal_terima'] !== $tanggal_sebelumnya) {
            $nomor_agenda = 1;
            $tanggal_sebelumnya = $s['tanggal_terima'];
        }

        $values[] = [
            $no++,
            $nomor_agenda++,
            $s['tanggal_terima'] ?? '',
            $s['alamat_pengirim'] ?? '',
            $s['tanggal_surat'] ?? '',
            $s['nomor_surat'] ?? '',
            $s['perihal'] ?? '',
            $s['tujuan_surat'] ?? '',
            $s['tanggal_masuk'] ?? '',
            $s['diteruskan'] ?? '',
            $s['tanggal_turun'] ?? '',
            $s['tanggal_keluar'] ?? '',
            $s['nama_instansi'] ?? ''
        ];
    }

    // Kosongkan isi sheet terlebih dahulu
    $clear = new Google_Service_Sheets_ClearValuesRequest();
    $service->spreadsheets_values->clear($spreadsheetId, $range, $clear);

    // Kirim data ke Google Sheet
    $body = new Google_Service_Sheets_ValueRange(['values' => $values]);
    $params = ['valueInputOption' => 'RAW'];
    $service->spreadsheets_values->update($spreadsheetId, $range, $body, $params);

 // Output sukses ke log
 error_log("✅ Export ke Google Sheet sukses. Baris: " . count($values));

 // Redirect langsung ke Google Sheet (tab yang sama)
 $sheetUrl = "https://docs.google.com/spreadsheets/d/$spreadsheetId/edit#gid=0";
 header("Location: $sheetUrl");
 exit;

} catch (Exception $e) {
 error_log("❌ Exception: " . $e->getMessage());
 echo "❌ Gagal kirim: " . htmlspecialchars($e->getMessage());
}
