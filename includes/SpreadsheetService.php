<?php

class SpreadsheetService
{
    protected $Client;

    public function __construct($Client)
    {
        $this->Client = $Client;
    }

    public function insertRow($data)
    {
        // Anggap ini manggil Google API
        return $this->Client->append($data);
    }
}
