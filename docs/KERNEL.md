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

Les pages de sculpture (`/studio`, `/monde`, `/builder`, `/ghost/editor`, `/ghost/plan`, `/ghost/gym`) exigent un rôle. Le chat public (`POST /n/{slug}/ghost`) ne sculpte pas.

Test : `tests/Unit/KernelBoundaryTest.php` lit le source. Un `Grantor` dans `Engine.php` fait échouer la suite.
