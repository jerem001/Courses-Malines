# Apache local + PHP-FPM 8.4 — Courses Malines

Configuration de validation locale utilisée sur Debian 13. Le frontend Vue est servi depuis `frontend/dist` et l'API Laravel par PHP-FPM. Aucun `php artisan serve` n'est nécessaire.

## Prérequis

- Projet cloné sous `/var/www/Courses-Malines`
- Application Laravel complète sous `api/`, avec `vendor/`, `.env`, migrations et Sanctum
- `npm install && npm run build` exécutés dans `frontend/`
- Apache 2.4, PHP-FPM 8.4 et modules `alias`, `proxy`, `proxy_fcgi`, `headers`
- Le port 80 doit être disponible (vérifier la présence éventuelle de Nginx)

```bash
sudo a2enmod alias proxy proxy_fcgi headers
sudo systemctl enable --now php8.4-fpm
```

## VirtualHost

Créer `/etc/apache2/sites-available/courses-malines.conf` :

```apache
<VirtualHost *:80>
    ServerName courses-malines.local
    DocumentRoot /var/www/Courses-Malines/frontend/dist
    DirectoryIndex index.html

    ErrorLog ${APACHE_LOG_DIR}/courses-malines-error.log
    CustomLog ${APACHE_LOG_DIR}/courses-malines-access.log combined

    # Mapper chaque URL /api/... vers le contrôleur frontal Laravel.
    # Ne pas utiliser Alias /api : il peut faire disparaître le préfixe
    # /api de l'URL interprétée par Laravel.
    AliasMatch "^/api(?:/.*)?$" "/var/www/Courses-Malines/api/public/index.php"

    <Directory /var/www/Courses-Malines/api/public>
        Options -Indexes +FollowSymLinks
        AllowOverride None
        Require all granted

        <Files "index.php">
            SetHandler "proxy:unix:/run/php/php8.4-fpm.sock|fcgi://localhost/"
        </Files>
    </Directory>

    <Directory /var/www/Courses-Malines/frontend/dist>
        Options -Indexes +FollowSymLinks
        AllowOverride None
        Require all granted
        FallbackResource /index.html
    </Directory>

    Header always set X-Content-Type-Options "nosniff"
    Header always set Referrer-Policy "strict-origin-when-cross-origin"
</VirtualHost>
```

Activer et tester :

```bash
sudo a2ensite courses-malines.conf
sudo apache2ctl configtest
sudo systemctl reload apache2
```

Ajouter `IP_DE_LA_VM courses-malines.local` au fichier hosts du poste client.

## Vérifications

```bash
curl -s -o /dev/null -w 'GET /me: %{http_code}\n' -H 'Host: courses-malines.local' -H 'Accept: application/json' http://127.0.0.1/api/v1/auth/me
curl -s -o /dev/null -w 'POST /login: %{http_code}\n' -H 'Host: courses-malines.local' -H 'Accept: application/json' -H 'Content-Type: application/json' -d '{}' -X POST http://127.0.0.1/api/v1/auth/login
```

Attendu : `401` (non authentifié) et `422` (champs manquants). La connexion depuis Vue a été validée manuellement sur la VM de développement.

## Sécurité et limites

- Configuration **locale uniquement**, en HTTP ; ajouter HTTPS avant exposition réseau non fiable.
- `APP_DEBUG=false` pour éviter l'affichage des traces ; ne jamais versionner `.env`.
- Les permissions d'écriture sont nécessaires uniquement pour `storage/` et `bootstrap/cache/`.
- Le dépôt contient actuellement un **starter Laravel** dans `backend/`. Le dossier `api/` complet généré sur la VM doit encore être versionné proprement (sans `.env`, `vendor/`, logs ni secrets).
- La conservation d'un jeton dans `sessionStorage` est provisoire ; renforcer l'authentification avant ouverture publique.
