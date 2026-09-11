<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConsultationRequest extends Model
{
    use HasFactory;

    protected $table = 'consultation_requests';
    protected $primaryKey = 'consultation_id';

    // Kolom yang bisa diisi secara mass-assignment
    protected $fillable = [
        'course_id',
        'trainer_id',
        'learner_id',
        'topic',
        'status_consultation',
        'feedback',
    ];

    // Default cast dan tipe data otomatis
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // =============================
    // 🔗 Relasi antar tabel
    // =============================

    public function course()
    {
        return $this->belongsTo(Course::class, 'course_id', 'course_id');
    }

    public function trainer()
    {
        return $this->belongsTo(User::class, 'trainer_id', 'user_id');
    }

    public function learner()
    {
        return $this->belongsTo(User::class, 'learner_id', 'user_id');
    }
}
