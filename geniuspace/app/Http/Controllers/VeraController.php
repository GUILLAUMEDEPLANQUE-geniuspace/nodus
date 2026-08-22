<?php

namespace App\Http\Controllers;

use App\Models\GpNode;
use App\Support\Engine;
use App\Support\VeraCatalog;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Vera — jobboard éditorial, clone de github.com/GUILLAUMEDEPLANQUE-geniuspace/vera
 * Surfaces publiques : offres, tests métier, carnet, fiches, délais, tarif entreprise.
 */
class VeraController extends Controller
{
    public function room(Request $request, GpNode $node, string $tab): View
    {
        $tab = $tab ?: 'home';
        return match ($tab) {
            'offres', 'jobs' => view('vera.jobs', ['node' => $node, 'tab' => 'offres']),
            'epreuve', 'preuve' => view('vera.preuve', ['node' => $node, 'tab' => 'preuve']),
            'guides', 'savoirs', 'academie' => view('vera.savoirs', ['node' => $node, 'tab' => 'savoirs']),
            'lexique' => view('vera.lexique', ['node' => $node, 'tab' => 'lexique']),
            'viviers' => view('vera.viviers', ['node' => $node, 'tab' => 'viviers']),
            'europe' => view('vera.europe', ['node' => $node, 'tab' => 'europe']),
            'guilde', 'passport', 'carnet' => view('vera.passport', [
                'node' => $node,
                'tab' => 'passport',
                'carnet' => Engine::myCarnet($node),
                'mine' => \App\Support\Grantor::mine(),
            ]),
            'journal', 'blog' => app(MagazineController::class)->index($request, $node->slug),
            'reliques', 'drive' => view('vera.drive', ['node' => $node, 'tab' => 'reliques']),
            'videos' => view('vera.videos', [
                'node' => $node,
                'tab' => 'videos',
                'medias' => \App\Models\Media::query()->where('node_id', $node->id)->get(),
            ]),
            'pacte', 'delais' => view('vera.pacte', ['node' => $node, 'tab' => 'pacte']),
            'ppqc', 'tarif' => view('vera.ppqc', ['node' => $node, 'tab' => 'ppqc']),
            'entreprises', 'maisons' => view('vera.companies', ['node' => $node, 'tab' => 'entreprises']),
            'marches' => view('vera.marches', ['node' => $node, 'tab' => 'marches']),
            default => view('vera.home', ['node' => $node, 'tab' => 'home']),
        };
    }

    public function jobShow(string $slug): View
    {
        $job = VeraCatalog::job($slug);
        abort_unless($job, 404);
        $node = GpNode::query()->where('slug', 'vera')->first();
        $jobNode = GpNode::query()->where('slug', $slug)->first();
        $trail = $jobNode ? Engine::trail($jobNode) : [];
        $also = $jobNode ? Engine::alsoInWorld($jobNode, 3) : [];
        $align = $jobNode ? Engine::align('carnet-karim', $jobNode->id) : null;
        $heritage = $jobNode ? Engine::inherit($jobNode) : [];
        $details = $jobNode ? Engine::fields($jobNode->id) : [];
        return view('vera.job', compact('job', 'node', 'jobNode', 'trail', 'also', 'align', 'heritage', 'details') + ['tab' => 'offres']);
    }

    public function vivier(string $slug): View
    {
        $vivier = collect(VeraCatalog::json('viviers'))->firstWhere('slug', $slug);
        abort_unless($vivier, 404);
        return view('vera.vivier', ['vivier' => $vivier, 'tab' => 'viviers']);
    }

    public function savoirCat(string $cat, ?string $article = null): View
    {
        $cats = VeraCatalog::json('savoirs-cats');
        $arts = VeraCatalog::json('savoirs-arts');
        $catRow = collect($cats)->firstWhere('slug', $cat);
        abort_unless($catRow, 404);
        if ($article) {
            $a = collect($arts)->first(fn ($x) => $x['slug'] === $article && $x['cat'] === $cat);
            abort_unless($a, 404);
            return view('vera.savoir', ['article' => $a, 'catRow' => $catRow, 'tab' => 'savoirs']);
        }
        $list = array_values(array_filter($arts, fn ($a) => $a['cat'] === $cat));
        return view('vera.savoir-cat', ['cat' => $catRow, 'arts' => $list, 'tab' => 'savoirs']);
    }

    public function company(string $slug): View
    {
        $company = VeraCatalog::company($slug);
        abort_unless($company, 404);
        $jobs = array_values(array_filter(VeraCatalog::jobs(), fn ($j) => $j['companySlug'] === $slug));
        return view('vera.company', compact('company', 'jobs') + ['tab' => 'entreprises']);
    }
}
