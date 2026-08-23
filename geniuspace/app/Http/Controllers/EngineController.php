<?php

namespace App\Http\Controllers;

use App\Models\GpNode;
use App\Support\Engine;
use App\Support\Grantor;
use Illuminate\Http\JsonResponse;

/**
 * API lecture pour ATS / sites carrière. Ils consomment les détails
 * sans connaître le front, ni le mot « CCK ».
 */
class EngineController extends Controller
{
    public function fields(string $slug): JsonResponse
    {
        $n = GpNode::query()->where('slug', $slug)->firstOrFail();

        return response()->json(Engine::publicFields($n));
    }

    public function neighbors(string $slug): JsonResponse
    {
        $n = GpNode::query()->where('slug', $slug)->firstOrFail();
        $payload = Engine::publicFields($n);
        $payload['liens'] = Engine::neighbors($n);

        return response()->json($payload);
    }

    public function media(string $slug): JsonResponse
    {
        $n = GpNode::query()->where('slug', $slug)->firstOrFail();
        $payload = Engine::publicFields($n);
        $payload['medias'] = Grantor::publicMedia($n);
        $payload['preuves'] = Grantor::mine($n->id);

        return response()->json($payload);
    }
}
