<?php

namespace App\Models\Thread;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Models\Course;

class ForumThread extends Model
{
    use HasFactory;

    protected $table = 'forum_threads';
    protected $primaryKey = 'thread_id';
    public $incrementing = true;
    protected $keyType = 'int';

    protected $fillable = [
        'thread_seq',
        'course_id',
        'created_by',
        'topic_title',
        'forum_title',
        'forum_question',
        'attachment_type',
        'attachment_path',
    ];

    /**
     * Relasi ke model Course (induk thread)
     */
    public function course()
    {
        return $this->belongsTo(Course::class, 'course_id');
    }

    /**
     * Relasi ke User (pembuat thread)
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by', 'user_id');
    }

    /**
     * Relasi ke semua reply (hanya level 1)
     */
    public function replies()
    {
        return $this->hasMany(ForumReply::class, 'thread_id')
                    ->whereNull('parent_id')
                    ->orderBy('created_at', 'asc');
    }

    /**
     * Relasi semua reply secara nested
     */
    public function allReplies()
    {
        return $this->hasMany(ForumReply::class, 'thread_id');
    }
}
