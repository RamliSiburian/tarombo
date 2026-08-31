<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nodes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->string('name');
            $table->enum('gender', ['male', 'female'])->default('male');
            $table->string('marga')->nullable();
            $table->string('asal_daerah')->nullable();
            $table->string('tahun_lahir', 10)->nullable();
            $table->string('tahun_wafat', 10)->nullable();
            $table->string('foto')->nullable();
            $table->text('deskripsi')->nullable();
            $table->enum('status', ['active', 'pending'])->default('active');
            $table->integer('level')->default(0);
            $table->timestamps();

            $table->foreign('parent_id')->references('id')->on('nodes')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nodes');
    }
};
