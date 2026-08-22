@extends('layouts.vera')
@section('title', 'PPQC — payer le candidat qualifié, pas l’annonce | Vera')
@section('description', 'Publication gratuite. Paiement uniquement sur un candidat qui a réussi l’épreuve métier et la grille. Geo-Tension Pricing.')
@section('canonical', url('/n/vera/ppqc'))
@php $jobs = array_slice(\App\Support\VeraCatalog::jobs(), 0, 10); @endphp
@section('content')
<div class="vera-wrap" style="padding:2.6rem 0 4rem;max-width:44rem">
  <p class="vera-kicker">Modèle</p>
  <h1 style="font-size:clamp(2rem,5vw,3.2rem)">Pay-Per-Qualified-Candidate</h1>
  <p class="vera-lead">Publier est gratuit, enrichissement et 360° inclus. Vous payez quand quelqu’un a tenu l’épreuve et la grille — pas un clic, pas un CV.</p>
  <ol style="margin:1.6rem 0;padding-left:1.2rem;line-height:1.7">
    <li>Offre en ligne, salaire publié, pacte signé. 0 €.</li>
    <li>Le candidat passe l’épreuve (2–6 min) et la grille publique.</li>
    <li>Score épreuve ≥ 55 et grille ≥ 55 : le profil est qualifié. Facture PPQC.</li>
    <li>Geo-Tension : plus le bassin est tendu, plus le profil vaut. Pas un CPM.</li>
  </ol>
  <h2>Prix de bassin</h2>
  <ul style="list-style:none;padding:0;border-top:1px solid var(--border)">
    @foreach($jobs as $j)
      <li style="display:flex;justify-content:space-between;gap:1rem;padding:.8rem 0;border-bottom:1px solid var(--border)">
        <a href="/n/vera/offres/{{ $j['slug'] }}">
          <span style="font-weight:500">{{ $j['title'] }}</span>
          <span style="display:block;font-size:.75rem;color:var(--muted)">{{ $j['city'] }} · tension {{ $j['ppqc']['tension'] }}</span>
        </a>
        <span style="font-family:var(--display);font-size:1.6rem">{{ $j['ppqc']['euros'] }} €</span>
      </li>
    @endforeach
  </ul>
</div>
@endsection
