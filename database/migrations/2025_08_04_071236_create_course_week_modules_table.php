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
        Schema::create('course_week_module', function (Blueprint $table) {
            $table->id('course_week_id');
            $table->unsignedBigInteger('course_id');
            $table->string('course_week_title');
            $table->boolean('course_week_visibility')->default(true);
            $table->date('course_start')->nullable();
            $table->date('course_end')->nullable();
            $table->boolean('is_checked')->default(false);
            $table->boolean('is_approve')->default(false);
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
        Schema::dropIfExists('course_week_modules');
    }
};
