# Passation — GESCO · Gestion des colloques scientifiques

> Document de passation pour la personne qui reprend le développement.
> Dernière mise à jour : 2026-08-10.

Ce fichier résume **l'état du projet**, la **mise en route**, l'**architecture**, les **conventions**, le **travail réalisé**, ce qui **reste à faire** et les **pièges** à connaître.

---

## 1. Vue d'ensemble

**GESCO** est le portage **Laravel** d'une extension WordPress existante (`uk-colloques`) qui gère un colloque scientifique de bout en bout pour l'**Université de Kara** : séries de colloques → éditions datées → soumissions de résumés → relecture en aveugle → paiements → créneaux/ateliers → livret/badges/attestations → e-mails → rapports → comptes participants.

- **But** : plateforme professionnelle, autonome (sans WordPress).
- **Stack** : Laravel 13 (PHP 8.4), **Blade + Livewire 4 + Alpine**, Tailwind v4 (+ design system maison), SQLite en dev, Pest pour les tests.
- **Périmètre couvert à ce jour** : fondations, comptes participants, back-office éditions, page publique, **soumission**, **relecture**, **resoumission**, **paiement** (voir §6).

### Sources de référence (hors dépôt, dans `Downloads/`)
- `Site Web/uk-colloques/` — **plugin WordPress source** (27 modules, ~19 600 lignes). Référence fonctionnelle.
  - `uk-colloques/README.md` — modules 1 → 27 (spécifications fonctionnelles).
  - `uk-colloques/inc/install.php` — schéma SQL d'origine (`wp_ukc_*`).
  - `Site Web/Documentation-Projet/PASSATION.md` — passation du plugin WP (contexte métier, MoneyFusion, FedaPay, HAL…).
- `gesco-mockup/` — maquettes HTML/CSS portant la charte (espace-participant, admin-documents, badges, attestations…).
- `Herd/bizzcart.com/` — autre app Laravel dont on **réutilise des patterns** (couche `app/Services`, passerelles de paiement `Services/FinanceAI/Gateways`, spatie/permission, dompdf).

---

## 2. Environnement & mise en route

### Poste local (Windows + Laravel Herd)
⚠️ **PHP, Composer et artisan ne sont PAS dans le PATH** (ni bash ni PowerShell). Ils sont fournis par **Laravel Herd** dans `~/.config/herd/bin` (= `C:\Users\TOURE\.config\herd\bin`), sous forme de shims `.bat`.

Pour toute commande (Git Bash) :
```bash
export PATH="$HOME/.config/herd/bin:$PATH"
cd "C:/Users/TOURE/Herd/gesco"
php.bat artisan ...        # PHP 8.4
composer.bat ...           # Composer 2.10
```
Node/npm, eux, sont dans le PATH (node v24, npm 12).

- Le site est servi par **Herd** sur **http://gesco.test** (HTTP 200).
- Base de données : **SQLite** (`database/database.sqlite`, ignorée par git). Migrations pensées **portables MySQL** pour la prod.

### Installation à froid
```bash
export PATH="$HOME/.config/herd/bin:$PATH"
cd "C:/Users/TOURE/Herd/gesco"
composer.bat install
cp .env.example .env        # puis renseigner
php.bat artisan key:generate
php.bat artisan migrate --seed   # crée le schéma + rôles + admin + colloque de démo
php.bat artisan storage:link     # fichiers de soumission (disk public)
npm install && npm run build
```

### Comptes & données de démo (seeders)
- **Back-office** : http://gesco.test/admin/connexion → `admin@univkara.tg` / `password` (à changer en prod).
- **Page publique de démo** : http://gesco.test/colloque/edition-2026.
- Seeders : `RolesAndAdminSeeder` (rôles `administrator`/`editor` + permission `manage_colloques` + admin), `DemoColloqueSeeder` (colloque + édition ouverte `edition-2026`).

### Lancer les tests
```bash
php.bat artisan test           # 42 tests, ~144 assertions (base :memory:)
php.bat artisan test --filter=PaymentTest
```
> ⚠️ Le lanceur affiche une sortie **JSON compacte** (plugin `laravel/pao`) — pas la trace complète. Pour déboguer une exception dans un test, capter l'exception dans le test (try/catch + `fwrite(STDERR, …)`), ou utiliser `->assertOk()`/dumps ciblés. `vendor/bin/phpunit` **ne fonctionne pas** (Pest requis).

### Vérification visuelle
Le **navigateur intégré exige une approbation par action** sur `gesco.test`. Se rabattre sur `curl` (statut HTTP + `grep` du contenu attendu + absence d'erreur) comme méthode de contrôle rapide.

---

## 3. Architecture — points de repère

```
app/
├─ Livewire/
│  ├─ Account/           # Front participant (guard « account »)
│  │  ├─ Login, Register, ForgotPassword, ResetPassword, VerifyEmailNotice, Profile, Dashboard
│  │  ├─ Submissions/    # Create, Index (« Mes communications »), Show (fiche + resoumission)
│  │  └─ Payment/Pay     # Paiement des frais
│  ├─ Admin/             # Back-office (guard « web » + permission manage_colloques)
│  │  ├─ Login, Dashboard
│  │  ├─ Editions/       # Index, Form (CRUD éditions)
│  │  ├─ Submissions/    # Index, Show (workflow + relecteurs)
│  │  └─ Payments/       # Index, Record (encaissement manuel)
│  ├─ Reviewer/          # Portail relecteur par jeton : Portal, Evaluate
│  └─ Public/ColloquePage
├─ Actions/              # Logique métier réutilisable (voir §5)
│  ├─ UpsertParticipantForAccount, NotifySubmissionStatus, AssignReviewer, ConfirmPayment
├─ Mail/                 # SubmissionStatusMail, ReviewerInvitationMail, PaymentReceiptMail
├─ Payments/             # Abstraction passerelles (interface + registre + gateways)
│  ├─ Contracts/PaymentGateway
│  ├─ GatewayRegistry
│  └─ Gateways/          # ManualGateway (actif), MoneyFusionGateway, FedaPayGateway (échafaudés)
├─ Support/Fees          # Résolution des frais depuis settings.fees
├─ Models/               # 14 modèles Eloquent + User
└─ Http/Controllers/     # Account/ (verify, logout), Admin/ (logout, ReceiptController)

resources/
├─ css/ukc.css           # ★ Design system (tokens --ukc-*, composants .ukc-*)
├─ views/
│  ├─ components/
│  │  ├─ ukc/icon.blade.php          # ★ Icônes SVG <x-ukc.icon name=…>
│  │  └─ layouts/                    # app, auth, participant, admin
│  ├─ livewire/…                     # vues des composants (miroir de app/Livewire)
│  ├─ emails/…                       # gabarits markdown des Mailables
│  └─ receipts/payment.blade.php     # reçu imprimable

routes/web.php           # toutes les routes (front, admin, relecteur, public)
config/payments.php      # passerelle par défaut
database/migrations/     # 14 tables métier + permissions spatie
database/seeders/        # RolesAndAdminSeeder, DemoColloqueSeeder
```

### Modèle de données (14 tables)
`colloques`, `editions`, `participants`, `submissions`, `reviews`, `payments`, `slots`, **`event_sessions`** (⚠️ pas `sessions`, réservé par Laravel), `ateliers`, `attestations`, `emails`, **`committee_members`**, `media`, `accounts` (+ `account_password_reset_tokens`). Champs libres en JSON : `editions.settings`, `participants.meta`, `submissions.authors/meta`, `payments.meta`, `ateliers.tracks`.

### Authentification — multi-guard
- **`web`** : personnel du back-office (table `users`), permissions via **spatie/laravel-permission** (rôles `administrator`/`editor`, permission `manage_colloques`). Routes admin protégées par `auth:web` + `can:manage_colloques`.
- **`account`** : comptes participants (table `accounts`, auth propre, e-mail vérifié, reset dédié). `Account::canSubmit()` = e-mail vérifié **et** affiliation renseignée.
- **Relecteurs** : pas de compte — accès par **jeton** (`participants.access_token`, rôle `reviewer`).
- Redirection des invités **selon la zone** dans `bootstrap/app.php` (`/admin` → `admin.login`, sinon `account.login`).

---

## 4. Conventions de design (à respecter)

- **Charte Université de Kara** : bleu `#223ea4` (foncé `#1b3182`), or `#ffd700`, vert `#009933`, police **Jost** (auto-hébergée via le plugin bunny dans `vite.config.js`).
- **Tokens & composants** : source unique dans `resources/css/ukc.css` (`--ukc-*`, `.ukc-btn`/`--primary|green|gold|outline|ghost|danger|sm`, `.ukc-card`, `.ukc-pill--ok|warn|info|muted|err`, `.ukc-table`, `.ukc-field`, `.ukc-steps`, `.ukc-empty`, shell `.ukc-shell/.ukc-sidebar/.ukc-topbar`). Préférer ces classes aux littéraux.
- **Icônes = SVG uniquement** via `<x-ukc.icon name="…" :size="18" />` (jeu Feather dans `resources/views/components/ukc/icon.blade.php`). **Jamais d'emoji dans l'UI.** Exception assumée : les **e-mails** peuvent garder des emojis.
- **Layouts** : `x-layouts.app` (squelette + Livewire), `auth` (carte centrée), `participant` et `admin` (shell sidebar auto-suffisant). Les composants full-page passent le titre via `->layout('components.layouts.x', ['title' => …])`.
- **Sécurité systématique** : validation Livewire (`rules()`/`#[Validate]`), garde d'autorisation dans `mount()` (`abort_unless`), permissions spatie côté admin, jetons aléatoires (`Str::random(64)`), gardes métier dans les Actions.

---

## 5. Logique métier réutilisable (Actions & Support)

- **`UpsertParticipantForAccount`** — crée/retrouve le participant d'un compte sur une édition. **Identité imposée par le compte** (nom infalsifiable), **rôle fort non rétrogradé** (`reviewer`/`committee`/`moderator`/`rapporteur`), `access_token` généré.
- **`NotifySubmissionStatus`** — envoie `SubmissionStatusMail` à l'auteur correspondant + **journalise** dans `emails`. Utilisé pour l'accusé de réception et chaque décision.
- **`AssignReviewer`** — crée/retrouve un participant relecteur (jeton de portail) + `Review`, passe la soumission en `under_review`.
- **`ConfirmPayment`** — passe un paiement à `paid` avec **garde anti-double-paiement** (un seul `paid` par participant/édition), génère `receipt_no` (`REC-{année}-{seq}`), envoie `PaymentReceiptMail`.
- **`Support\Fees`** — `resolveAmount(edition, grade)` (insensible casse), `highestAmount(edition)`, `grid(edition)` depuis `settings.fees`.
- **Passerelles** — `GatewayRegistry` résout les passerelles **disponibles** (`isAvailable()` = identifiants configurés) et la **par défaut** (`config('payments.default')`, repli `manual`). `ManualGateway` est fonctionnel ; `MoneyFusionGateway`/`FedaPayGateway` sont **échafaudées** (voir §7).

---

## 6. Travail réalisé (modules)

Tout est **couvert par des tests Pest** (42 tests, 144 assertions au 2026-08-10). Correspondance avec les modules WP entre parenthèses.

1. **Fondations** — stack Livewire, design system, 14 migrations + modèles, comptes participants (inscription/connexion/reset/vérif e-mail/profil). *(commit `54263ae`)*
2. **Back-office éditions + page publique** — CRUD éditions (statuts, dates, soumissions, axes, grille tarifaire, slug auto), page `/colloque/{slug}` visible si publiée. Auth staff + spatie/permission. *(commit `54263ae`)*
3. **Soumission liée au compte** (Modules 2, 17, 25) — formulaire `/colloque/{slug}/soumettre` (bandeau compte, gating `canSubmit`, axes/type/mots-clés/résumé/co-auteurs/fichier), garde-fou nb max de communications, `ref_code` + `access_token`, accusé de réception. Liste « Mes communications ». *(commit `cfbcf4c`)*
4. **Workflow admin des soumissions** (Module 2) — liste filtrable, fiche détaillée, actions **en relecture / correction / accepter / refuser** avec message + e-mail à l'auteur (méthode `applyStatus`, ⚠️ `transition` réservé Livewire). *(commit `f83324b`)*
5. **Resoumission / versionnage** (Module 2) — fiche participant `/mes-communications/{submission}` : retour relecteurs (après décision) + resoumission quand `revision`. Une ligne **canonique** (`revision_of=0`) + **archives** (`revision_of=id`), `version++`. *(commit `f83324b`)*
6. **Relecture / peer review** (Module 3) — assignation depuis la fiche admin (panneau Relecteurs + synthèse note moyenne/recommandations), **portail relecteur en aveugle par jeton** `/relecture/{token}` + évaluation `/relecture/{token}/{review}` (recommandation, note /20, commentaires auteur + confidentiels), invitation par e-mail. *(commit `f83324b`)*
7. **Paiement** (Modules 4, 12, 17, 18) — encaissement **manuel** back-office (liste + totaux + reçu imprimable + e-mail + marquer réglé/rembourser), parcours **participant** (`/mon-compte/paiement/{edition}`, éligible si communication acceptée, montant du grade), **abstraction passerelle** (manuel actif ; MoneyFusion/FedaPay échafaudées). Garde anti-double-paiement partout. *(commit `f83324b`)*

**État git** : `54263ae` (fondations + éditions + page publique), `cfbcf4c` (soumission liée au compte), `f83324b` (workflow soumissions + resoumission + relecture + paiement). Arbre propre.

---

## 7. Ce qui reste à faire

### Priorité haute
1. **Passerelles de paiement en ligne** — implémenter l'appel API réel dans `MoneyFusionGateway`/`FedaPayGateway` (méthodes `initiate()` + vérification au retour + **webhook**). S'active dès que les identifiants sont dans `config/services.php` (env `MONEYFUSION_API_URL`, `FEDAPAY_SECRET`). **Pas de sandbox public** → tester par un vrai paiement de petit montant. S'inspirer de `bizzcart.com` (`app/Services/FinanceAI/Gateways`) et de la passation WP.
2. **Créneaux & ateliers/sessions** (Modules 7, 26) — proposer/assigner des créneaux aux communications acceptées, choix par le participant, sessions plénières/d'atelier, fiches de session. Tables `slots`, `event_sessions`, `ateliers` déjà prêtes.
3. **Documents** (Modules 6, 8, 16, 19) — livret des résumés (.docx), badges (planche A4), **attestations vérifiables par QR** (page publique de vérif via `attestations.uuid`). `barryvdh/laravel-dompdf` (utilisé par bizzcart) est un bon candidat pour les PDF.

### Ensuite
4. **Back-office Participants** (Module 5) — liste par édition, rôles dérivés, invitations aux payés, « Sa page » (impersonation lecture seule).
5. **E-mails** (Modules 7, 11) — SMTP réel (Réglages), e-mails programmés (rappels à une audience via une commande planifiée).
6. **Rapports** (Module 9) — export Excel (.xlsx) multi-feuilles + PDF. (bizzcart n'a pas d'export xlsx natif ; envisager `maatwebsite/excel` ou un générateur maison.)
7. **Page publique enrichie** — programme, comités, partenaires, FAQ, contact, hero avec compte à rebours (Modules 15, 20, 21) — d'après `gesco-mockup/` et le plugin WP.
8. **Espace participant enrichi** — d'après `gesco-mockup/espace-participant.html` (communications, paiements, documents, attestation, stepper).
9. **Réglages** back-office (SMTP, passerelle par défaut, certifier/attestations) au lieu de tout en `.env`.

---

## 8. Pièges & bonnes pratiques (à lire avant de coder)

- **Toolchain Herd** : toujours `export PATH="$HOME/.config/herd/bin:$PATH"` puis `php.bat`/`composer.bat` (§2).
- **Livewire 4 — méthodes réservées** : ne pas nommer une action `transition` (collision avec `Livewire\Component::transition`). Utiliser un autre nom (ex. `applyStatus`).
- **Livewire 4 — « Invalid snapshot structure » en test** = une exception/`abort()` **avalée pendant le render initial** (le snapshot devient NULL, l'erreur ne remonte qu'au 1ᵉʳ `->set()`/`->call()`). Chercher un `abort_unless` faux dans `mount()`/`render()`, pas le trait `WithFileUploads`. Astuce diagnostic : dumper `$c->html()` et vérifier la présence de `wire:id`.
- **Modèle fraîchement créé ≠ rechargé** : `Model::create([...])` **ne reflète pas les valeurs par défaut SQL** en mémoire (ex. `revision_of` reste `null`, pas `0`). Une comparaison stricte `=== 0` casse alors. → **caster** (`'revision_of' => 'integer'`) et comparer en `(int)`, ou passer `->fresh()`. (Incident réel corrigé sur la resoumission.)
- **Composants Livewire = classe** (`--class`), pas les single-file components de Livewire 4 (préfixe emoji `⚡` peu maniable pour l'outillage). Générer avec `php.bat artisan make:livewire Chemin/Nom --class`.
- **Nouvelle classe/action métier** : privilégier une **Action** dans `app/Actions` (réutilisable + testable) plutôt que de la logique dans le composant.
- **Nouvelle table « à couplage lâche »** : suivre le style des migrations existantes — FK `constrained` sur les parents forts (colloque, édition), simple index pour les références optionnelles.
- **E-mails** : Mailables markdown (`x-mail::message`), templates dans `resources/views/emails`. En dev `MAIL_MAILER=log` → les e-mails partent dans `storage/logs/laravel.log`. Toujours journaliser côté métier quand c'est pertinent (table `emails`).
- **Reçus / documents imprimables** : vue Blade + `window.print()` (règle `@media print`), pas de dépendance obligatoire. Passer à dompdf si un vrai PDF serveur est requis.
- **Tests** : `RefreshDatabase` est activé globalement (`tests/Pest.php`), base `:memory:`. Des **helpers globaux** sont partagés entre fichiers de test (`openEdition()`, `verifiedAccount()` dans `AccountSubmissionTest`; `adminStaff()`, `seededSubmission()` dans `AdminSubmissionWorkflowTest`; `payEdition()` dans `PaymentTest`). Réutiliser plutôt que redéclarer (sinon « cannot redeclare »).

---

## 9. Déploiement (à définir)

Pas encore mis en place. Pistes : cible **MySQL** en prod (migrations déjà portables), `php artisan migrate --force`, `npm run build`, `php artisan storage:link`, file de queue pour les e-mails, planificateur pour les rappels. Prévoir un vrai driver mail (SMTP/Postmark/Resend — `config/services.php` déjà prêt).

---

## 10. Ressources

- `PASSATION.md` (ce fichier) — état et reprise du projet Laravel.
- `README.md` — squelette Laravel par défaut (à enrichir).
- Sources externes : `Downloads/Site Web/uk-colloques/README.md` (modules 1→27), `Downloads/Site Web/Documentation-Projet/PASSATION.md` (métier), `Downloads/gesco-mockup/` (maquettes), `Herd/bizzcart.com/` (patterns Laravel réutilisables).

---

*Fil conducteur : cohérence de la charte (tokens + icônes SVG), sécurité (validation/autorisation/gardes métier dans les Actions), et **tests systématiques** (chaque module a ses tests Pest). Déployer souvent, par petits lots.*
