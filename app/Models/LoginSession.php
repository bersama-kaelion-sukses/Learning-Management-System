<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LoginSession extends Model
{
    protected $table = 'login_sessions';
        protected $primaryKey = 'login_id';

    
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }
}