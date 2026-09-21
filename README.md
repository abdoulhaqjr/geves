# GESVES

**Plateforme de Gestion des ÉVénements Scientifiques — Université de Kara**

GESVES est l'application web de la **Direction de la Recherche et de l'Innovation (DRI)** de l'Université de Kara pour piloter tout le cycle de vie des manifestations scientifiques — **colloques, séminaires et journées scientifiques** : appel à communications, soumission et évaluation des résumés, programme et déroulement en direct, inscriptions et paiements, documents officiels (attestations, badges, livret des résumés) et archives.

Un espace unique pour les **organisateurs**, les **comités**, le **secrétariat** et la **communauté universitaire**.

---

## ✨ Fonctionnalités principales

- **Multi-événements par édition** — chaque manifestation (colloque, séminaire, journée) est une *édition* avec ses propres réglages, axes, échéances et équipe.
- **Espace participant** — profil, sélection du colloque courant (et des appels ouverts), soumission de résumés (wizard guidé, quota par édition), suivi des communications, paiements, documents, espaces de fonction (modérateur / rapporteur / relecteur).
- **Soumission & évaluation** — dépôt de résumés multi-étapes, assignation de relecteurs, grilles d'évaluation, décisions du comité scientifique.
- **Programme & déroulement** — ateliers, sessions, créneaux, salles, visioconférence, annonces en direct, rapports de séance.
- **Back-office avec RBAC par édition** — rôles fins (administrateur, gestionnaire, secrétariat, comité scientifique/supervision, comptabilité, appui…) avec capacités cadrées par édition.
- **Paiements** — suivi des frais d'inscription, relances, passerelles en ligne.
- **Documents** — attestations, badges, livret des résumés, fiches de séance et PV configurables par édition.
- **Assistants IA** — assistante publique (participants) et **copilote d'administration** capable, après **autorisation explicite**, d'exécuter certaines actions (relances, envois programmés…), avec résilience multi-fournisseurs (relance transparente, routage de modèle, bascule de fournisseur).
- **Réclamations & incidents** — enregistrement via le chatbot et traitement assisté par IA côté back-office.

## 🧱 Stack technique

- **Backend** : Laravel (PHP **8.3+**)
- **Frontend** : Blade · **Alpine.js 3** · **Vite 8** · **Tailwind CSS 4**
- **Composants** : Tom Select, intl-tel-input, FullCalendar, Chart.js, LiveKit (visio)
- **Tests** : **Pest** (SQLite en mémoire) · **PHPStan / Larastan** (niveau 5)
- **Environnement de dev** : [Laravel Herd](https://herd.laravel.com/) (Windows)

## ✅ Prérequis

- PHP **8.3+** et Composer (fournis par Herd)
- Node.js **18+** et npm
- Un serveur de base de données (SQLite par défaut en dev ; MySQL/PostgreSQL en production)

## 🚀 Installation

```bash
# 1. Cloner
git clone https://github.com/abdoulhaqjr/geves.git
cd geves

# 2. Dépendances PHP et JS
composer install
npm install

# 3. Environnement
cp .env.example .env
php artisan key:generate

# 4. Base de données (SQLite en dev)
php artisan migrate --seed

# 5. Build des assets
npm run build
```

> **Herd (Windows)** : PHP/Composer/artisan ne sont pas toujours dans le PATH.
> Ajouter `export PATH="$HOME/.config/herd/bin:$PATH"` puis utiliser `php.bat` / `composer.bat`.
> L'application est servie sur le domaine local `*.test` (ex. `https://gesves.test`).

## 🛠️ Développement

```bash
npm run build          # compiler les assets (recommandé sur *.test en dev)
npm run dev            # serveur Vite (HMR)
php artisan test       # ou : vendor/bin/pest
vendor/bin/phpstan analyse   # analyse statique (Larastan niveau 5)
php artisan queue:work --stop-when-empty   # livrer e-mails/notifications en file
```

> ⚙️ Une partie de la configuration (mail, paiements, IA, visio, SMS, URL du site)
> est pilotée depuis les **Réglages en base** (back-office), pas uniquement le `.env`.

## 📁 Aperçu de l'arborescence

```
app/            Contrôleurs, modèles, actions, IA (App\AI), support métier
resources/      Vues Blade, JS (Alpine), CSS (charte ukc)
routes/         Routes web (public, espace participant, back-office admin)
database/       Migrations et seeders (données de démo)
tests/          Suite Pest (Feature)
```

## 📄 Licence & mentions

Application interne à l'**Université de Kara** (Direction de la Recherche et de l'Innovation). Usage institutionnel, non commercial.
