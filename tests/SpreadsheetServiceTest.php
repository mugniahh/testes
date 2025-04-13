<?php

use PHPUnit\Framework\TestCase;

class GoogleSheetsServiceTest extends TestCase {

    public function testGetSpreadsheetData() {
        // Membuat mock untuk Google Sheets API Client
        $mockClient = $this->createMock(Google_Service_Sheets::class);
        $mockSheets = $this->createMock(Google_Service_Sheets_SpreadsheetValues::class);

        // Membuat data yang akan dikembalikan oleh mock
        $mockSheets->method('getValues')->willReturn([
            ['A1', 'B1'],
            ['A2', 'B2']
        ]);

        // Mengatur agar mock client mengembalikan mockSheets saat dipanggil
        $mockClient->spreadsheets->values = $mockSheets;

        // Menginstansiasi service dengan client yang dimock
        $googleSheetsService = new GoogleSheetsService($mockClient);

        // Menguji apakah data yang diambil sesuai dengan yang di-mock
        $result = $googleSheetsService->getSpreadsheetData('some_spreadsheet_id');
        
        // Memastikan hasil yang dikembalikan sesuai dengan mock
        $this->assertEquals([
            ['A1', 'B1'],
            ['A2', 'B2']
        ], $result);
    }
}
