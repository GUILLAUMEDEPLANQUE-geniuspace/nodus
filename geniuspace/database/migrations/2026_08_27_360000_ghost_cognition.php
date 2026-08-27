<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Croyances probabilistes + reflections. Pas un compteur « knowledge = 87 ». */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ghost_beliefs', function (Blueprint $t) {
            $t->id();
            $t->string('node_id')->index();
            $t->string('claim', 180);
            $t->decimal('p', 8, 4)->default(0.5);
            $t->unsignedInteger('supporting')->default(0);
            $t->unsignedInteger('contradicting')->default(0);
            $t->unsignedInteger('unknown')->default(0);
            $t->string('context', 180)->default('');
            $t->decimal('decay', 8, 4)->default(0.02);
            $t->date('last_observed')->nullable();
            $t->timestamps();
            $t->unique(['node_id', 'claim']);
        });

        Schema::create('ghost_reflections', function (Blueprint $t) {
            $t->id();
            $t->string('node_id')->index();
            $t->decimal('expected', 8, 4)->nullable();
            $t->decimal('actual', 8, 4)->nullable();
            $t->decimal('gap', 8, 4)->nullable();
            $t->string('why', 240)->default('');
            $t->string('change_rule', 240)->nullable();
            $t->decimal('confidence', 8, 4)->default(0);
            $t->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ghost_reflections');
        Schema::dropIfExists('ghost_beliefs');
    }
};
