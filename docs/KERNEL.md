# Kernel — qui appelle qui

Quatre classes. Une flèche = une dépendance. **Pas de cycle.**

```
            ┌─────────┐
            │ Engine  │  graphe + champs. Vérité métier.
            └────▲────┘
                 │
        ┌────────┴────────┐
        │                 │
   ┌────┴────┐       ┌────┴────┐
   │ Chrome  │       │ Grantor │
   │ habillage│       │ preuves │
   └────▲────┘       └────▲────┘
        │                 │
        └────────┬────────┘
                 │
            ┌────┴────┐
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

```
INTENT → PLAN → CONTRACT → AUTHORIZE → PREVIEW → APPLY → OBSERVE → VERIFY → STATE'
```

Les pages de sculpture (`/studio`, `/monde`, `/builder`, `/ghost/editor`, `/ghost/plan`, `/ghost/gym`, `/ghost/lab`, `/ghost/cerveau`) exigent un rôle. Le chat public (`POST /n/{slug}/ghost`) ne sculpte pas. Une question factuelle passe par le tribunal : preuve ou refus. La consolidation (rêve) n’écrit pas le monde.


Test : `tests/Unit/KernelBoundaryTest.php` lit le source. Un `Grantor` dans `Engine.php` fait échouer la suite.
