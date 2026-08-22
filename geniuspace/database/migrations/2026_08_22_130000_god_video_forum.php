<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Eloquent reste agnostique : sqlite (démo) → mysql (o2switch) → pgsql.
 * Aucun type propriétaire ici.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('media', function (Blueprint $t) {
            $t->string('author_name')->default('Créateur');
            $t->string('author_avatar')->default('/realms/luffy.jpg');
            $t->unsignedBigInteger('user_id')->nullable();
            $t->string('author_role')->default('Auteur');
        });
        Schema::table('threads', function (Blueprint $t) {
            $t->string('author_avatar')->default('/realms/zoro.jpg');
        });
        Schema::table('replies', function (Blueprint $t) {
            $t->string('author_avatar')->default('/realms/nami.jpg');
            $t->unsignedBigInteger('quote_id')->nullable();
        });
        Schema::create('spatial_nodes', function (Blueprint $t) {
            $t->id();
            $t->string('universe_id');
            $t->string('node_id');
            $t->string('kind')->default('core');
            $t->float('x')->default(0);
            $t->float('y')->default(0);
            $t->float('z')->default(0);
            $t->float('radius')->default(50);
            $t->float('angle')->default(0);
            $t->float('speed')->default(0.003);
        });
        Schema::create('forum_awards', function (Blueprint $t) {
            $t->id();
            $t->string('thread_id');
            $t->string('kind')->default('feu');
            $t->string('author')->default('');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('forum_awards');
        Schema::dropIfExists('spatial_nodes');
    }
};
