# Courses Malines — Feuille de route produit

**Version :** 1.0 (5 novembre 2026). **Statut :** plan prévisionnel, non livré sauf mention contraire.

## Vision
Comparer le coût **réel du panier complet**, promotions et qualité nutritionnelle incluses. Démarrage personnel privé, architecture multi-utilisateurs adaptée à une future ouverture publique.

**Configuration de référence** : Leclerc, Intermarché, Carrefour ; mode `both` (favoris + proximité) ; rayon 15 km, modifiable ; commune à renseigner. Mode de panier **hybride** : ajout automatique uniquement lorsque l’intégration est autorisée et fiable ; à défaut, ajout guidé. Paiement toujours confirmé chez le marchand.

## Lots et critères de validation

### Lot 1 — Socle applicatif et profils · v0.1.0 · EN COURS
- [x] Premier starter Laravel (API Sanctum, migrations, modèles, tests).
- [x] Premier frontend Vue 3 (connexion, tableau de bord, magasins).
- [x] Référentiel d'enseignes, préférences (15 km, both), favoris de magasins.
- [ ] Intégration du starter dans un Laravel complet sur Debian.
- [ ] Tests, build, Nginx/PHP-FPM, HTTPS, journaux et sauvegardes.
- [ ] Recette de bout en bout et contrôle d'isolation entre utilisateurs.

**Recette :** connexion/déconnexion, routes privées, favoris isolés, préférences persistantes, aucune inscription publique. **Attention :** le rayon est enregistré mais la découverte géographique n'est pas encore disponible.

### Lot 2 — Listes de courses et catalogue · v0.2.0
- [ ] Produits, GTIN/codes-barres, marques, conditionnements et unités normalisées.
- [ ] Listes CRUD, lignes, quantités, duplication, modèles réutilisables.
- [ ] Recherche rapide, suggestions, saisie manuelle, scan mobile.
- [ ] Open Food Facts : nutrition, Nutri-Score, NOVA, allergènes, provenance et cache.
- [ ] Références substituables sans confusion de poids/format.

**Recette :** sauvegarde/rechargement des listes, quantités fiables, données nutritionnelles manquantes clairement signalées.

### Lot 3 — Géolocalisation, Drives et prix · v0.3.0
- [ ] Géocodage de commune/code postal ; Drives proches dans un rayon configurable.
- [ ] Gestion distincte enseigne/point de vente ; favoris, distance, services disponibles.
- [ ] Contrat `StoreConnector` (recherche, prix, stock, promos, capacités).
- [ ] Étude des API et des conditions d’accès ; premier connecteur autorisé.
- [ ] Normalisation produit/magasin, prix au kilo/litre/unité.
- [ ] Horodatage, disponibilité, origine, historique et fraîcheur du prix.
- [ ] Repli lorsque la source ne permet pas une comparaison fiable.

**Recette :** aucun tarif présenté comme actuel sans horodatage/source ; prix spécifiques au Drive, jamais à l'enseigne entière.

### Lot 3 bis — Import intelligent des achats (tickets et PDF) · v0.3.1
- [ ] Import de photos de tickets (JPG/PNG) et de tickets ou factures PDF, y compris les PDF scannés.
- [ ] Extraction du texte natif des PDF ou OCR pour les images ; repérage de l'enseigne, de la date, des libellés, quantités et prix lorsque disponibles.
- [ ] Nettoyage des libellés abrégés et rapprochement avec le catalogue (GTIN si présent, marque, format, correspondances probables).
- [ ] Écran de vérification des articles détectés, gestion des doublons et des correspondances incertaines.
- [ ] Ajout sélectif aux produits favoris ou à une liste de courses existante ; aucune insertion silencieuse.
- [ ] Historique d'imports et fréquence d'achat optionnels pour suggérer les produits habituels.
- [ ] Contrôle de la confidentialité : fichiers temporaires, suppression après traitement par défaut, limites de taille, contrôle d'accès et absence de conservation non consentie.
- [ ] Tests sur tickets flous, multi-pages, libellés abrégés, erreurs OCR et prix/remises distincts.

**Recette :** un ticket photo ou PDF permet de proposer une liste d'articles vérifiable, sans inventer les correspondances incertaines ; l'utilisateur choisit ceux à ajouter à ses favoris.

### Lot 4 — Promotions et optimisation du panier · v0.4.0
- [ ] Remises immédiates, pourcentages, lots, 2+1 gratuit et paliers d’achat.
- [ ] Coupons, cartes de fidélité, remises différées et règles de cumul distinctes.
- [ ] Calcul du prix total par quantité, conditionnement et offre applicable.
- [ ] Scénarios 1 Drive, 2 Drives ou multi-Drives, avec frais et déplacements.
- [ ] Budget plafond, seuils de qualité, allergies et alternatives validées.

**Recette :** calculs explicables et testés aux seuils ; économies calculées sur une base comparable.

### Lot 5 — Paniers Drive hybrides · v0.5.0
- [ ] Connecteurs déclarant leurs possibilités d’authentification, ajout et lecture de panier.
- [ ] Ajout automatique lorsque l’intégration est autorisée et stable.
- [ ] Sinon ouverture guidée des fiches produit avec quantités et suivi.
- [ ] Gestion des erreurs partielles, reprises et divergences prix/stock.
- [ ] Vérification finale du panier avant redirection au site marchand.

**Recette :** aucun paiement ni commande déclenché par l’application ; mode guidé utilisable sans compte marchand connecté.

### Lot 6 — Historique, économies et alertes · v0.6.0
- [ ] Historique des prix par produit et magasin, filtres et graphiques.
- [ ] Budget courses, économies réalisées et méthode de calcul explicite.
- [ ] Alertes prix/promotions facultatives, fréquence et désinscription.
- [ ] Export CSV et bilan mensuel ; comparaison estimé / réellement payé.

**Recette :** aucun chiffre d'économie ambigu, source et référence visibles.

### Lot 7 — Famille et mobile · v0.7.0
- [ ] Foyers et invités, partage de listes, rôles et droits d’accès.
- [ ] Synchronisation des courses et progression des achats.
- [ ] PWA mobile installable, ergonomie tactile, mode hors ligne limité.
- [ ] Accessibilité et tests navigateurs.

**Recette :** cloisonnement des foyers, permissions testées, parcours mobile utilisable.

### Lot 8 — Sécurité et préparation de l'ouverture publique · v0.8.0
- [ ] Inscription configurable, vérification d'e-mail, réinitialisation de mot de passe.
- [ ] Authentification web par cookies HttpOnly, protections CSRF, rate limiting.
- [ ] RGPD : transparence, export et suppression, durées de conservation.
- [ ] Gestion chiffrée des secrets, audit de dépendances, limitation d'abus.
- [ ] Supervision, quotas, tests de charge, sauvegardes et restauration.

**Recette :** audit de sécurité et documentation d'exploitation avant toute ouverture.

### Lot 9 — Bêta, lancement et exploitation · v1.0.0
- [ ] Bêta fermée, retours et corrections.
- [ ] Documentation utilisateurs, conditions d'utilisation et support.
- [ ] Validation des droits d'utilisation des catalogues par marchand.
- [ ] Déploiement progressif, monitoring, maintenance des connecteurs.

**Recette :** parcours comparaison → panier guidé ou automatique opérationnel avec données fiables et protections actives.

## Évolutions après la v1.0.0
Enseignes supplémentaires, alternatives locales, prévisions de budget, suggestions personnalisées respectueuses de la vie privée.

## Principes transversaux
Tests automatisés, cloisonnement des données par utilisateur, migrations réversibles, observabilité sans données sensibles. Prévoir des caches et limites d'appels aux fournisseurs. Ne jamais traiter une remise fidélité différée comme une remise immédiate. Ne pas inventer de prix ou promettre un connecteur non vérifié. Nutri-Score et NOVA sont présentés comme indicateurs, non comme garantie de santé.

## Décisions ouvertes
Commune/code postal ; premier Drive et source fiable de prix ; fournisseur de géocodage ; domaine/HTTPS et sauvegardes Debian ; conditions d'ouverture publique.

**Dépendances principales :** Lots 1 → 2 → 3 → 3 bis → 4 → 5 → 6 → 7 → 8 → 9. Sécurité et qualité des données commencent dès le lot 1.
