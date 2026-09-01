<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('nodes', function (Blueprint $table) {
            $table->integer('sort_order')->default(1)->after('level');
        });

        Schema::table('node_requests', function (Blueprint $table) {
            $table->integer('anak_ke')->nullable()->after('gender');
            $table->integer('sort_order')->default(1)->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('nodes', function (Blueprint $table) {
            $table->dropColumn('sort_order');
        });

        Schema::table('node_requests', function (Blueprint $table) {
            $table->dropColumn(['anak_ke', 'sort_order']);
        });
    }
};
