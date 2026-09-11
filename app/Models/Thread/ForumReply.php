<?php

namespace App\Models\Thread;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class ForumReply extends Model
{
    use HasFactory;

    protected $table = 'forum_replies';
    protected $primaryKey = 'reply_id';
    public $incrementing = true;
    protected $keyType = 'int';

    protected $fillable = [
        'thread_id',
        'parent_id',
        'replied_by',
        'reply_content',
        'attachment_type',
        'attachment_path',
    ];

    /**
     * Relasi ke thread induk
     */
    public function thread()
    {
        return $this->belongsTo(ForumThread::class, 'thread_id');
    }

    /**
     * Relasi ke user yang membalas
     */
    public function replier()
    {
        return $this->belongsTo(User::class, 'replied_by');
    }

    /**
     * Relasi ke balasan lainnya (nested)
     */
    public function replies()
    {
        return $this->hasMany(ForumReply::class, 'parent_id')
                    ->orderBy('created_at', 'asc');
    }

    public function user() 
    {
        return $this->belongsTo(User::class, 'user_id');
    }
    public function repliedBy()
    {
        return $this->belongsTo(User::class, 'replied_by', 'user_id');
    }
    /**
     * Relasi ke parent reply (jika ini adalah balasan terhadap balasan)
     */
    public function parent()
    {
        return $this->belongsTo(ForumReply::class, 'parent_id');
    }
}
