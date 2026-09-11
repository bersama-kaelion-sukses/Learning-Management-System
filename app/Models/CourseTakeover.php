<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CourseTakeover extends Model
{
    use HasFactory;

    protected $table = 'course_takeovers';
    protected $primaryKey = 'takeover_id';

    protected $fillable = [
        'course_id',
        'old_trainer_id',
        'new_trainer_id',
        'start_date',
        'end_date',
        'status',
        'remarks',
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date'   => 'datetime',
    ];

    // ========================
    // RELASI
    // ========================

    // ke Course
    public function course()
    {
        return $this->belongsTo(Course::class, 'course_id', 'course_id');
    }

    // trainer lama
    public function oldTrainer()
    {
        return $this->belongsTo(User::class, 'old_trainer_id', 'user_id');
    }

    // trainer baru
    public function newTrainer()
    {
        return $this->belongsTo(User::class, 'new_trainer_id', 'user_id');
    }
}
