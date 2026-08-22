# Champs de fiche (moteur, pas un formulaire)

Des **briques** qu’on pose sur une fiche (texte, image, lieu, prix…), pas une colonne SQL par métier.

En interne les développeurs disent encore « CCK ». L’interface dit **champs de la fiche** ou **détail**.

Le graphe (`nodes` + `edges`) et ces champs sont **l’unique enregistrement de la vérité métier**. L’UI n’affiche que des lieux, des preuves et des décisions.

## Modèles (P0)

Dans le Studio, l’opérateur choisit un métier — les détails se créent. Il ne compose pas depuis zéro.

| Modèle | Détails |
| --- | --- |
| Offre tech | salaire, télétravail, contrat, séniorité, stack, visa |
| Offre industrie | + habilitation, CACES, 3×8 |
| Personnage | fruit, prime, affiliation |
| Produit | référence, stock, matière, prix |
| Maison | délai de réponse, fiabilité, industrie, ville |

`field_key` est stable (`salaire`, pas le libellé traduit). Unité + min/max sur les échelles.

## Un enregistrement, quatre surfaces

Le même champ `salaire` s’affiche :

1. en badge sur la card
2. en fourchette sur la fiche
3. en position vs médiane dans le comparateur
4. en `baseSalary` JobPosting

Code : `Engine::surface($field, 'badge'|'fiche'|'compare'|'jsonld')`.

## API (ATS / embed)

```
GET /v1/nodes/{slug}/fields
GET /v1/nodes/{slug}/neighbors
GET /v1/nodes/{slug}/media
```

JSON humain : `fiche`, `details`, `liens.fait_partie_de`. Jamais `CCK`, `parent_of`, `node`.

## Éditeur visuel

Studio (`/n/{slug}/studio`) : modèles + palette + aperçu live.

## Règle d’or

Un nouveau besoin métier = un champ ou une arête, pas une migration SQL.
