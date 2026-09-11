<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ParticipantDivisionSummaryExport implements FromCollection, WithHeadings
{
    protected $participants;

    public function __construct($participants)
    {
        $this->participants = $participants;
    }

    public function collection()
    {
        // Hitung total per divisi
        $summary = collect($this->participants)
            ->groupBy(fn($p) => $p->user->division->division_name ?? 'Tidak Ada Divisi')
            ->map(function ($group, $divisionName) {
                $total = $group->count();
                $joined = $group->where('has_entered', 'Sudah Masuk')->count();

                return [
                    'division' => $divisionName,
                    'total_participant' => $total,
                    'joined' => $joined,
                    'not_joined' => $total - $joined,
                ];
            })
            ->values();

        // Hitung total keseluruhan
        $grandTotal = $summary->sum('total_participant');
        $summaryWithPercent = $summary->map(function ($item) use ($grandTotal) {
            $item['percentage'] = $grandTotal > 0 
                ? round(($item['total_participant'] / $grandTotal) * 100, 2) . '%'
                : '0%';
            return $item;
        });

        return new Collection($summaryWithPercent);
    }

    public function headings(): array
    {
        return [
            'Divisi',
            'Total Peserta',
            'Sudah Masuk',
            'Belum Masuk',
            'Kontribusi (%)',
        ];
    }
}
