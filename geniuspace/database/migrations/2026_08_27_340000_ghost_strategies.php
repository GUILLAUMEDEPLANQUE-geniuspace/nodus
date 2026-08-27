<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ghost_strategies', function (Blueprint $t) {
            $t->id();
            $t->string('node_id');
            $t->string('code');
            $t->string('objective')->default('');
            $t->json('genome');
            $t->string('status')->default('untested');
            $t->string('parent_code')->nullable();
            $t->unsignedSmallInteger('generation')->default(0);
            $t->decimal('fitness', 8, 4)->default(0);
            $t->decimal('performance', 8, 4)->default(0);
            $t->decimal('novelty', 8, 4)->default(0);
            $t->decimal('cost', 8, 4)->default(0);
            $t->decimal('risk', 8, 4)->default(0);
            $t->decimal('expected_gain', 8, 4)->default(0);
            $t->decimal('observed_gain', 8, 4)->default(0);
            $t->decimal('confidence', 8, 4)->default(0);
            $t->timestamps();
            $t->unique(['node_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ghost_strategies');
    }
};
