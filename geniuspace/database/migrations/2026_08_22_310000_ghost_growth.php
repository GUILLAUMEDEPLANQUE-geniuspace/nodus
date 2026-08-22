<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Boucle de croissance Ghost : erreurs, règles, skills, candidats. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ghost_failures', function (Blueprint $t) {
            $t->id();
            $t->string('node_id')->index();
            $t->string('session_id')->nullable()->index();
            $t->string('task', 240);
            $t->string('skill', 64)->nullable();
            $t->string('error_type', 32);
            $t->string('correction', 240);
            $t->string('severity', 16)->default('medium');
            $t->timestamp('created_at')->useCurrent();
        });

        Schema::create('ghost_rules', function (Blueprint $t) {
            $t->string('id')->primary();
            $t->string('when_error', 32);
            $t->string('then_do', 240);
            $t->unsignedInteger('from_failures')->default(1);
            $t->timestamp('updated_at')->nullable();
        });

        Schema::create('ghost_skill_stats', function (Blueprint $t) {
            $t->string('name')->primary();
            $t->unsignedInteger('runs')->default(0);
            $t->unsignedInteger('wins')->default(0);
            $t->unsignedInteger('version')->default(1);
            $t->timestamp('updated_at')->nullable();
        });

        Schema::create('ghost_skill_candidates', function (Blueprint $t) {
            $t->id();
            $t->string('name', 80);
            $t->text('procedure');
            $t->float('rate')->default(0);
            $t->unsignedInteger('samples')->default(0);
            $t->string('status', 16)->default('pending');
            $t->timestamps();
            $t->unique('name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ghost_skill_candidates');
        Schema::dropIfExists('ghost_skill_stats');
        Schema::dropIfExists('ghost_rules');
        Schema::dropIfExists('ghost_failures');
    }
};
