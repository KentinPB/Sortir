# Sortir.com

Application web développée avec **Symfony 7.4** permettant à des étudiants/participants d'un même organisme (répartis sur plusieurs campus) de créer, consulter et s'inscrire à des sorties (activités, sorties culturelles, sportives, etc.).

## Sommaire

- [Fonctionnalités](#fonctionnalités)
- [Stack technique](#stack-technique)
- [Prérequis](#prérequis)
- [Installation](#installation)
- [Base de données](#base-de-données)
- [Lancer le projet](#lancer-le-projet)
- [Comptes de démonstration](#comptes-de-démonstration)
- [Structure du projet](#structure-du-projet)
- [Cycle de vie d'une sortie](#cycle-de-vie-dune-sortie)

## Fonctionnalités

- **Authentification** : connexion, déconnexion, gestion de compte via Symfony Security.
- **Gestion des sorties** : création, modification, publication, annulation et suppression d'une sortie par son organisateur.
- **Inscription / désistement** des participants aux sorties, avec gestion automatique du nombre de places.
- **Filtrage des sorties** sur la page d'accueil (campus, nom, dates, organisateur, inscrit/non inscrit, sorties terminées).
- **Gestion des lieux** avec filtrage dynamique par ville (API interne + JavaScript).
- **Profils utilisateurs** : consultation, édition, upload de photo de profil.
- **Back-office administrateur** : gestion des utilisateurs, des campus et des villes.
- **États automatiques des sorties** (`En création`, `Ouverte`, `Clôturée`, `En cours`, `Terminée`, `Annulée`, `Historisée`) recalculés via `SortieStateManager`.

## Stack technique

- **PHP** ≥ 8.4.15
- **Symfony** 7.4 (Framework Bundle, Security Bundle, Doctrine Bundle, Messenger, Mailer, Twig, Asset Mapper…)
- **Doctrine ORM** 3.x / **Doctrine Migrations**
- **PostgreSQL** (voir `compose.yaml`) — la base peut être adaptée à MySQL/MariaDB si besoin
- **Bootstrap 5.3** (CSS/JS via CDN)
- **Stimulus / Turbo** (Symfony UX)
- **PHPUnit** 13 pour les tests
- **Symfony Docker Compose** pour l'environnement de développement (base de données + Mailpit)

## Prérequis

- PHP 8.4.15 ou supérieur, avec les extensions `ctype` et `iconv`
- [Composer](https://getcomposer.org/)
- [Symfony CLI](https://symfony.com/download) (recommandé)
- Docker et Docker Compose (pour la base de données et Mailpit)
- Node.js (facultatif, uniquement si vous ajoutez des dépendances JS via Asset Mapper/npm)

## Installation

1. **Cloner le dépôt**

   ```bash
   git clone <url-du-depot> sortir
   cd sortir
   ```

2. **Installer les dépendances PHP**

   ```bash
   composer install
   ```

3. **Configurer les variables d'environnement**

   Copier le fichier `.env` en `.env.local` et adapter les valeurs si besoin (notamment `DATABASE_URL`, `MAILER_DSN`, `APP_SECRET`).

   ```bash
   cp .env .env.local
   ```

4. **Démarrer les services Docker** (base de données PostgreSQL + Mailpit pour les emails)

   ```bash
   docker compose up -d
   ```

## Base de données

1. **Créer la base de données**

   ```bash
   php bin/console doctrine:database:create --if-not-exists
   ```

2. **Exécuter les migrations**

   ```bash
   php bin/console doctrine:migrations:migrate
   ```

3. **Charger les fixtures** (données de démonstration : campus, villes, lieux, participants, sorties)

   ```bash
   php bin/console doctrine:fixtures:load
   ```

## Lancer le projet

Avec le Symfony CLI :

```bash
symfony server:start
```

Ou avec le serveur PHP intégré :

```bash
php -S 127.0.0.1:8000 -t public
```

L'application est ensuite accessible sur `https://localhost:8000` (ou `http://127.0.0.1:8000`).

Pour compiler/installer les assets gérés par AssetMapper :

```bash
php bin/console importmap:install
php bin/console asset-map:compile
```

## Comptes de démonstration

Les fixtures créent plusieurs participants avec le mot de passe **`password123`**, par exemple :

| Email                  | Pseudo    | Rôle          |
|-------------------------|-----------|---------------|
| jeannine@sortir.com      | Jeannine L.|Administrateur|
| remi@sortir.com       | MarcB     | Utilisateur |

Consultez `src/DataFixtures/AppFixtures.php` pour la liste complète.


## Structure du projet

```
config/            Configuration Symfony (packages, routes, sécurité...)
migrations/         Migrations Doctrine
public/             Point d'entrée web, assets publics, uploads
src/
 ├── Controller/     Contrôleurs (Main, Sortie, Profile, Admin, Api...)
 ├── Entity/         Entités Doctrine (Sortie, Participant, Campus, Lieu, Ville, Etat)
 ├── Form/           Formulaires Symfony (SortieType, ProfileType, CampusType...)
 ├── Repository/      Repositories Doctrine
 ├── Security/        UserChecker (blocage des comptes désactivés)
 ├── Service/         SortieStateManager (gestion automatique des états)
 └── DataFixtures/    Fixtures de démonstration
templates/           Vues Twig (base, sortie, profil, admin, sécurité)
assets/              JavaScript / CSS (AssetMapper, Stimulus)
tests/               Tests PHPUnit
```

## Cycle de vie d'une sortie

Les états d'une sortie (`Etat`) évoluent automatiquement selon la date et le nombre d'inscrits, via `App\Service\SortieStateManager` :

1. **En création** — brouillon, visible uniquement par l'organisateur, modifiable/supprimable librement.
2. **Ouverte** — publiée, inscriptions possibles.
3. **Clôturée** — date limite d'inscription dépassée ou nombre de places atteint.
4. **En cours** — la sortie a démarré.
5. **Terminée** — la sortie est achevée (date de fin dépassée).
6. **Annulée** — annulée par l'organisateur avant son démarrage (avec motif obligatoire).
7. **Historisée** — archivée un mois après la fin (ou l'annulation) de la sortie.

---

Projet réalisé dans le cadre d'une formation ENI (application Symfony "Sortir.com").
