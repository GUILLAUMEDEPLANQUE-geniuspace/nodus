# Invariants — 15 règles, pas 16 concepts

Le moteur ne grandit plus par ajout de mots. Il se durcit. Chaque règle a un test (`tests/Feature/InvariantTest.php`, `tests/Unit/KernelBoundaryTest.php`). Une PR qui en casse une est refusée.

| Id | Règle | Où ça casse |
| --- | --- | --- |
| I-READ | Lectures publiques. Écritures authentifiées. | Forum, Drive, Studio, Magazine, Create, Image, lore |
| I-STAFF | Sculpter = `node_staff`. **Pas** d’auto-owner. GET studio / monde / builder / gym / editor = staff. | `Acl::canWrite` |
| I-NO-DEMO | `/login/demo` local / tests seulement. | `routes/web.php` |
| I-GRANT | Média gated : ligne `grants`, sinon 403. | `Grantor`, `MediaController` |
| I-PATH | `/play` refuse `..` | `Invariants::safeRel`, `SignedMedia::fullPath` |
| I-PREVIEW | `preview=1` ne sert jamais `private/` | `MediaController::play` |
| I-JARGON | Copy sans CCK / parent_of / GpNode | `Vocab::banned` |
| I-FIELDS | Métier = `cck_fields.field_key`. Pas de colonne `salary`. | schéma `nodes` |
| I-NO-ACT | Ghost n’achète, n’embauche, n’ouvre, ne rembourse pas. | `GhostTools::call` → null |
| I-PLAN | PLAN n’écrit pas. APPLY = staff admin. | `/ghost/plan` vs `/ghost/apply` |
| I-DENY | Remboursement / client / paiement : deny | `GhostAction::PERMS` |
| I-FLOOR | Prix tenu ≥ plancher | `Pay::cents` → `Invariants::heldCents` |
| I-CRAWL | Pas de wiki, pas de crawl | `Ghost::reply` |
| I-MEMORY | Hallucination hors mémoire | `GhostLearn::mayRemember` |
| I-CAP | Autonomie = **54** | `Invariants::AUTONOMY_CAP` |

Pas dans le contrat (volontaire) : vector DB, auto-skills, ACT autonome hors volume/remise, Critic LLM.
