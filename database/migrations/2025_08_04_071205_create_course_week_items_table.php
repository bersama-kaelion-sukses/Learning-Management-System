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
        Schema::create('course_week_item', function (Blueprint $table) {
            $table->id('item_id');
            $table->unsignedBigInteger('course_id');
            $table->unsignedBigInteger('course_week_id');
            $table->string('course_item_name');
            $table->text('course_describe')->nullable();
            $table->string('course_item_type')->nullable();
            $table->date('course_due_start')->nullable();
            $table->date('course_due_end')->nullable();
            $table->boolean('course_one_timesubmitted')->default(false);
            $table->string('course_media')->nullable();
            $table->string('course_pre_requirment')->nullable();
            $table->json('course_attachment')->nullable();
            $table->string('course_assignment')->nullable();
            $table->boolean('is_checked')->default(false);
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
        Schema::dropIfExists('course_week_items');
    }
};
