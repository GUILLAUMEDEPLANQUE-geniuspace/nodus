# Kernel — qui appelle qui

Quatre classes. Une flèche = une dépendance. **Pas de cycle.**

```
            ┌─────────┐
            │ Engine  │  graphe + champs. Vérité métier.
            └────▲────┘
                 │
        ┌────────┴────────┐
        │                 │
   ┌───┴────┐       ┌───┴────┐
   │ Chrome  │       │ Grantor │
   │ habillage│       │ preuves │
   └───▲────┘       └───▲────┘
        │                 │
        └────────┬────────┘
                 │
            ┌───┴────┐
            │  Ghost  │  orchestre. Propose. N’écrit pas un grant.
            └─────────┘
```

| Classe | Peut appeler | Interdit |
| --- | --- | --- |
| `Engine` | `nodes`, `edges`, `cck_fields` | Ghost, Grantor, Chrome |
| `Chrome` | Engine | Ghost, Grantor |
| `Grantor` | Engine, Acl | Ghost |
| `Ghost` | Engine, Chrome, Grantor | SQL métier, `StudioController::cck` |

`Acl` n’écrit plus `node_staff`. Le owner naît à la **création** du lieu (`CreateController`) ou par un owner (`WorldEditorController::staff`).

`alignment()` nomme les preuves manquantes (« Il vous manque la preuve CACES »). Diff d’ensembles, pas du ML. Les citations Ghost publiques passent par `Ghost::publicCitations` : world/evidence, jamais belief, jamais `/ghost/lab`.

Le chat public (`GET|POST /n/{slug}/ghost`) passe par `GhostHost::publicSurface`. `Ghost::reply` reste entier. La télémétrie (growth, belief, critic, simulations) ne sort pas de l’orbe.

```
INTENT → PLAN → CONTRACT → AUTHORIZE → PREVIEW → APPLY → OBSERVE → VERIFY → STATE'
```

Les pages de sculpture (`/studio`, `/monde`, `/builder`, `/ghost/editor`, `/ghost/plan`, `/ghost/gym`, `/ghost/lab`, `/ghost/cerveau`, `/ghost/maturity`) exigent un rôle. Le chat public ne sculpte pas. Une question factuelle passe par le tribunal : preuve ou refus. La consolidation (rêve) n’écrit pas le monde.

Test : `tests/Unit/KernelBoundaryTest.php` lit le source. Un `Grantor` dans `Engine.php` fait échouer la suite. `KernelHostTest` vérifie que l’hôte ne connaît pas Dream et que le contrôleur appelle `publicSurface`.
