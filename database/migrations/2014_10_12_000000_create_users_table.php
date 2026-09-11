<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id('user_id');                    // PK
            $table->string('full_name');              // Var
            $table->string('emp_id')->nullable();     // FK optional
            $table->string('password');               // Var
            $table->unsignedBigInteger('role_id');    // int FK -> roles
            $table->json('sub_role')->nullable();     // Null JSON
            $table->boolean('is_active')->default(true);
            $table->string('departement_cat')->nullable();
            $table->boolean('is_deleted')->default(false);
            $table->string('person_process')->nullable();
            $table->timestamps();                     // created_at & updated_at
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
