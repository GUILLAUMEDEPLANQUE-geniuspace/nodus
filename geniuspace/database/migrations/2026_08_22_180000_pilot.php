<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('node_seo', function (Blueprint $t) {
            $t->string('gsc')->default('');
        });
        Schema::create('applications', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('user_id');
            $t->string('node_id');
            $t->text('relics')->default('[]');
            $t->string('status')->default('sent');
        });
        Schema::create('index_pings', function (Blueprint $t) {
            $t->id();
            $t->string('node_id');
            $t->string('url');
            $t->unsignedSmallInteger('http')->default(0);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('index_pings');
        Schema::dropIfExists('applications');
        Schema::table('node_seo', fn (Blueprint $t) => $t->dropColumn('gsc'));
    }
};
