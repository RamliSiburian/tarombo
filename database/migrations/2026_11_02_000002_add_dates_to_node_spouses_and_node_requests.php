<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('node_spouses', function (Blueprint $table) {
            $table->string('tahun_lahir', 10)->nullable()->after('marga');
            $table->string('tahun_wafat', 10)->nullable()->after('tahun_lahir');
        });

        Schema::table('node_requests', function (Blueprint $table) {
            $table->string('spouse_tahun_lahir', 10)->nullable()->after('spouse_marga');
            $table->string('spouse_tahun_wafat', 10)->nullable()->after('spouse_tahun_lahir');
        });
    }

    public function down(): void
    {
        Schema::table('node_requests', function (Blueprint $table) {
            $table->dropColumn(['spouse_tahun_lahir', 'spouse_tahun_wafat']);
        });

        Schema::table('node_spouses', function (Blueprint $table) {
            $table->dropColumn(['tahun_lahir', 'tahun_wafat']);
        });
    }
};
