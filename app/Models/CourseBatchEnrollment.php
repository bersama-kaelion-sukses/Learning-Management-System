<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CourseBatchEnrollment extends Model
{
    use HasFactory;

    protected $table = 'course_batch_enrollment';
    protected $primaryKey = 'batch_id';
    public $timestamps = true;

    protected $fillable = [
        'course_id',
        'batch_name',
        'user_id',
        'assign_date',
        'is_approve',
        'person_process',
        'last_process',
    ];

    protected $casts = [
        'assign_date' => 'date',
        'is_approve' => 'boolean',
        'last_process' => 'datetime',
    ];
}
