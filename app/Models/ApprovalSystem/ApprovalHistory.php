<?php

namespace App\Models\ApprovalSystem;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class ApprovalHistory extends Model
{
    protected $table = 'approval_histories';
    protected $primaryKey = 'history_id';
    public $timestamps = false;

    protected $fillable = [
        'request_id',
        'step_no',
        'actor_user_id',
        'action',
        'notes',
        'created_at',
    ];

    // Relasi ke ApprovalRequest
    public function request()
    {
        return $this->belongsTo(ApprovalRequest::class, 'request_id', 'request_id');
    }

    // Relasi ke user actor
    public function actor()
    {
        return $this->belongsTo(User::class, 'actor_user_id', 'user_id');
    }
}
