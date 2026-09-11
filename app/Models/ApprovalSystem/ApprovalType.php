<?php

namespace App\Models\ApprovalSystem;

use Illuminate\Database\Eloquent\Model;

class ApprovalType extends Model
{
    protected $table = 'approval_type';
    protected $primaryKey = 'approval_id';
    public $timestamps = false;

    protected $fillable = [
        'approval_name',
        'is_active',
    ];

    // Relasi: Satu jenis approval punya banyak request
    public function requests()
    {
        return $this->hasMany(ApprovalRequest::class, 'approval_id', 'approval_id');
    }
}