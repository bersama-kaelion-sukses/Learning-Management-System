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
        Schema::create('course_enrollment', function (Blueprint $table) {
            $table->id('enrollment_id');
            $table->unsignedBigInteger('batch_id')->nullable();
            $table->unsignedBigInteger('course_id');
            $table->unsignedBigInteger('user_id');
            $table->date('enroll_date')->nullable();
            $table->boolean('status_join')->default(false);
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
        Schema::dropIfExists('course_enrollments');
    }
};
