# Ghost OS — agent cognitif ancré par lieu

> Ghost **ne possède pas la vérité.** Il observe, raisonne et agit sur un monde vérifiable.
> Le LLM (optionnel) comprend, planifie, explique. Le graphe Nodus décide de ce qui est vrai.
> **Le LLM propose. Le moteur Nodus décide et exécute.**

## V3 livrée — GhostAction (éditeur + business)

```
INTENT → CONTEXT → PLAN (DSL) → PREVIEW → APPLY → UNDO
                                         ↘ VERIFY
```

Ghost ne parle plus seulement du lieu. Il **manipule** la fiche et **prépare** le commerce — jamais en SQL, jamais sans preview.

| Pièce | Fichier | Contrat |
| --- | --- | --- |
| GhostAction | `GhostAction.php` | action, actor, target, ops, preview, execution, audit |
| DSL | `GhostEdit.php` | `field.add` `after=contrat` — le moteur calcule `sort` |
| Blocks | `read_editor` | composition, pas une liste plate de champs |
| Curseur | `editor_context` | « ici » = ancre spatiale |
| Catalogue | `CckCatalog::capabilities` | vocabulaire, pas un dump |
| Manifest | `GhostManifest` | ce que Ghost peut faire **ici** |
| Business | `GhostBiz.php` | READ / PREPARE / ACT + autonomie volume/remise |
| Transactions | `ghost_actions` | snapshot → undo |

Niveaux :

| Niveau | Exemple | Auto ? |
| --- | --- | --- |
| READ | « J’ai 143 clients. » | oui |
| PREPARE | « Campagne prête, 15 %. » | oui (rien n’est envoyé) |
| ACT | « Lance. » | confirm si volume ≥ 100 ou remise > 10 % |

Refusés : remboursement, suppression de client, modification de paiement.

Outils **lecture** : `read_editor`, `list_field_types`, `search_media`, `search_playlist`, `customers.segment`, `orders.filter`, `campaign.preview`.

Outils **écriture** (`field.add`, `campaign.launch`, …) : **null** dans `GhostTools::call`. L’écriture passe par `POST /ghost/apply` après preview.

API V3 :

| Méthode | URL | Rôle |
| --- | --- | --- |
| `GET` | `/n/{slug}/ghost/editor` | Structure + manifest |
| `POST` | `/n/{slug}/ghost/plan` | DSL, **n’écrit pas** la fiche |
| `POST` | `/n/{slug}/ghost/apply` | Staff. Transaction |
| `POST` | `/n/{slug}/ghost/undo` | Restaure le snapshot |

Pas dans V3 (volontaire) : vector DB, 10 000 cas gym, auto-skills en prod, ACT sans confirm hors règle volume/remise, Critic LLM.

## V1 livrée — cerveau, pas un intent unique


Avant :

```
message → intent() → 1 tool → groundedReply → LLM optionnel → réponse
```

Maintenant :

```
User
  → Understand (but + contraintes)
  → Memory (faits structurés)
  → Plan (skill + étapes)
  → Observe (multi-outils)
  → Reason (compose)
  → Verify (preuves)
  → Respond
  → Experience (feedback)
```

Le LLM n’est **jamais** le coffre.

## V2 livrée — boucle de croissance (pas plus de documents)

```
données → expériences → observations → mémoires
      → hypothèses → actions → résultats → feedback
      → nouvelles capacités
```

Ce qui est **dans le code** :

| Pièce | Fichier | Contrat |
| --- | --- | --- |
| Faits structurés | `GhostMemory` + `ghost_facts` | conversation → (sujet, prédicat, objet, confiance, source, statut) |
| Cycle de vie | `GhostLearn::promote` | unknown → observed → supported → verified → trusted · contradicted → stale → revoked |
| Hiérarchie de confiance | `GhostLearn::mayRemember` | hallucination / hypothèse **n’entrent pas** |
| Erreurs | `ghost_failures` | task, skill, error_type, correction |
| Règles | `ghost_rules` | une erreur répétée devient une règle (R-crawl, R-act…) |
| Skills | `GhostSkills` + `ghost_skill_stats` | catalogue versionné + taux |
| Candidats | `ghost_skill_candidates` | pattern détecté → **validation humaine** avant prod |
| Gym | `GhostGym` · `/n/{slug}/ghost/gym` | 12 épreuves, niveaux 1–8 |
| Maturité | `GhostMaturity` · `/n/{slug}/ghost/maturity` | 8 jauges. Autonomie **plafonnée à 54** |

Pas dans V2 (volontaire) :

- embeddings / vector DB (le pack du lieu + le graphe suffisent)
- crawl web « pour nourrir » (interdit)
- auto-écriture de skills en production (candidat seulement)
- ACT autonome

## Contrat

| Niveau | Outils | Ghost peut ? |
| --- | --- | --- |
| OBSERVE | list_*, search_graph, compare, rank, match, find_path | oui |
| SUGGEST | (réponse) | oui |
| PREPARE | panier négocié, brouillon dossier | oui |
| ACT | achat, candidature, unlock, modifier le graphe | **jamais seul** |

## API

| Méthode | URL | Rôle |
| --- | --- | --- |
| `GET` | `/n/{slug}/ghost` | Hello + profil + CTAs |
| `GET` | `/n/{slug}/ghost/context` | Pack de vérité (debug / staff) |
| `POST` | `/n/{slug}/ghost` | `{ "message": "…", "history": [] }` |

Réponse chat :

```json
{
  "reply": "…",
  "citations": [{ "label": "…", "url": "…" }],
  "tools": ["list_products", "compare_products", "rank"],
  "actions": [{ "label": "…", "href": "…" }],
  "profile": "marchand|rh|guide",
  "mode": "grounded|llm+grounded|verified-block",
  "goal": "find_product",
  "skill": "find_product",
  "plan": [{ "tool": "list_products", "level": "observe" }],
  "verify": { "valid": true, "status": "known" },
  "memory": [{ "label": "Préfère", "value": "sombre", "confidence": 0.91, "status": "known" }],
  "permission": "observe"
}
```

## Mémoire

Pas un transcript. Des **faits** :

```
(visitor, prefers_style, sombre)  confidence 0.91  source explicit  status known
(visitor, budget_max, 200)        confidence 0.90  source explicit  status known
```

Statuts : `known` · `inferred` · `uncertain` · `contradicted` · `unknown`.

Une hallucination n’entre pas en mémoire. Une inférence ne devient pas un fait.

Tables : `ghost_facts`, `ghost_experiences`, `ghost_tool_stats`.

## Skills

`greet` · `find_product` · `negotiate` · `match_job` · `verify_claim` · `inspect_carnet` · `investigate_place` · `build_application`

Chaque skill a des outils, un plafond, une procédure. Ghost n’invente pas le plan.

## Outils de raisonnement

En plus des lectures (`list_products`, `list_neighbors`, `list_media`, `check_grants`, `list_rooms`, `list_fiches`, `order_options`) :

| Outil | Rôle |
| --- | --- |
| `compare_products` | Filtre prix / style |
| `rank` | Classe selon contraintes + mémoire |
| `match_user_job` | Score, forces, manques, preuves |
| `search_graph` | Cherche fiches / champs / voisins |
| `find_path` | Chemin dans le graphe du lieu |

## Vérificateur

Avant d’envoyer la phrase :

- chaque chiffre € existe dans le coffre
- pas de jargon moteur
- pas d’ACT simulé (« je vous débloque », « vous êtes embauché »)
- sinon : `verified-block` + phrase de repli

## Règles dures (inchangées)

1. Aucune affirmation hors contexte du lieu.
2. Aucun grant / unlock écrit par le modèle.
3. Prix = montants produits / champs seulement.
4. RH = orientation épreuve, pas promesse d’embauche.
5. UI sans jargon (pas Node, CCK, edge, grant).
6. **Ghost ne passe jamais PREPARE → ACT tout seul.**

## LLM optionnel

Sans config : mode **grounded** (planner déterministe + tools + réponses ancrées).

```env
GHOST_LLM_URL=http://127.0.0.1:11434/api/chat
GHOST_LLM_MODEL=llama3.1
```

Le LLM reçoit le system prompt + JSON contexte + la réponse grounded à respecter sur les faits. Le vérificateur passe **après**.

## Fichiers

| Fichier | Rôle |
| --- | --- |
| `app/Support/Ghost.php` | Orchestrateur |
| `app/Support/GhostMemory.php` | Faits + working memory |
| `app/Support/GhostPlanner.php` | But + skill + étapes |
| `app/Support/GhostExecutor.php` | Multi-outils + plafond |
| `app/Support/GhostVerifier.php` | Preuves |
| `app/Support/GhostSkills.php` | Catalogue |
| `app/Llm/GhostTools.php` | Lectures + raisonnement |
| `resources/views/partials/ghost-orb.blade.php` | UI |
| `tests/Feature/GhostMindTest.php` | Cerveau |
| `tests/Feature/GhostTest.php` | Régression orbe |

```bash
cd geniuspace && php artisan migrate --force && php artisan test --filter=Ghost
```
