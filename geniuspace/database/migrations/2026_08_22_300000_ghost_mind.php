<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Mémoire Ghost : faits, expériences, réputation d'outils. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ghost_facts', function (Blueprint $t) {
            $t->id();
            $t->string('node_id')->index();
            $t->string('session_id')->nullable()->index();
            $t->unsignedBigInteger('user_id')->nullable();
            $t->string('subject', 80);
            $t->string('predicate', 80);
            $t->string('object', 240);
            $t->float('confidence')->default(0.5);
            $t->string('source', 32)->default('explicit');
            $t->string('status', 24)->default('known');
            $t->timestamp('last_confirmed_at')->nullable();
            $t->timestamp('expires_at')->nullable();
            $t->timestamps();
            $t->unique(['node_id', 'session_id', 'subject', 'predicate'], 'ghost_facts_spo');
        });

        Schema::create('ghost_experiences', function (Blueprint $t) {
            $t->id();
            $t->string('node_id')->index();
            $t->string('session_id')->nullable()->index();
            $t->string('kind', 32);
            $t->text('payload');
            $t->timestamp('created_at')->useCurrent();
        });

        Schema::create('ghost_tool_stats', function (Blueprint $t) {
            $t->string('tool')->primary();
            $t->unsignedInteger('success')->default(0);
            $t->unsignedInteger('fail')->default(0);
            $t->string('last_error', 240)->nullable();
            $t->timestamp('updated_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ghost_tool_stats');
        Schema::dropIfExists('ghost_experiences');
        Schema::dropIfExists('ghost_facts');
    }
};
