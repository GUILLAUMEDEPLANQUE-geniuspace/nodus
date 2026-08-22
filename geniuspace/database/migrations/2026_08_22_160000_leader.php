<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('nodes', function (Blueprint $t) {
            $t->string('host')->default('')->index();
        });
        Schema::table('products', function (Blueprint $t) {
            $t->string('city')->default('');
            $t->float('lat')->nullable();
            $t->float('lng')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('nodes', function (Blueprint $t) {
            $t->dropColumn('host');
        });
        Schema::table('products', function (Blueprint $t) {
            $t->dropColumn(['city', 'lat', 'lng']);
        });
    }
};
