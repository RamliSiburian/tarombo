<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('nodes', function (Blueprint $table) {
            // Marks whether the birth order (sort_order) of this node
            // among its siblings has been officially confirmed by an admin.
            // false = unconfirmed / possible conflict
            // true  = admin has verified the birth order is correct
            $table->boolean('order_confirmed')->default(false)->after('sort_order');
        });
    }

    public function down(): void
    {
        Schema::table('nodes', function (Blueprint $table) {
            $table->dropColumn('order_confirmed');
        });
    }
};
