<?php

use PHPUnit\Framework\TestCase;
use App\SpreadsheetService;

class SpreadsheetServiceTest extends TestCase
{
    public function testInsertRowReturnsTrue()
    {
        // Step 1: Buat mock untuk Google_Service_Sheets_Resource_SpreadsheetsValues
        $mockValues = $this->createMock(\Google_Service_Sheets_Resource_SpreadsheetsValues::class);

        // Step 2: Atur return value
        $mockValues->method('append')->willReturn(true); // Atau response dummy

        // Step 3: Buat mock untuk Google_Service_Sheets dan inject mock values ke dalamnya
        $mockService = $this->getMockBuilder(\Google_Service_Sheets::class)
            ->disableOriginalConstructor()
            ->onlyMethods([])
            ->getMock();

        // Inject manual properti $spreadsheets
        $mockService->spreadsheets_values = $mockValues;

        // Step 4: Buat service kamu dan test
        $service = new SpreadsheetService($mockService);
        $result = $service->insertRow(['Nama' => 'Aldo']);

        $this->assertTrue($result);
    }
}
