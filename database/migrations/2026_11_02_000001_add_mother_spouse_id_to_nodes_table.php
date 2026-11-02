<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('nodes', function (Blueprint $table) {
            $table->foreignId('mother_spouse_id')
                ->nullable()
                ->after('parent_id')
                ->constrained('node_spouses')
                ->nullOnDelete();
        });

        // Also add mother_spouse_id to node_requests if not present
        if (!Schema::hasColumn('node_requests', 'mother_spouse_id')) {
            Schema::table('node_requests', function (Blueprint $table) {
                $table->foreignId('mother_spouse_id')
                    ->nullable()
                    ->after('parent_node_id')
                    ->constrained('node_spouses')
                    ->nullOnDelete();
            });
        }

        // Auto-link existing node 281 (Siburian) to its parent's spouse if applicable
        $node = DB::table('nodes')->where('id', 281)->first();
        if ($node && $node->parent_id) {
            $spouse = DB::table('node_spouses')->where('node_id', $node->parent_id)->first();
            if ($spouse) {
                DB::table('nodes')->where('id', 281)->update(['mother_spouse_id' => $spouse->id]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('nodes', function (Blueprint $table) {
            $table->dropForeign(['mother_spouse_id']);
            $table->dropColumn('mother_spouse_id');
        });

        if (Schema::hasColumn('node_requests', 'mother_spouse_id')) {
            Schema::table('node_requests', function (Blueprint $table) {
                $table->dropForeign(['mother_spouse_id']);
                $table->dropColumn('mother_spouse_id');
            });
        }
    }
};
