<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('course', function (Blueprint $table) {
            $table->id('course_id');
            $table->string('course_title');
            $table->string('course_category');
            $table->string('course_related_dept')->nullable();
            $table->string('course_trainer_name')->nullable();
            $table->text('course_describe')->nullable();
            $table->integer('max_participant')->default(0);
            $table->string('course_image')->nullable();
            $table->boolean('is_approved')->default(false);
            $table->boolean('is_public')->default(false);
            $table->timestamp('last_process')->nullable();
            $table->string('person_process')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('courses');
    }
};
