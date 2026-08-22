<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Clés stables, unités, bornes. Le métier vit dans cck_fields, jamais en colonne SQL.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cck_fields', function (Blueprint $t) {
            $t->string('field_key')->default('');
            $t->string('unit')->default('');
            $t->float('min_val')->nullable();
            $t->float('max_val')->nullable();
            $t->unsignedTinyInteger('schema_version')->default(1);
        });
    }

    public function down(): void
    {
        Schema::table('cck_fields', function (Blueprint $t) {
            $t->dropColumn(['field_key', 'unit', 'min_val', 'max_val', 'schema_version']);
        });
    }
};
