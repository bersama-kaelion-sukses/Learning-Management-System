<?php

namespace App\Models\CourseType;
use Illuminate\Database\Eloquent\Model;

class CourseTypeQuestion extends Model
{
    protected $table = 'course_item_questions';
    protected $primaryKey = 'question_id';   // <- tambahin
    public $incrementing = true;             // <- tambahin
    protected $keyType = 'int';              // <- tambahin

    protected $fillable = [
        'item_id',
        'question_text',
        'question_image'
    ];

    public function options()
    {
        return $this->hasMany(CourseTypeOption::class, 'question_id', 'question_id');
    }
}
