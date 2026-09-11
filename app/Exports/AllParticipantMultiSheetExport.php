<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class AllParticipantMultiSheetExport implements WithMultipleSheets
{
    protected $participants;

    public function __construct($participants)
    {
        $this->participants = $participants;
    }

    public function sheets(): array
    {
        return [
            'List Peserta' => new AllParticipantExport($this->participants),
            'Ringkasan Divisi' => new ParticipantDivisionSummaryExport($this->participants),
        ];
    }
}