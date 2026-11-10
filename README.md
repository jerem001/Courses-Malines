# 🛒 Courses Malines — v0.1.0 (starter)

Application de comparaison des courses, d'abord privée, pensée pour une ouverture publique multi-utilisateurs.

## Fonctionnalités de départ
- Connexion privée via Laravel Sanctum ; aucune inscription publique.
- Enseignes initiales : **Leclerc, Intermarché, Carrefour**.
- Préférences : favoris + proximité, rayon par défaut **15 km**, localisation facultative.
- Gestion des magasins favoris, tableau de bord et interface Vue 3.
- Tests de routes protégées et d'isolation des données.

**Statut : lot 1 validé sur une VM locale (7 tests / 14 assertions, connexion et préférences depuis Vue), mais dépôt encore partiel : le Laravel complet créé dans `api/` sur la VM n'est pas encore versionné.** Les véritables catalogues et la géolocalisation arrivent dans les prochains lots.

## Installation sur Debian
Voir [docs/DEPLOIEMENT-DEBIAN.md](docs/DEPLOIEMENT-DEBIAN.md) et [docs/APACHE-LOCAL.md](docs/APACHE-LOCAL.md) pour le virtual host Apache/PHP-FPM utilisé localement.

## Lots
Voir [ROADMAP.md](ROADMAP.md) : lots 1 à 9, livrables et critères de recette.

## Arborescence
- `backend/` : code métier à fusionner dans un projet Laravel neuf.
- `frontend/` : application Vue 3 / Vite autonome.
- `docs/` : procédures.

## API
- `POST /api/v1/auth/login`
- `GET /api/v1/auth/me`, `POST /api/v1/auth/logout`
- `GET /api/v1/brands`, `GET /api/v1/stores`
- `GET|PUT /api/v1/preferences`
- `GET|POST /api/v1/favorite-stores`, `DELETE /api/v1/favorite-stores/{store}`

## Sécurité
Aucune donnée de paiement et aucun mot de passe de Drive à stocker. Utiliser HTTPS, limiter l'accès et ne jamais committer de `.env` ni de secrets. Le prototype utilise `sessionStorage` pour son jeton : à remplacer par des cookies HttpOnly, sessions et protection CSRF avant ouverture publique.
