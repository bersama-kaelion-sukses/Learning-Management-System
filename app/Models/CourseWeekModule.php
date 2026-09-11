<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CourseWeekModule extends Model
{
    use HasFactory;

    protected $table = 'course_week_module';
    protected $primaryKey = 'course_week_id';
    public $timestamps = true;

    protected $fillable = [
        'course_id',
        'course_week_title',
        'course_week_visibility',
        'week_order',
        'course_start',
        'course_end',
        'is_checked',
        'last_process',
        'person_process',
    ];

    protected $casts = [
        'course_week_visibility' => 'boolean',
        'is_checked'             => 'boolean',
        'course_start'           => 'date',
        'course_end'             => 'date',
        'last_process'           => 'datetime',
    ];

    // 🔗 Relasi ke item (materi dalam week)
    public function items()
    {
        return $this->hasMany(CourseWeekItem::class, 'course_week_id', 'course_week_id')->orderBy('item_order', 'asc');
    }

    // 🔗 Relasi balik ke course
    public function course()
    {
        return $this->belongsTo(Course::class, 'course_id', 'course_id');
    }
}
