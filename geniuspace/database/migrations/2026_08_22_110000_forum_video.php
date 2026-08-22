<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('threads', function (Blueprint $t) {
            $t->unsignedInteger('views')->default(0);
            $t->unsignedInteger('fires')->default(0);
            $t->unsignedInteger('replies_count')->default(0);
        });
        Schema::table('media', function (Blueprint $t) {
            $t->string('kind')->default('video');
            $t->unsignedInteger('views')->default(0);
            $t->string('rating')->default('0');
        });
        Schema::create('replies', function (Blueprint $t) {
            $t->id();
            $t->string('thread_id');
            $t->string('author');
            $t->text('body');
            $t->unsignedInteger('votes')->default(0);
        });
        Schema::create('live_messages', function (Blueprint $t) {
            $t->id();
            $t->string('thread_id');
            $t->string('author');
            $t->text('body');
        });
        Schema::create('drive_files', function (Blueprint $t) {
            $t->id();
            $t->string('node_id');
            $t->string('title');
            $t->string('path');
            $t->string('kind')->default('file');
            $t->boolean('locked')->default(false);
        });
        Schema::create('guild_messages', function (Blueprint $t) {
            $t->id();
            $t->string('node_id');
            $t->string('author');
            $t->text('body');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guild_messages');
        Schema::dropIfExists('drive_files');
        Schema::dropIfExists('live_messages');
        Schema::dropIfExists('replies');
    }
};
