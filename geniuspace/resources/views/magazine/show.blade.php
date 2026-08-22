@extends('layouts.app')
@section('title', $article->title.' | '.$node->title)
@section('description', \Illuminate\Support\Str::limit($article->resume, 160))
@section('canonical', url('/n/'.$node->slug.'/blog/'.$article->urlSlug()))
@push('jsonld')
<script type="application/ld+json">{!! json_encode([
  '@'.'context' => 'https://schema.org',
  '@'.'graph' => array_values(array_filter([
    ['@'.'type'=>'BreadcrumbList','itemListElement'=>[
      ['@'.'type'=>'ListItem','position'=>1,'name'=>'Geniuspace','item'=>url('/')],
      ['@'.'type'=>'ListItem','position'=>2,'name'=>$node->title,'item'=>url('/n/'.$node->slug)],
      ['@'.'type'=>'ListItem','position'=>3,'name'=>'Magazine','item'=>url('/n/'.$node->slug.'/blog')],
      ['@'.'type'=>'ListItem','position'=>4,'name'=>$article->title,'item'=>url('/n/'.$node->slug.'/blog/'.$article->urlSlug())],
    ]],
    [
      '@'.'type' => $article->video_path ? 'VideoObject' : 'BlogPosting',
      'headline' => $article->title,
      'description' => $article->resume,
      'datePublished' => optional($article->published_at)->toAtomString(),
      'dateModified' => optional($article->updated_at)->toAtomString(),
      'author' => ['@'.'type'=>'Person','name'=>$article->author],
      'image' => url($article->cover ?: $node->hero),
      'url' => url('/n/'.$node->slug.'/blog/'.$article->urlSlug()),
      'isPartOf' => ['@'.'type'=>'Blog','name'=>$node->title.' Magazine','url'=>url('/n/'.$node->slug.'/blog')],
      'about' => $article->definition_term ?: $article->title,
    ],
    count($article->faqs()) ? [
      '@'.'type' => 'FAQPage',
      'mainEntity' => array_map(fn($f)=>['@'.'type'=>'Question','name'=>$f['q'],'acceptedAnswer'=>['@'.'type'=>'Answer','text'=>$f['a']]], $article->faqs()),
    ] : null,
  ])),
], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) !!}</script>
@endpush
@section('content')
<article class="mag {{ $node->kind==='auto'?'skin-auto':'' }}">
  @if($article->cover || $node->hero)
    <header class="mag-hero">
      <img src="{{ $article->cover ?: $node->hero }}" alt="">
      <div class="veil"></div>
      <div class="copy wrap">
        <p class="kicker">{{ $article->dossier }} · {{ $article->theme }}</p>
        <h1 class="font-display">{{ $article->title }}</h1>
        <p class="muted">{{ $article->author }} · {{ $article->author_role }} · {{ $article->reading_min }} min · maj {{ optional($article->updated_at)->format('d/m/Y') }} · {{ $article->views }} lectures</p>
      </div>
    </header>
  @endif
  <div class="wrap mag-body">
    <section class="mag-box">
      <p class="kicker">Résumé opérationnel</p>
      <p class="lede">{{ $article->resume }}</p>
    </section>
    @if($article->tocItems())
      <nav class="mag-toc">
        <p class="kicker">Sommaire</p>
        <ol>@foreach($article->tocItems() as $i => $h)<li><a href="#s{{ $i }}">{{ $h }}</a></li>@endforeach</ol>
      </nav>
    @endif
    @if($article->video_path)
      <video class="mag-video" src="/{{ ltrim($article->video_path,'/') }}" controls playsinline></video>
    @endif
    @if($article->definition)
      <aside class="mag-def">
        <p class="kicker">Encadré définition · {{ $article->definition_term }}</p>
        <p>{{ $article->definition }}</p>
      </aside>
    @endif
    <div class="mag-prose">{!! nl2br(\App\Support\Linker::html($node, $article->body)) !!}</div>
    @if($article->tails())
      <section class="mag-box">
        <p class="kicker">Requêtes longue traîne à couvrir</p>
        <table class="mag-table">
          <thead><tr><th>Requête</th><th>Page / note</th></tr></thead>
          <tbody>
            @foreach($article->tails() as $t)
              <tr><td>{{ $t['q'] }}</td><td>{{ $t['note'] }}</td></tr>
            @endforeach
          </tbody>
        </table>
      </section>
    @endif
    @if($article->faqs())
      <section class="mag-faq">
        <h2 class="font-display">FAQ</h2>
        @foreach($article->faqs() as $f)
          <details><summary>{{ $f['q'] }}</summary><p>{{ $f['a'] }}</p></details>
        @endforeach
      </section>
    @endif
    @if($cluster->count())
      <section>
        <p class="kicker">Cluster · fiches du graphe</p>
        <div class="rel">@foreach($cluster as $c)<a class="chip" href="/n/{{ $node->slug }}/f/{{ $c->slug }}">{{ $c->title }}</a>@endforeach</div>
      </section>
    @endif
    @if($related->count())
      <section>
        <p class="kicker">Dans le même thème</p>
        @foreach($related as $r)
          <p><a href="/n/{{ $node->slug }}/blog/{{ $r->urlSlug() }}">{{ $r->title }}</a></p>
        @endforeach
      </section>
    @endif
    <p style="margin-top:2rem"><a class="btn-line" href="/n/{{ $node->slug }}/blog">← Magazine</a></p>
  </div>
</article>
@isset($tabs)
<nav class="dock">
  @foreach($tabs as $t)
    <a href="/n/{{ $node->slug }}/{{ $t->key }}" class="{{ ($tab ?? '') === $t->key ? 'active' : '' }}">{{ $t->label }}</a>
  @endforeach
</nav>
@endisset
@endsection
