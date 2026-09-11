<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class AllParticipantExport implements FromCollection, WithHeadings, WithMapping
{
    protected $participants;

    public function __construct($participants)
    {
        $this->participants = $participants;
    }

    public function collection()
    {
        return $this->participants;
    }

    public function headings(): array
    {
        return [
            'No',
            'Nama Learner',
            'Departemen',
            'Kursus',
            'Tanggal Enroll',
            'Status Masuk Kursus',
            'Terakhir Akses',
        ];
    }

    public function map($p): array
    {
        static $i = 0;

        return [
            ++$i,
            $p->user->full_name ?? '-',
            $p->user->division->division_name ?? '-',
            $p->course->course_title ?? '-',
            optional($p->created_at)->format('d M Y H:i'),
            $p->status_opened ?? 'Belum Masuk',
            $p->formatted_last_access ?? '-',
        ];
    }
}
