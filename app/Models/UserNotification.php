<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class UserNotification extends Model
{
    use HasFactory;

    protected $table = 'user_notifications';
    protected $primaryKey = 'notification_id';

    protected $fillable = [
        'user_id',
        'source_type',
        'source_id',
        'message',
        'redirect_url',
        'status',
        'is_read',
    ];

    protected $casts = [
        'is_read' => 'boolean',
    ];

    /**
     * Relasi ke user penerima notifikasi.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    /**
     * Scope untuk notifikasi belum dibaca.
     */
    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    /**
     * Scope untuk notifikasi yang sudah dibaca.
     */
    public function scopeRead($query)
    {
        return $query->where('is_read', true);
    }

    /**
     * Tandai notifikasi sebagai sudah dibaca.
     */
    public function markAsRead()
    {
        $this->is_read = true;
        $this->status = 'read';
        $this->save();
    }

    /**
     * Tandai notifikasi sebagai baru lagi.
     */
    public function markAsUnread()
    {
        $this->is_read = false;
        $this->status = 'new';
        $this->save();
    }
}
