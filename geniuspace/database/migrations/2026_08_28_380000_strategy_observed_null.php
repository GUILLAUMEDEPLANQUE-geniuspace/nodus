<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('ghost_strategies')) {
            return;
        }
        if (! Schema::hasColumn('ghost_strategies', 'observed_null')) {
            Schema::table('ghost_strategies', function (Blueprint $t) {
                $t->boolean('observed_null')->default(true);
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('ghost_strategies') && Schema::hasColumn('ghost_strategies', 'observed_null')) {
            Schema::table('ghost_strategies', function (Blueprint $t) {
                $t->dropColumn('observed_null');
            });
        }
    }
};
