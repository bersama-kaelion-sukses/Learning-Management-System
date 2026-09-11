<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class LearnerProgressExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
{
    protected $results;

    public function __construct($results)
    {
        $this->results = $results;
    }

    public function collection()
    {
        return $this->results;
    }

    public function headings(): array
    {
        return [
            'No',
            'Course Title',
            'Learner Name',
            'Division',
            'Total Items',
            'Checked Items',
            'Progress (%)',
            'Status',
            'Last Updated',
        ];
    }

    public function map($row): array
    {
        static $no = 0;
        return [
            ++$no,
            $row['course_title'] ?? '-',
            $row['learner_name'] ?? '-',
            $row['division_name'] ?? '-',
            $row['total_items'] ?? 0,
            $row['checked_items'] ?? 0,
            $row['progress_pct'] ?? '0%',
            $row['status_course'] ?? '-',
            $row['last_update'] ?? '-',
        ];
    }
}
