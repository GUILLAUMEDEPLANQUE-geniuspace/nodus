@extends('layouts.vera')
@section('title', 'Lexique Vera — les mots, dits simplement')
@section('description', 'Conseil, délai de réponse, test métier, carnet de preuves : tous les mots Vera expliqués pour le candidat et pour l’entreprise.')
@section('canonical', url('/n/vera/lexique'))
@php $terms = \App\Support\VeraCatalog::json('glossary'); $C = \App\Support\VeraCatalog::class; @endphp
@push('jsonld')
<script type="application/ld+json">
{!! json_encode(['@context'=>'https://schema.org','@type'=>'DefinedTermSet','name'=>'Lexique Vera','url'=>url('/n/vera/lexique'),'hasDefinedTerm'=>array_map(fn($t)=>['@type'=>'DefinedTerm','name'=>$t['label'],'description'=>$t['definition'],'@id'=>url('/n/vera/lexique').'#'.$t['key']], $terms)], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) !!}
</script>
@endpush
@section('content')
<div class="vera-wrap" style="padding:2.4rem 0 4rem;max-width:44rem">
  <nav class="crumb"><a href="/n/vera">Vera</a> · Lexique</nav>
  <h1 style="font-size:clamp(2rem,5vw,3.2rem);margin-top:.6rem">Les mots, dits simplement</h1>
  <p class="vera-lead">Pas un jargon RH. Chaque mot a une phrase en clair, puis ce que ça change pour vous (candidat) et pour l’entreprise.</p>
  <ul style="list-style:none;padding:0;margin:2.4rem 0;display:grid;gap:2.2rem">
    @foreach($terms as $t)
      @php $plain = $C::say($t['key']); @endphp
      <li class="lex-item" id="{{ $t['key'] }}">
        <h2>{{ $plain['word'] !== $t['key'] ? $plain['word'] : $t['label'] }}@if($plain['word'] !== $t['label'] && $plain['word'] !== $t['key']) <span style="font-size:.7rem;color:var(--muted);font-family:var(--font);letter-spacing:.08em;text-transform:uppercase;font-weight:500">{{ $t['label'] }}</span>@endif</h2>
        @if($plain['plain'])<p style="font-weight:500">{{ $plain['plain'] }}</p>@endif
        <p>{{ $t['definition'] }}</p>
        <dl class="vera-grid g2" style="margin-top:1rem">
          <div><dt class="vera-kicker">Pour vous</dt><dd style="color:var(--muted);margin:.3rem 0 0">{{ $t['candidate'] }}</dd></div>
          <div><dt class="vera-kicker">Pour l’entreprise</dt><dd style="color:var(--muted);margin:.3rem 0 0">{{ $t['house'] }}</dd></div>
        </dl>
      </li>
    @endforeach
  </ul>
</div>
@endsection
