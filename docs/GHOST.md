# Ghost OS — agent cognitif ancré par lieu

> Ghost **ne possède pas la vérité.** Il observe, raisonne et agit sur un monde vérifiable.
> Le LLM (optionnel) comprend, planifie, explique. Le graphe Nodus décide de ce qui est vrai.
> **Le LLM propose. Le moteur Nodus décide et exécute.**
> **Autonome sur la stratégie, jamais sur l’autorité.**

Nodus maintient un monde structuré et vérifiable. Ghost est une couche cognitive capable de **proposer des transitions** de ce monde, contraintes par un moteur déterministe d’autorité, de capacités et de vérification.

## V8 — Mémoire, preuves, consolidation

Nodus reste le noyau d’exécution. V8 absorbe les **algorithmes** d’un cerveau personnel (index, liens, oubli, refus sans preuve) sans absorber IndexedDB, un LLM local, ni un vector DB.

```
WORLD (Engine) ──► CORTEX (TF-IDF-like + hashing-trick, pas BM25, pas MiniLM)
                      │
         chunks (preuves)   beliefs (visiteur)   ← couches séparées
                      │
                 TRIBUNAL
            preuve  /  refus
                      │
                 SYNAPSE  w' = w + α(1−w)
                      │
                  DECAY   w × 2^(−Δt/hl)
                      │
                  DREAM   prune → liaisons distantes → candidats
                      │
                 GROWTH   prediction error. Sans observation : rien.
```

| Pièce | Fichier | Contrat |
| --- | --- | --- |
| Cortex | `GhostCortex.php` | Hit = `{layer, score}`. `world` ≠ `belief`. |
| Chunk | `GhostChunk.php` | 600 / 150. ID `sha1` stable. |
| Synapse | `GhostSynapse.php` | Hebbian. Pas une arête métier. |
| Decay | `GhostDecay.php` | Demi-vie par relation. SERENDIPITY = 14 j. |
| Tribunal | `GhostTribunal.php` | Réponse extraite + citations, ou refus. Jamais belief. Citations publiques cliquables (pas de page staff). |
| Consistency | `GhostConsistency.php` | SUPPORT / CONTRADICTION / NEUTRAL / NEW. Jaccard + négation. Pas un T5. |
| Dream | `GhostDream.php` | Consolidation. `applied = false`. |
| Growth | `GhostGrowth.php` | Sans `observed`, pas de mise à jour. |

Pas dans V8 : transformers.js, OCR, PDF, auto-ACT, mélange world/belief, observed simulé.

## V7 — Boucle cognitive

```
WORLD (Engine)
   → OBSERVE
   → SITUATION (hard / soft / trade-off)
   → MEMORY (semantic · episodic · procedural · social · working)
   → HYPOTHESIS + contre + rivales
   → PLAN
   → CRITIC → REPLAN
   → SIMULATE (A/B/C, observed = null)
   → DECIDE
   → CONTRACT → AUTHORIZE
   → EXECUTE  (jamais seul)
   → VERIFY
   → REFLECT (expected vs actual)
   → LEARN (généraliser, versionner la skill)
   → SELF MODEL
```

| Pièce | Fichier | Contrat |
| --- | --- | --- |
| Core | `GhostCore.php` | Orchestrateur. Pas un god object métier. |
| Situation | `GhostSituation.php` | JSON validé. Le LLM propose, Nodus recale. |
| Working memory | `GhostWorkingMemory.php` | But, sous-buts, connus, inconnus, risques. |
| Critic | `GhostCritic.php` | Attaque le raisonnement. Pas l’arbitre de vérité. |
| Simulator | `GhostSimulator.php` | « Si je fais X ? » Jamais une observation. |
| Reflector | `GhostReflector.php` | Écart prédiction / monde → règle. |
| Belief | `GhostBelief.php` | P + preuves + decay. Pas `knowledge = 87`. |
| Self | `GhostSelfModel.php` | Matrice de capacités. Autonomie ≠ booléen. |
| Skill | `recover_failed_campaign` | Préconditions. Envoi = ACT. |

Quatre temporalités : fast / episode / learning / strategic.

Signature inchangée : **autonome sur la stratégie, jamais sur l’autorité.**

Pas dans V7 : fine-tune, auto-ACT, vector DB, critic comme juge de vérité.

## V4 — Contrat, provenance, deux réalités

```
MONDE NODUS (nodes / edges / fields)
        │
     CONTEXT
        │
   GHOST MEMORY     ← croyance visiteur. Jamais la vérité du monde.
        │
    UNDERSTAND
        │
      PLAN
        │
   GhostActionContract
     intent · authority · ops · preview
     allowed / forbidden
     expected_state · evidence
        │
     NODUS ENGINE
        │
   DENY / CONFIRM / APPLY
        │
     OBSERVE  (état vu ≠ état déclaré)
        │
      VERIFY  Claim → Evidence → PASS / FAIL / UNKNOWN
        │
    FAIL → LEARN          PASS → STATE' + provenance
```

| Pièce | Fichier | Contrat |
| --- | --- | --- |
| GhostActionContract | `GhostActionContract.php` | INTENT → PLAN → CONTRACT → AUTHORIZE → PREVIEW → APPLY → OBSERVE → VERIFY |
| Provenance | `GhostProvenance.php` + `ghost_transitions` | STATE' ← produced_by ACTION ← verified_by VERIFICATION |
| Claims | `GhostVerifier` | prix cité = prix observé, sinon FAIL / UNKNOWN |
| Manifeste | `GhostManifest::gates` | READ / PREPARE(=PROPOSE) / ACT / DENY. Ghost ne reçoit pas DENY. |
| Deux réalités | `GhostMemory` | world = Engine. belief = visitor. `mixed()` doit rester false. |

Produit (inchangé) : OBSERVE / PREPARE / ACT.

Technique : OBSERVE (lecture) · PROPOSE (transition candidate) · AUTHORIZE (permission + confirm) · EXECUTE (mutation) · VERIFY (état observé).

PREPARE (produit) = PROPOSE (noyau).

Pas dans V4 (volontaire) : vector DB, auto-ACT hors volume/remise, mélange world/belief, apply sans contrat.

## V6 — Hypothèse, expérience, observation

V5 estimait un « observed_gain » par formule (prior + jitter). **Ce n’était pas une expérience.** V6 sépare.

```
OBJECTIF
  → WORLD OBSERVER (Engine, preuves, salon, médias)
  → PROBLEM MODEL (leviers du problème, pas les 9 par défaut)
  → HYPOTHESIS + contre-hypothèse
  → GENERATE / MUTATE / RECOMBINE
  → ESTIMATE (prior. Pas une observation.)
  → DESIGN expérience
  → OBSERVE le monde (naturelle, ou en attente d’autorité)
  → EVALUATE (surprise, réfutation, discovery)
  → LEARN (échec → contrainte)
  → PREPARE → ACT (humain)
```

| Pièce | Contrat |
| --- | --- |
| GhostWorldObserver | Lit le monde. N’écrit pas. Pas de crc32. |
| GhostHypothesis | Observation → hypothèse → prédiction → contre. Cherche à **réfuter**. |
| GhostExperiment | estimate ≠ observe. `observed` null tant que le monde n’a pas parlé. |
| Novelty | distance de génome. |
| Innovation | observed × surprise. Nulle sans observation. |
| Surprise | \|observed − predicted\| |
| BASE | prior d’estimation. **Pas** une causalité découverte. |
| Autorité | inchangée. `strategy.deploy` = ACT + CONFIRM. |

Une tenue (`winner`) exige `evidence = world`. Une estimation reste `untested`.

Le LLM, s’il existe un jour, entre par `GhostHypothesis::ingestConcepts`. Il propose. Il ne juge pas.

Pas dans V6 : auto-déploiement, A/B qui mute le monde tout seul, vector DB, « trois idées ».

## V5 — Strategy Discovery (corrigé)

Ghost **cherche** dans l’espace des leviers. V5 générait des génomes. V6 les ancre sur le monde.

Signature : **autonome sur la stratégie, jamais sur l’autorité.**

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
| `GET` | `/n/{slug}/ghost/editor` | Staff admin. Structure + manifest |
| `POST` | `/n/{slug}/ghost/plan` | Staff admin. DSL, **n’écrit pas** la fiche |
| `POST` | `/n/{slug}/ghost/apply` | Staff admin. Transaction |
| `POST` | `/n/{slug}/ghost/undo` | Staff admin. Restaure le snapshot |
| `GET` | `/n/{slug}/ghost/context` | Staff admin. Pack de vérité |
| `POST` | `/n/{slug}/ghost/skills` | Staff admin. Valide un candidat |
| `GET`/`POST` | `/n/{slug}/ghost/gym` | Staff admin. Salle d’épreuve |
| `GET`/`POST` | `/n/{slug}/ghost/lab` | Staff admin. Laboratoire de stratégies. Deploy = preview |
| `GET`/`POST` | `/n/{slug}/ghost/cerveau` | Staff admin. Index, preuves, consolidation. `applied = false` |
| `GET` | `/n/{slug}/ghost/maturity` | Public. Jauges, autonomie = 54 |
| `GET`/`POST` | `/n/{slug}/ghost` | Public. Chat. N’écrit pas la fiche |

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
| `GET` | `/n/{slug}/ghost` | Hello + profil + CTAs (public) |
| `GET` | `/n/{slug}/ghost/context` | Pack de vérité — **staff admin** |
| `POST` | `/n/{slug}/ghost` | `{ "message": "…", "history": [] }` (public) |

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

## Mémoire — deux réalités

Pas un transcript. Deux coffres distincts :

**World truth** — ce que Nodus sait du monde (`Engine::fields`, graphe, produits). Source `engine`. Confiance 1.

**Agent belief** — ce que Ghost croit du visiteur (`ghost_facts`, subject = `visitor`). Source `explicit` / `inferred`.

```
(visitor, prefers_style, sombre)  confidence 0.91  source explicit  status known
(visitor, budget_max, 200)        confidence 0.90  source explicit  status known
```

Une hallucination n’entre pas. Un fait visiteur n’écrase pas un champ Engine. `GhostMemory::mixed()` est l’invariant.

Statuts : `known` · `inferred` · `uncertain` · `contradicted` · `unknown`.

Cycle de vie (stratégies, pas les faits) : unknown → observed → inferred → supported → verified → trusted · contradicted → stale → revoked.

Ghost mémorise : faits, expériences, erreurs, corrections, règles, skills. Pas des hallucinations.

Tables : `ghost_facts`, `ghost_experiences`, `ghost_tool_stats`, `ghost_transitions`.

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

Claim → ensemble de preuves → règle → PASS / FAIL / UNKNOWN.

Exemple : « Cette offre coûte 180 € » + `product.price = 180` → VERIFIED. 999 € dans un coffre à 180 → FAIL. Aucun prix observé → UNKNOWN (pas PASS).

Garde-fous linguistiques inchangés : jargon, « je vous débloque », ACT simulé → `verified-block`.

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
| `app/Support/Ghost.php` | Orchestrateur (délègue au Core) |
| `app/Support/GhostCore.php` | Boucle cognitive v7 |
| `app/Support/GhostSituation.php` | Situation structurée |
| `app/Support/GhostWorkingMemory.php` | Mémoire de travail |
| `app/Support/GhostCritic.php` | Attaque le raisonnement |
| `app/Support/GhostSimulator.php` | Contrefactuels. Pas le monde. |
| `app/Support/GhostReflector.php` | Expected vs actual |
| `app/Support/GhostBelief.php` | P + preuves + decay |
| `app/Support/GhostSelfModel.php` | Métacognition |
| `app/Support/GhostMemory.php` | Faits + 5 couches · world ≠ belief |
| `app/Support/GhostActionContract.php` | Transition d’état vérifiable |
| `app/Support/GhostProvenance.php` | produced_by / verified_by |
| `app/Support/GhostPlanner.php` | But + skill + étapes |
| `app/Support/GhostExecutor.php` | Multi-outils + plafond |
| `app/Support/GhostVerifier.php` | Claim → evidence → PASS/FAIL/UNKNOWN |
| `app/Support/GhostSkills.php` | Catalogue |
| `app/Llm/GhostTools.php` | Lectures + raisonnement |
| `resources/views/partials/ghost-orb.blade.php` | UI |
| `tests/Feature/GhostMindTest.php` | Cerveau |
| `tests/Unit/GhostContractTest.php` | Contrat |
| `tests/Feature/GhostProvenanceTest.php` | Provenance |
| `tests/Feature/GhostTest.php` | Régression orbe |

```bash
cd geniuspace && php artisan migrate --force && php artisan test --filter=Ghost
```
