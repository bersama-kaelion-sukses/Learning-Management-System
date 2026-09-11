<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CourseLearnerActivity extends Model {

    protected $table = 'course_user_activity';
    protected $fillable = [
        'course_id',
        'user_id',
        'is_opened',
        'last_access',
        'person_process'
    ];

    public $timestamps = true;

    // Relasi opsional
    public function course()
    {
        return $this->belongsTo(Course::class, 'course_id', 'course_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }
}