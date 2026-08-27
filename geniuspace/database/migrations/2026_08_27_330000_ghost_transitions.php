<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Provenance causale : STATE ← produced_by ACTION ← verified_by VERIFICATION. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ghost_transitions', function (Blueprint $t) {
            $t->string('id')->primary();
            $t->string('action_id')->index();
            $t->string('node_id')->index();
            $t->unsignedBigInteger('actor_id')->nullable()->index();
            $t->string('produced_by', 40);
            $t->json('verified_by')->nullable();
            $t->json('evidence')->nullable();
            $t->string('before_hash', 40)->nullable();
            $t->string('after_hash', 40)->nullable();
            $t->string('status', 24)->default('applied');
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ghost_transitions');
    }
};
