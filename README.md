<<<<<<< HEAD
# Performics University Management System

Plateforme universitaire Laravel pour gérer les étudiants, les enseignants, les cours,
les salles et les emplois du temps.

## Prérequis

- PHP 8.2 ou supérieur et les extensions PDO correspondant à votre base de données
- Composer
- Node.js et npm
- MySQL 8+ (ou SQLite pour le développement local)

## Installation

```sh
cp .env.example .env
composer install
npm install
php artisan key:generate
```

Configurez les paramètres `DB_*` dans `.env`, puis créez la base MySQL
`performics_university` avant d'exécuter les migrations :

```sh
php artisan migrate
npm run build
```

Si vous choisissez plutôt de réutiliser une base qui contient l'ancien schéma
et que vous confirmez vouloir effacer toutes ses tables, utilisez
`php artisan migrate:fresh --seed` après avoir vérifié le serveur et le nom de
base dans `.env`. Cette commande est destructive et ne doit pas être lancée sur
une base de production.

L'inscription publique crée uniquement un compte étudiant. Après avoir appliqué les
migrations, créez le premier compte Super Admin avec l'assistant interactif :

```sh
php artisan university:create-super-admin
```

La commande demande le prénom, le nom, l'adresse électronique, l'identifiant et le
mot de passe de manière interactive ; aucun mot de passe administrateur ne doit être
ajouté dans `.env`. Elle ne crée pas un nouveau compte si un Super Admin existe déjà.

Une fois connecté, le Super Admin ouvre **Compte → Invitations du personnel**,
saisit l'adresse de la personne et choisit son rôle. Il lui transmet le lien privé
affiché ; la personne complète son prénom, son nom et son mot de passe. Chaque lien
est valable 7 jours et ne peut servir qu'une fois. La création d'un compte enseignant
crée également son profil enseignant. N'ajoutez jamais de sélection de rôle privilégié
à l'inscription publique.

## Fonctionnalités en place

- Authentification Laravel, vérification d'adresse électronique et gestion de profil.
- Accès d'administration contrôlé par rôle ; les étudiants et enseignants ne peuvent
  pas accéder aux routes administratives.
- CRUD paginés des étudiants, enseignants, cours et salles, avec recherche,
  formulaires de création/modification, fiches et protections de suppression.
- Inscription directe des étudiants aux cours actifs de leur formation, uniquement
  lorsque leur dossier, leur classe et le semestre concerné sont actifs.
- Saisie, modification, suppression et consultation des notes sur 20 ; les enseignants
  ne peuvent noter que les étudiants inscrits à leurs cours affectés.
- Appel daté par séance (présent, absent, en retard ou absence justifiée) et historique
  de présence consultable individuellement par chaque étudiant.
- Affectation de plusieurs enseignants à un cours.
- Structure relationnelle facultés, départements, formations, niveaux, classes,
  groupes, années universitaires et semestres.
- Séances hebdomadaires, filtres de calendrier, impression et vérification des
  collisions d'enseignant, de salle et de groupe, ainsi que de la capacité.
- Journalisation des modifications des séances.

- CRUD de la structure académique, des examens et des événements, notifications,
  journal d'activité et génération automatique d'emplois du temps.
- Impression et enregistrement PDF des vues calendrier via la fonction d'impression
  du navigateur.

## Tests

```sh
php artisan test
```

La suite utilise une base SQLite en mémoire et ne modifie pas les données de
développement.
=======
# gestion_des_emploies_du_temps_universitaire
Une application qui permet de gérer et de faciliter  la gestion des emploies du temps 
>>>>>>> origin/main
