# Champs personnalisés

Inspiré de JoomCCK. Des **briques** qu’on pose sur une fiche (texte, image, lieu, prix…), pas une colonne SQL par métier.

En interne les développeurs disent encore « CCK ». L’interface dit **champs de la fiche**.

## Éditeur visuel

Dans le **Studio** de chaque univers (`/n/{slug}/studio`) :

1. Palette de types (Texte, Image, Prix, Lieu…)
2. Aperçu live de la fiche, à droite
3. Nommer, poser une valeur, enregistrer
4. Éditer / retirer un champ déjà posé

Huit types essentiels. Le mode avancé déverrouille ~24 types (galerie, paywall, graphe…).

## Surfaces

| Surface | Exemple |
| --- | --- |
| Offre | rémunération, remote, test, contrat |
| Personnage | fruit, prime, rôle |
| Boutique | SKU, stock, matière |
| Journal | temps de lecture, sources |
| Vidéo | langue, chapitres |

## Schéma

Table `cck_fields` : `node_id`, `name`, `type`, `value`, `target_kind`, `sort`.

Règle d’or : un nouveau besoin métier = un champ, pas une migration SQL.
