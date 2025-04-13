<?php

namespace App;

class SpreadsheetService
{
    protected $service;

    public function __construct($googleSheetService)
    {
        $this->service = $googleSheetService;
    }

    public function insertRow($data)
    {
        // Anggap spreadsheetId dan range sudah ditentukan
        $spreadsheetId = 'xxx';
        $range = 'Sheet1!A1';
        $valueRange = new \Google_Service_Sheets_ValueRange([
            'values' => [array_values($data)]
        ]);

        $params = ['valueInputOption' => 'RAW'];

        return $this->service->spreadsheets_values->append(
            $spreadsheetId,
            $range,
            $valueRange,
            $params
        );
    }
}
