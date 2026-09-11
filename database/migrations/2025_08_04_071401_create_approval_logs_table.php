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
        Schema::create('approval_log', function (Blueprint $table) {
            $table->id('log_id');                     // PK
            $table->string('related_table');          // Nama tabel terkait
            $table->unsignedBigInteger('related_id'); // ID dari tabel terkait
            $table->string('status')->nullable();     // Status approval
            $table->text('note')->nullable();         // Catatan
            $table->unsignedBigInteger('approved_by')->nullable(); // FK ke users.user_id
            $table->timestamp('approved_at')->nullable();          // Waktu approval
            $table->integer('level')->nullable();     // Level approval
            $table->string('action_type')->nullable();// Jenis aksi (approve/reject/revise)
            $table->string('attachment_url')->nullable(); // Lampiran (opsional)
            $table->timestamp('created_at')->useCurrent(); // Waktu dibuat
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('approval_logs');
    }
};
