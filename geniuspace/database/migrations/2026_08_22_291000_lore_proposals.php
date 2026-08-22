<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('lore_proposals')) {
            Schema::create('lore_proposals', function (Blueprint $t) {
                $t->id();
                $t->string('node_id');
                $t->unsignedBigInteger('user_id')->nullable();
                $t->string('session_id')->default('');
                $t->string('field_name');
                $t->text('value');
                $t->unsignedInteger('stake')->default(0);
                $t->string('status')->default('pending');
                $t->timestamp('created_at')->nullable();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('lore_proposals');
    }
};
