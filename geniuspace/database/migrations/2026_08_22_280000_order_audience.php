<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * audience = fiche (vendeur, public) | commande (acheteur, au panier).
 * Pas une colonne SQL métier : le type d’option reste un champ.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cck_fields', function (Blueprint $t) {
            $t->string('audience')->default('fiche'); // fiche | commande
        });
    }

    public function down(): void
    {
        Schema::table('cck_fields', function (Blueprint $t) {
            $t->dropColumn('audience');
        });
    }
};
