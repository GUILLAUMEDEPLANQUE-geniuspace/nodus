<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Journal Ghost — audit des tours (pas de secrets). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ghost_logs', function (Blueprint $t) {
            $t->id();
            $t->string('node_id')->index();
            $t->string('session_id')->nullable()->index();
            $t->unsignedBigInteger('user_id')->nullable();
            $t->text('message');
            $t->text('reply');
            $t->text('tools')->default('');
            $t->string('mode')->default('grounded');
            $t->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ghost_logs');
    }
};
