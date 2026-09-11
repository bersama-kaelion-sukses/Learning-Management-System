<?php

namespace App\Models\ApprovalSystem;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class ApprovalRequest extends Model
{
    protected $table = 'approval_requests';
    protected $primaryKey = 'request_id';

    protected $fillable = [
        'approval_id',
        'requester_id',
        'request_title',
        'request_detail',
        'status',
        'current_step',
        'submitted_at',
    ];

    protected $casts = [
        'submitted_at' => 'datetime', 
    ];
    // Relasi ke ApprovalType
    public function approvalType()
    {
        return $this->belongsTo(ApprovalType::class, 'approval_id', 'approval_id');
    }

    // Relasi ke User (requester/pengaju)
    public function requester()
    {
        return $this->belongsTo(User::class, 'requester_id', 'user_id');
    }

    // Relasi ke routes
    public function routes()
    {
        return $this->hasMany(ApprovalRoute::class, 'request_id', 'request_id')
            ->orderBy('step_no');
    }

    // Relasi ke histories
    public function histories()
    {
        return $this->hasMany(ApprovalHistory::class, 'request_id', 'request_id')
            ->orderBy('created_at', 'asc');
    }
}
