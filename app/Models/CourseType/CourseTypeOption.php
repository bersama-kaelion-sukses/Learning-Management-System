<?php

namespace App\Models\CourseType;
use Illuminate\Database\Eloquent\Model;

use App\Models\CourseType\Course;

class CourseTypeOption extends Model {
    protected $table = 'course_item_options';
    protected $primaryKey = 'option_id';   // <- tambahin
    public $incrementing = true;
    protected $keyType = 'int';

    protected $fillable = [
        'question_id',
        'option_text',
        'is_correct', 
    ];

    public function question()
    {
        return $this->belongsTo(CourseTypeQuestion::class, 'question_id', 'question_id');
    }
}