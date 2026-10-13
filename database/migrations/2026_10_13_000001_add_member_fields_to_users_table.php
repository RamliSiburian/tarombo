<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('marga')->nullable()->after('name');
            $table->string('phone', 20)->nullable()->after('marga');
            // role column may already exist; add if not
            if (!Schema::hasColumn('users', 'role')) {
                $table->string('role')->default('member')->after('phone');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['marga', 'phone']);
        });
    }
};
