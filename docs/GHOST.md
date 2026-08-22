# Ghost OS — agent ancré par lieu

> Le Ghost n’est **pas** un ChatGPT collé sur le site.  
> C’est un agent **par Node** qui ne sort pas du coffre (champs, voisins, produits, grants).

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
  "tools": ["list_products"],
  "actions": [{ "label": "…", "href": "…" }],
  "profile": "marchand|rh|guide",
  "mode": "grounded|llm+grounded"
}
```

## Profils

| Preset Chrome | Profil Ghost |
| --- | --- |
| `merch` | marchand |
| `vera` | rh |
| `living` | guide |

## Règles dures

1. Aucune affirmation hors contexte Node.
2. Aucun grant / unlock écrit par le modèle.
3. Prix = montants produits / champs seulement.
4. RH = orientation épreuve, pas promesse d’embauche.
5. UI sans jargon (pas Node, CCK, edge, grant).

## LLM optionnel

Sans config : mode **grounded** (intent + tools + réponses déterministes).

```env
GHOST_LLM_URL=http://127.0.0.1:11434/api/chat
GHOST_LLM_MODEL=llama3.1
# ou
OLLAMA_BASE_URL=http://127.0.0.1:11434
OLLAMA_MODEL=llama3.1
```

Le LLM reçoit le system prompt + JSON contexte + la réponse grounded à respecter sur les faits.

## Fichiers

| Fichier | Rôle |
| --- | --- |
| `app/Support/Ghost.php` | Contexte, intent, grounded, LLM |
| `app/Llm/GhostTools.php` | Tools lecture |
| `app/Http/Controllers/GhostController.php` | HTTP |
| `resources/views/partials/ghost-orb.blade.php` | UI |
| `tests/Feature/GhostTest.php` | Régression |

## Lancer les tests

```bash
cd geniuspace && php artisan migrate --force && php artisan test --filter=Ghost
```
