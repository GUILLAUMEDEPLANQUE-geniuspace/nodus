# HLS — pas maintenant

Sur o2switch (mutu) : MP4 progressif + jeton `SignedMedia`. C’est voulu.

Quand tu as un VPS / Bunny Stream / R2 :

1. `BUNNY_LIBRARY=` et `BUNNY_CDN=` dans `.env`
2. `Hls::ready()` passe à true
3. Le player prend `.m3u8` au lieu du MP4

Le SEO de `/n/club-205/v/...` (page, chapitres, Clip schema) est plus urgent que le bitrate.
