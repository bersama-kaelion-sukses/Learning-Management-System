<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CourseTask extends Model
{
    use HasFactory;

    protected $table = 'course_task';
    protected $primaryKey = 'task_id';
    public $timestamps = true;

    protected $fillable = [
        'course_id',
        'task_item_title',
        'task_description',
        'task_type',
        'due_date',
        'person_process',
    ];

    protected $casts = [
        'due_date' => 'date',
    ];
}