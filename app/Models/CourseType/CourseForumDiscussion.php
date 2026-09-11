<?php

namespace App\Models\CourseType;

use Illuminate\Database\Eloquent\Model;
use App\Models\CourseType\CourseForumDiscussionReply;
use App\Models\CourseWeekItem;

class CourseForumDiscussion extends Model
{
    protected $table = 'course_item_forums'; // atau "forums" kalau tabelnya pakai nama forum
    protected $primaryKey = 'forum_id';
    public $incrementing = true;
    protected $keyType = 'int';

    protected $fillable = [
        'item_id',
        // 'course_id',
        'forum_title',
        'forum_question',
        'attachment_type',
        'attachment_value',
        'is_draft',
    ];
    // relasi ke Item (kalau ada model Item)
    public function item()
    {
        return $this->belongsTo(CourseWeekItem::class, 'item_id','item_id');
    }
    public function replies()
    {
        return $this->hasMany(CourseForumDiscussionReply::class, 'forum_id', 'forum_id')
            ->whereNull('parent_id') 
            ->with('children', 'user');
    }
}