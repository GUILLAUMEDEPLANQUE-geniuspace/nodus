@php $j = $job; $co = $j['company']; @endphp
<a class="job-card" href="/n/vera/offres/{{ $j['slug'] }}">
  <div class="job-head">
    <span class="mark">{{ mb_substr($co['name'],0,1) }}</span>
    <div style="flex:1;min-width:0">
      <p class="co">{{ $co['name'] }} · {{ $co['industry'] }}</p>
      <h3>{{ $j['title'] }}</h3>
      <p style="font-size:.85rem;color:var(--muted);margin:.3rem 0 0">{{ $j['location'] }} · {{ $j['remoteLabel'] }} · {{ $j['contractLabel'] }} · {{ $j['seniorityLabel'] }}</p>
      <div class="chips" style="margin-top:.7rem">
        <span class="salary">{{ $j['salaryLabel'] }}</span>
        @if(!empty($j['equity']))<span class="badge">Equity</span>@endif
        @if(!empty($j['full']))<span class="badge primary">Offre lue · test</span>@endif
        @if(($j['scarcity']['band']??'')==='penurie' || ($j['scarcity']['band']??'')==='rare')
          <span class="badge primary">{{ $j['scarcity']['label'] }} {{ $j['scarcity']['score'] }}</span>
        @endif
        @if(!empty($j['pass']))
          <span class="badge bad">Passez</span>
        @else
          <span class="badge {{ $j['honorTone'] }}">{{ $j['honorCaption'] }} · {{ $co['slaDays'] }} j</span>
        @endif
        <span class="badge">Ghost {{ $j['ghostRisk'] }}</span>
        @if(!empty($align['word']))
          <span class="badge {{ ($align['level'] ?? '')==='fort'?'good':(($align['level'] ?? '')==='faible'?'bad':'') }}">{{ $align['word'] }}</span>
        @endif
      </div>
      <p style="font-size:.75rem;color:var(--subtle);margin:.45rem 0 0">{{ $j['processHours'] }} h de process publié · il y a {{ $j['daysAgo'] }} j</p>
      <div class="chips" style="margin-top:.55rem">
        @foreach(array_slice($j['skills']??[],0,4) as $s)<span class="badge">{{ $s }}</span>@endforeach
      </div>
    </div>
  </div>
</a>
