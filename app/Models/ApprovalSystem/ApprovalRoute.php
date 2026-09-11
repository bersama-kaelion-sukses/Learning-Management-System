<?php

namespace App\Models\ApprovalSystem;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class ApprovalRoute extends Model
{
    protected $table = 'approval_routes';
    protected $primaryKey = 'route_id';

    protected $fillable = [
        'request_id',
        'step_no',
        'approver_user_id',
        'backup_user_id',
        'action_status',
        'acted_by',
        'acted_at',
        'notes',
    ];

    // Relasi ke ApprovalRequest
    public function request()
    {
        return $this->belongsTo(ApprovalRequest::class, 'request_id', 'request_id');
    }

    // Relasi ke approver
    public function approver()
    {
        return $this->belongsTo(User::class, 'approver_user_id', 'user_id');
    }

    // Relasi ke backup approver
    public function backup()
    {
        return $this->belongsTo(User::class, 'backup_user_id', 'user_id');
    }

    // Relasi ke actor yang melakukan approve/reject
    public function actor()
    {
        return $this->belongsTo(User::class, 'acted_by', 'user_id');
    }
}
