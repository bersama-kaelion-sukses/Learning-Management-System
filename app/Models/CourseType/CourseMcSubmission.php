<?php

namespace App\Models\CourseType;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Models\CourseWeekItem;

class CourseMcSubmission extends Model
{
    use HasFactory;

    protected $table = 'course_mc_submissions';
    protected $primaryKey = 'mc_submission_id';
    public $incrementing = true;

    protected $fillable = [
        'item_id',
        'user_id',
        'questions',   // JSON (array of question_id)
        'grade',
        'answer_details',
        'feedback',
        'submitted_at',
        'is_remedial',
        'attempt_no'
    ];

    protected $casts = [
        'answer_details' => 'array',
        'questions' => 'array',       // otomatis decode/encode JSON
        'submitted_at' => 'datetime',
    ];

    // Relasi ke user
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    // Relasi ke item (CourseWeekItem)
    public function item()
    {
        return $this->belongsTo(CourseWeekItem::class, 'item_id', 'item_id');
    }
}
