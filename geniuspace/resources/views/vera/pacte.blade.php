@extends('layouts.vera')
@section('title', 'Pacte de réponse — l’honneur est public | Vera')
@section('description', 'Publier sur Vera, c’est signer un Pacte : une date de réponse, écrite. Si l’entreprise manque, son honneur baisse. Public.')
@section('canonical', url('/n/vera/pacte'))
@php $league = \App\Support\VeraCatalog::honorLeague(); $C = \App\Support\VeraCatalog::class; @endphp
@section('content')
<section class="vera-hero">
  <div class="vera-wrap">
    <p class="vera-kicker">Ce que les autres cachent</p>
    <h1>Elles répondent,<br>ou ça se voit.</h1>
    <p class="vera-lead">Publier sur Vera, c’est signer un Pacte : une date de réponse, écrite. Si l’entreprise manque, son honneur baisse. Public. Les professionnels viennent ici pour ça — pas pour une autre liste d’offres.</p>
  </div>
</section>
<section class="section">
  <div class="vera-wrap vera-grid g3">
    <article class="principle"><h3>Une date, pas une promesse</h3><p>Chaque offre porte un délai (7, 10, 14 ou 21 jours). Votre suivi affiche le compte à rebours. Indeed n’a jamais osé ça : trop d’annonceurs à ménager.</p></article>
    <article class="principle"><h3>L’honneur est un chiffre</h3><p>Réponses à l’heure / dossiers clos. Atelier Nord : 98. Relais : 44. Ce n’est pas une note culture. C’est le respect du temps des gens.</p></article>
    <article class="principle"><h3>Le Verdict dit de passer</h3><p>Avant de postuler, Vera calcule si l’offre mérite vos heures : ghost, honneur, fourchette, longueur du process. Un « Passez » est un service, pas un échec.</p></article>
  </div>
</section>
<section class="section paper">
  <div class="vera-wrap">
    <h2>Qui répond à l’heure</h2>
    <p style="color:var(--muted)">Classement public. On ne vend pas une meilleure place.</p>
    <ol style="margin:1.4rem 0 0;padding:0;list-style:none;border:1px solid var(--border);border-radius:1rem;overflow:hidden;background:var(--surface)">
      @foreach($league as $i => $h)
        <li style="display:flex;align-items:center;gap:1rem;padding:.9rem 1.1rem;border-top:{{ $i? '1px solid var(--border)':'0' }}">
          <span style="font-family:var(--display);width:1.4rem">{{ $i+1 }}</span>
          <span class="mark">{{ mb_substr($h['name'],0,1) }}</span>
          <div style="flex:1">
            <a href="/n/vera/maisons/{{ $h['slug'] }}" style="font-weight:500">{{ $h['name'] }}</a>
            <p style="font-size:.75rem;color:var(--muted);margin:0">{{ $h['industry'] }} · {{ $h['honorAnswered'] }}/{{ $h['honorDue'] }} à l’heure · SLA {{ $h['slaDays'] }} j</p>
          </div>
          <div style="text-align:right">
            <div style="font-family:var(--display);font-size:1.8rem;line-height:1">{{ $h['honorScore'] }}</div>
            <p style="font-size:.7rem;color:var(--muted);margin:0">{{ $C::honorCaption($h['honorScore'], $h['honorDue'] ?? 1) }}</p>
          </div>
        </li>
      @endforeach
    </ol>
    <p style="margin-top:1.2rem"><a href="/n/vera/offres?pacte=solide" style="color:var(--primary)">Offres à pacte solide</a></p>
  </div>
</section>
@endsection
