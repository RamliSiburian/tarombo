<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('node_requests', function (Blueprint $table) {
            $table->foreignId('node_id')->nullable()->after('parent_node_id')->constrained('nodes')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('node_requests', function (Blueprint $table) {
            $table->dropForeign(['node_id']);
            $table->dropColumn('node_id');
        });
    }
};
