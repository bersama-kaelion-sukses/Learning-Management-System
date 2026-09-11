<?php

namespace App\Models\CourseType;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

use App\Models\CourseType\CourseForumDiscussion;

class CourseForumDiscussionReply extends Model {
    
    protected $table = 'course_item_forum_replies';
    protected $primaryKey = 'reply_id';
    public $incrementing = true;
    protected $keyType = 'int';

    protected $fillable = [
        'forum_id',
        'user_id',
        'reply_text',
        'parent_id',
        'grade',
        'feedback',
    ];

    // relasi ke Discussion
    public function discussion()
    {
        return $this->belongsTo(CourseForumDiscussion::class, 'forum_id', 'forum_id');
    }
    // relasi ke user
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    // relasi ke parent
    public function parent()
    {
        return $this->belongsTo(CourseForumDiscussionReply::class, 'parent_id');
    }

    // relasi ke children (nested replies)
    public function children()
    {
        return $this->hasMany(CourseForumDiscussionReply::class, 'parent_id')
            ->with('children', 'user'); // recursive
    }
}