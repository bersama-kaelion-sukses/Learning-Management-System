<?php

namespace App\Exports;

use App\Models\Course;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class CoursesExport implements FromCollection, WithHeadings, WithMapping
{
    protected $courses;

    public function __construct($courses)
    {
        $this->courses = $courses;
    }

    public function collection()
    {
        return $this->courses;
    }

    public function headings(): array
    {
        return [
            'No',
            'Nama Kursus',
            'Kategori Kursus',
            'Nama Trainer',
            'Maksimal Peserta',
            'Kursus Aktif',
            'Tipe Visibilitas',
            'Status Permintaan',
            'Periode',
            'Tanggal Dibuat',
        ];
    }

    public function map($course): array
    {
        static $i = 0;

        return [
            ++$i,
            $course->course_title,
            $course->course_category,
            $course->course_trainer_name,
            $course->max_participant,
            $course->is_approved ? 'Ya' : 'Tidak',
            $course->is_public ? 'Publik' : 'Internal',
            $course->is_requested ? 'Diajukan' : 'Tidak Diajukan',
            "{$course->start_course} — {$course->end_course}",
            optional($course->created_at)->format('d M Y'),
        ];
    }
}
