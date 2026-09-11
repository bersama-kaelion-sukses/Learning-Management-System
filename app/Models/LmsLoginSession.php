<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LmsLoginSession extends Model
{
    use HasFactory;

    protected $table = 'login_sessions';    // Nama tabel
    protected $primaryKey = 'login_id';     // Primary Key custom
    public $timestamps = true;              // Ada created_at & updated_at

    protected $fillable = [
        'emp_id',
        'user_id',
        'role_id',
        'sub_role',       // JSON
        'session',
        'login_time',
        'device_info',
        'ip_address',
        'is_success',
        'logout_time',
    ];

    protected $casts = [
        'sub_role' => 'array',       // JSON
        'is_success' => 'boolean',   // boolean
        'login_time' => 'datetime',  // timestamp
        'logout_time' => 'datetime', // timestamp
    ];
}
