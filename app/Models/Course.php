<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Course extends Model
{
    use HasFactory;

    protected $table = 'course';
    protected $primaryKey = 'course_id';
    public $timestamps = true;

    protected $fillable = [
        'course_title',
        'course_category',
        'course_trainer_id',
        'course_trainer_name',
        'course_describe',
        'max_participant',
        'start_course',
        'end_course',
        'course_image',
        'is_approved',
        'is_public',
        'is_deleted',
        'is_requested',
        'is_draft',
        'is_takeover',
        'last_process',
        'person_process',
    ];

    protected $casts = [
        'start_course' => 'datetime',
        'end_course'   => 'datetime',
        'is_deleted'   => 'boolean',
        'is_approved'  => 'boolean',
        'is_public'    => 'boolean',
        'is_requested' => 'boolean',
        'last_process' => 'datetime',
    ];

    // 🔗 Relasi ke enrollment
    public function enrollments()
    {
        return $this->hasMany(CourseEnrollment::class, 'course_id', 'course_id');
    }

    // 🔗 Relasi ke trainer (User)
    public function trainer()
    {
        return $this->belongsTo(User::class, 'course_trainer_id', 'user_id');
    }

    // 🔗 Relasi ke Division berdasarkan category
    public function division()
    {
        return $this->belongsTo(Division::class, 'course_category', 'division_name');
    }

    // 🔗 Relasi ke minggu (CourseWeekModule)
    public function weeks()
    {
        return $this->hasMany(CourseWeekModule::class, 'course_id', 'course_id')->orderBy('week_order', 'asc');;
    }
    public function takeovers() {
       return $this->hasMany(CourseTakeover::class, 'course_id', 'course_id');
    }
    public function activeEnrollments()
    {
        return $this->hasMany(CourseLearnerActivity::class, 'course_id')->where('is_opened', 1);
    }

    public function nonActiveEnrollments()
    {
        return $this->hasMany(CourseLearnerActivity::class, 'course_id')->where('is_opened', 0);
    }
}
