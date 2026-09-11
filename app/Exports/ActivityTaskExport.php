<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class ActivityTaskExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
{
    protected $results;

    public function __construct($results)
    {
        $this->results = $results;
    }

    /**
     * Data utama untuk diekspor
     */
    public function collection()
    {
        return $this->results;
    }

    /**
     * Header kolom di Excel
     */
    public function headings(): array
    {
        return [
            'No',
            'Course Name',
            'Course Module',
            'Course Item Name',
            'Tipe Tugas',
            'Learner',
            'Hasil',
            'Submitted At',
        ];
    }

    /**
     * Mapping tiap baris ke kolom Excel
     */
    public function map($row): array
    {
        static $no = 0;

        return [
            ++$no,
            $row['course_name'] ?? '-',
            $row['course_module'] ?? '-',
            $row['course_item_name'] ?? '-',
            $row['tipe_tugas'] ?? '-',
            $row['learner'] ?? '-',
            $row['hasil'] ?? '-',
            $row['submitted_at'] ?? '-',
        ];
    }
}
