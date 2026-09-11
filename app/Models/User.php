<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Carbon\Carbon;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $table = 'users';        
    protected $primaryKey = 'user_id';
    public $timestamps = true;

    protected $fillable = [
        'user_id',
        'photo_profile',
        'full_name',
        'emp_id',
        'password',
        'position_id',
        'role_id',
        'sub_role',
        'is_active',
        'departement_cat',
        'is_deleted',
        'person_process',
        'is_first_login',
        'password_changed_at',
        'failed_login_count', 
        'is_locked',
    ];

    protected $hidden = [
        'password',
    ];

    protected $casts = [
        'password' => 'hashed',
        'sub_role' => 'array',
        'is_active' => 'boolean',
        'is_deleted' => 'boolean',
        'role_id' => 'integer',
    ];

    public function position()
    {
        return $this->belongsTo(Position::class, 'position_id', 'position_id');
    }
    
    public function division() 
    {
        return $this->belongsTo(Division::class, 'departement_cat', 'division_id');
    }

    public function mainRole() 
    {
        return $this->belongsTo(LmsRole::class, 'role_id', 'role_id');
    }

    // ✅ Relasi ke enrollment
    public function enrollments()
    {
        return $this->hasMany(CourseEnrollment::class, 'user_id', 'user_id');
    }

    // ✅ Relasi shortcut ke courses lewat enrollment
    public function courses()
    {
        return $this->belongsToMany(Course::class, 'course_enrollment', 'user_id', 'course_id')
            ->withTimestamps();
    }
    public function takeoversAsOldTrainer()
    {
        return $this->hasMany(CourseTakeover::class, 'old_trainer_id', 'user_id');
    }

    // sebagai trainer baru
    public function takeoversAsNewTrainer()
    {
        return $this->hasMany(CourseTakeover::class, 'new_trainer_id', 'user_id');
    }
    public function getPasswordExpiryDaysAttribute()
    {
        if (!$this->password_changed_at) {
            return null; // belum pernah ganti password
        }

        $expiredAt = Carbon::parse($this->password_changed_at)->addMonths(3);
        return now()->diffInDays($expiredAt, false);
        // hasil bisa negatif kalau sudah lewat
    }
}
