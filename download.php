<?php
require_once('includes/config.php');

if (isset($_GET['type'])) {
    $type = $_GET['type'];

    // Query untuk mendapatkan data surat
    $sql = "
    SELECT 
        'Masuk' AS jenis_surat,
        tanggal_terima,
        alamat_pengirim,
        tanggal_surat,
        nomor_surat,
        perihal,
        tujuan_surat,
        tanggal_masuk,
        tanggal_keluar,
        nama_instansi
    FROM surat_masuk

    UNION

    SELECT 
        'Keluar' AS jenis_surat,
        tanggal_terima,
        alamat_pengirim,
        tanggal_surat,
        nomor_surat,
        perihal,
        tujuan_surat,
        tanggal_masuk,
        tanggal_keluar,
        nama_instansi
    FROM surat_keluar
    ORDER BY tanggal_terima ASC;
    ";

    $stmt = $pdo->query($sql);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if ($type === 'excel') {
        // Download sebagai file Excel
        header("Content-Type: application/vnd.ms-excel");
        header("Content-Disposition: attachment; filename=daftar_surat.xls");

        // Header kolom
        echo "Jenis Surat\tTanggal Terima\tAlamat Pengirim\tTanggal Surat\tNomor Surat\tPerihal\tTujuan Surat\tTanggal Masuk\tTanggal Keluar\tNama Instansi\n";

        // Data baris
        foreach ($data as $row) {
            echo implode("\t", array_map('htmlspecialchars', $row)) . "\n";
        }
        exit();
    } elseif ($type === 'pdf') {
        // Download sebagai file PDF
        require('vendor/fpdf/fpdf.php');

        $pdf = new FPDF();
        $pdf->AddPage();
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell(0, 10, 'Daftar Surat', 0, 1, 'C');

        // Header tabel
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell(25, 10, 'Jenis Surat', 1);
        $pdf->Cell(30, 10, 'Tgl Terima', 1);
        $pdf->Cell(40, 10, 'Alamat Pengirim', 1);
        $pdf->Cell(30, 10, 'Tgl Surat', 1);
        $pdf->Cell(30, 10, 'No Surat', 1);
        $pdf->Cell(40, 10, 'Perihal', 1);
        $pdf->Ln();

        // Data tabel
        $pdf->SetFont('Arial', '', 9);
        foreach ($data as $row) {
            $pdf->Cell(25, 10, $row['jenis_surat'], 1);
            $pdf->Cell(30, 10, $row['tanggal_terima'], 1);
            $pdf->Cell(40, 10, $row['alamat_pengirim'], 1);
            $pdf->Cell(30, 10, $row['tanggal_surat'], 1);
            $pdf->Cell(30, 10, $row['nomor_surat'], 1);
            $pdf->Cell(40, 10, $row['perihal'], 1);
            $pdf->Ln();
        }

        $pdf->Output('D', 'daftar_surat.pdf');
        exit();
    } else {
        echo "Tipe file tidak valid!";
        exit();
    }
} else {
    echo "Parameter 'type' tidak ditemukan!";
    exit();
}
