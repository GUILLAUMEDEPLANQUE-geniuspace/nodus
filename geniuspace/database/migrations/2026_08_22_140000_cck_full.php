<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cck_fields', function (Blueprint $t) {
            $t->text('options')->default('');
            $t->string('seo_title')->default('');
            $t->string('drip_at')->nullable();
            $t->float('lat')->nullable();
            $t->float('lng')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('cck_fields', function (Blueprint $t) {
            $t->dropColumn(['options', 'seo_title', 'drip_at', 'lat', 'lng']);
        });
    }
};
