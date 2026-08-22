<?php

namespace App\Support;

use App\Models\GpNode;
use Illuminate\Support\Facades\DB;

/**
 * Une maison née du template (pas /n/vera) reçoit le catalogue d’offres.
 * Vera reste le flagship historique. On n’y touche pas.
 */
class Maison
{
    public static function attachCatalog(GpNode $node): int
    {
        if (($node->slug ?? '') === 'vera') {
            return 0;
        }
        $n = 0;
        foreach (VeraCatalog::jobs() as $j) {
            $jid = 'vj-'.$j['slug'];
            if (! GpNode::query()->find($jid)) {
                continue;
            }
            $exists = DB::table('edges')->where('from_id', $node->id)->where('to_id', $jid)->exists();
            if (! $exists) {
                DB::table('edges')->insert([
                    'from_id' => $node->id,
                    'to_id' => $jid,
                    'kind' => 'offers',
                    'label' => 'Mission',
                ]);
            }
            $n++;
        }

        return $n;
    }
}
