<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('articles', function (Blueprint $t) {
            $t->string('id')->primary();
            $t->string('node_id');
            $t->string('slug');
            $t->string('title');
            $t->string('theme')->default('Technique');
            $t->string('dossier')->default('Dossier métier');
            $t->text('resume')->default('');
            $t->text('body')->default('');
            $t->string('definition_term')->default('');
            $t->text('definition')->default('');
            $t->text('toc')->default('');
            $t->text('longtail')->default('');
            $t->text('faq')->default('');
            $t->string('cover')->default('');
            $t->string('video_path')->default('');
            $t->string('author')->default('');
            $t->string('author_role')->default('Membre');
            $t->unsignedSmallInteger('reading_min')->default(8);
            $t->unsignedInteger('views')->default(0);
            $t->timestamp('published_at')->nullable();
            $t->timestamp('updated_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('articles');
    }
};
