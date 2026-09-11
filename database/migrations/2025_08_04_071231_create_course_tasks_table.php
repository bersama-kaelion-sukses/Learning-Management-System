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
        Schema::create('course_task', function (Blueprint $table) {
            $table->id('task_id');
            $table->unsignedBigInteger('course_id');
            $table->string('task_item_title');
            $table->text('task_description')->nullable();
            $table->string('task_type')->nullable();
            $table->date('due_date')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->string('person_process')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('course_tasks');
    }
};
