<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('replies', function (Blueprint $t) {
            $t->string('badge')->default('');
            $t->string('product_id')->default('');
            $t->string('file_title')->default('');
            $t->string('file_path')->default('');
            $t->boolean('file_locked')->default(false);
            $t->string('video_title')->default('');
            $t->string('video_path')->default('');
            $t->string('video_meta')->default('');
        });
    }

    public function down(): void
    {
        Schema::table('replies', function (Blueprint $t) {
            $t->dropColumn(['badge', 'product_id', 'file_title', 'file_path', 'file_locked', 'video_title', 'video_path', 'video_meta']);
        });
    }
};
