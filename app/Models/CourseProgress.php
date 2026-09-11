<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Course;

class CourseProgress extends Model
{
    use HasFactory;

    // Nama tabel sesuai DB
    protected $table = 'course_progress';
    protected $primaryKey = 'progress_id';
    public $timestamps = true;

    // Kolom yang boleh diisi mass-assignment
    protected $fillable = [
        'user_id',
        'course_id',
        'course_week_id',
        'course_item_id',
        'total_module',
        'total_item',
        'total_checked',
        'progress_pct',
        'current_step_module',
        'current_step_item',
        'status_course',
        'status_item',
        'last_update',
    ];

    // Casting tipe data
    protected $casts = [
        'total_module'        => 'integer', 
        'total_item'          => 'integer',
        'total_checked'       => 'integer',
        'progress_pct'        => 'float',
        'current_step_module' => 'integer',
        'current_step_item'   => 'integer',
        'status_course'       => 'boolean', 
        'status_item'         => 'boolean',
        'last_update'         => 'datetime',
        'created_at'          => 'datetime',
        'updated_at'          => 'datetime',
    ];

    public function course()
    {
        return $this->belongsTo(Course::class, 'course_id', 'course_id');
    }
    
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }
}
