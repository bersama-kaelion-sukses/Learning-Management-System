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
        Schema::create('course_certificatedis', function (Blueprint $table) {
            $table->id('certificated_id');
            $table->unsignedBigInteger('course_id');
            $table->unsignedBigInteger('user_id');
            $table->date('request_date')->nullable();
            $table->date('issued_date')->nullable();
            $table->string('certificated_url')->nullable();
            $table->integer('download_count')->default(0);
            $table->string('verified_by')->nullable();
            $table->timestamp('last_process')->nullable();
            $table->boolean('is_approved')->default(false);
            $table->string('person_process')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('course_certificate_dis');
    }
};
