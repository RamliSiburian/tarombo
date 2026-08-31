<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('node_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_node_id')->constrained('nodes')->onDelete('cascade');
            $table->string('name');
            $table->enum('gender', ['male', 'female'])->default('male');
            $table->string('marga')->nullable();
            $table->string('asal_daerah')->nullable();
            $table->string('tahun_lahir', 10)->nullable();
            $table->string('tahun_wafat', 10)->nullable();
            $table->string('foto')->nullable();
            $table->text('deskripsi')->nullable();
            // Spouse data (if male)
            $table->string('spouse_name')->nullable();
            $table->string('spouse_marga')->nullable();
            $table->string('spouse_foto')->nullable();
            $table->text('spouse_deskripsi')->nullable();
            // Requester info
            $table->string('requester_name');
            $table->string('requester_email');
            // Admin review
            $table->enum('status', ['pending', 'accepted', 'rejected'])->default('pending');
            $table->text('admin_note')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('node_requests');
    }
};
