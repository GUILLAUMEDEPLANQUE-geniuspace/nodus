<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Transactions Ghost : preview / apply / undo. Pas un dump de chat. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ghost_actions', function (Blueprint $t) {
            $t->string('id')->primary();
            $t->string('node_id')->index();
            $t->unsignedBigInteger('actor_id')->nullable()->index();
            $t->string('action', 64);
            $t->string('level', 16);
            $t->string('autonomy', 16);
            $t->json('ops');
            $t->json('preview');
            $t->json('before')->nullable();
            $t->json('after')->nullable();
            $t->json('snapshot')->nullable();
            $t->string('status', 16)->default('preview');
            $t->string('result', 240)->nullable();
            $t->string('ask', 240)->nullable();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ghost_actions');
    }
};
