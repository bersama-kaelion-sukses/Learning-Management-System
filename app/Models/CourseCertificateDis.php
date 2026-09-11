<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CourseCertificateDis extends Model
{
    use HasFactory;

    protected $table = 'course_certificatedis';
    protected $primaryKey = 'certificated_id';
    public $timestamps = true;

    protected $fillable = [
        'course_id',
        'user_id',
        'request_date',
        'issued_date',
        'certificated_url',
        'download_count',
        'verified_by',
        'last_process',
        'is_approved',
        'person_process',
    ];

    protected $casts = [
        'request_date' => 'date',
        'issued_date' => 'date',
        'last_process' => 'datetime',
        'is_approved' => 'boolean',
        'download_count' => 'integer',
    ];
}