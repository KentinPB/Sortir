# Sortir.com

Application web développée avec **Symfony 7.4** permettant à des étudiants/participants d'un même organisme (répartis sur plusieurs campus) de créer, consulter et s'inscrire à des sorties (activités, sorties culturelles, sportives, etc.).

## Sommaire

- [Fonctionnalités](#fonctionnalités)
- [Stack technique](#stack-technique)
- [Prérequis](#prérequis)
- [Installation](#installation)
- [Base de données](#base-de-données)
- [Lancer le projet](#lancer-le-projet)
- [Tests](#tests)
- [Comptes de démonstration](#comptes-de-démonstration)
- [Sécurité](#sécurité)
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
- **MySQL / MariaDB** avec le moteur **InnoDB** (les migrations sont écrites pour ce SGBD)
- **Bootstrap 5.3** (CSS/JS via CDN)
- **Turbo** (Symfony UX) et JavaScript natif (filtrage des lieux par ville)
- **PHPUnit** 13 pour les tests

> **Pourquoi InnoDB ?** Les premières tables avaient été créées avec le moteur MyISAM, qui ignore les clés étrangères et les transactions : les contraintes de relation ne s'appliquaient pas et certaines migrations échouaient. Une migration convertit les tables en InnoDB, et `config/packages/doctrine.yaml` impose désormais InnoDB et l'encodage `utf8mb4` pour toutes les nouvelles tables.

## Prérequis

- PHP 8.4.15 ou supérieur, avec les extensions `ctype`, `iconv`, `intl` et `pdo_mysql`
- [Composer](https://getcomposer.org/)
- Un serveur **MySQL 8 ou MariaDB 10.11** local (Laragon, Wamp, XAMPP…) — ou Docker (facultatif, voir ci-dessous)
- [Symfony CLI](https://symfony.com/download) (facultatif)

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

   Le fichier `.env` (versionné) contient des valeurs par défaut **sans secret**. Créer un fichier `.env.local` (non versionné) pour vos valeurs, par exemple :

   ```dotenv
   DATABASE_URL="mysql://utilisateur:motdepasse@127.0.0.1:3306/sortir?serverVersion=10.11.2-MariaDB&charset=utf8mb4"
   MAILER_DSN=smtp://127.0.0.1:1025
   ```

   En production, définir aussi `APP_ENV=prod` et un `APP_SECRET` propre dans `.env.local`.

4. **(Facultatif) Docker** : `compose.yaml` fournit une base MariaDB et Mailpit.

   ```bash
   docker compose up -d
   ```

   Avec Docker, utiliser `DATABASE_URL="mysql://root:root@127.0.0.1:3306/sortir?serverVersion=10.11.2-MariaDB&charset=utf8mb4"`.

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

## Tests

```bash
php bin/phpunit
```

Les tests unitaires de `tests/Service/SortieStateManagerTest.php` vérifient le calcul automatique des états (voir [Cycle de vie d'une sortie](#cycle-de-vie-dune-sortie)). L'heure est fournie par une horloge figée (`MockClock` du composant `symfony/clock`) : les résultats ne dépendent pas du jour où les tests sont lancés, et aucune base de données n'est nécessaire.

## Comptes de démonstration

Les fixtures créent plusieurs participants avec le mot de passe **`password123`**, par exemple :

| Email               | Pseudo      | Rôle           |
|---------------------|-------------|----------------|
| jeannine@sortir.com | Jeannine L. | Administrateur |
| marc@sortir.com     | MarcB       | Administrateur |
| remi@sortir.com     | Rémi S.     | Utilisateur    |

Consultez `src/DataFixtures/AppFixtures.php` pour la liste complète.

> `password123` est un mot de passe de démonstration créé directement par les fixtures. Il ne respecte pas les règles appliquées par le formulaire de profil (voir [Sécurité](#sécurité)) : un nouveau mot de passe saisi par un utilisateur devra être plus robuste.

## Sécurité

- **Jetons CSRF** sur toutes les actions qui modifient des données (inscription, désistement, suppression, formulaires) : un autre site ne peut pas déclencher ces actions à l'insu d'un utilisateur connecté.
- **Contrôles côté serveur** : l'état de la sortie et les places restantes sont revérifiés au moment de l'inscription.
- **Mots de passe** hachés par Symfony ; au moins 8 caractères et robustesse minimale (`PasswordStrength`) lors d'un changement.
- **Unicité** de l'e-mail et du pseudo garantie à la fois par le formulaire (`UniqueEntity`) et par un index unique en base.
- **Comptes désactivés** bloqués à la connexion (`UserChecker`).
- **Photos de profil** : type et taille contrôlés (JPEG/PNG, 2 Mo), nom de fichier généré par l'application, ancienne photo supprimée.
- **Dépendances** vérifiées avec `composer audit`.

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
