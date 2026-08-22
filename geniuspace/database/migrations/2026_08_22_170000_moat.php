<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $t) {
            $t->unsignedInteger('nodecoins')->default(0);
        });
        Schema::table('nodes', function (Blueprint $t) {
            $t->unsignedInteger('appear_order')->default(0);
            $t->string('appear_label')->default('');
        });
        Schema::table('products', function (Blueprint $t) {
            $t->unsignedInteger('appear_order')->default(0);
        });
        Schema::table('threads', function (Blueprint $t) {
            $t->unsignedInteger('appear_order')->default(0);
        });
        Schema::create('node_arcs', function (Blueprint $t) {
            $t->id();
            $t->string('node_id');
            $t->unsignedInteger('ord')->default(1);
            $t->string('label');
        });
        Schema::create('user_cursors', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('user_id');
            $t->string('node_id');
            $t->unsignedInteger('cursor')->default(99);
            $t->unique(['user_id', 'node_id']);
        });
        Schema::create('bounties', function (Blueprint $t) {
            $t->id();
            $t->string('node_id');
            $t->string('keyword');
            $t->unsignedInteger('reward')->default(500);
            $t->string('title_reward')->default('Expert');
            $t->string('status')->default('open');
            $t->unsignedBigInteger('claimer_id')->nullable();
            $t->text('draft')->default('');
            $t->unsignedTinyInteger('llm_score')->default(0);
            $t->string('llm_note')->default('');
        });
        Schema::create('inventory', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('user_id');
            $t->string('kind');
            $t->string('node_id')->default('');
            $t->string('label');
            $t->string('meta')->default('');
        });
        Schema::create('citations', function (Blueprint $t) {
            $t->id();
            $t->string('thread_id');
            $t->string('target_slug');
            $t->string('product_id')->default('');
            $t->unsignedBigInteger('user_id')->nullable();
        });
        Schema::create('product_splits', function (Blueprint $t) {
            $t->id();
            $t->string('product_id');
            $t->unsignedBigInteger('user_id');
            $t->unsignedTinyInteger('percent');
        });
        Schema::create('ledger', function (Blueprint $t) {
            $t->id();
            $t->string('product_id');
            $t->unsignedBigInteger('user_id')->nullable();
            $t->unsignedInteger('amount_cents')->default(0);
            $t->string('kind')->default('sale');
            $t->string('note')->default('');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ledger');
        Schema::dropIfExists('product_splits');
        Schema::dropIfExists('citations');
        Schema::dropIfExists('inventory');
        Schema::dropIfExists('bounties');
        Schema::dropIfExists('user_cursors');
        Schema::dropIfExists('node_arcs');
        Schema::table('users', fn (Blueprint $t) => $t->dropColumn('nodecoins'));
        Schema::table('nodes', function (Blueprint $t) {
            $t->dropColumn(['appear_order', 'appear_label']);
        });
        Schema::table('products', fn (Blueprint $t) => $t->dropColumn('appear_order'));
        Schema::table('threads', fn (Blueprint $t) => $t->dropColumn('appear_order'));
    }
};
