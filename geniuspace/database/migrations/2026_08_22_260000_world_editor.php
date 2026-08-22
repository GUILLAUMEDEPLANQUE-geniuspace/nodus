<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Couche créateur : thème, scène, actions. Le moteur (panier, unlock, graphe) reste figé.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('node_theme', function (Blueprint $t) {
            $t->string('node_id')->primary();
            $t->string('primary')->default('#c9a36a');
            $t->string('bg')->default('#07080c');
            $t->string('fg')->default('#f3eadc');
            $t->string('muted')->default('#8d8794');
            $t->string('logo')->default('');
            $t->string('favicon')->default('');
            $t->string('hero')->default('');
            $t->string('hero_video')->default('');
            $t->string('poster')->default('');
            $t->string('skin')->default('');
            $t->string('dock')->default('bottom');
            $t->string('display_font')->default('');
            $t->text('extra_json')->default('');
        });
        Schema::create('node_scene_layers', function (Blueprint $t) {
            $t->id();
            $t->string('node_id');
            $t->string('tab')->default('');
            $t->string('kind')->default('image');
            $t->string('label')->default('');
            $t->string('src')->default('');
            $t->text('body')->default('');
            $t->float('x')->default(10);
            $t->float('y')->default(10);
            $t->float('w')->default(30);
            $t->float('h')->default(20);
            $t->unsignedSmallInteger('z')->default(1);
            $t->float('opacity')->default(1);
            $t->string('action_key')->default('');
            $t->string('action_target')->default('');
            $t->boolean('visible')->default(true);
            $t->boolean('locked')->default(false);
            $t->string('motion')->default('none');
            $t->unsignedSmallInteger('delay_ms')->default(0);
            $t->index('node_id');
        });
        Schema::create('node_actions', function (Blueprint $t) {
            $t->id();
            $t->string('node_id');
            $t->string('scope');
            $t->string('action_key');
            $t->string('label');
            $t->string('icon')->default('');
            $t->string('variant')->default('primary');
            $t->unsignedInteger('sort')->default(0);
            $t->boolean('enabled')->default(true);
            $t->string('href')->default('');
            $t->text('extra_json')->default('');
            $t->index(['node_id', 'scope']);
        });
        Schema::table('node_tabs', function (Blueprint $t) {
            $t->boolean('enabled')->default(true);
        });
    }

    public function down(): void
    {
        Schema::table('node_tabs', function (Blueprint $t) {
            $t->dropColumn('enabled');
        });
        Schema::dropIfExists('node_actions');
        Schema::dropIfExists('node_scene_layers');
        Schema::dropIfExists('node_theme');
    }
};
