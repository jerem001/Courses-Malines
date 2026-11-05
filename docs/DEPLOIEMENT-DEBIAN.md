# Installation sur Debian — starter Courses Malines v0.1.0

Le dépôt contient un **starter**, pas une installation complète Laravel. Cette procédure est prévue pour une VM de **développement/validation**, non pour l'exposition à Internet.

## Prérequis

Git, PHP compatible avec Laravel et extensions nécessaires (dont pdo_pgsql, mbstring, xml, curl, zip), Composer, PostgreSQL, Node.js LTS compatible (22+) et npm. Redis sera utile plus tard ; facultatif pour les premiers tests.

Créer un compte PostgreSQL dédié et une base `courses_malines`, avec un mot de passe fort.

## 1. Récupération et squelette Laravel

```bash
git clone https://github.com/jerem001/Courses-Malines.git
cd Courses-Malines
composer create-project laravel/laravel api
cd api
php artisan install:api
cp -a ../backend/app/. app/
cp -a ../backend/database/. database/
cp -a ../backend/routes/. routes/
cp -a ../backend/tests/. tests/
```

`install:api` ajoute Sanctum et configure normalement `routes/api.php`. Vérifier dans `bootstrap/app.php` que le chargement des routes API n'est présent **qu'une fois**. Ne pas effacer les fichiers standards Laravel lors de la copie.

## 2. Configuration backend

Renseigner `api/.env` (non committé) :

```dotenv
APP_NAME="Courses Malines"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://127.0.0.1:8000
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=courses_malines
DB_USERNAME=courses_malines
DB_PASSWORD=SECRET_LOCAL_A_REMPLACER
CACHE_STORE=file
QUEUE_CONNECTION=database
```

Puis :

```bash
php artisan key:generate
php artisan migrate
php artisan db:seed --class=BrandSeeder
php artisan route:list --path=api
php artisan test
```

Premier utilisateur privé (dans `php artisan tinker`) :

```php
\App\Models\User::create([
    'name' => 'Administrateur',
    'email' => 'admin@example.test',
    'password' => 'MOT_DE_PASSE_LONG_ET_UNIQUE',
]);
```

Le modèle `User` standard doit utiliser le cast `hashed` pour les mots de passe. Ne pas activer de route d'inscription publique.

## 3. Validation en développement

Terminal 1 :

```bash
cd Courses-Malines/api
php artisan serve --host=127.0.0.1 --port=8000
```

Terminal 2 :

```bash
cd Courses-Malines/frontend
npm install
cp .env.example .env
npm run dev -- --host 127.0.0.1
```

Depuis votre poste, utiliser un tunnel SSH :

```bash
ssh -L 5173:127.0.0.1:5173 -L 8000:127.0.0.1:8000 utilisateur@IP_VM
```

Puis ouvrir `http://localhost:5173`. Le frontend utilise le proxy Vite `/api` vers `localhost:8000`, sur la même VM.

## 4. Vérifications

- `php artisan test` sans erreur ; API `/api/v1` présente.
- Connexion au compte privé et déconnexion fonctionnelles.
- Routes protégées inaccessibles sans jeton.
- Préférences par défaut : `both`, `15 km`.
- Trois enseignes seedées ; aucun Drive fictif importé.
- Tests d'isolation des préférences et favoris.

## 5. Avant mise en production

Installer Nginx, PHP-FPM, HTTPS et sauvegardes PostgreSQL ; désactiver `APP_DEBUG`; protéger les secrets et fixer CORS. Ne jamais exposer `artisan serve` ou Vite dev sur Internet. Revoir le système d'authentification du frontend (jeton en sessionStorage dans ce prototype) avant ouverture publique : cookies HttpOnly, protections CSRF et sessions sécurisées.
