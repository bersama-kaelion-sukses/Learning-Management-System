<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class InstructorFeedbackExport implements FromCollection, WithHeadings, WithStyles
{
    protected $data;

    public function __construct($data)
    {
        // Data sudah dimapping dari controller
        $this->data = collect($data);
    }

    /**
     * Export the mapped data rows
     */
    public function collection()
    {
        return $this->data;
    }

    /**
     * Excel Header Columns
     */
    public function headings(): array
    {
        return [
            'Course',
            'Learner Name',
            'Division',
            'Submitted At',

            // Q1–Q14 Likert Scale (Sudah mapped text)
            'Q1',
            'Q2',
            'Q3',
            'Q4',
            'Q5',
            'Q6',
            'Q7',
            'Q8',
            'Q9',
            'Q10',
            'Q11',
            'Q12',
            'Q13',
            'Q14',

            // Q15–Q17 Free Text
            'Q15 - Saran / Feedback 1',
            'Q16 - Saran / Feedback 2',
            'Q17 - Saran / Feedback 3',
        ];
    }

    /**
     * Basic Excel Styling
     */
    public function styles(Worksheet $sheet)
    {
        return [
            // Header row style
            1 => [
                'font' => [
                    'bold' => true,
                    'size' => 12,
                ]
            ]
        ];
    }
}
