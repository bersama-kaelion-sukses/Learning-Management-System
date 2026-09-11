<?php

namespace App\Models\CourseType;

use Illuminate\Database\Eloquent\Model;
use App\Models\courseType\CourseEssay;
use App\Models\CourseWeekItem;
use App\Models\User;

class CourseEssaySubmission extends Model
{
    protected $table = 'course_essay_submissions';
    protected $primaryKey = 'essay_submission_id';
    public $incrementing = true;

    protected $fillable = [
        'essay_id',
        'item_id',
        'user_id',
        'answer_text',
        'is_graded',
        'grade',
        'feedback',
        'is_remedial',
        'submitted_at',

    ];

    protected $casts = [
        'is_graded' => 'boolean',
        'submitted_at' => 'datetime',
    ];

    // Relasi
    public function essay()
    {
        return $this->belongsTo(CourseEssay::class, 'essay_id', 'essay_id');
    }

    public function item()
    {
        return $this->belongsTo(CourseWeekItem::class, 'item_id', 'item_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }
}

