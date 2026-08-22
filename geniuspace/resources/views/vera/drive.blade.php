@extends('layouts.vera')
@section('title', 'Fichiers — visites et modes opératoires liés aux offres | Vera')
@section('description', 'Lecteur Vera : visites, modes opératoires, schémas. La preuve à côté du poste, pas un ZIP mort.')
@section('canonical', url('/n/vera/reliques'))
@php
  $assets = [
    ['title'=>'Atelier Relève — Fos','kind'=>'visite','src'=>'/offer/releve-atelier.jpg','job'=>'technicien-maintenance-releve','proof'=>91],
    ['title'=>'Chantier ombrières Kora','kind'=>'visite','src'=>'/offer/kora-chantier.jpg','job'=>'electricien-ombrieres-kora','proof'=>88],
    ['title'=>'Domicile Maison Lise','kind'=>'visite','src'=>'/offer/lise-domicile.jpg','job'=>'aide-domicile-lise','proof'=>86],
    ['title'=>'Unité Mireille','kind'=>'visite','src'=>'/offer/mireille-unite.jpg','job'=>'infirmier-produit-mireille','proof'=>84],
    ['title'=>'Loft Sable — remote','kind'=>'visite','src'=>'/offer/sable-loft.jpg','job'=>'frontend-sable-remote','proof'=>80],
    ['title'=>'Kit consignation','kind'=>'outil','src'=>'/offer/tool-consignation.jpg','job'=>'technicien-maintenance-releve','proof'=>94],
    ['title'=>'Clé dynamométrique','kind'=>'outil','src'=>'/offer/tool-couple.jpg','job'=>'technicien-maintenance-releve','proof'=>82],
    ['title'=>'Onduleur string','kind'=>'outil','src'=>'/offer/tool-onduleur.jpg','job'=>'electricien-ombrieres-kora','proof'=>83],
    ['title'=>'Karim — voix Relève','kind'=>'voix','src'=>'/offer/v/karim.mp4','poster'=>'/offer/karim.jpg','job'=>'technicien-maintenance-releve','proof'=>90],
    ['title'=>'Camille — voix Mireille','kind'=>'voix','src'=>'/offer/v/camille.mp4','poster'=>'/offer/camille.jpg','job'=>'infirmier-produit-mireille','proof'=>88],
  ];
@endphp
@section('content')
<div class="vera-wrap" style="padding:2.4rem 0 4rem;max-width:48rem">
  <nav class="crumb"><a href="/n/vera">Vera</a> · Drive</nav>
  <h1 style="font-size:clamp(2rem,5vw,3.2rem);margin-top:.6rem">Fichiers</h1>
  <p class="vera-lead">Fichiers et visites liés aux offres et aux fiches. Lecteur intégré, vidéo, transcript de preuve. Ce n’est pas un cloud.</p>
  <ul style="list-style:none;padding:0;margin-top:1.8rem;display:grid;gap:.8rem">
    @foreach($assets as $a)
      <li class="v-card">
        <p class="vera-kicker">{{ $a['kind'] }} · proof {{ $a['proof'] }}</p>
        <h3>{{ $a['title'] }}</h3>
        @if(($a['kind']??'')==='voix')
          <video src="{{ $a['src'] }}" poster="{{ $a['poster']??'' }}" controls style="width:100%;margin-top:.6rem;border-radius:.6rem;max-height:18rem"></video>
        @else
          <img src="{{ $a['src'] }}" alt="{{ $a['title'] }}" style="width:100%;margin-top:.6rem;border-radius:.6rem;max-height:16rem;object-fit:cover">
        @endif
        <p style="margin-top:.6rem"><a href="/n/vera/offres/{{ $a['job'] }}" style="color:var(--primary)">Offre liée</a></p>
      </li>
    @endforeach
  </ul>
</div>
@endsection
