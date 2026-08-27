<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cortex / synapses / chunks / rêves. Pas un vector DB.
 * Preuves persistées. Le monde (Engine) n’est pas recopié ici.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ghost_chunks', function (Blueprint $t) {
            $t->string('id', 24)->primary();
            $t->string('node_id')->index();
            $t->string('asset_id', 80)->index();
            $t->text('text');
            $t->string('source_kind', 40)->default('note');
            $t->decimal('importance', 8, 4)->default(0.5);
            $t->timestamp('created_at')->useCurrent();
        });

        Schema::create('ghost_synapses', function (Blueprint $t) {
            $t->id();
            $t->string('node_id')->index();
            $t->string('source', 120);
            $t->string('target', 120);
            $t->string('relation', 40)->default('CO_OCCURRENCE');
            $t->decimal('weight', 8, 4)->default(0.15);
            $t->timestamp('last_reinforced_at')->useCurrent();
            $t->unique(['node_id', 'source', 'target', 'relation']);
        });

        Schema::create('ghost_dreams', function (Blueprint $t) {
            $t->id();
            $t->string('node_id')->index();
            $t->string('code', 24);
            $t->string('from_id', 120);
            $t->string('to_id', 120);
            $t->string('insight', 240);
            $t->string('status', 24)->default('candidate');
            $t->boolean('applied')->default(false);
            $t->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ghost_dreams');
        Schema::dropIfExists('ghost_synapses');
        Schema::dropIfExists('ghost_chunks');
    }
};
