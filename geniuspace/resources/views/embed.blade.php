<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>{{ $node->title }} — lieu</title>
  <meta name="robots" content="noindex">
  <link rel="stylesheet" href="{{ $node->skin === 'vera' ? '/css/vera.css' : '/css/geniuspace.css' }}">
  <style>
    html,body{margin:0;background:{{ $node->skin==='vera' ? '#f2efe6' : '#07080c' }};color:{{ $node->skin==='vera' ? '#161614' : '#f3eadc' }};font-family:system-ui,sans-serif}
    .box{padding:1rem}
    video{width:100%;border-radius:.8rem;background:#000;max-height:12rem;object-fit:cover}
    .cta{display:inline-block;margin-top:.8rem;padding:.55rem 1rem;border-radius:999px;background:{{ $node->skin==='vera' ? '#1b4332' : '#c9a36a' }};color:{{ $node->skin==='vera' ? '#f2efe6' : '#1a140c' }};text-decoration:none;font-weight:600}
    .muted{color:{{ $node->skin==='vera' ? '#5c5a52' : '#8d8794' }};font-size:.85rem}
    h1{font-size:1.35rem;margin:.4rem 0}
  </style>
</head>
<body>
  <div class="box">
    <p class="muted" style="letter-spacing:.12em;text-transform:uppercase;font-size:.7rem">{{ $node->title }}</p>
    @if($src)
      <video src="{{ $src }}" poster="{{ $node->hero }}" playsinline muted controls></video>
    @elseif($file)
      <img src="{{ $file->thumb_path ?: $file->path }}" alt="" style="width:100%;border-radius:.8rem;max-height:12rem;object-fit:cover">
    @else
      <img src="{{ $node->hero }}" alt="" style="width:100%;border-radius:.8rem;max-height:12rem;object-fit:cover">
    @endif
    <h1>{{ $node->title }}</h1>
    <p class="muted">{{ $node->subtitle ?: $node->summary }}</p>
    @if(!empty($heritage['delayLabel']))
      <p style="margin:.5rem 0 0">{{ $heritage['delayLabel'] }}</p>
    @endif
    <a class="cta" href="{{ url($cta['href']) }}" target="_blank" rel="noopener">{{ $cta['label'] }}</a>
  </div>
</body>
</html>
