<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\CourseProgress;

class CourseSubmission extends Model
{
    use HasFactory;

    // Nama tabel di database
    protected $table = 'course_submission';
    protected $primaryKey = 'submission_id';
    public $timestamps = true;

    // Kolom yang bisa diisi mass-assignment
    protected $fillable = [
        'progress_id',
        'course_id',
        'course_week_id',
        'course_item_id',
        'user_id',
        'status_item',
        'submission_file',
        'score',
        'grade_result',
    ];

    // Casting tipe data
    protected $casts = [
        'progress_id'    => 'integer',
        'course_id'      => 'integer',
        'course_week_id' => 'integer',
        'course_item_id' => 'integer',
        'user_id'        => 'integer',
        'status_item'    => 'boolean',   // 0 / 1
        'score'          => 'float',
        'created_at'     => 'datetime',
        'updated_at'     => 'datetime',
    ];

    // Relasi ke CourseProgress
    public function progress()
    {
        return $this->belongsTo(CourseProgress::class, 'progress_id', 'progress_id');
    }
}
