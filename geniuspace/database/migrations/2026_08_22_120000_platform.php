<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $t) {
            $t->string('bio')->default('');
            $t->string('avatar')->default('/realms/luffy.jpg');
            $t->string('banner')->default('/realms/sea-hero.jpg');
        });
        Schema::create('node_staff', function (Blueprint $t) {
            $t->id();
            $t->string('node_id');
            $t->unsignedBigInteger('user_id');
            $t->string('role')->default('mod'); // owner admin mod
            $t->unique(['node_id', 'user_id']);
        });
        Schema::create('folders', function (Blueprint $t) {
            $t->id();
            $t->string('node_id');
            $t->unsignedBigInteger('parent_id')->nullable();
            $t->string('title');
        });
        Schema::table('drive_files', function (Blueprint $t) {
            $t->unsignedBigInteger('folder_id')->nullable();
            $t->string('mime')->default('application/octet-stream');
            $t->unsignedInteger('size')->default(0);
            $t->unsignedBigInteger('user_id')->nullable();
        });
        Schema::create('cck_fields', function (Blueprint $t) {
            $t->id();
            $t->string('node_id');
            $t->string('name');
            $t->string('type')->default('text');
            $t->text('value')->default('');
            $t->string('target_kind')->default('node');
            $t->string('target_id')->default('');
            $t->unsignedInteger('sort')->default(0);
        });
        Schema::create('node_tabs', function (Blueprint $t) {
            $t->id();
            $t->string('node_id');
            $t->string('key');
            $t->string('label');
            $t->string('icon')->default('spark');
            $t->unsignedInteger('sort')->default(0);
        });
        Schema::create('node_seo', function (Blueprint $t) {
            $t->string('node_id')->primary();
            $t->string('title')->default('');
            $t->text('description')->default('');
            $t->string('keywords')->default('');
            $t->boolean('noindex')->default(false);
        });
        Schema::create('playlists', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('user_id');
            $t->string('title');
            $t->string('share_slug')->unique();
        });
        Schema::create('playlist_items', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('playlist_id');
            $t->unsignedBigInteger('media_id');
        });
        Schema::create('ats_steps', function (Blueprint $t) {
            $t->id();
            $t->string('node_id');
            $t->unsignedTinyInteger('step');
            $t->string('title');
            $t->text('prompt')->default('');
        });
        Schema::create('visits', function (Blueprint $t) {
            $t->id();
            $t->string('node_id');
            $t->string('path')->default('/');
            $t->string('session')->default('');
        });
        Schema::create('notifications', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('user_id')->nullable();
            $t->string('title');
            $t->string('url')->default('/');
            $t->boolean('read')->default(false);
        });
        Schema::create('forum_bans', function (Blueprint $t) {
            $t->id();
            $t->string('node_id');
            $t->string('author');
            $t->string('reason')->default('');
        });
        Schema::create('forum_categories', function (Blueprint $t) {
            $t->id();
            $t->string('node_id');
            $t->string('title');
        });
        Schema::table('threads', function (Blueprint $t) {
            $t->unsignedBigInteger('category_id')->nullable();
            $t->boolean('pinned')->default(false);
            $t->boolean('pending')->default(false);
        });
        Schema::table('replies', function (Blueprint $t) {
            $t->boolean('pending')->default(false);
            $t->string('media_path')->default('');
        });
        Schema::create('dm_messages', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('from_id')->nullable();
            $t->unsignedBigInteger('to_id')->nullable();
            $t->text('body');
        });
        Schema::create('edits', function (Blueprint $t) {
            $t->id();
            $t->string('path');
            $t->string('source')->default('');
            $t->string('context')->default('');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('edits');
        Schema::dropIfExists('dm_messages');
        Schema::dropIfExists('forum_categories');
        Schema::dropIfExists('forum_bans');
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('visits');
        Schema::dropIfExists('ats_steps');
        Schema::dropIfExists('playlist_items');
        Schema::dropIfExists('playlists');
        Schema::dropIfExists('node_seo');
        Schema::dropIfExists('node_tabs');
        Schema::dropIfExists('cck_fields');
        Schema::dropIfExists('folders');
        Schema::dropIfExists('node_staff');
    }
};
