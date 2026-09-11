<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class LmsRole extends Model
{
    protected $table = 'roles';
        protected $primarykey = 'role_id';
        public $timestamps = true;
}
