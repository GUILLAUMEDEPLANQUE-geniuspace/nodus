# Consignes pour les agents IA

Tu travailles sur NODUS. Avant de coder :

1. Lis `docs/ARCHITECTURE.md` et `docs/CCK.md`.
2. N’introduis **pas** une nouvelle peau CSS (pas de 6e couleur, pas d’emoji). Tokens dans `src/styles.css`.
3. Un Node manga et un Node recruteur **ne partagent pas le même layout** : `skins.ts`.
4. Les onglets du dock viennent de `node_tabs`. Si tu ajoutes une salle (wiki îles, live, etc.), c’est une ligne `node_tabs` + un `tab === "…"` dans LivingWorld / VeraHouse.
5. Les champs métier = CCK. Pas de colonne SQL `salary` sur `nodes`.
6. Migrations : nouveau fichier `migrations/0008_….sql`. Ne pas réécrire 0001–0007.
7. Commenter **pourquoi** (contrat, piège prod), pas chaque ligne JSX.
8. Auth : lectures publiques, écritures `authMiddleware`. En prod, `node_staff`.
9. Parent/enfant est le cœur. Ne pas aplatir le graphe en tags.
10. Le dock reste **en bas** et suit le scroll. Pas de mega-menu haut.
