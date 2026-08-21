# CCK — Custom Content Kit

Inspiré de JoomCCK et de Vera (`GUILLAUMEDEPLANQUE-geniuspace/vera`).

## Est-ce implanté ?

**Oui, en couche données + rendu**, sur plusieurs métiers :

| Surface | Exemple de champs | target_kind |
|---|---|---|
| Offre d’emploi | rémunération, remote, stack, épreuve, contrat | `node` |
| Personnage | fruit, prime, rôle, armes | `node` |
| Boutique | SKU, stock, matière, relique Drive | `product` |
| Journal / blog | temps de lecture, sources, tags | `thread` |
| Fiche vidéo | langue, arc, schema VideoObject | `media` |

**Pas encore :** builder visuel de types (UI admin pour créer un nouveau `field_type` à la souris), validation choice/scale côté formulaire, JSON-LD auto depuis chaque champ.

## Schéma

`cck_fields` :

- `node_id` — univers propriétaire
- `field_key` / `label` / `value`
- `field_type` — `text \| html \| choice \| media \| relation \| scale`
- `target_kind` — `node \| thread \| product \| media`
- `target_id` — id de la cible (produit, thread, fichier…)

## Règle d’or (devs + IA)

Ne jamais ajouter `salaire` en dur dans un composant d’offre.
Ajouter une ligne CCK, afficher via `<CckPanel fields={...} />`.

Fichiers : `src/lib/cck.ts`, `src/components/cck-panel.tsx`, `migrations/0005_life.sql` + `0007_cck.sql`.
