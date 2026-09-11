<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CourseEnrollment extends Model
{
    use HasFactory;

    protected $table = 'course_enrollment';
    protected $primaryKey = 'enrollment_id';
    public $timestamps = true;

    protected $fillable = [
        'course_id',
        'user_id',
        'enroll_date',
        'status_join',
        'is_approve',
        'last_process',
        'person_process',
    ];

    protected $casts = [
        'status_join' => 'boolean',
        'is_approve' => 'boolean',
        'enroll_date' => 'date',
        'last_process' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    public function course()
    {
        return $this->belongsTo(Course::class, 'course_id', 'course_id');
    }
    public function activity()
    {
        return $this->hasOne(CourseLearnerActivity::class, 'user_id', 'user_id')
            ->whereRaw("course_user_activity.course_id = ?", [$this->course_id]);
    }
}
