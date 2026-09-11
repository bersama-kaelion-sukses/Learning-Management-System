<?php

namespace App\Models\CourseType;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use App\Models\CourseWeekItem;
use App\Models\User;

class CourseAttachmentSubmission extends Model
{
    use HasFactory;

    protected $table = 'course_attachment_submissions';
    protected $primaryKey = 'submission_id';
    public $timestamps = false; // karena kita pakai submitted_at custom

    protected $fillable = [
        'user_id',
        'item_id',
        'file_path',
        'grade',
        'is_remedial',
        'submitted_at',

    ];

    // Relasi ke User
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // Relasi ke Item
    public function item()
    {
        return $this->belongsTo(CourseWeekItem::class, 'item_id');
    }
}
