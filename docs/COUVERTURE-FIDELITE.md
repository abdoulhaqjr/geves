# Couverture fidélité — maquettes → routes

> Passe de vérification « fidélité au mockup ». Chaque maquette du dossier de
> référence `~/Downloads/gesco-mockup/*.html` est portée sur une route Laravel
> implémentée, avec sa vue Blade et son niveau de test.
>
> **31 maquettes · 31 routes portées · aucune page manquante.**
> Dernière vérification : 2026-08-28 — 357 tests verts, 1175 assertions, Pint conforme.

## Niveaux de test

| Étiquette | Signification |
|-----------|---------------|
| **Smoke 200** | Route parcourue par `tests/Feature/SmokeTest.php`, rendu `200` asserté. |
| **Test métier** | Couverte par un test fonctionnel dédié (paramètres d'URL, formulaire ou flux). |

---

## Site public — 10 pages · layout `app`

Accessibles sans connexion. Le widget flottant Gesco est monté globalement via le
layout `app` (`resources/views/components/layouts/app.blade.php`), sous
`@unless(admin.*|reviewer.*|session.meeting|public.gesco)` — donc présent en public
et dans l'espace participant, exclu de l'admin, du portail relecteur, de la salle
visio et de la page chat dédiée.

| Maquette | Route | Vue Blade | Test |
|----------|-------|-----------|------|
| `index.html` | `home` | `home` | Smoke 200 |
| `page-colloque-amelioree.html` | `public.colloque` | `public.colloque` | Smoke 200 |
| `colloques-passes.html` | `public.archives` | `public.archives` | Smoke 200 |
| `colloque-passe-detail.html` | `public.archive` | `public.archive` | Test métier |
| `colloque-deroulement.html` | `public.deroulement` | `public.deroulement` | Smoke 200 |
| `colloque-visio.html` | `session.meeting` | `meeting.join` | Test métier |
| `chat-agent.html` | `public.gesco` | `public.gesco` (+ widget `ukc.gesco-widget`) | Smoke 200 |
| `connexion.html` | `account.login` | `account.auth.login` | Test métier |
| `inscription.html` | `account.register` | `account.auth.register` | Test métier |
| `attestation-verify.html` | `attestation.verify.landing` | `attestations.verify-landing` | Smoke 200 |

## Espace participant — 4 pages · layout `participant`

Réservées au compte connecté (garde `account`).

| Maquette | Route | Vue Blade | Test |
|----------|-------|-----------|------|
| `espace-participant.html` | `account.dashboard` | `account.dashboard` | Smoke 200 |
| `compte.html` | `account.profile` · `account.security` | `account.profile` · `account.security` | Smoke 200 |
| `soumission-formulaire.html` | `account.submissions.create` | `account.submissions.create` | Test métier |
| `soumission-detail.html` | `account.submissions.show` | `account.submissions.show` | Test métier |

## Back-office — 17 pages · layout `admin`

Réservées au staff habilité (permission `manage_colloques`). Toutes vérifiées en
rendu `200` par la suite de fumée.

| Maquette | Route | Vue Blade | Test |
|----------|-------|-----------|------|
| `admin-dashboard.html` | `admin.dashboard` | `admin.dashboard` | Smoke 200 |
| `admin-submissions.html` | `admin.submissions.index` | `admin.submissions.index` | Smoke 200 |
| `admin-submission-detail.html` | `admin.submissions.show` | `admin.submissions.show` | Smoke 200 |
| `admin-submission-forms.html` | `admin.editions.form` | `admin.editions.form` | Smoke 200 |
| `admin-editions.html` | `admin.editions.index` | `admin.editions.index` | Smoke 200 |
| `admin-edition-detail.html` | `admin.editions.edit` | `admin.editions.edit` | Smoke 200 |
| `admin-creneaux.html` | `admin.program.slots` | `admin.program.slots` | Smoke 200 |
| `admin-payments.html` | `admin.payments.index` | `admin.payments.index` | Smoke 200 |
| `admin-reports.html` | `admin.reports.index` | `admin.reports.index` | Smoke 200 |
| `admin-emails.html` | `admin.emails.index` | `admin.emails.index` | Smoke 200 |
| `admin-documents.html` | `admin.documents.attestations` | `admin.documents.attestations` | Smoke 200 |
| `admin-attestation-templates.html` | `admin.documents.templates` | `admin.documents.template-designer` | Smoke 200 |
| `admin-badge-templates.html` | `admin.documents.templates?kind=badge` | `admin.documents.badge-designer` | Smoke 200 |
| `admin-ai-assistant.html` | `admin.ai` | `admin.ai` | Smoke 200 |
| `admin-users.html` | `admin.users.index` | `admin.users.index` | Smoke 200 |
| `admin-settings.html` | `admin.settings` | `admin.settings` | Smoke 200 |
| `badge-scan.html` | `admin.badges.scan` | `admin.badges.scan` | Smoke 200 |

---

## Suite de fumée

`tests/Feature/SmokeTest.php` (5 tests, 42 assertions) parcourt chaque famille de
routes et exige un rendu `200` — verrou de non-régression contre les 500 introduits
par un changement de vue ou de contrôleur.

- **Public** (invité) : accueil, colloque, archives, déroulement, Gesco, vérification d'attestation.
- **Back-office** (staff `manage_colloques`) : tableau de bord, soumissions + export, paiements + export + saisie, documents, éditeur de modèles, éditions, e-mails + templates, rapports, utilisateurs, programme, réglages, IA, participants, scan de badges.
- **Documents générés** (staff) : livret PDF **et** DOCX, badges, sessions, rapport XLSX/PDF, édition (édition + form-builder).
- **Espace participant** (compte) : tableau de bord, communications, documents, profil, sécurité.
- **Détail** : fiche soumission, éditeur de modèle attestation + badge.

La suite a débusqué un vrai 500 latent : `Admin\UserController::index` appelait le
scope `User::role('administrator')` de spatie, qui lève `RoleDoesNotExist` quand le
rôle n'existe pas encore en base (installation fraîche). Corrigé en
`User::whereHas('roles', fn ($q) => $q->where('name', $r))->count()` (renvoie `0`
sans lever d'exception).

Les fabriques de test partagées (`openEdition`, `verifiedAccount`, `adminStaff`,
`seededSubmission`) sont centralisées dans `tests/Pest.php` — globales quel que soit
l'ordre de chargement des fichiers, donc la suite est exécutable en isolation.
