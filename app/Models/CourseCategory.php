<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class CourseCategory extends Model
{
    protected $table = 'course_categories';
        protected $primaryKey = 'category_id';
        public $timestamps = false;
}