<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Médias, preuves et lieux = le même moteur.
 * Un grant est une preuve : user/session × média/fichier/relique, liée à un lieu.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('grants', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('user_id')->nullable();
            $t->string('session_id')->default('');
            $t->string('node_id')->default('');
            $t->string('subject_type'); // media | file | relic | product | proof
            $t->string('subject_id');
            $t->string('reason')->default('teaser'); // teaser | quest | purchase | ats | staff | drop
            $t->string('label')->default('');
            $t->text('meta')->default('');
            $t->timestamp('created_at')->useCurrent();
            $t->index(['session_id', 'subject_type', 'subject_id']);
            $t->index(['user_id', 'subject_type', 'subject_id']);
            $t->index(['node_id']);
        });

        Schema::create('media_doors', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('media_id');
            $t->unsignedInteger('at_sec')->default(0);
            $t->string('kind')->default('door'); // door | drop | proof
            $t->string('label')->default('');
            $t->string('target_slug')->default('');
            $t->string('relic_title')->default('');
            $t->string('relic_path')->default('');
        });

        Schema::table('drive_files', function (Blueprint $t) {
            $t->string('lock_kind')->default(''); // '' | purchase | quest | ats | staff | drop
            $t->string('lock_ref')->default('');
            $t->string('thumb_path')->default('');
            $t->unsignedInteger('appear_order')->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('drive_files', function (Blueprint $t) {
            $t->dropColumn(['lock_kind', 'lock_ref', 'thumb_path', 'appear_order']);
        });
        Schema::dropIfExists('media_doors');
        Schema::dropIfExists('grants');
    }
};
