<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('node_requests', function (Blueprint $table) {
            $table->boolean('is_self')->default(false)->after('requester_email');
            $table->string('requester_phone', 25)->nullable()->after('is_self');
            $table->foreignId('user_id')->nullable()->after('is_self')->constrained('users')->nullOnDelete();
        });

        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'node_id')) {
                $table->foreignId('node_id')->nullable()->after('membership_status')->constrained('nodes')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'node_id')) {
                $table->dropForeign(['node_id']);
                $table->dropColumn('node_id');
            }
        });

        Schema::table('node_requests', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn(['is_self', 'requester_phone', 'user_id']);
        });
    }
};
