<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('node_tabs', function (Blueprint $t) {
            $t->string('color')->default('#c9a36a');
            $t->string('bg')->default('');
            $t->boolean('animate')->default(false);
            $t->string('seo_title')->default('');
            $t->text('seo_desc')->default('');
        });
    }

    public function down(): void
    {
        Schema::table('node_tabs', function (Blueprint $t) {
            $t->dropColumn(['color', 'bg', 'animate', 'seo_title', 'seo_desc']);
        });
    }
};
