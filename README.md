# Geniuspace

**Une seule stack : Laravel 13 + Blade.** Le dossier [`geniuspace/`](./geniuspace/) est l’application. Le prototype React / TanStack a été retiré.

Repo : [GUILLAUMEDEPLANQUE-geniuspace/nodus](https://github.com/GUILLAUMEDEPLANQUE-geniuspace/nodus)

Deux univers en démo, plus dix flagships :

| Univers | C’est quoi, en clair |
| --- | --- |
| **Vera** | Un site d’offres d’emploi où le salaire est écrit, le délai de réponse est public, et on passe un test de 6 min avant d’envoyer un CV. |
| **Lumen** | Une galerie : chaque œuvre a une fiche, un certificat, une vidéo de making-of. |
| **Coffre** | Relique, hôte qui négocie, passage. `/n/coffre-celeste/p/p-cel-1` |

Les 10 flagships (Coffre → Table) sont en tête de `/create`. Les ~50 templates suivants sont du **démarrage** métier, pas des skins. Chrome Grok (`server/`) : hors stack, voir `server/README.md`.

## Lancer

```bash
cd geniuspace
composer install
php artisan migrate --force
php artisan db:seed --force
php artisan serve --host=0.0.0.0 --port=8080
```

Document root en prod (o2switch) : `geniuspace/public/`.

## Pour les développeurs

1. [CHANGELOG.md](./CHANGELOG.md) — ce qui est livré, pas la vision
2. [docs/ARCHITECTURE.md](./docs/ARCHITECTURE.md) — graphe, salles, peaux
3. [docs/CCK.md](./docs/CCK.md) — champs personnalisés (briques, pas du SQL métier)
4. [docs/FOR_AI.md](./docs/FOR_AI.md) — consignes agents

## Mots du produit, dits simplement

| Mot interne | En clair |
| --- | --- |
| Offre | Une annonce d’emploi, salaire visible |
| Test métier | Simulation de 6 minutes (consignation, circuit, soin, code) |
| Délai de réponse | L’entreprise s’engage à une date. Si elle rate, ça se voit. |
| Carnet de preuves | Les tests réussis, exportables en JSON |
| Éditeur de monde | Structure, design, boutons, mouvement — le lieu entier, sans code |
| Fiches | Guides métier liés aux offres |
| Profils oubliés | Seniors à la journée, RSA, multi-activité… |
| Candidat qualifié | Quelqu’un qui a réussi le test. L’entreprise ne paie que ça. |
| Champs personnalisés | Briques (texte, image, lieu, prix) posées sur une fiche |
| Maison / Offre / Fiche | Un lieu dans l’univers. On ne dit pas « Node » |
| Fait partie de | Le lien parent → enfant. On ne dit pas « edge » |

Les noms historiques (PPQC, Pacte, Passport, CCK) restent dans le [lexique](geniuspace/resources/views/vera/lexique.blade.php) pour ceux qui les connaissent. L’interface parle français d’abord. Le graphe et les champs sont le moteur : on vend le classement, le carnet, l’alignement — jamais « notre graphe de connaissances ».

## Tests

```bash
cd geniuspace && php artisan test
```
