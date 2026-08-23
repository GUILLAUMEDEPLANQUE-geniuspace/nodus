<?php

namespace App\Support;

use App\Models\GpNode;
use App\Models\Media;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Ghost du lieu : agent cognitif ancré, pas un chat généraliste.
 * Orchestrateur = mémoire + planner + executor + vérificateur.
 * Le LLM raisonne ; le graphe décide de ce qui est vrai.
 */
class Ghost
{
    public static function profile(GpNode $node): string
    {
        $flag = Flagships::of($node);
        if ($flag) {
            $id = $flag['id'] ?? '';
            if (in_array($id, ['vault', 'table', 'scene'], true)) {
                return 'marchand';
            }
            if ($id === 'maison-rh') {
                return 'rh';
            }

            return 'guide';
        }
        $preset = Chrome::presetFor($node);

        return match ($preset) {
            'vera' => 'rh',
            'merch', 'vault', 'table', 'scene' => 'marchand',
            default => 'guide',
        };
    }

    public static function hostName(GpNode $node): string
    {
        return Flagships::of($node)['ghost']['name'] ?? match (self::profile($node)) {
            'marchand' => 'L’hôte',
            'rh' => 'L’accueil',
            default => 'Le guide',
        };
    }

    public static function systemPrompt(GpNode $node): string
    {
        $profile = self::profile($node);
        $base = "Tu es le Ghost du lieu « {$node->title} ». "
            ."Tu ne parles QUE de ce lieu et de son coffre (fiches, produits, vidéos, preuves). "
            ."Tu ne cites que le contexte fourni. Si l'info n'y est pas : « Je n'ai pas cette preuve dans le coffre. » "
            ."Tu n'inventes ni prix, ni salaire, ni spoil, ni promesse d'embauche. "
            ."Tu ne débloques jamais un fichier toi-même : tu orientes vers l'action (achat, épreuve, unlock). "
            ."Les fiches viennent du coffre du lieu, jamais d'un crawl web. "
            ."Réponds en français, court, utile. Aucun jargon technique (pas Node, CCK, edge, grant).";

        return $base.' '.match ($profile) {
            'marchand' => 'Profil marchand : tu aides sur les œuvres, certificats, options de commande et making-of. Négociation seulement dans les fourchettes indiquées.',
            'rh' => 'Profil RH : tu présentes les missions, délais, épreuves. Tu n\'embauchés pas : tu orientes vers l\'épreuve ou les offres.',
            default => 'Profil guide : tu expliques le lieu, les salles, les fiches liées et le carnet de preuves.'
                .(Flagships::canvas($node) === 'atelier'
                    ? ' Rideau : tu ne parles jamais d’un perso ou d’un arc au-delà du curseur du visiteur. Si on te le demande : « Cette fiche n’existe pas encore pour toi. »'
                    : ''),
        };
    }

    /**
     * Pack de vérité pour une tour de dialogue.
     *
     * @return array<string, mixed>
     */
    public static function context(GpNode $node): array
    {
        $fields = [];
        foreach (Engine::fields($node->id) as $key => $f) {
            $val = Engine::surface($f, 'fiche');
            if ($val === '') {
                continue;
            }
            $fields[] = [
                'key' => $key,
                'label' => $f->name,
                'value' => $val,
                'min' => $f->min_val,
                'max' => $f->max_val,
            ];
        }

        $neighbors = Engine::neighbors($node);
        $hidden = Spoiler::hiddenTitles($node);
        if ($hidden) {
            $neighbors['contient'] = array_values(array_filter(
                $neighbors['contient'] ?? [],
                fn ($l) => ! in_array($l['titre'] ?? '', $hidden, true)
            ));
        }

        $products = Product::query()->where('node_id', $node->id)->limit(12)->get()
            ->filter(fn ($p) => Spoiler::ok((int) ($p->appear_order ?? 0), $node->id))
            ->map(function ($p) use ($node) {
            $opts = Order::fields($p->id);

            return [
                'id' => $p->id,
                'titre' => $p->title,
                'prix' => $p->price,
                'url' => '/n/'.$node->slug.'/p/'.$p->id,
                'options' => array_map(fn ($o) => [
                    'key' => $o->field_key ?: Str::slug($o->name),
                    'label' => $o->name,
                    'type' => $o->type,
                    'choices' => Order::choices($o),
                ], $opts),
            ];
        })->values()->all();

        $medias = Media::query()->where('node_id', $node->id)->limit(8)->get()->map(fn ($m) => [
            'id' => $m->id,
            'titre' => $m->title,
            'mode' => $m->mode,
            'acces' => $m->access,
            'ouvert' => Grantor::canSeeMedia($m),
            'url' => '/n/'.$node->slug.'/v/'.$m->id,
            'prix' => $m->price ?? '',
        ])->values()->all();

        $files = [];
        if (\Illuminate\Support\Facades\Schema::hasTable('drive_files')) {
            $files = DB::table('drive_files')->where('node_id', $node->id)->limit(12)->get()->map(fn ($f) => [
                'id' => $f->id,
                'titre' => $f->title,
                'locke' => (bool) ($f->locked ?? false),
                'ouvert' => Grantor::canSeeFile(\App\Models\DriveFile::query()->find($f->id)),
            ])->values()->all();
        }

        $tabs = DB::table('node_tabs')->where('node_id', $node->id)
            ->when(\Illuminate\Support\Facades\Schema::hasColumn('node_tabs', 'enabled'), fn ($q) => $q->where('enabled', 1))
            ->orderBy('sort')->get(['key', 'label'])->map(fn ($t) => [
                'key' => $t->key,
                'label' => $t->label,
                'url' => '/n/'.$node->slug.'/'.$t->key,
            ])->all();

        $chrome = Chrome::bag($node);
        $actions = collect($chrome['heroActions'] ?? [])->map(fn ($a) => [
            'label' => $a->label,
            'key' => $a->action_key,
            'href' => Chrome::href($node, $a),
        ])->values()->all();

        return [
            'lieu' => [
                'id' => $node->id,
                'slug' => $node->slug,
                'titre' => $node->title,
                'resume' => $node->summary ?: $node->subtitle,
                'nature' => Vocab::kind($node->kind),
                'url' => Engine::href($node),
                'profil' => self::profile($node),
            ],
            'details' => $fields,
            'liens' => $neighbors,
            'produits' => $products,
            'videos' => $medias,
            'fichiers' => $files,
            'salles' => $tabs,
            'actions' => $actions,
            'preuves_visiteur' => Grantor::mine($node->id),
            'fiches' => Geniuspedia::cards($node, 6),
            'fourchette' => self::range($node, $products[0] ?? null),
            'rideau' => Spoiler::curtain($node) + ['caches' => $hidden],
        ];
    }

    /**
     * @param  list<array{role: string, content: string}>  $history
     * @param  array<string, mixed>  $opts
     * @return array<string, mixed>
     */
    public static function reply(GpNode $node, string $message, array $history = [], array $opts = []): array
    {
        $message = trim(Str::limit($message, 800));
        $ctx = self::context($node);
        $working = GhostMemory::load($node);
        GhostMemory::ingest($node, $message, $working);

        if (preg_match('/wikip[eé]dia|fandom\.|google\.com|internet entier|wiki pirate|chatgpt/u', mb_strtolower($message))) {
            $blocked = [
                'reply' => 'Je n’ai pas cette preuve dans le coffre. Je ne sors pas du pack de ce lieu — pas de crawl, pas de wiki.',
                'citations' => [['label' => 'Pack du lieu', 'url' => '/n/'.$node->slug]],
                'tools' => [],
                'actions' => [['label' => 'Fiches du lieu', 'href' => '/n/'.$node->slug]],
                'profile' => self::profile($node),
                'mode' => 'grounded',
                'goal' => 'verify_claim',
                'skill' => 'verify_claim',
                'plan' => [],
                'verify' => ['valid' => true, 'status' => 'known'],
                'memory' => GhostMemory::publicFacts($node),
                'permission' => GhostSkills::OBSERVE,
                'maturity' => GhostMaturity::of($node),
            ];
            GhostLearn::afterTurn($node, $message, ['skill' => 'verify_claim'], ['tools' => []], ['valid' => true], 'grounded');

            return $blocked;
        }

        $editorCtx = $opts['editor_context'] ?? [];
        if (GhostBiz::route($message) || GhostEdit::looksLike($message)) {
            $action = GhostBiz::route($message)
                ? GhostBiz::plan($message)
                : GhostEdit::parse($message, GhostEdit::read($node), is_array($editorCtx) ? $editorCtx : []);
            if (GhostEdit::looksLike($message) && empty($editorCtx) && ! empty($action['ops'])) {
                $action['preview'][] = 'Rien n’est écrit. Ouvrez le Studio pour Appliquer.';
            }
            GhostEdit::store($node, $action);
            $out = self::fromAction($node, $action);
            GhostLearn::afterTurn($node, $message, ['skill' => $out['skill']], ['tools' => $out['tools']], ['valid' => true], 'grounded');

            return $out;
        }

        $plan = GhostPlanner::plan($node, $message, $ctx, $working);
        $exec = GhostExecutor::run($node, $plan, $message, $ctx, $working);
        $ctx = GhostExecutor::merge($ctx, $exec);

        $intent = $plan['intent'] ?? self::intent($message, $ctx);
        $toolResult = $exec['primary'] ?? null;
        $grounded = self::groundedReply($node, $message, $intent, $ctx, $toolResult);
        $grounded = self::withPick($grounded, $exec['pick'] ?? null, $working, $plan);

        $mode = 'grounded';
        $llm = self::maybeLlm($node, $message, $history, $ctx, $grounded);
        if ($llm !== null) {
            $grounded['reply'] = $llm;
            $mode = 'llm+grounded';
        }

        $check = GhostVerifier::check($grounded['reply'], $exec, $ctx);
        if (! $check['valid']) {
            $grounded['reply'] = $check['safe_reply'];
            $mode = 'verified-block';
        }

        GhostMemory::rememberTurn($node, $plan, $exec, $check);
        GhostLearn::afterTurn($node, $message, $plan, $exec, $check, $mode);
        self::logTurn($node, $message, $grounded['reply'], $exec['tools'] ?? [], $mode);

        $citations = array_values(array_unique(array_merge($exec['citations'] ?? [], $grounded['citations'] ?? []), SORT_REGULAR));
        $actions = $grounded['actions'] ?? [];
        if ($actions === []) {
            $actions = $exec['actions'] ?? [];
        }
        if ($exec['pending'] ?? []) {
            $actions[] = ['label' => 'Confirmer l’action', 'href' => $ctx['lieu']['url'] ?? '/'];
        }

        return [
            'reply' => $grounded['reply'],
            'citations' => $citations,
            'tools' => $exec['tools'] ?? [],
            'actions' => $actions,
            'profile' => self::profile($node),
            'mode' => $mode,
            'goal' => $plan['goal'] ?? null,
            'skill' => $plan['skill'] ?? null,
            'plan' => $plan['steps'] ?? [],
            'verify' => ['valid' => $check['valid'], 'status' => $check['status']],
            'memory' => GhostMemory::publicFacts($node),
            'permission' => $exec['ceiling'] ?? GhostSkills::OBSERVE,
            'maturity' => GhostMaturity::of($node),
        ];
    }

    /**
     * @param  array<string, mixed>  $action
     * @return array<string, mixed>
     */
    private static function fromAction(GpNode $node, array $action): array
    {
        $skill = match ($action['action'] ?? '') {
            'campaign.create', 'campaign.launch' => 'run_campaign',
            'message.send', 'orders.filter' => 'send_tracking',
            'customers.segment' => 'segment_customers',
            default => 'edit_page',
        };
        $reply = implode(' ', $action['preview'] ?? []);
        if (! empty($action['ask'])) {
            $reply .= ' '.$action['ask'];
        }

        return [
            'reply' => $reply,
            'citations' => $action['citations'] ?? [],
            'tools' => ['read_editor'],
            'actions' => [['label' => 'Studio', 'href' => '/n/'.$node->slug.'/studio']],
            'profile' => self::profile($node),
            'mode' => 'grounded',
            'goal' => $action['action'] ?? 'observe',
            'skill' => $skill,
            'plan' => $action['ops'] ?? [],
            'verify' => ['valid' => ($action['status'] ?? '') !== 'blocked', 'status' => ($action['status'] ?? '') === 'blocked' ? 'contradicted' : 'known'],
            'memory' => GhostMemory::publicFacts($node),
            'permission' => $action['level'] ?? GhostSkills::OBSERVE,
            'maturity' => GhostMaturity::of($node),
            'ghost_action' => $action,
        ];
    }

    /**
     * @param  array{reply:string, citations:list<array>, actions:list<array>}  $grounded
     * @param  array<string, mixed>|null  $pick
     * @param  array<string, mixed>  $working
     * @param  array<string, mixed>  $plan
     * @return array{reply:string, citations:list<array>, actions:list<array>}
     */
    private static function withPick(array $grounded, ?array $pick, array $working, array $plan): array
    {
        if (! $pick) {
            return $grounded;
        }
        $title = $pick['titre'] ?? $pick['title'] ?? null;
        if (! $title) {
            return $grounded;
        }
        $style = $plan['constraints']['style'] ?? ($working['constraints']['style'] ?? null);
        $max = $plan['constraints']['max_price'] ?? null;
        $prefix = '';
        if ($style) {
            $prefix = "Tu as dit préférer « {$style} ». ";
        }
        if (($plan['goal'] ?? '') === 'find_product') {
            $prix = $pick['prix'] ?? '';
            $list = (int) round((float) preg_replace('/[^\d.,]/', '', (string) $prix));
            $note = ($max && (int) $max <= ($list ?: (int) $max)) ? " sous {$max} €" : '';
            $grounded['reply'] = $prefix."Parmi ce qui est dans le coffre{$note} : « {$title} »".($prix ? " — {$prix}" : '').'. '.$grounded['reply'];
            if (! empty($pick['url'])) {
                array_unshift($grounded['actions'], ['label' => $title, 'href' => $pick['url']]);
            }
        }
        if (($plan['goal'] ?? '') === 'match_job' && isset($pick['score'])) {
            $missing = implode(', ', $pick['missing'] ?? []);
            $gap = $missing ? " Il manque : {$missing}." : ' Les preuves collent.';
            $grounded['reply'] = "Piste : « {$title} » (score ".round(((float) $pick['score']) * 100).' %).'.$gap.' Je n’embauche pas : l’épreuve tranche.';
        }

        return $grounded;
    }

    public static function intent(string $message, array $ctx): string
    {
        $m = mb_strtolower($message);
        if (preg_match('/prix|co[uû]t|combien|offre|n[eé]goc|rabais|r[eé]duc|propose|€|euro/u', $m)) {
            return 'price';
        }
        if (preg_match('/fiche|guide|wiki|pack|geniuspedia|article/u', $m)) {
            return 'fiches';
        }
        if (preg_match('/certificat|rwa|authent|preuve mat[eé]riel/u', $m)) {
            return 'certificate';
        }
        if (preg_match('/d[eé]bloqu|unlock|paywall|suite|teaser|making/u', $m)) {
            return 'unlock';
        }
        if (preg_match('/[eé]preuve|test m[eé]tier|mission|offre d.emploi|candidat|cv|recrut/u', $m)) {
            return 'jobs';
        }
        if (preg_match('/carnet|preuv|badge|inventaire|relique/u', $m)) {
            return 'carnet';
        }
        if (preg_match('/taille|gravure|option|personnalis|commander|panier/u', $m)) {
            return 'order';
        }
        if (preg_match('/salle|forum|vid[eé]o|boutique|magasin|lien|voisin|qui/u', $m)) {
            return 'navigate';
        }
        if (preg_match('/bonjour|salut|hello|hey|qui es-tu/u', $m)) {
            return 'hello';
        }

        return 'general';
    }

    /**
     * @param  array<string, mixed>|null  $toolResult
     * @return array{reply: string, citations: list<array>, actions: list<array>}
     */
    private static function groundedReply(GpNode $node, string $message, string $intent, array $ctx, ?array $toolResult): array
    {
        $citations = $toolResult['citations'] ?? [];
        $actions = $toolResult['actions'] ?? [];
        $data = $toolResult['data'] ?? null;

        $hidden = $ctx['rideau']['caches'] ?? [];
        foreach ($hidden as $title) {
            if ($title !== '' && mb_stripos($message, $title) !== false) {
                return [
                    'reply' => 'Cette fiche n’existe pas encore pour toi. Avance le rideau, ensuite on en parle.',
                    'citations' => [['label' => 'Rideau', 'url' => '/n/'.$node->slug]],
                    'actions' => [['label' => 'Les fiches ouvertes', 'href' => '/n/'.$node->slug.'/personnages']],
                ];
            }
        }

        if ($intent === 'hello') {
            $cta = $ctx['actions'][0]['label'] ?? 'Explorer';
            $name = self::hostName($node);
            $wake = Flagships::of($node)['ghost']['wake'] ?? "Je réponds uniquement avec ce qui est dans ce lieu.";

            return [
                'reply' => "{$name} · {$node->title}. {$wake} Essayez « prix », « certificat », « épreuve » ou « {$cta} ».",
                'citations' => [['label' => $node->title, 'url' => $ctx['lieu']['url']]],
                'actions' => $ctx['actions'],
            ];
        }

        if ($intent === 'price') {
            $neg = self::negotiate($node, $message, $ctx);
            if ($neg) {
                return $neg;
            }
            $lines = [];
            foreach (($data['produits'] ?? $ctx['produits'] ?? []) as $p) {
                $lines[] = "· {$p['titre']} — {$p['prix']}";
            }
            if (! $lines) {
                return [
                    'reply' => "Je n'ai pas de tarif produit renseigné dans le coffre de {$node->title}.",
                    'citations' => [],
                    'actions' => $actions,
                ];
            }

            return [
                'reply' => "Tarifs affichés dans ce lieu :\n".implode("\n", $lines)."\nJe ne sors pas de ces montants — proposez un chiffre, je tiens la fourchette.",
                'citations' => $citations,
                'actions' => $actions,
            ];
        }

        if ($intent === 'certificate') {
            $locked = collect($ctx['fichiers'] ?? [])->first(fn ($f) => $f['locke'] && str_contains(mb_strtolower($f['titre']), 'certificat'));
            if ($locked) {
                $ouvert = $locked['ouvert'] ? 'Vous y avez déjà accès.' : 'Il s\'ouvre après acquisition de l\'œuvre (pas après le seul making-of).';

                return [
                    'reply' => "Le fichier « {$locked['titre']} » est au coffre. {$ouvert}",
                    'citations' => [['label' => $locked['titre'], 'url' => '/drive?slug='.$node->slug]],
                    'actions' => [['label' => 'Voir la vitrine', 'href' => Chrome::shopPath($node)]],
                ];
            }

            return [
                'reply' => 'Aucun certificat nommé dans le Drive de ce lieu pour l\'instant.',
                'citations' => [],
                'actions' => $actions,
            ];
        }

        if ($intent === 'unlock') {
            $gated = collect($ctx['videos'] ?? [])->first(fn ($v) => ($v['acces'] ?? '') !== 'free' && ! $v['ouvert']);
            if ($gated) {
                return [
                    'reply' => "« {$gated['titre']} » a un teaser public. La suite se débloque ici — je ne contourne pas le coffre.",
                    'citations' => [['label' => $gated['titre'], 'url' => $gated['url']]],
                    'actions' => [['label' => 'Ouvrir la vidéo', 'href' => $gated['url']]],
                ];
            }

            return [
                'reply' => 'Aucune média verrouillé en attente sur ce lieu, ou vous y avez déjà accès.',
                'citations' => [],
                'actions' => [['label' => 'Carnet', 'href' => '/n/'.$node->slug.'/carnet']],
            ];
        }

        if ($intent === 'jobs') {
            $offres = collect($ctx['liens']['contient'] ?? [])->filter(fn ($l) => ($l['nature'] ?? '') === 'Offre' || str_contains(mb_strtolower($l['titre'] ?? ''), 'mission'));
            $epreuve = collect($ctx['salles'] ?? [])->first(fn ($s) => in_array($s['key'], ['epreuve', 'offres'], true));
            $lines = $offres->take(5)->map(fn ($o) => '· '.$o['titre'])->all();
            $reply = $lines
                ? "Missions / liens utiles :\n".implode("\n", $lines)."\nJe ne valide pas un CV : l'épreuve tranche."
                : 'Pour ce lieu, passez par les missions ou l\'épreuve — je ne promets pas d\'embauche.';

            return [
                'reply' => $reply,
                'citations' => $offres->take(3)->map(fn ($o) => ['label' => $o['titre'], 'url' => $o['url']])->values()->all(),
                'actions' => $epreuve ? [['label' => $epreuve['label'], 'href' => $epreuve['url']]] : $actions,
            ];
        }

        if ($intent === 'fiches') {
            $cards = $ctx['fiches'] ?? [];
            $voisins = $ctx['liens']['contient'] ?? [];
            foreach (array_merge($voisins, $cards) as $c) {
                $titre = (string) ($c['titre'] ?? '');
                if ($titre !== '' && mb_stripos($message, $titre) !== false) {
                    $extrait = (string) ($c['extrait'] ?? $c['resume'] ?? $c['lien'] ?? '');

                    return [
                        'reply' => $titre.($extrait !== '' ? ' · '.$extrait : ''),
                        'citations' => [['label' => $titre, 'url' => $c['url'] ?? '/n/'.$node->slug]],
                        'actions' => [['label' => $titre, 'href' => $c['url'] ?? '/n/'.$node->slug.'/personnages']],
                    ];
                }
            }
            if (! $cards) {
                return [
                    'reply' => 'Pas encore de fiche pack dans ce lieu. Les guides se posent ici.',
                    'citations' => [],
                    'actions' => [['label' => 'Guides', 'href' => '/n/'.$node->slug.'/guides']],
                ];
            }
            $lines = collect($cards)->take(5)->map(fn ($c) => '· '.$c['titre'].' — '.$c['extrait'])->all();

            return [
                'reply' => "Fiches de ce lieu :\n".implode("\n", $lines),
                'citations' => collect($cards)->take(3)->map(fn ($c) => ['label' => $c['titre'], 'url' => $c['url']])->values()->all(),
                'actions' => [['label' => $cards[0]['titre'], 'href' => $cards[0]['url']]],
            ];
        }

        if ($intent === 'carnet') {
            $preuves = $ctx['preuves_visiteur'] ?? [];
            if (! $preuves) {
                return [
                    'reply' => 'Votre carnet est encore vide sur ce lieu. Un unlock, une visite ou une épreuve y posera une preuve.',
                    'citations' => [],
                    'actions' => [['label' => 'Carnet', 'href' => '/n/'.$node->slug.'/carnet']],
                ];
            }
            $lines = collect($preuves)->take(5)->map(fn ($p) => '· '.($p['titre'] ?? $p['quoi'] ?? 'Preuve'))->all();

            return [
                'reply' => "Preuves déjà tenues ici :\n".implode("\n", $lines),
                'citations' => [['label' => 'Carnet', 'url' => '/n/'.$node->slug.'/carnet']],
                'actions' => [['label' => 'Exporter le carnet', 'href' => '/n/'.$node->slug.'/carnet.json']],
            ];
        }

        if ($intent === 'order' && ! empty($ctx['produits'])) {
            $withOpts = collect($ctx['produits'])->first(fn ($p) => ! empty($p['options']));
            if ($withOpts) {
                $opts = collect($withOpts['options'])->map(fn ($o) => $o['label'])->implode(', ');

                return [
                    'reply' => "Sur « {$withOpts['titre']} » vous pouvez choisir : {$opts}. Les suppléments s'ajoutent au panier.",
                    'citations' => [['label' => $withOpts['titre'], 'url' => $withOpts['url']]],
                    'actions' => [['label' => 'Voir le produit', 'href' => $withOpts['url']]],
                ];
            }
        }

        if ($intent === 'navigate') {
            $salles = collect($ctx['salles'])->take(6)->map(fn ($s) => '· '.$s['label'])->implode("\n");

            return [
                'reply' => $salles ? "Salles de ce lieu :\n{$salles}" : 'Ce lieu n\'a pas encore de plan de salles.',
                'citations' => [['label' => $node->title, 'url' => $ctx['lieu']['url']]],
                'actions' => array_slice($ctx['actions'], 0, 3),
            ];
        }

        // general : résumé + 1 détail fort
        $bits = [];
        if ($ctx['lieu']['resume']) {
            $bits[] = $ctx['lieu']['resume'];
        }
        foreach (array_slice($ctx['details'], 0, 3) as $d) {
            $bits[] = $d['label'].' : '.$d['value'];
        }
        $reply = $bits
            ? implode(' · ', $bits)
            : "Je suis le Ghost de {$node->title}. Posez une question sur un produit, une vidéo, une mission ou une preuve.";

        return [
            'reply' => Str::limit($reply, 500),
            'citations' => [['label' => $node->title, 'url' => $ctx['lieu']['url']]],
            'actions' => array_slice($ctx['actions'], 0, 3),
        ];
    }

    /** Négociation dans la fourchette (min/max de fiche + plancher). Clos = ligne panier. */
    private static function negotiate(GpNode $node, string $message, array $ctx): ?array
    {
        if (! preg_match('/(\d+(?:[.,]\d+)?)/u', $message, $m)) {
            return null;
        }
        $offer = (int) round((float) str_replace(',', '.', $m[1]));
        if ($offer < 20) {
            return null;
        }
        $products = $ctx['produits'] ?? [];
        $p = self::matchProduct($products, $message) ?? ($products[0] ?? null);
        if (! $p) {
            return null;
        }
        $range = self::range($node, $p);
        $list = $range['list'];
        $floor = $range['floor'];
        $ceil = $range['ceil'];
        $shop = [['label' => 'Voir l’œuvre', 'href' => $p['url'] ?? Chrome::shopPath($node)]];
        if ($list && $offer >= $list && ($ceil === 0 || $offer <= $ceil || $offer >= $list)) {
            $price = min($offer, $ceil ?: $offer);
            if ($price < $list) {
                $price = $list;
            }

            return [
                'reply' => "Oui. À {$list} €, je clos. L’œuvre « {$p['titre']} » est au panier — le certificat s’ouvre au paiement.",
                'citations' => [['label' => $p['titre'], 'url' => $p['url'] ?? '']],
                'actions' => self::putDeal($p, $list),
            ];
        }
        if ($floor && $offer >= $floor && ($ceil === 0 || $offer <= max($ceil, $list))) {
            $held = $ceil && $offer > $ceil ? $ceil : $offer;

            return [
                'reply' => "J’ai la fourchette. {$held} €, c’est tenu pour « {$p['titre']} ». Je pose la ligne au panier.",
                'citations' => [['label' => $p['titre'], 'url' => $p['url'] ?? '']],
                'actions' => self::putDeal($p, $held),
            ];
        }
        if ($floor && $offer >= (int) round($floor * 0.85)) {
            return [
                'reply' => "Trop bas pour ce que c’est. Je peux descendre à {$floor} €, pas en dessous. Le plancher est écrit.",
                'citations' => [['label' => $p['titre'], 'url' => $p['url'] ?? '']],
                'actions' => $shop,
            ];
        }
        if ($floor) {
            return [
                'reply' => "Non. {$offer} € ne tient pas pour « {$p['titre']} ». Le plancher est {$floor} €.",
                'citations' => [['label' => $p['titre'], 'url' => $p['url'] ?? '']],
                'actions' => $shop,
            ];
        }

        return null;
    }

    /**
     * Fourchette marchande : min/max des champs prix + plancher. Jamais sous le min.
     *
     * @param  array<string, mixed>|null  $p
     * @return array{floor: int, ceil: int, list: int}
     */
    public static function range(GpNode $node, ?array $p): array
    {
        $list = $p ? (int) round((float) preg_replace('/[^\d.,]/', '', (string) ($p['prix'] ?? '0'))) : 0;
        $floor = $list ? (int) round($list * 0.9) : 0;
        $ceil = $list;
        $flag = Flagships::of($node);
        $floorKey = (string) ($flag['ghost']['floor_key'] ?? 'prix_plancher');
        foreach (Engine::fields($node->id) as $key => $f) {
            $name = mb_strtolower((string) $f->name);
            $isFloor = in_array($key, ['prix_plancher', 'plancher', $floorKey], true) || $name === 'plancher';
            $isPrice = in_array($key, ['prix', 'price', 'salaire', $floorKey], true) || $name === 'prix';
            if ($isFloor && is_numeric($f->value) && (float) $f->value > 0) {
                $floor = (int) $f->value;
            }
            if ($isFloor || $isPrice) {
                if (is_numeric($f->min_val) && (float) $f->min_val > 0) {
                    $floor = (int) $f->min_val;
                }
                if (is_numeric($f->max_val) && (float) $f->max_val > 0) {
                    $ceil = (int) $f->max_val;
                }
            }
        }
        if (isset($p['min']) && is_numeric($p['min']) && (float) $p['min'] > 0) {
            $floor = (int) $p['min'];
        }
        if (isset($p['max']) && is_numeric($p['max']) && (float) $p['max'] > 0) {
            $ceil = (int) $p['max'];
        }

        return ['floor' => $floor, 'ceil' => $ceil, 'list' => $list];
    }

    /** @param  list<array<string, mixed>>  $products */
    private static function matchProduct(array $products, string $message): ?array
    {
        $m = mb_strtolower($message);
        foreach ($products as $p) {
            $t = mb_strtolower((string) ($p['titre'] ?? ''));
            if ($t !== '' && str_contains($m, $t)) {
                return $p;
            }
        }

        return null;
    }

    /**
     * Pose la ligne au prix négocié. Le checkout lit extra_cents (souvent négatif).
     *
     * @param  array<string, mixed>  $p
     * @return list<array{label: string, href: string}>
     */
    private static function putDeal(array $p, int $offer): array
    {
        $list = (int) round((float) preg_replace('/[^\d.,]/', '', (string) ($p['prix'] ?? '0')));
        $delta = ($offer - $list) * 100;
        $cart = session()->get('cart', []);
        $line = ($p['id'] ?? 'deal').'|ghost';
        $cart[$line] = [
            'product_id' => $p['id'],
            'title' => $p['titre'],
            'price' => $offer.' €',
            'extra_cents' => $delta,
            'held_cents' => $offer * 100,
            'options' => ['nego' => $offer.' €'],
            'files' => [],
        ];
        session()->put('cart', $cart);

        return [
            ['label' => 'Voir le panier · '.$offer.' €', 'href' => '/panier'],
            ['label' => 'L’œuvre', 'href' => $p['url'] ?? '/panier'],
        ];
    }

    private static function maybeLlm(GpNode $node, string $message, array $history, array $ctx, array $grounded): ?string
    {
        $url = config('services.ghost.url', env('GHOST_LLM_URL', ''));
        if ($url === '' && env('OLLAMA_BASE_URL')) {
            $url = rtrim(env('OLLAMA_BASE_URL'), '/').'/api/chat';
        }
        if ($url === '') {
            return null;
        }

        try {
            $payload = [
                'model' => env('GHOST_LLM_MODEL', env('OLLAMA_MODEL', 'llama3.1')),
                'stream' => false,
                'messages' => array_merge(
                    [['role' => 'system', 'content' => self::systemPrompt($node)."\n\nContexte JSON (vérité) :\n".Str::limit(json_encode($ctx, JSON_UNESCAPED_UNICODE), 6000)]],
                    array_slice($history, -6),
                    [['role' => 'user', 'content' => $message]],
                    [['role' => 'system', 'content' => 'Réponse ancrée déjà calculée (à respecter sur les faits) : '.$grounded['reply']]],
                ),
            ];
            $res = Http::timeout(25)->post($url, $payload);
            if (! $res->successful()) {
                return null;
            }
            $json = $res->json();
            $text = $json['message']['content'] ?? $json['choices'][0]['message']['content'] ?? null;

            return is_string($text) && trim($text) !== '' ? trim($text) : null;
        } catch (\Throwable $e) {
            Log::debug('Ghost LLM skip: '.$e->getMessage());

            return null;
        }
    }

    private static function logTurn(GpNode $node, string $message, string $reply, array $tools, string $mode): void
    {
        if (! \Illuminate\Support\Facades\Schema::hasTable('ghost_logs')) {
            return;
        }
        try {
            DB::table('ghost_logs')->insert([
                'node_id' => $node->id,
                'session_id' => Grantor::guestId(),
                'user_id' => auth()->id(),
                'message' => Str::limit($message, 500),
                'reply' => Str::limit($reply, 2000),
                'tools' => json_encode($tools, JSON_UNESCAPED_UNICODE),
                'mode' => $mode,
                'created_at' => now(),
            ]);
        } catch (\Throwable $e) {
            // non bloquant
        }
    }
}
