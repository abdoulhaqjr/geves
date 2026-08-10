# Passation — GESCO · Gestion des colloques scientifiques

> Document de passation pour la personne qui reprend le développement.
> Dernière mise à jour : 2026-08-10 (bizzcart : PDF, enums, sauvegardes, impersonation, observers/events).

Ce fichier résume **l'état du projet**, la **mise en route**, l'**architecture**, les **conventions**, le **travail réalisé**, ce qui **reste à faire** et les **pièges** à connaître.

---

## 1. Vue d'ensemble

**GESCO** est le portage **Laravel** d'une extension WordPress existante (`uk-colloques`) qui gère un colloque scientifique de bout en bout pour l'**Université de Kara** : séries de colloques → éditions datées → soumissions de résumés → relecture en aveugle → paiements → créneaux/ateliers → livret/badges/attestations → e-mails → rapports → comptes participants.

- **But** : plateforme professionnelle, autonome (sans WordPress).
- **Stack** : Laravel 13 (PHP 8.4), **Blade + Livewire 4 + Alpine**, Tailwind v4 (+ design system maison), SQLite en dev, Pest pour les tests.
- **Périmètre couvert à ce jour** : fondations, comptes participants, back-office éditions, page publique, **soumission**, **relecture**, **resoumission**, **paiement**, **ateliers/sessions/créneaux**, **participants**, **documents (livret/badges/attestations QR)**, **paiement en ligne (CashPay/FedaPay)** (voir §6).

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

### Lancer les tests & la qualité
```bash
php.bat artisan test                 # 176 tests (base :memory:)
php.bat artisan test --filter=PaymentTest
php.bat vendor/bin/phpstan analyse   # analyse statique (Larastan niveau 5)
php.bat vendor/bin/pint              # style (Laravel Pint)
composer.bat check                   # pint --test + phpstan + tests (= le CI)
```
> ⚠️ Le lanceur affiche une sortie **JSON compacte** (plugin `laravel/pao`) — pas la trace complète. Pour déboguer une exception dans un test, capter l'exception dans le test (try/catch + `fwrite(STDERR, …)`), ou utiliser `->assertOk()`/dumps ciblés. `vendor/bin/phpunit` **ne fonctionne pas** (Pest requis).

**Observers & Events (patterns bizzcart)** : *Observers* — invariants garantis à la création (sans écraser une valeur fournie) : `SubmissionObserver` (`ref_code` AA-NNN + `access_token`), `ParticipantObserver` (`access_token`), `AttestationObserver` (`uuid`) ; enregistrés via `#[ObservedBy]` sur les modèles ; appelants allégés (`Submissions\Create` — plus de `generateRefCode()`, Upsert*, `IssueAttestation`). ⚠️ La resoumission (`Account\Submissions\Show`) fournit `ref_code` explicitement (conservé). *Events* — `SubmissionStatusChanged` + listeners `NotifyAuthorOfStatus` / `InviteCoauthorsOnAcceptance` **auto-découverts** par Laravel 13 (⚠️ **ne pas** aussi `Event::listen` manuellement → double envoi). `Admin\Submissions\Show::applyStatus` dispatche l'event. *(commit `1bf8ab1`)*

**Impersonation staff → participant** (`lab404/laravel-impersonate`) : vraie « connexion en tant que », **cross-guard** (`web` → `account`). Trait `Impersonate` sur `User` (`canImpersonate()` = permission `manage_colloques`) et `Account`. `Admin\ImpersonationController` : `take` (`POST /admin/participants/{p}/impersonate`, bouton « Se connecter en tant que » sur l'aperçu si compte lié) → impersonifie le compte, redirige vers l'espace ; `leave` (`POST /impersonation/quitter`, `auth:account`). Bannière persistante dans le layout participant (`app('impersonate')->isImpersonating()`). Pendant l'impersonation, le guard `web` est déconnecté → admin inaccessible (retour par « quitter »). ⚠️ Un `composer require --no-scripts` laisse le provider hors du cache de découverte (config `session_key` null → 500) : refaire `php artisan package:discover` après. *(commit `bcc6869`)*

**Réutilisation patterns bizzcart (lots 1-3)** : **PDF serveur** via `barryvdh/laravel-dompdf` — vues autonomes `pdf.attestation` (A4 paysage, QR SVG rendu par dompdf) / `pdf.receipt` ; `AttestationController@pdf` (`/attestation/{uuid}/pdf`) et reçus admin/participant en `?pdf=1` (⚠️ dompdf ne lit ni JS ni CSS-variables → vues dédiées à couleurs littérales, pas les vues `.ukc-*`). **Enums de statut** `App\Enums\SubmissionStatus`/`PaymentStatus` (backed, `label()`/`pill()`, trait `HasLabels` : `values/options/pills`) : les modèles exposent `statuses()`/`statusPills()`/`statusEnum()` (source unique = l'enum) ; `status` **reste une string en base** (pas de cast, pour ne pas casser les comparaisons `=== 'accepted'`). **Sauvegardes** `spatie/laravel-backup` : `config/backup.php` publié + `backup:clean/run/monitor` planifiés. **186 tests verts.**

**Outillage qualité (revue technique)** : contraintes composer **pinnées** (plus de `*`) ; **Larastan** niveau 5 (`phpstan.neon`) avec **baseline** (`phpstan-baseline.neon`, dette figée — surtout inférence Eloquent générique/JSON, pas des bugs ; le CI ne bloque que sur les nouvelles erreurs) ; **CI GitHub Actions** (`.github/workflows/ci.yml` : Pint + PHPStan + build + Pest). **Model factories** dispo pour Colloque/Edition/Account/Participant/Submission/Payment (`Model::factory()`, states). ⚠️ Le style **Pint** est appliqué **projet-wide** (concat sans espaces `'a'.$b`) — lancer `pint` avant de commiter. E-mails transactionnels en **file (ShouldQueue)** → un **worker** est requis en prod (`queue:work`/`queue:listen` ; `composer dev` en lance un). Écritures multi-étapes clés sous **`DB::transaction`** (paiement anti-doublon avec `lockForUpdate`, sync comités, soumission).

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
│  └─ Gateways/          # ManualGateway, CashpayGateway (SEMOA), FedaPayGateway ; contrat OnlineGateway
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
- **Passerelles** — `GatewayRegistry` résout les passerelles **disponibles** (`isAvailable()` = identifiants configurés) et la **par défaut** (`config('payments.default')`, repli `manual`). `ManualGateway` (hors-ligne), `CashpayGateway` (SEMOA) et `FedaPayGateway` sont **implémentées** ; contrat `OnlineGateway` (verify + parseWebhook) pour le flux en ligne (voir §6 module 11).

---

## 6. Travail réalisé (modules)

Tout est **couvert par des tests Pest** (42 tests, 144 assertions au 2026-08-10). Correspondance avec les modules WP entre parenthèses.

1. **Fondations** — stack Livewire, design system, 14 migrations + modèles, comptes participants (inscription/connexion/reset/vérif e-mail/profil). *(commit `54263ae`)*
2. **Back-office éditions + page publique** — CRUD éditions (statuts, dates, soumissions, axes, grille tarifaire, slug auto), page `/colloque/{slug}` visible si publiée. Auth staff + spatie/permission. *(commit `54263ae`)*
3. **Soumission liée au compte** (Modules 2, 17, 25) — formulaire `/colloque/{slug}/soumettre` (bandeau compte, gating `canSubmit`, axes/type/mots-clés/résumé/co-auteurs/fichier), garde-fou nb max de communications, `ref_code` + `access_token`, accusé de réception. Liste « Mes communications ». *(commit `cfbcf4c`)*
4. **Workflow admin des soumissions** (Module 2) — liste filtrable, fiche détaillée, actions **en relecture / correction / accepter / refuser** avec message + e-mail à l'auteur (méthode `applyStatus`, ⚠️ `transition` réservé Livewire). *(commit `f83324b`)*
5. **Resoumission / versionnage** (Module 2) — fiche participant `/mes-communications/{submission}` : retour relecteurs (après décision) + resoumission quand `revision`. Une ligne **canonique** (`revision_of=0`) + **archives** (`revision_of=id`), `version++`. *(commit `f83324b`)*
6. **Relecture / peer review** (Module 3) — assignation depuis la fiche admin (panneau Relecteurs + synthèse note moyenne/recommandations), **portail relecteur en aveugle par jeton** `/relecture/{token}` + évaluation `/relecture/{token}/{review}` (recommandation, note /20, commentaires auteur + confidentiels), invitation par e-mail. *(commit `f83324b`)*
7. **Paiement** (Modules 4, 12, 17, 18) — encaissement **manuel** back-office (liste + totaux + reçu imprimable + e-mail + marquer réglé/rembourser), parcours **participant** (`/mon-compte/paiement/{edition}`, éligible si communication acceptée, montant du grade), **abstraction passerelle** (manuel ; CashPay/FedaPay implémentées au module 11). Garde anti-double-paiement partout. *(commit `f83324b`)*
8. **Ateliers, sessions & créneaux** (Modules 7, 26) — admin : ateliers (titre + axes), sessions (plénière/atelier, jour/horaire/salle, modérateur/rapporteur), créneaux + assignation des acceptées. Participant : choix d'un créneau libre sur sa fiche. *(commit `0144165`)*
9. **Back-office Participants** (Modules 5, 27) — liste par édition (rôle, paiement, invité), filtres, invitation individuelle + groupée (payés non invités), `ParticipantInvitationMail`. *(commit `16bedeb`)*
10. **Documents** (Modules 6, 8, 16, 19) — **attestations vérifiables par QR** (`IssueAttestation` UUID idempotent, émission par type/audience, révocation ; document imprimable avec QR → page publique `/verifier/{uuid}`), **livret** des résumés (par axe) et **badges** (A4, couleur par rôle) imprimables. Lib `simplesoftwareio/simple-qrcode` (SVG). *(commit `1f9d6c0`)*
11. **Paiement en ligne** (Modules 16, 18, 19) — contrat `OnlineGateway` (verify + parseWebhook **fail-closed**) ; `PaymentController` retour (`/paiement/retour/{gateway}`) + webhook (`/webhooks/paiement/{gateway}`, hors CSRF) avec confirmation via `ConfirmPayment` (le statut vient **toujours** d'une vérification serveur). **CashPay (SEMOA)** : OAuth token caché, `POST /orders` (bill_url), `GET /orders/{ref}`, callback **JWT HS256** vérifié (`firebase/php-jwt`). **FedaPay** : `POST /transactions` + `/token`, `GET /transactions/{id}` (approved), webhook **HMAC-SHA256** (`X-FEDAPAY-Signature`). Registre = CashPay + FedaPay + Manuel. *(commit `dbc1435`)*

12. **Programme public & fiches de session** (Modules 7, 26) — section « Programme » sur la page publique (plénières + ateliers + communications programmées via les créneaux, partiel `_session.blade.php`) ; **fiches de session imprimables** (`DocumentController@sessionSheets`, `/admin/editions/{edition}/fiches-session`). *(commit `502a78f`)*

13. **Réglages back-office** — table `settings` (clé/valeur JSON) + `App\Support\Settings` (caché) ; `AppServiceProvider::applySettingsToConfig()` applique au boot (mail SMTP, `services.cashpay/fedapay`, `services.openai/anthropic/gemini` + `services.ai.default`, `payments.default`) en repli sur `.env`. `Admin\Settings\Index` (Général, SMTP + e-mail de test, Paiement + identifiants, **IA** : fournisseur par défaut + clés OpenAI/Claude/Gemini). *(commits `6a249c6`, `f41b1a7`)*
14. **Espace participant enrichi** — dashboard : stepper (participation/attestation dérivées), blocs **Mes communications**, **Paiements & reçus**, **Mes attestations** (téléchargement par UUID) ; reçu participant `Account\ReceiptController` (`/mon-compte/recu/{payment}`, contrôle de propriété). *(commit `6a249c6`)*

15. **Page publique enrichie** (Modules 15, 20, 21) — comités (groupés par rôle), partenaires (logos par niveau), FAQ (accordéon), **compte à rebours** dans le hero (Alpine `ukcCountdown` dans `resources/js/app.js`). Gérés depuis le formulaire d'édition (comités = table `committee_members` sync ; partenaires/FAQ = `settings`). *(commit `d55865c`)*

16. **Paramétrage assisté d'édition par IA** (Modules 22, 23) — couche `App\AI` : contrat `AiProvider` (extract multimodal), providers Anthropic/OpenAI/Grok/Gemini (lisent `services.<provider>.key/.model`), `AiManager` (résolution défaut + repli + préférence PDF), parsing JSON robuste. Action `ExtractEditionFromDocuments` (instruction + schéma → normalisation). Carte « Paramétrage assisté (IA) » sur le formulaire d'édition : upload PDF/images → pré-remplissage des champs trouvés (révisable). *(commit `0682f9e`)*

17. **Modèles de badges/attestations + génération IA** (Modules 16, 19) — table `document_templates` (edition, kind badge|attestation, type, layout JSON). `App\Documents\LayoutSanitizer` (validation **déterministe** : bornage, liste blanche, défauts) + `LayoutRenderer` (HTML positionné + variables `{…}` + QR). Action `GenerateDocumentLayout` (l'IA produit **uniquement le JSON**). `Admin\Documents\TemplateDesigner` (génération IA + aperçu live + couleur d'accent + JSON éditable). Rendu branché : attestation + badges utilisent le modèle de l'édition si présent (repli sur le design fixe). *(commit `4a587db`)*

27. **Page publique : bannière promo + infos pratiques** (mockup WP) — bannière promo (bandeau doré en tête, texte + lien/CTA optionnels, masquée si vide ; `settings.promo`) ; section « Infos pratiques » (Lieu/Hébergement/Transport en cartes, ancrée dans la nav scrollspy ; `settings.practical`). Champs ajoutés au formulaire d'édition (save fusionne les settings, cf. correctif module 22). *(commit `e3e7bfe`)*

26. **Rôles agrégés inter-éditions** (Module WP 27) — les rôles ne sont plus figés par participation mais **dérivés** de l'activité de la personne sur **toutes les éditions**, par rapprochement d'e-mail (insensible casse). `App\Support\AccountActivity` : communications (auteur correspondant ou co-auteur via `authors` JSON, LIKE + filtre PHP), sessions modérées/rapportées (`moderator_email`/`rapporteur_email`), comités (`committee_members.email`, repli nom complet), relecteur (participation `role=reviewer`). `roles()` agrège Communicant/Relecteur/Modérateur/Rapporteur/Membre de comité, repli **Auditeur**. `Account\Dashboard` : carte « Mes rôles & activité ». `Admin\Participants\Preview` : pastilles « Rôles (toutes éditions) » si compte lié. *(commit `0606411`)*

25. **Modèles d'e-mails éditables** (Modules WP 12/13) — `App\Support\EmailTemplates` : 6 modèles (5 statuts de soumission + invitation participant) avec défauts, override via le réglage `email_templates` (champ vide = défaut), placeholders `{prenom} {nom_complet} {ref} {titre} {edition}`, helpers `forSubmission`/`forParticipant`. `SubmissionStatusMail` + `ParticipantInvitationMail` tirent sujet/titre/intro d'EmailTemplates (⚠️ ancienne const `SubmissionStatusMail::MESSAGES` supprimée ; `NotifySubmissionStatus` lit toujours `$mail->meta['subject']`). Seuls sujet/titre/intro sont éditables — les détails structurés (réf, dates, boutons) et le gabarit de marque restent fixes. `Admin\Emails\Templates` (`/admin/emails/modeles`, lien page E-mails) : édition + bouton « Défaut ». *(commit `c069947`)*

24. **Documents de l'édition côté participant** (Modules WP 10/11) — la table `media` (jusqu'ici inutilisée) est désormais gérée. `Media::TYPES` (document/livret/vidéo/photo/lien) + `typeLabel`. `Admin\Editions\Documents` (`/admin/editions/{edition}/documents`, lien liste éditions) : ajout/retrait de ressources (type/intitulé/URL). `Account\DocumentController@livret` (`/mon-compte/edition/{edition}/livret`) : livret des résumés avec **contrôle de propriété** (abort 403 si non-participant). `Account\Dashboard` : bloc « Documents de mes éditions » (médias + lien livret si ≥1 communication acceptée, pour chaque édition où le compte participe). *(commit `cd2f113`)*

23. **HAL — étape 1 (autocomplétion des affiliations)** — `App\Support\HalClient` interroge le référentiel des structures **AuréHAL** (API publique), normalise (label/acronyme/pays), **cache 1 j**, **fail-closed** (erreur/timeout → liste vide, saisie libre préservée), requêtes < 3 car. ignorées. `config/services.php` → `services.hal.enabled` (`HAL_ENABLED`) + endpoint ; appliqué depuis le réglage `hal.enabled` au boot. `Account\Profile` : autocomplétion du champ affiliation quand activé (`updatedAffiliation`/`chooseAffiliation`, click-outside). Réglages back-office : bloc « Interconnexion HAL » (toggle). **OFF par défaut.** ⚠️ Étape 2 (dépôt **SWORD** du texte intégral) non faite : nécessite un compte HAL + une étape « article complet » PDF pour les acceptés. *(commit `323da2f`)*

22. **Finitions page publique + impersonation admin** — *Page publique* : formulaire de **contact anti-spam** (`Public\ContactForm` + `ContactMessageMail` : honeypot, délai minimal anti-bot, throttle IP `RateLimiter`, envoi à `settings.contact.email` repli `mail.from`, journalisé) ; **hero média** (image/vidéo de fond depuis `settings.hero_image`/`hero_video`, overlay, repli bloc-couleur) ; **scrollspy** (`ukcScrollspy` IntersectionObserver dans `resources/js/app.js`) + smooth-scroll + lien Contact nav. Champs hero/contact ajoutés au formulaire d'édition. ⚠️ **Fix** : `Editions\Form::save()` **fusionne** désormais les settings existants (avant : il écrasait `submission_form` et toute clé non listée). *Impersonation* : `Admin\Participants\Preview` (`/admin/participants/{participant}/apercu`) — « Sa page » **lecture seule** (identité, communications, paiements & reçus, attestations), bandeau, aucune action ; lien depuis la liste participants. *(commit `a31c2f0`)*

21. **Formulaire de soumission paramétrable** (Module WP 14, adapté archi comptes) — l'identité vient du compte ; le formulaire porte sur titre/axe/type/mots-clés/résumé/fichier/co-auteurs. `App\Support\SubmissionFormConfig` (libellés, aides, requis/activé, bornes résumé min/max, champs personnalisés depuis `settings.submission_form`, défauts alignés). `Admin\Editions\SubmissionFormEditor` (`/admin/editions/{edition}/formulaire`, lien liste éditions) : champs standard + champs personnalisés (texte/texte long/liste/e-mail/nombre/case ; options une par ligne). `Account\Submissions\Create` consomme la config (libellés dynamiques, champs activables, obligatoires configurables, validation `accepted`/`in:`, stockage dans `submission.meta.custom_fields` + affichage fiche admin). *(commit `32a5be5`)*

20. **Parcours co-auteurs** (Modules WP 19-20) — les co-auteurs communiquants règlent leurs frais et deviennent éligibles à l'attestation de communication. `Public\CoauthorPayment` (`/colloque/{slug}/co-auteur`, **public sans compte**) : recherche par e-mail → communications acceptées associées → correction du grade (grille tarifaire) → paiement au tarif du grade via passerelles ; lien pré-rempli `?email=` + entrée discrète sur la page publique (section tarifs). `App\Actions\UpsertCoauthorParticipant` (participant co-auteur par e-mail, identité figée, rôle non rétrogradé, grade corrigeable). `PaymentController::return` branché sur la page publique co-auteur quand `meta.flow=coauthor`. Notification à l'acceptation : `App\Actions\NotifyCoauthorsForPayment` + `CoauthorPaymentInvitationMail` (lien pré-rempli, journalisée `emails`, dédoublonnage), câblée dans `Admin\Submissions\Show::applyStatus('accepted')`. Attestation « communication » : `Attestations::communicantTargets()` inclut les co-auteurs **ayant payé**. *(commit `497bd0a`)*

19. **Rapports xlsx/pdf** (Module 9) — exports par édition en **PHP pur** (aucune dépendance). `App\Support\XlsxWriter` : générateur .xlsx natif (ZipArchive + OOXML SpreadsheetML), multi-feuilles, en-tête gras, cellules texte (`inlineStr`) / numériques (auto), échappement XML, colonnes > Z. `App\Support\EditionReport` : agrégateur unique (synthèse + Participants/Communications/Paiements, note moyenne via `withAvg('reviews','score')`) réutilisé par xlsx/pdf/Livewire. `Admin\ReportController` : `xlsx` (4 feuilles) + `pdf` (HTML imprimable `@media print`). `Admin\Reports\Index` (`/admin/rapports`) : sélecteur édition, tuiles, boutons Excel/PDF ; lien sidebar. *(commit `614d9b4`)*

18. **E-mails programmés** (Modules 7, 11) — campagnes ciblées + rappels planifiés. `App\Support\EmailAudience` (audiences : tous / payés / non réglés / communication acceptée / relecteurs / non invités ; dédoublonnage par e-mail, exclusion des sans-adresse). `App\Mail\CampaignMail` + gabarit `emails.campaign` (sujet/corps libres). Action `QueueEmailCampaign` (une ligne `emails` par destinataire, statut `queued`, sujet/corps personnalisés via `{prenom} {nom} {nom_complet} {edition}`). Action `SendDueEmails` (envoie les `queued` dus `scheduled_at<=now`, marque `sent`/`failed`, **idempotent**). Commande `emails:send` + `Schedule::command('emails:send')->everyMinute()->withoutOverlapping()` (routes/console.php). `Admin\Emails\Index` (`/admin/emails`) : composer (immédiat/programmé), compteur d'audience live, journal filtrable, envoi des dus, annulation. Lien sidebar (remplace le placeholder « Documents » désactivé). *(commit `851cd61`)*

**État git** : `…`, `502a78f`, `6a249c6`, `d55865c`, `51dcd2a`, `0682f9e` (paramétrage assisté IA), `4a587db` (modèles badges/attestations IA), `851cd61` (e-mails programmés), `614d9b4` (rapports xlsx/pdf), `497bd0a` (parcours co-auteurs), `32a5be5` (formulaire de soumission paramétrable), `a31c2f0` (finitions page publique + impersonation), `323da2f` (HAL étape 1), `cd2f113` (documents de l'édition côté participant), `c069947` (modèles d'e-mails éditables), `0606411` (rôles agrégés inter-éditions), `e3e7bfe` (bannière promo + infos pratiques). Arbre propre. **171 tests verts**.

---

## 7. Ce qui reste à faire

### Priorité haute
1. **Validation des paiements en ligne au sandbox** — CashPay (SEMOA) et FedaPay sont **implémentés** (voir §6 module 11). Reste à : renseigner les identifiants (`.env` : `CASHPAY_*`, `FEDAPAY_*`, `PAYMENT_GATEWAY`), **tester un vrai paiement** en sandbox, confirmer l'enveloppe de réponse FedaPay (`v1/transaction`) et le format exact du corps du callback CashPay (JWT brut vs champ `token`). Puis exposer les webhooks publiquement (URL `/webhooks/paiement/{gateway}`) et les déclarer côté marchand.
2. **Livret .docx « vrai »** via `phpoffice/phpword` si un format Word éditable est requis (le livret actuel est imprimable HTML → PDF). *(le programme public et les fiches de session sont faits — voir §6 module 12.)*

### Ensuite
3. **E-mails** (Modules 7, 11) — ~~e-mails programmés (rappels à une audience via une commande planifiée)~~ **fait** (module 18, commit `851cd61`). Reste : brancher un vrai SMTP en prod (déjà réglable dans /admin/reglages) et lancer le planificateur (`php artisan schedule:work` en dev, ou cron `schedule:run` en prod) pour que `emails:send` tourne. En dev `MAIL_MAILER=log`, l'envoi immédiat marche sans planificateur (flush direct dans le composant).
4. ~~**Rapports** (Module 9) — export Excel (.xlsx) multi-feuilles + PDF.~~ **Fait** (module 19, commit `614d9b4`) : générateur maison `App\Support\XlsxWriter` (PHP pur, ZipArchive) + rapport PDF imprimable.
5. ~~**Page publique — finitions**~~ **fait** : contact anti-spam, hero photo/vidéo, scrollspy/menu sticky (module 22) + **bannière promo & infos pratiques** (module 27, commit `e3e7bfe`). Reste éventuellement : tarifs en cartes mises en avant (offre « à la une »).
6. ~~**Documents de l'édition côté participant**~~ **fait** (module 24, commit `cd2f113`) : gestion admin des médias (`media`) + bloc « Documents de mes éditions » (livret + ressources) sur le dashboard participant.
7. **Back-office Participants** — ~~« Sa page » (impersonation lecture seule)~~ (module 22) et ~~rôles dérivés inter-éditions~~ (module 26, commit `0606411`) **faits**.
8. ~~**Formulaire de soumission paramétrable** (module WP 14)~~ **fait** (module 21, commit `32a5be5`). Non porté volontairement (widgets front spécifiques WP) : tél. intl-tel-input, résumé WYSIWYG Quill, tags Select2 — le stack GESCO reste en champs simples.
9. **HAL** — ~~autocomplétion des affiliations (AuréHAL)~~ **fait** (étape 1, module 23, commit `323da2f`). Reste **étape 2** : dépôt **SWORD** du texte intégral vers une collection HAL (nécessite compte HAL + étape « article complet » PDF pour les acceptés). Optionnel : brancher l'autocomplétion aussi sur les co-auteurs du formulaire de soumission (aujourd'hui : profil compte seulement).

> **Comparaison WP↔GESCO (27 modules)** faite le 2026-08-10. Couverts en plus depuis : e-mails programmés (18), rapports xlsx/pdf (19), parcours co-auteurs (20), formulaire de soumission paramétrable (21), finitions page publique + impersonation (22), HAL étape 1 (23), documents de l'édition côté participant (24), modèles d'e-mails éditables (25), rôles agrégés inter-éditions (26), **bannière promo + infos pratiques (27)**. **Portage des 27 modules WP terminé.** Reste surtout : HAL étape 2 (SWORD, compte HAL requis) et les validations dépendant de l'utilisateur (sandbox paiements, clés IA réelles, SMTP + planificateur en prod).

---

## 8. Pièges & bonnes pratiques (à lire avant de coder)

- **Toolchain Herd** : toujours `export PATH="$HOME/.config/herd/bin:$PATH"` puis `php.bat`/`composer.bat` (§2).
- **Livewire 4 — méthodes réservées** : ne pas nommer une action `transition` (collision avec `Livewire\Component::transition`). Utiliser un autre nom (ex. `applyStatus`).
- **Livewire 4 — variable de vue `$slots` réservée** : Livewire injecte `$slots` (slots nommés) dans chaque vue ; ne jamais passer une donnée de vue nommée `slots` (elle est shadowée par un `SlotProxy`). Renommer (ex. `creneaux`). Idem prudence avec d'autres noms magiques.
- **Colonnes `nullable` avec défaut « 0 » applicatif** : `slots.submission_id`/`participant_id` sont `nullable` mais le code traite « 0 = libre ». À la création d'un créneau, forcer `submission_id => 0` (sinon `null` ≠ `0` et les requêtes `where(...,0)` le manquent) — même piège que `revision_of`.
- **Paiement en ligne — confiance zéro dans l'URL** : le statut « payé » vient **toujours** d'une vérification serveur (`OnlineGateway::verify`) ou d'un webhook **à signature vérifiée** (`parseWebhook`, **fail-closed** = retourne `null` si invalide). Ne jamais confirmer un paiement d'après un paramètre d'URL de retour. `ConfirmPayment` reste idempotent + anti-double-paiement.
- **`firebase/php-jwt` (HS256)** : refuse les clés trop courtes (`Provided key is too short`). En test, utiliser une clé d'API ≥ ~32 caractères (les vraies clés SEMOA le sont). Le corps du callback CashPay peut être le JWT brut **ou** un champ `token` — `CashpayGateway::parseWebhook` gère les deux ; à confirmer au sandbox.
- **Webhooks hors CSRF** : les routes `/webhooks/*` sont exclues de la vérification CSRF dans `bootstrap/app.php` (`validateCsrfTokens(except: ['webhooks/*'])`).
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
