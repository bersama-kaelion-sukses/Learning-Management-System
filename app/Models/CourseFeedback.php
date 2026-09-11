<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model; // ← ini yang benar

class CourseFeedback extends Model {

    protected $table = 'course_feedback';
    protected $primaryKey = 'feedback_id';

    const CREATED_AT = 'submitted_at';
    const UPDATED_AT = 'updated_at';
    
    protected $fillable = [
        'course_id',
        'user_id',
        'q1', 'q2', 'q3', 'q4', 'q5', 'q6', 'q7',
        'q8', 'q9', 'q10', 'q11', 'q12', 'q13', 'q14',
        'q15', 'q16', 'q17',
        'submitted_at'
    ];

        protected $dates = [
        'submitted_at',
        'updated_at'
    ];
        // 🔗 Relasi ke Course
    public function course()
    {
        return $this->belongsTo(Course::class, 'course_id', 'course_id');
    }

    // 🔗 Relasi ke User
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }
}