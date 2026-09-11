<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CourseWeekItem extends Model
{
    use HasFactory;

    protected $table = 'course_week_item';
    protected $primaryKey = 'item_id';
    public $timestamps = true;

    protected $fillable = [
        'course_id',
        'course_week_id',
        'course_item_name',
        'item_order',
        'course_describe',
        'course_item_type',
        'course_due_start',
        'course_due_end',
        'course_duration',
        'passing_grade',
        'course_multiply_chance', 
        'course_media',
        'course_assignment',
        'last_process',
        'person_process',
    ];

    protected $casts = [
        'course_due_start'        => 'datetime',
        'course_due_end'          => 'datetime',
        'last_process'            => 'datetime',
    ];

    // 🔗 Relasi ke module (week)
    public function module()
    {
        return $this->belongsTo(CourseWeekModule::class, 'course_week_id', 'course_week_id');
    }

    // 🔗 Relasi ke essay
    public function essay()
    {
        return $this->hasOne(\App\Models\CourseType\CourseEssay::class, 'item_id', 'item_id');
    }

    // 🔗 Relasi ke forum
    public function forum()
    {
        return $this->hasOne(\App\Models\CourseType\CourseForumDiscussion::class, 'item_id', 'item_id');
    }

    // 🔗 Relasi ke questions + options
    public function questions()
    {
        return $this->hasMany(\App\Models\CourseType\CourseTypeQuestion::class, 'item_id', 'item_id')
            ->with('options');
    }

    // 🔗 Relasi ke course
    public function course()
    {
        return $this->belongsTo(Course::class, 'course_id', 'course_id');
    }

    // 🔗 Relasi ke submissions
    public function submissions()
    {
        return $this->hasMany(\App\Models\CourseType\CourseEssaySubmission::class, 'item_id', 'item_id');
    }
    public function attachment() 
    {
        return $this->hasMany(\App\Models\CourseType\CourseAttachmentSubmission::class, 'item_id', 'item_id');
    }

    public function mcSubmissions()
    {
        return $this->hasMany(\App\Models\CourseType\CourseMcSubmission::class, 'item_id', 'item_id');
    }
}
