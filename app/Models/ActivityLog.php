<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class ActivityLog extends Model
{
    use HasFactory;

    // Nama tabel sesuai yang kamu buat
    protected $table = 'activity_logs';

    // Kolom yang boleh diisi (mass assignable)
    protected $fillable = [
        'user_id',
        'activity_desc',
        'created_at',
        'updated_at',
    ];

    // Relasi ke tabel users (optional)
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    /**
     * Helper untuk mencatat aktivitas baru.
     * Contoh pemanggilan:
     * ActivityLog::record("Mengakses halaman Dashboard IT");
     */
    public static function record(string $desc)
    {
        self::create([
            'user_id' => Auth::id(),
            'activity_desc' => $desc,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
