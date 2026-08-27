<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ghost_hypotheses', function (Blueprint $t) {
            $t->id();
            $t->string('node_id');
            $t->string('code');
            $t->text('observation')->default('');
            $t->text('hypothesis')->default('');
            $t->text('prediction')->default('');
            $t->text('counter_hypothesis')->default('');
            $t->string('metric')->default('');
            $t->decimal('confidence', 8, 4)->default(0);
            $t->string('status')->default('draft');
            $t->text('verdict')->nullable();
            $t->decimal('surprise', 8, 4)->nullable();
            $t->json('levers')->nullable();
            $t->timestamps();
            $t->unique(['node_id', 'code']);
        });

        Schema::create('ghost_strategy_experiments', function (Blueprint $t) {
            $t->id();
            $t->string('node_id')->default('');
            $t->string('strategy_code')->default('');
            $t->string('hypothesis_code')->default('');
            $t->string('objective')->default('');
            $t->string('population')->default('');
            $t->string('control_group')->default('');
            $t->string('treatment_group')->default('');
            $t->string('metric')->default('');
            $t->decimal('baseline', 8, 4)->default(0);
            $t->decimal('prediction', 8, 4)->default(0);
            $t->decimal('observed', 8, 4)->nullable();
            $t->decimal('delta', 8, 4)->nullable();
            $t->decimal('confidence', 8, 4)->nullable();
            $t->string('evidence')->default('');
            $t->string('status')->default('planned');
            $t->string('causal_method')->default('');
            $t->unsignedInteger('sample_size')->default(0);
            $t->json('confounders')->nullable();
            $t->string('stopping_reason')->default('');
            $t->timestamps();
            $t->index(['node_id', 'strategy_code']);
        });

        Schema::table('ghost_strategies', function (Blueprint $t) {
            $t->decimal('surprise', 8, 4)->nullable();
            $t->decimal('innovation', 8, 4)->nullable();
            $t->string('evidence')->default('');
            $t->string('hypothesis_code')->default('');
        });
    }

    public function down(): void
    {
        Schema::table('ghost_strategies', function (Blueprint $t) {
            $t->dropColumn(['surprise', 'innovation', 'evidence', 'hypothesis_code']);
        });
        Schema::dropIfExists('ghost_strategy_experiments');
        Schema::dropIfExists('ghost_hypotheses');
    }
};
