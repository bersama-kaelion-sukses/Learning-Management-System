<?php

namespace App\Models\CourseType;

use Illuminate\Database\Eloquent\Model;
use App\Models\CourseWeekItem;

class CourseEssay extends Model
{
    protected $table ='course_item_essays';

    protected $primaryKey = 'essay_id';
    public  $incrementing = true;

    protected $fillable = [
        'item_id',
        'essay_title',
        'instruction',
        'attachment_type',
        'attachment_value',
        'is_essay_submitted',
    ];

    protected $casts = [
        'is_essay_submitted' => 'boolean',
    ];

    public function courseItem() 
    {
        return $this->belongsTo(CourseWeekItem::class, 'item_id', 'item_id');
    }
    public function submissions()
    {
        return $this->hasMany(CourseEssaySubmission::class, 'essay_id', 'essay_id');
    }
    protected static function booted()
    {
        static::deleting(function ($essay) {
            if ($essay->submissions()->exists()) {
                $essay->submissions()->delete();
            }
        });
    }
}