{{-- Passages : voisins du lieu, jamais « edge » ni « graphe ». --}}
@php
  $flag = $flag ?? \App\Support\Flagships::of($node);
  $nb = $nb ?? \App\Support\Engine::neighbors($node);
  $passages = array_merge($nb['fait_partie_de'] ?? [], $nb['contient'] ?? []);
  $label = $flag['wormhole']['label'] ?? 'Passage';
@endphp
@if(count($passages))
  @foreach(array_slice($passages, 0, 3) as $i => $p)
    <a class="wormhole wormhole-{{ $i }}" href="{{ $p['url'] }}" data-visit="{{ $p['titre'] }}">
      <span class="wormhole-tip">{{ $label }} · {{ $p['titre'] }}</span>
    </a>
  @endforeach
@endif
