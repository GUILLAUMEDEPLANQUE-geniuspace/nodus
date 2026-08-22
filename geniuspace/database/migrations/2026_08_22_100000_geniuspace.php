<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nodes', function (Blueprint $t) {
            $t->string('id')->primary();
            $t->string('slug')->unique();
            $t->string('kind');
            $t->string('title');
            $t->string('subtitle')->default('');
            $t->text('summary')->default('');
            $t->text('body')->default('');
            $t->string('hero')->default('');
            $t->string('skin')->default('living'); // living | vera
            $t->boolean('featured')->default(false);
        });
        Schema::create('edges', function (Blueprint $t) {
            $t->id();
            $t->string('from_id');
            $t->string('to_id');
            $t->string('kind')->default('parent_of');
            $t->string('label')->default('');
        });
        Schema::create('media', function (Blueprint $t) {
            $t->id();
            $t->string('node_id');
            $t->string('title');
            $t->string('path'); // storage path, jamais URL publique brute si gated
            $t->string('mode')->default('lore');
            $t->string('access')->default('free');
            $t->unsignedInteger('teaser_sec')->default(0);
            $t->string('price')->default('');
            $t->string('duration')->default('');
            $t->text('chapters')->default('');
            $t->text('transcript')->default('');
        });
        Schema::create('products', function (Blueprint $t) {
            $t->string('id')->primary();
            $t->string('node_id');
            $t->string('title');
            $t->string('price');
            $t->text('summary')->default('');
            $t->string('kind')->default('objet');
            $t->string('rating')->default('0');
            $t->unsignedInteger('votes')->default(0);
            $t->string('stock')->default('');
            $t->boolean('rwa')->default(false);
            $t->unsignedInteger('energy')->default(20);
            $t->string('image')->default('');
        });
        Schema::create('threads', function (Blueprint $t) {
            $t->string('id')->primary();
            $t->string('node_id');
            $t->string('kind')->default('forum');
            $t->string('title');
            $t->string('author');
            $t->text('body');
            $t->string('cover')->default('');
        });
        Schema::create('wiki_pages', function (Blueprint $t) {
            $t->id();
            $t->string('node_id');
            $t->string('title');
            $t->text('body');
        });
        Schema::create('quests', function (Blueprint $t) {
            $t->id();
            $t->string('node_id');
            $t->unsignedTinyInteger('step')->default(1);
            $t->string('title');
            $t->string('skill')->default('');
            $t->text('prompt');
            $t->string('option_a');
            $t->string('option_b');
        });
        Schema::create('crowd_goals', function (Blueprint $t) {
            $t->string('node_id')->primary();
            $t->unsignedInteger('target')->default(10000);
            $t->unsignedInteger('current')->default(0);
            $t->string('reward')->default('');
        });
        Schema::create('node_i18n', function (Blueprint $t) {
            $t->string('node_id');
            $t->string('locale', 8);
            $t->string('title');
            $t->text('summary')->default('');
            $t->primary(['node_id', 'locale']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('node_i18n');
        Schema::dropIfExists('crowd_goals');
        Schema::dropIfExists('quests');
        Schema::dropIfExists('wiki_pages');
        Schema::dropIfExists('threads');
        Schema::dropIfExists('products');
        Schema::dropIfExists('media');
        Schema::dropIfExists('edges');
        Schema::dropIfExists('nodes');
    }
};
