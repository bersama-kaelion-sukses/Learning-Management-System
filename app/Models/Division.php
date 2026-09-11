<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Division extends Model
{
    protected $table = 'divisions';
        protected $primaryKey = 'division_id';
        public $timestamps = false;
}