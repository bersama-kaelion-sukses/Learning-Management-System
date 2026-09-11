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
        Schema::create('course_batch_enrollment', function (Blueprint $table) {
            $table->id('batch_id');
            $table->unsignedBigInteger('course_id');
            $table->string('batch_name');
            $table->unsignedBigInteger('user_id');
            $table->date('assign_date')->nullable();
            $table->boolean('is_approve')->default(false);
            $table->string('person_process')->nullable();
            $table->timestamp('last_process')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('course_batch_enrollments');
    }
};
