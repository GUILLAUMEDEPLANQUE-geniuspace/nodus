@extends('layouts.vera')
@section('title', 'Vous ne payez que le candidat qualifié | Vera')
@section('description', 'Publier une offre est gratuit. Vous payez seulement si quelqu’un a réussi le test métier. Prix selon la tension du bassin.')
@section('canonical', url('/n/vera/tarif'))
@php $jobs = array_slice(\App\Support\VeraCatalog::jobs(), 0, 10); @endphp
@section('content')
<div class="vera-wrap" style="padding:2.6rem 0 4rem;max-width:44rem">
  <p class="vera-kicker">Pour les entreprises</p>
  <h1 style="font-size:clamp(2rem,5vw,3.2rem)">Vous ne payez que si quelqu’un réussit le test</h1>
  <p class="vera-lead">Publier est gratuit. Pas un clic, pas un CV. Une facture seulement quand un candidat a tenu le test métier et la grille publique (note ≥ 55).</p>
  <ol style="margin:1.6rem 0;padding-left:1.2rem;line-height:1.7">
    <li>Offre en ligne, salaire publié, délai de réponse signé. 0 €.</li>
    <li>Le candidat passe le test (2–6 min) et la grille visible.</li>
    <li>Notes ≥ 55 : le profil est qualifié. Facture.</li>
    <li>Plus le bassin est tendu, plus le profil vaut. Pas un tarif au clic.</li>
  </ol>
  <h2>Prix selon le lieu</h2>
  <ul style="list-style:none;padding:0;border-top:1px solid var(--border)">
    @foreach($jobs as $j)
      <li style="display:flex;justify-content:space-between;gap:1rem;padding:.8rem 0;border-bottom:1px solid var(--border)">
        <a href="/n/vera/offres/{{ $j['slug'] }}">
          <span style="font-weight:500">{{ $j['title'] }}</span>
          <span style="display:block;font-size:.75rem;color:var(--muted)">{{ $j['city'] }} · tension {{ $j['ppqc']['tension'] }}/100</span>
        </a>
        <span style="font-family:var(--display);font-size:1.6rem">{{ $j['ppqc']['euros'] }} €</span>
      </li>
    @endforeach
  </ul>
</div>
@endsection
