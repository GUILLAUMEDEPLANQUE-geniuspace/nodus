@extends('layouts.vera')
@section('title', 'Lexique Vera — Verdict, Pacte, Brief, Épreuve, Fiches')
@section('description', 'Tous les mots Vera, expliqués pour le candidat et pour l’entreprise : Verdict, Pacte, Brief, épreuve, fiches, fichiers.')
@section('canonical', url('/n/vera/lexique'))
@php $terms = \App\Support\VeraCatalog::json('glossary'); @endphp
@push('jsonld')
<script type="application/ld+json">
{!! json_encode(['@context'=>'https://schema.org','@type'=>'DefinedTermSet','name'=>'Lexique Vera','url'=>url('/n/vera/lexique'),'hasDefinedTerm'=>array_map(fn($t)=>['@type'=>'DefinedTerm','name'=>$t['label'],'description'=>$t['definition'],'@id'=>url('/n/vera/lexique').'#'.$t['key']], $terms)], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) !!}
</script>
@endpush
@section('content')
<div class="vera-wrap" style="padding:2.4rem 0 4rem;max-width:44rem">
  <nav class="crumb"><a href="/n/vera">Vera</a> · Lexique</nav>
  <h1 style="font-size:clamp(2rem,5vw,3.2rem);margin-top:.6rem">Lexique</h1>
  <p class="vera-lead">Chaque mot porte un <span class="term-bang">!</span> dans le produit. Ici, la fiche complète : définition, usage candidat, usage entreprise.</p>
  <ul style="list-style:none;padding:0;margin:2.4rem 0;display:grid;gap:2.2rem">
    @foreach($terms as $t)
      <li class="lex-item" id="{{ $t['key'] }}">
        <h2>{{ $t['label'] }}<span class="term-bang">!</span></h2>
        <p>{{ $t['definition'] }}</p>
        <dl class="vera-grid g2" style="margin-top:1rem">
          <div><dt class="vera-kicker">Candidat</dt><dd style="color:var(--muted);margin:.3rem 0 0">{{ $t['candidate'] }}</dd></div>
          <div><dt class="vera-kicker">Entreprise</dt><dd style="color:var(--muted);margin:.3rem 0 0">{{ $t['house'] }}</dd></div>
        </dl>
      </li>
    @endforeach
  </ul>
</div>
@endsection
