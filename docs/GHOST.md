# Ghost OS — agent cognitif ancré par lieu

> Ghost **ne possède pas la vérité.** Il observe, raisonne et agit sur un monde vérifiable.
> Le LLM (optionnel) comprend, planifie, explique. Le graphe Nodus décide de ce qui est vrai.

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
