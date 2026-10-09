# CLAUDE.md - Guide de contribution GEVES

Ce fichier est lu automatiquement par Claude Code. Il résume l'essentiel pour être
productif tout de suite. Le contexte détaillé (état, architecture, modules, pièges)
est dans **[`PASSATION.md`](PASSATION.md)** - à lire avant de coder sur un domaine
que vous ne connaissez pas.

## 👥 Mémoire collective (projet à plusieurs)

Le projet est développé **à plusieurs**, chacun avec Claude Code. La **mémoire locale
de Claude Code ne se transfère pas** entre postes/agents : **`CLAUDE.md` et
`PASSATION.md` (versionnés) sont la mémoire et le contexte partagés**. À la fin de
**chaque tâche notable** (nouvelle fonctionnalité, décision d'archi, changement de
convention ou de workflow), **mettez-les à jour** puis committez/poussez avec le
reste. C'est ce qui permet à tous les agents d'avoir le même contexte.

## Le projet en une phrase

**GEVES** (Gestion des ÉVénements Scientifiques - Direction de la Recherche et de
l'Innovation, **Université de Kara**) : plateforme **Laravel 13** qui gère de bout en
bout des événements scientifiques (colloques, journées, séminaires) - soumissions,
relecture en aveugle, paiements, programme/ateliers, documents (badges/attestations/
livret), e-mails, rapports, comptes participants, et assistance **IA**.

Stack : PHP 8.4, Blade + **Livewire 4** + Alpine 3, Tailwind v4 + design system maison
(`resources/css/ukc.css`), Vite 8, SQLite en dev, **Pest** pour les tests, PHPStan/
Larastan **niveau 5**.

### Prise en main back-office

- La page **Prise en main** (`/admin/prise-en-main`, `admin.onboarding`) est ouverte
  à tout membre du staff connecté. Les administrateurs de colloque et super
  administrateurs y modifient les textes, les rôles ciblés, les vidéos téléversées
  ou référencées et leur visibilité dans l’espace participant. Les guides sont dans
  `tutorial_guides` ; ne jamais réintroduire leur contenu en dur dans la vue.
- Les demandes de production ou de mise à jour vidéo sont stockées dans
  `tutorial_video_requests`. Le travailleur choisit Gemini, Claude ou OpenAI parmi
  les clés saisies dans **Réglages**, puis fait relire le scénario par un autre
  fournisseur disponible. `TutorialVideoRenderer` rend ensuite le MP4 avec Node,
  FFmpeg et Hyperframes sur le serveur : aucun compte Codex/Claude/Gemini ouvert
  sur le poste de l’administrateur n’est nécessaire. Le contrat persistant
  `meta` est construit par `TutorialVideoProduction` et s’inspire de **Brag** : logo
  UK, palette et police GEVES, lit musical original audible et mouvements UI sobres.
  Une vidéo dure au moins 36 secondes : elle reprend l’introduction exacte du guide,
  puis une scène séquentielle pour chaque étape, dans leur ordre. Le premier rendu
  produit des repères illustrés à partir du guide ; ne pas le présenter
  comme une capture d’un écran authentifié. L’agent rattache le MP4 au guide après
  contrôle. Les livrables sont sous
  `storage/app/tutorial-video-production/request-{id}/` : plan, brief de
  composition, poster, MP4 et texte de partage. Chaque demande déclenche `GenerateTutorialVideo` sur la file
  `tutorial-videos`, puis `TutorialVideoWorkerLauncher` démarre un travailleur caché
  sous Windows. Il conserve le PID du worker et contrôle qu'il existe encore avant
  de réutiliser le verrou : ne jamais remettre un verrou booléen d'une heure avant
  d'avoir obtenu ce PID, car une demande resterait alors indéfiniment en attente.
  `TutorialVideoAgent` lance le rendu Hyperframes, valide le MP4 produit et
  le publie dans le disque public. La commande `tutorial-videos:dispatch-pending`
  reprend les demandes créées avant l’activation de ce pont.
- L’espace participant expose `/prise-en-main` (`account.onboarding`). Il filtre les
  guides selon le colloque courant et les rôles réels (participant, modérateur,
  rapporteur) ; une vidéo masquée ne doit jamais y être rendue. Ses onglets de
  catégories filtrent aussi les cartes sans rechargement, avec la même présentation
  en pilules que le back-office.

## Toolchain - ⚠️ PHP/Composer/artisan ne sont PAS dans le PATH

Ils sont fournis par **Laravel Herd** sous forme de shims `.bat`. Pour toute commande
(Git Bash) :

```bash
export PATH="$HOME/.config/herd/bin:$PATH"
php.bat artisan ...        # PHP 8.4
composer.bat ...           # Composer
```

Node/npm sont dans le PATH. Le site est servi par **Herd** sur **https://geves.test**
(le dossier local reste `gesco`, malgré le renommage GEVES ; `gesco.test` ne répond plus).

### Commandes utiles

```bash
php.bat artisan test                       # suite Pest (base :memory:)
php.bat artisan test --filter=NomTest      # un fichier
php.bat vendor/bin/phpstan analyse         # analyse statique (niveau 5)
php.bat vendor/bin/pint                     # style (lancer AVANT de commiter)
composer.bat check                          # pint --test + phpstan + tests (= le CI)
npm run build                               # build des assets (requis pour voir le rendu)
```

⚠️ `vendor/bin/phpunit` ne marche pas (Pest requis). Le lanceur affiche une sortie
JSON compacte, pas la trace complète.

⚠️ **PHPStan et les chemins relatifs** : lancé depuis la racine, `phpstan analyse`
se termine en **code 1 sans rien écrire** - ni erreurs, ni « No errors », ni fatale.
Le `paths: - app` relatif de `phpstan.neon` en est la cause ; en chemins absolus la
même analyse s'exécute normalement. Un silence n'est donc **pas** un succès : si la
commande ne dit rien, rejouer l'analyse avec une configuration temporaire reprenant
`phpstan.neon` avec `paths` et `includes` en absolu.

## Voir le rendu

Builder les assets (`npm run build`) puis ouvrir **geves.test** - **ne pas** compter
sur le serveur Vite dev (bloqué en cross-origin ici). Le navigateur intégré exige une
approbation par action sur `geves.test` ; `curl` (statut HTTP + `grep`) est un contrôle
rapide fiable.

## Workflow Git

- On travaille directement sur **`master`** (pas de branches de fonctionnalité sauf
  demande explicite).
- **`git pull --rebase` avant chaque `git push`** (on est plusieurs : évite les merges
  et les conflits).
- **Pousser vers GitHub à la fin de chaque tâche** - ne pas laisser du travail terminé
  seulement en local. Dépôt privé : `https://github.com/abdoulhaqjr/geves`.
- **Commits en français.** Éviter les heredoc PowerShell dans l'outil Bash.

## Conventions qui comptent

### Vocabulaire : ÉVÉNEMENT / SÉRIE / TYPE (ex-édition / ex-colloque)

Depuis la refonte « événements typés », le modèle a **deux niveaux** :

- **`App\Models\EventSeries`** (table `event_series`, ex-`Colloque`) = la **série
  récurrente**. Elle **porte le TYPE** : colonne `type` + enum **`App\Enums\EventType`**
  (`colloque` / `seminaire` / `journees_scientifiques` / `autre`, défaut `colloque`).
- **`App\Models\Event`** (table `events`, FK `event_id`, ex-`Edition`) = l'**« événement »**
  daté, le hub opérationnel (participants, soumissions, paiements, programme, documents…).
  Relation **`Event::series()`** (FK **explicite** `event_series_id` - le nom de méthode
  « series » ferait sinon déduire `series_id`) ; inverse **`EventSeries::events()`**.
  L'**accesseur `Event::type()`** renvoie le type hérité de la série (défaut `colloque`).

- **Le type se choisit dans la fiche de l'événement**, bloc *Série de rattachement*
  (onglet Général) : sélecteur **`series_type`** (`admin/evenements/form.blade.php`),
  persisté par `SaveEvent` (création d'une série OU mise à jour de la série choisie).
  Toutes les éditions d'une série héritent du type. ⚠️ Pas encore d'écran dédié de
  gestion des séries (phase 2 possible).
- ⚠️ **Classes support / contrôleurs / factories / vues renommés** : `Edition*`→`Event*`,
  `Colloque*`→`EventSeries*`, `CurrentColloque`→`CurrentEvent`, dossier de vues
  `admin/evenements`, noms de routes `admin.evenements.*`. UI en français : « événement »,
  « série », « type ».
- ⚠️ **Exceptions ASSUMÉES qui gardent « edition/colloque » (NE PAS renommer)** : la
  couche RBAC par édition - capacités **`editions_read`/`editions_write`**, alias
  middleware **`edition.cap`**, **`EnsureEditionAccess`**, **`ScopesEditionAccess`**,
  **`User::canAnyEdition()`/`canOnEdition()`**, super-capacité **`manage_colloques`** ; la
  **route publique `/colloque/{slug}`** (`public.colloque`) et le mot « colloque » comme
  **libellé d'affichage / valeur de type** ; les **jetons du concepteur** `[edition.name]`
  / `[edition.theme]` / `[edition.dates]` (`DocumentVars`) ; le champ de merge e-mail
  `{{edition_name}}`. Ce sont des clés/identifiants, pas de l'affichage « édition ».
- ⚠️ **Relations imbriquées** : charger via `event.series` et `submission.event` (jamais
  `edition.colloque` / `submission.edition`, qui n'existent plus).

### Nom de la plateforme = dynamique

Ne **jamais** écrire un littéral « Gesco »/« GEVES » dans l'UI. Le nom d'affichage est
**`config('app.name')`**, surchargeable via **Réglages** (clé `app_name`). Pour les
traductions, utiliser le placeholder **`:app`**. (Certaines classes historiques gardent
le nom `Gesco` : `AskGesco`, `GescoController` - c'est du code, pas de l'affichage.)

### Charte & design system

- Source unique dans `resources/css/ukc.css` (tokens `--ukc-*`, composants `.ukc-*` :
  `.ukc-btn--primary|green|gold|outline|ghost|danger`, `.ukc-card`, `.ukc-pill--ok|warn|
  info|muted|err`, `.ukc-table`, `.ukc-field`, `.ukc-steps`, shell `.ukc-shell/-sidebar/
  -topbar`). Préférer ces classes aux littéraux.
- **Icônes = SVG uniquement** via `<x-ukc.icon name="…" :size="18" />`. **Jamais
  d'emoji dans l'UI** (exception assumée : les e-mails).
- **Ne jamais utiliser le tiret cadratin `—` (U+2014)** dans le projet (UI, code,
  seeds, docs) : utiliser un tiret simple `-`.
- Charte UK : bleu `#223ea4`, or `#ffd700`, vert `#009933`, police **Jost**.
- **Dégradé des héros publics = un seul token `--ukc-hero`** (`ukc.css`, bleu-foncé →
  bleu → `--ukc-blue-bright`). Tous les héros publics (accueil, pages internes, colloque,
  live, auth) l'utilisent : **ne jamais recopier un dégradé bleu en dur** dans un héros.
  Proscrire le navy quasi-noir `#0f1a33`/`#0f1f5c` sur le public (fonds pleins / voiles →
  `--ukc-hero-navy` `#14205a`). Les reflets or/vert viennent des calques `::before`.

### Kit Tailwise (back-office & espace participant)

Le **back-office admin ET l'espace participant** sont habillés avec le kit dashboard
**Tailwise** (bundle ThemeForest acheté, dans `Downloads/…/Tailwise Bundle`). ⚠️ On
**n'utilise pas** la chaîne d'outils Tailwind v3 du kit : Tailwise est adopté comme
**système de design dans le pipeline Tailwind v4 de GEVES**, via
`resources/css/tailwise.css` (importé en dernier), **100 % scopé sous `.ukc-shell`**
(police Public Sans, cartes `.box`, sidebar, KPI, etc.).

- ⚠️ **Le public reste sur la charte UK** (fidèle aux maquettes) - **ne JAMAIS**
  re-skinner le public avec Tailwise (le public n'a pas `.ukc-shell`).
- Composants Tailwise déjà portés et **à réutiliser** côté back-office : menu latéral
  rétractable à icônes, topbar + **palette ⌘K** (`<x-ukc.command-palette>`,
  `App\Support\AdminPalette`), widgets stat à **tendance réelle** (`<x-ukc.trend>`,
  `App\Support\Trend`), **tri par en-tête** (`<x-ukc.th>`, `App\Support\ListSort` - liste
  blanche anti-injection), **Tom Select**, **slideover** (`<x-ukc.slideover>`),
  **filedrop** (`<x-ukc.filedrop>`), **FullCalendar v6** (⚠️ pas v7 : classes CSS hachées).
- Éditeurs de documents (badge/template) = **pages autonomes hors `.ukc-shell`**
  (overlay `resources/css/designer-tailwise.css`, tokens surchargés via `html:root`).
- Vérif visuelle : viewport ≥1024px pour voir le menu déployé (reset `desktop` après).
- ⚠️ **Catégories du menu latéral** (`components/layouts/admin.blade.php`, `<span class="nav-label">`),
  chacune masquée si aucun de ses liens n'est accessible : **Scientifique** (Événements,
  Soumissions, Programme, Formations) · **Gestion** (Participants, Paiements, Secrétariat,
  Documents) · **Communication** (E-mails, Annonces, Témoignages, Réclamations) · **Pilotage**
  (Projet, Rapports, Assistant IA) · **Système** (Utilisateurs, Réglages). En ajoutant un lien,
  le ranger dans la bonne catégorie **et** inclure sa capacité dans la garde du `nav-label` du
  groupe (sinon un label vide peut apparaître).

### Composants transverses à réutiliser

- **Téléphone** : tout champ numéro de téléphone = `<x-ukc.phone>` (intl-tel-input,
  drapeau + indicatif, valeur **E.164** à la soumission).
- **Résumé de soumission** : CKEditor 5 (WYSIWYG, comptage en **mots**, `licenseKey:
  'GPL'`, texte justifié). **Mots-clés** : **Tom Select** (`data-tom data-tom-create
  data-tom-max`). Affichage d'un résumé stocké : **`App\Support\Html::render()`** (HTML
  assaini, jamais `strip_tags` brut).
- **Affiliations (autocomplétion)** : API libre **ROR** (`App\Support\RorClient`, api.ror.org, sans clé, cache + fail-closed) via l'endpoint `account.affiliations` (repli local) ; le wizard alimente un `<datalist>` en direct. Ne pas revenir à un datalist statique alimenté par la base.
- **Grades de participant** : liste unique **`App\Support\ParticipantTypes::all()`**,
  désormais **éditable dans Réglages** (clé `participant_types`, un grade par ligne ;
  `ParticipantTypes::DEFAULTS` sert de point de départ et de repli - la liste n'est
  jamais vide, sinon l'inscription serait bloquée). ⚠️ **Ne jamais redéclarer une
  liste de grades ailleurs** : le formulaire d'inscription l'avait fait, et les grades
  ainsi créés (« Doctorant / étudiant ») ne correspondaient à aucune catégorie
  tarifaire - le montant restait « à préciser ». Un test de garde
  (`PaymentRightsGuardTest`) échoue si une liste parallèle réapparaît.
  (profil + auteur correspondant). Le **grade est capturé par édition** sur le
  participant (`participant.title`) → une personne peut avoir des grades différents au
  fil des colloques ; ne pas le lire uniquement depuis le profil global.
- **Wizard de soumission** (`resources/js/app.js` → `submissionWizard`) : blocage
  d'étape (`validateStep`), erreurs en **toast** (`window.ukcToast`), auteur
  correspondant éditable (identité forcée côté serveur quand « self », cf.
  `StoreSubmissionRequest::prepareForValidation`). L'auteur peut corriger son résumé en
  place tant que `Submission::isAuthorEditable()` (aucune relecture) - sinon
  resoumission officielle (`canBeRevised`).
- **Liaison auteurs ↔ comptes** : table `submission_authors` (`Submission::authorLinks()`),
  moteur `App\Support\AuthorMatcher` (fort = e-mail/téléphone ; faible = nom+affiliation,
  IA en renfort, **déclinable**), action `LinkSubmissionAuthors` (appelée par
  `CreateSubmission` : lie + notifie `authorship`/`AuthorLinkedMail`/SMS best-effort, ou
  invite via `AuthorInviteMail`), listener `LinkPendingAuthorships` (auto-liaison à
  l'inscription). Une communication liée apparaît dans « Mes communications » de chaque
  auteur ; seul le déposant (`$isOwner`) peut la modifier.
- **Confirmations** : `window.ukcConfirm(msg, opts)` (popup Tailwise, Promise<boolean>) via l'attribut **`data-confirm`** sur le bouton (ou le formulaire) - **jamais** `onclick="return confirm()"`. Le handler `data-ajax` transmet `e.submitter` à `FormData` (sinon la valeur du bouton cliqué, ex. `status`, est perdue → 400).
- **Pagination + tri d'un tableau = CÔTÉ SERVEUR** (patron unique de tout le back-office,
  cf. `admin/soumissions`) : le contrôleur `->paginate(N)->withQueryString()` (rendu par
  `resources/views/vendor/pagination/ukc.blade.php`, `{{ $page->links() }}`) et **`ListSort`**
  (liste blanche anti-injection) + en-têtes **`<x-ukc.th sort="…" :current :dir>`** pour le
  tri. Les filtres = **formulaire GET** (auto-submit), pas de filtre client sur une liste
  paginée (il ne verrait que la page affichée). Utilisé : Soumissions (liste, **Attribution
  auto**), Programme > **Sessions** et **Créneaux** (20/page).
  - ⚠️ **Deux tableaux triables/paginés sur une même page** (Programme > Sessions **et**
    Créneaux vivent dans une seule vue, onglets en `?tab=`) : chacun a ses paramètres
    préfixés (`s_sort`/`s_dir`/`s_page`, `c_sort`/`c_dir`/`c_page`) ; `<x-ukc.th>` et
    `ListSort::resolve()` prennent des noms de paramètres personnalisés (`param`/`dirParam`/
    `pageParam`). Le board Planning garde les collections **complètes** ; les onglets liste
    filtrent/trient/paginent ces collections **en mémoire** via `ProgramController::sessionsTable()`
    / `slotsTable()` + `paginateCollection()` (pas de requête dédiée). ⚠️ Larastan croit les
    dates castées et relations **non-nulles** : dans un `sortBy` par clé, passer par
    `optional()`/`data_get()` (elles sont bien nullables au runtime : séance sans horaire,
    plénière sans atelier, créneau libre) - sinon `?->prop ?? …` déclenche `nullsafe.neverNull`.
  - **`tableFilters(keys)`** (Alpine, `app.js`) = filtre **client instantané SANS pagination**
    (lignes `data-filter-row` + `data-<clé>` + `data-text`, gouvernées par
    `x-show="visibleIds.includes(ID)"`). Réservé aux listes **non paginées** (ex. Programme >
    Ateliers, pool « À planifier »). ⚠️ Ne PAS y réintroduire de pagination client
    (`perPage`/`pageIds`/`<x-ukc.pager>`) : c'était une divergence du reste du projet, retirée
    au profit de la pagination serveur ci-dessus.
- **Créneaux : heure de fin** figée à la création/modif (`Slot::ends_at`) d'après la durée
  présumée (`slotMinutes()` : `poster_min` pour un poster, sinon `oral_min`) - `generateSlots`
  la posait déjà ; `storeSlot`/`updateSlot` la posent désormais aussi. La liste des créneaux
  affiche **« début – fin »** (repli sur `oral_min` pour d'anciens créneaux sans `ends_at`).
- **Config runtime** : mail/paiements/IA/visio/SMS/nom sont surchargés **depuis la base**
  (`AppServiceProvider::applySettingsToConfig()`), pas le `.env`.
- **Passerelle de paiement par édition** : le gestionnaire/co-gestionnaire peut renseigner
  les identifiants de passerelle (FedaPay/CashPay + passerelle par défaut) pour **son
  édition** via l'onglet **Paiement** du formulaire d'édition (`admin.editions.payment`,
  cap `editions_write`). Stockés dans `edition.settings['payment']` (seules les valeurs
  **remplies** sont conservées). `App\Support\EditionPaymentConfig::apply($edition)`
  surcharge `payments.default` + `services.fedapay/cashpay.*` au runtime, **repli sur la
  config globale** quand un champ est vide. Appelé en tête de chaque parcours de paiement
  (Account/Public/Formation `show`+`pay`, `PaymentController::return` avec
  `$payment->edition`). Pour essayer plusieurs configurations d'affilée, utiliser
  **`EditionPaymentConfig::using($edition, fn () => …)`** qui **restaure** la config
  ensuite (sinon les identifiants d'un candidat fuient sur le suivant).
- **Webhooks de paiement (à ne pas casser)** : la signature doit être vérifiée **avant** de
  savoir à quelle édition le paiement se rattache. `PaymentController::webhook` essaie donc
  la config globale **puis chaque édition** ayant ses propres identifiants
  (`EditionPaymentConfig::candidates()`), chaque essai isolé par `using()`. Garde
  d'étanchéité : **le marchand d'une édition ne peut confirmer que SES paiements**. URL
  générique `/webhooks/paiement/{gateway}` **ou** dédiée
  `/webhooks/paiement/{gateway}/{edition}` (affichée + copiable dans l'onglet Paiement).
  Toujours **fail-closed**, et tous les rejets sont journalisés dans le canal de log
  **`payments`** (`storage/logs/payments.log`, 90 j) - c'est la pièce à consulter pour un
  « j'ai payé mais le système dit non ».
- **Filet de sécurité paiements** : `App\Actions\ReconcilePayments` + `php artisan
  payments:reconcile` (planifié **toutes les 10 min**) revérifient auprès de la passerelle
  les paiements `pending` ayant une `gateway_ref`, et les confirment si réglés - couvre le
  cas « le webhook n'est jamais arrivé ». `ReconcilePayments::settle()` est la **source
  unique** d'application d'un statut vérifié (retour navigateur, webhook, réconciliation).
  Bouton **« Revérifier »** sur un paiement en ligne en attente (`admin.payments.recheck`).

### URLs de l'espace participant = sous `/compte`

Les pages personnelles du compte vivent sous **`/compte/...`** (`/compte`, `/compte/profil`,
`/compte/notifications`, `/compte/preferences`, `/compte/securite`, `/compte/paiement/...`).
Toute nouvelle page « Mon compte » suit cette règle. Quand une URL change, **laisser une
`Route::permanentRedirect`** depuis l'ancienne : les notifications stockent une **URL
absolue** figée à la création (`Notifier::account(url: route(...))`), donc d'anciennes
lignes en base pointent encore vers l'ancien chemin.

### 2FA : la méthode est EXCLUSIVE (`totp` **ou** `sms`)

`hasSmsEnabled()` est **prioritaire** sur `hasTotpEnabled()` dans `twoFactorMethod()`.
Toute activation doit donc **poser `two_factor_method`** et nettoyer l'autre méthode -
sinon un compte qui bascule du SMS vers l'application reste en méthode `sms`, reçoit un
SMS à la connexion et voit son code d'authenticator refusé. Vaut pour
`Account\SecurityController::confirmTwoFactor` **et** `Admin\ProfileController` (même
correctif des deux côtés). ⚠️ En dev, `sms.default` = **`log`** : les codes SMS ne partent
pas, ils atterrissent dans `storage/logs/laravel.log`.

### Comptes = `User` unifié (option B) — ⚠️ pas `Account`

Les **participants sont des `App\Models\User`** : depuis la refonte « compte unifié »,
les guards **`web`** (back-office) et **`account`** (espace participant) résolvent la
**même table `users`** (seul le *contexte* diffère). Le modèle/colonne `Account`/`account_id`
est **legacy et mort**. Toute nouvelle table liée à un participant référence **`user_id`**
(users), jamais `account_id`. `Participant` (par édition) porte `user_id`.

### Impersonation (« se connecter en tant que »)

`lab404/laravel-impersonate`, cross-guard (`web` → `account`), via
`Admin\ImpersonationController` (`take`/`leave`). La bannière + le bouton « Quitter »
vivent dans `components/layouts/participant.blade.php`.

- ⚠️ **Qui peut impersonifier : UNIQUEMENT admin de colloque + super-admin**
  (`User::canImpersonate()` = **`isColloqueAdmin()`**). Un gestionnaire ou le secrétariat,
  même avec la capacité `participants`, ne le peut pas. Le bouton « Se connecter en tant
  que » (fiche `admin/participants/preview`) est **gardé par `canImpersonate()`**, pas
  seulement par la présence d'un compte lié.
- ⚠️ **En impersonation, « se déconnecter » QUITTE l'impersonation, il ne déconnecte
  PAS.** Staff et compte impersonifié partagent la même session : `session()->invalidate()`
  déconnecterait aussi l'admin. `Account\LogoutController` détecte l'impersonation
  (`app('impersonate')->isImpersonating()`) et appelle `leaveImpersonation()` au lieu de
  vider la session (filet de sécurité couvrant tout chemin de déconnexion). Le bouton de
  la barre participant pointe en plus directement sur `impersonation.leave` dans ce cas.
- Tests : `tests/Feature/ImpersonationTest.php`.

### Module Formation (en cours, par phases)

Module **activable par édition** (`settings['doc']['formation.enabled']`, via `EditionSettings`),
capacité RBAC **`formation`**. Tables `formations` (plusieurs par édition ; `fee` 0=gratuite ;
colonnes `meeting_*` pour la visio) + `formation_trainers` (`user_id` + repli libre) +
`formation_enrollments` (inscription `user_id`, `status` registered→paid). Admin :
`Admin\FormationController` (`admin.formations*`). Participant : `Account\FormationController`
(`account.formations` = `/formations`, inscription gratuite/payante). ⚠️ **Une formation
appartient à une édition (= l'événement)** : l'espace participant se scope sur **l'édition
sélectionnée** (`CurrentColloque::current()`). Si cette édition ne propose pas de formation
(module désactivé **ou** aucune formation publiée → `Formation::offeredIn($edition)` faux),
le **lien du menu est masqué ET la page renvoie 404**. **Paiement de nature
`formation`** : `Payment.meta.flow='formation'` (helper `Payment::flow()`) ; ⚠️ **`ConfirmPayment`
est flow-aware** — la garde anti-double ne vaut que pour la **participation**, donc participation +
formation(s) + co-auteur coexistent. Page publique : section `#formations` (formations `public`)
via `Public\ColloqueController`. E-mails : confirmation d'inscription (`FormationEnrollmentMail`,
kind notif `formation`) + message admin aux inscrits (`admin.formations.message`, réutilise
`CampaignMail`). Attestations : type `formation` (inscrits) + nouveau `formation_animation`
(formateurs), émises depuis la page Formations (`admin.formations.attestations`) ; colonne
`attestations.formation_id`, variable `{formation}`. **Visio = le module visio, pas une reimplémentation** : contrat
`App\Meetings\Contracts\Meetable` + trait **`App\Meetings\Concerns\JoinsMeetings`** (URL de
connexion, embarquement Jitsi, lecteur LiveKit) partagés par `EventSession` **et** `Formation` ;
seul **`meetingRoleFor()`** est propre à chaque modèle. La page « Rejoindre » est **la même**
(`MeetingController`, vue `meeting.join`) : `session.meeting` pour une session,
`account.formations.visio` pour une formation. Tout nouveau modèle portant une visio
**implémente `Meetable` + `use JoinsMeetings`** et passe par `MeetingController`, jamais par
une redirection maison. **Module complet (phases 1-6)** + raffinements admin :
**activation via le formulaire d'édition** (onglet Options, `settings.formation_enabled`, `Edition::formationEnabled()`),
**ajout via slideover** (bouton « Nouvelle formation »), **flyer** (`formations.flyer_path`, `<x-ukc.filedrop>`),
**paramétrage assisté IA** (`ExtractFormationFromDocuments`, `admin.formations.ai`). Suivi admin nominatif des inscrits fait.
Reste optionnel : lecteur visio inline. Détails en [§11 de PASSATION.md](PASSATION.md).

### Cycle de vie d'une relecture (2026-09-27)

Une relecture n'est plus un simple « assignée / rendue » : `Review::STATUSES` =
**`invited`** (confiée, sans réponse) → **`accepted`** / **`declined`** →
**`completed`**, plus **`cancelled`** (retirée par le comité).

- **Une place n'est occupée que par un statut actif** (`Review::ACTIVE_STATUSES`
  = invited/accepted/completed) : un refus ou une annulation libère la place et
  la soumission redevient à couvrir dans l'**attribution auto**. ⚠️ Le relecteur
  qui a refusé reste néanmoins **exclu** du plan (on ne le re-sollicite pas tout
  seul) - c'est le comité qui décide, le moteur ne fait que proposer.
- **Échéance réelle** : `due_at` est posée à l'assignation depuis
  **`ReviewGrid::dueDays($edition)`** (réglage `edition.settings['review_due_days']`,
  champ dans l'onglet **Relecteurs** de la fiche d'édition ; repli
  `Review::DEFAULT_DUE_DAYS` = 21 j). ⚠️ Ce réglage vit à la **racine** de
  `settings` comme `review_criteria`, **pas** dans le sac `doc` d'`EditionSettings`
  (réservé aux documents).
- **« En retard » ≠ « pas encore rendue »** : `Review::isLate()` = encore due
  **et** échéance dépassée. `EditionReport::reviewStats()` distingue donc
  `pending` / `late` / `declined` (avant, tout ce qui n'était pas `completed`
  était compté « en retard »).
- **Traçabilité** : `assigned_at`, `assigned_by_id`, `assign_reason` (la
  justification du moteur d'attribution auto **suit la paire** jusqu'à la
  relecture), `responded_at`, `decline_reason`.
- **Côté relecteur** (`/relectures`) : boutons **Accepter / Refuser** (motif
  facultatif, slideover) sur une invitation ; l'évaluation n'est proposée
  qu'ensuite. Rendre une évaluation **vaut acceptation** (`responded_at` posé) et
  une relecture `declined`/`cancelled` **n'est plus évaluable** (422).
- ⚠️ **Retirer une assignation n'efface rien** :
  `Admin\SubmissionController::removeReview` passe la relecture en `cancelled`
  (elle reste dans l'historique, hors compteurs) et **notifie le relecteur**. Ne
  pas revenir à `$review->delete()` : qui a été sollicité, et quand, fait partie
  du dossier.
- Phrase d'état partagée : **`Review::summarize($reviews)`** → « 2 relectures
  demandées - 1 rendue, 1 en retard de 3 j (1 refus) », affichée sur la fiche
  admin de la soumission.
- Notification staff dédiée : kind **`review_response`** (acceptation / refus).
- Tests : `tests/Feature/ReviewLifecycleTest.php`.

- **Cohérence des pages de relecture** (2026-09-27) : le vocabulaire et la
  grammaire sont les mêmes partout. Liste des soumissions → colonne
  **Relectures** (`rendues/demandées` + retard, via `Review::progressOf()`) ;
  fiche d'une soumission → `Review::summarize()` + état/échéance par relecture ;
  **Attribution auto** → couverture calculée sur les seules relectures actives ;
  **Rapports** → `pending`/`late`/`declined` distincts ; **espace relecteur**
  (`/relectures`) → sous-navigation `?tab=relectures|candidatures`, KPI
  **disjoints** (à confirmer + à évaluer + évaluées + refusées = total) et barre
  de filtres identique aux listes admin. L'**e-mail d'invitation** annonce
  l'échéance et la possibilité de refuser.
- **Conflit d'intérêts « même institution » : IGNORÉ par défaut** (case cochée
  dans l'onglet **Options** de la fiche d'édition,
  `settings['options']['coi_ignore_same_institution']`, lue par
  `ConflictOfInterest::ignoresSameInstitution()` - **défaut `true`**). La
  plateforme sert une université unique : la règle signalerait presque toutes
  les paires, elle n'a de sens que si on la **réactive** (décocher). Les autres
  règles restent actives. ⚠️ Défaut à `true` **côté moteur ET côté formulaire** :
  les deux doivent rester d'accord. Les tests qui veulent EXERCER la détection
  « même institution » posent explicitement `coi_ignore_same_institution => false`
  (cf. `ConflictOfInterestTest`, `ReviewLifecycleTest`).
- **Kit Tailwise = les DEUX back-offices** : les cartes KPI (`.ukc-stats`,
  `.ukc-stat-card`, `.lbl`/`.val`/`.hint`), la barre de filtres (`.filter-bar`)
  et la taille compacte `.ukc-btn--sm` étaient scopées à `.admin-page` ; elles le
  sont désormais à **`.ukc-shell`**, donc l'espace participant en hérite. ⚠️ Ne
  pas re-scoper ces règles à `.admin-page` : l'espace participant retomberait sur
  la carte centrée d'origine et sur la grille 4 colonnes du **site public**
  (`public.css` définit `.ukc-stats` sans scope).
- **Bulle du chatbot** : elle est fixée en bas à droite, là où les tableaux
  posent leur colonne « Action » et les slideovers leurs boutons. Deux garde-fous
  dans `resources/js/app.js` : `initChatbotOverlayGuard()` la masque tant qu'une
  surface modale est ouverte (`body.ukc-overlay-open`), et
  `initChatbotPoliteness()` l'efface (`.is-shy`) quand elle recouvre une action,
  la ramenant dès que le pointeur s'approche. Son `z-index` est **55** (au-dessus
  du contenu, sous slideover 60 / confirmation 90 / modales 100) - ne pas le
  remonter.
  - ⚠️ **Le décor de page reste sous 55.** `.ukc-nav-bar` (barre collante de la
    page colloque) était à **100**, le rang des modales : elle **tranchait** le
    panneau ouvert du chatbot, une bande blanche en travers de la conversation,
    et « Mon espace » / FR-EN paraissaient flotter par-dessus. Ramenée à **50**,
    l'étage de `.site-header` - c'est du décor, pas une surface modale. Quand un
    élément collant masque une surface flottante, **c'est presque toujours lui
    qui est trop haut**, pas la surface qui est trop basse : remonter le chat
    l'aurait fait recouvrir les modales, ce qu'un correctif antérieur avait
    précisément défait.

- **Vocabulaire de la relecture (un seul mot par chose)** : la **tâche** est une
  **« relecture »** (statuts `Review::STATUSES` : *Invitée / Acceptée / Refusée /
  Rendue / Annulée*), son **contenu** une **« évaluation »** (grille,
  recommandation, « Évaluer », « Grille d'évaluation », « Délai d'évaluation »).
  ⚠️ **Ne pas réintroduire « avis »** : c'était un troisième mot pour les mêmes
  objets, qui cohabitait avec les deux autres sur les mêmes écrans (« Avis
  rendus » à côté de « Rendue »). Les compteurs disent **« Rendues »**, jamais
  « Évaluées » ni « Avis rendus ».
- ⚠️ **« Relecture en attente » ≠ « pas encore rendue »** : un
  `where('status', '!=', 'completed')` compte aussi les relectures **refusées**
  et **annulées** - le relecteur garde un badge pour un travail qu'il n'a plus à
  faire et le comité voit une charge fantôme. Passer par
  **`Review::PENDING_STATUSES`/`isPending()`** (encore dû),
  **`ACTIVE_STATUSES`/`isActive()`** (occupe une place) ou **`isLate()`**. Le
  motif a déjà été écrit à trois endroits : un **test de garde**
  (`ReviewLifecycleTest`, « ne déduit jamais l'attente de son inachèvement »)
  scanne `app/` et `resources/views/` et échoue s'il revient.
- **Module Soumissions = cinq onglets** (2026-09-27) : *Soumissions · Formulaire
  de soumission · Attribution auto · Relecteurs & expertise · Options*, définis
  **une seule fois** dans `resources/views/admin/submissions/_nav.blade.php`
  (⚠️ ajouter un onglet = un seul fichier à éditer ; chaque page passe sa clé via
  `['current' => '…']`). Chaque page porte le **sélecteur d'édition** puis la
  sous-navigation, comme partout ailleurs.
  - **Relecteurs & expertise** (`admin.submissions.reviewers`) : matrice des axes
    évaluables + charge (relectures actives seulement).
  - **Options** (`admin.submissions.options`) : **toutes** les règles de l'édition
    pour ce module - *dépôt* (limites du formulaire), *attribution* (relecteurs
    visés, charge max), *évaluation* (grille, délai).
  - ⚠️ La fiche d'édition **n'a plus** d'onglet Relecteurs/Évaluation, et
    l'éditeur de formulaire **n'a plus** d'onglet « Règles & limites » : ces
    réglages vivent au plus près de ce qu'ils pilotent. Les formulaires postent
    toujours sur les routes `editions_*` (capacité **`editions_write`**) et
    s'affichent en **lecture seule** pour qui ne l'a pas.
  - Réglages à la racine de `edition.settings` : `review_target`,
    `review_max_load`, `review_criteria`, `review_due_days` (`ReviewGrid`) ;
    les limites de dépôt restent dans `settings['submission_form']['rules']`.
  - ⚠️ **`admin.editions.form.update` reconstruit champs + champs personnalisés**
    depuis la requête : ne jamais y poster les seules règles (elles seraient
    perdues). Endpoint dédié **`admin.editions.form.rules`** ; un test de garde
    (« ne perd pas les règles quand on enregistre les champs ») le protège.
  - **Éditeur du formulaire = enregistrement AUTOMATIQUE, sans bouton** (Alpine
    `formEditor`, `submission-form-editor.blade.php`) : l'état vit entièrement
    côté client ; **toute** action (réordonner un champ, ajouter, éditer,
    supprimer) **poste en AJAX** sur `admin.editions.form.update` (qui répond en
    **JSON** quand `wantsJson()`) et affiche un toast. Il n'y a **plus** de bouton
    « Enregistrer » (ni en haut ni en bas). L'**édition d'un champ** se fait dans
    un **slideover** (`<x-ukc.slideover>`, même schéma que « Nouvelle tâche » du
    Projet) : ouvrir → modifier → *Enregistrer* commit + persiste ; *Supprimer*
    (champ personnalisé) dans le pied du panneau. Ne pas réintroduire un `<form>`
    global avec bouton submit.
- ⚠️ **Un axe thématique se compare par CLÉ, jamais par chaîne brute** :
  l'expertise d'un relecteur stocke une **copie** du libellé de l'axe, et ce
  libellé est du texte libre. Un tiret cadratin recopié suffisait à faire échouer
  l'égalité stricte **en silence** - en base, les axes des relecteurs étaient en
  « Atelier 1 — … » et ceux de l'édition en « Atelier 1 - … », donc l'attribution
  automatique proposait *systématiquement* « hors axe ». Passer par
  **`ReviewerExpertise::covers()`** / **`axisKey()`** (tirets typographiques,
  casse et espaces normalisés) ; l'enregistrement **recanonise** sur le libellé de
  l'édition, donc un axe renommé ne casse plus l'expertise en silence.
- **En-tête d'une page admin** : `title` = le nom de l'onglet, `subtitle` = une
  courte indication de ce qu'on y fait (« Communications & résumés »), **jamais**
  le nom de l'édition - il est déjà dans le sélecteur juste en dessous, et il
  faisait passer l'en-tête sur trois lignes. ⚠️ `subtitle` est une valeur PHP
  échappée à l'affichage : y écrire `&amp;` produit un `&amp;amp;` visible.

- ⚠️ **Listes dans le chat** : `ukcMarkdownLite` produit de vrais `<ul>`/`<ol>`,
  mais le **reset Preflight de Tailwind** met `list-style: none` sur toutes les
  listes. Les trois surfaces de chat (`.admin-ai` copilote, `.gesco-chat` page
  `/assistante`, `.ukc-cb-msg` bulle flottante) doivent donc **rétablir**
  `list-style: disc|decimal outside` - sinon il ne reste qu'un texte indenté,
  sans puce ni numéro. Même piège pour toute liste stylée à la main.

### Édition archivée = lecture seule pour les participants

**`App\Support\EditionLock`** : `frozen($edition)` (statut **`archived`**) et
`guard($edition)` (403 explicite). Appelé dans les actions d'**écriture** de
l'espace participant : dépôt et corrections d'une communication
(`Account\SubmissionController::authorizeOwnership`, point de passage des cinq
actions), relecture (`submit`, `respond`, candidatures), rapport de séance,
paiement (`pay`), inscription à une formation.

- ⚠️ **Le déclencheur est `archived`, pas `closed` ni la date de fin.** `closed`
  signifie « dépôts clos » : c'est précisément la période où les relecteurs
  travaillent. Une édition dont les dates sont passées mais non archivée peut
  encore recevoir une relecture ou une version définitive. L'archivage est la
  décision explicite qui fige le dossier.
- ⚠️ **Le gel n'enlève jamais la LECTURE** : le relecteur consulte l'évaluation
  qu'il a rendue (bouton « Consulter », panneau `inert`, pas d'enregistrement),
  le rapporteur relit son rapport (branche « verrouillé » de la vue), le
  participant garde la page de paiement et son reçu. On masque les actions et on
  dit pourquoi - on ne renvoie pas un 403 sur un bouton resté cliquable.
- Le **back-office n'est pas concerné** : l'admin et les gestionnaires gardent la
  main (corriger une décision, émettre une attestation oubliée, rembourser). Leurs
  droits restent portés par le RBAC par édition.
- Tests : `tests/Feature/ArchivedEditionLockTest.php`.
- ⚠️ **Téléphone = identifiant UNIQUE** : `users.phone` porte un index unique, et
  **`App\Support\Phone::normalize()`** en donne la forme canonique (séparateurs
  retirés, `+` conservé ; **aucun indicatif deviné**). La normalisation est
  appliquée par le **mutateur `User::phone`** et **avant** chaque contrôle
  d'unicité - sinon « +228 90 01 52 84 » passerait la validation puis
  entrerait en collision à l'écriture. Trois écrans écrivent ce champ et doivent
  tous porter la règle : **inscription**, **profil** et **2FA par SMS**
  (`SecurityController::sendSmsSetupCode`, sans quoi on s'appropriait le numéro
  d'un tiers). `AuthorMatcher::normPhone()` délègue à la même classe : comparer
  et stocker suivent la même règle.
  Pourquoi : le téléphone est un **signal fort** de rapprochement (auteurs,
  formateurs) et le serveur **impose** ensuite l'identité du compte trouvé - un
  doublon rattachait donc une communication au mauvais compte, en silence.
  Diagnostic : **`php artisan users:phones`** (rapport, `--fix` pour normaliser).
  Tests : `tests/Feature/UserPhoneUniquenessTest.php`.
- **Module Programme = un seul écran, neuf onglets** : *Programme · Planning ·
  Ateliers · Sessions · Créneaux · Espaces · Discussions · Affectation auto · Options*,
  pilotés par l'URL (`?tab=`) et définis **une seule fois** dans
  `resources/views/admin/program/_nav.blade.php`. Route canonique
  **`admin.program.index`** (`/admin/programme`). Onglet par défaut = **`apercu`**.
- ⚠️ **Modérateur / rapporteur d'une session = rattachés à un COMPTE par e-mail**
  (`event_sessions.moderator_email` / `rapporteur_email`) : c'est l'e-mail qui ouvre
  les espaces `/moderation` et `/rapports` de la personne. Le formulaire de session
  (`_modals.blade.php`) ne postait QUE le nom (`sModerator`/`sRapporteur`) : à chaque
  modification, `moderator_email` repassait à null et **le lien au compte était coupé**
  (le seed le posait pourtant). Désormais ces deux champs sont une **auto-complétion**
  (Alpine `searchPeople`/`pickPerson` dans le `x-data` de `admin/program/index.blade.php`,
  endpoint **`admin.program.people`** qui cherche les comptes par prénom/nom/e-mail) :
  choisir une personne fixe le nom ET envoie son e-mail dans un **champ caché**
  (`sModeratorEmail`/`sRapporteurEmail`, déjà lus par `storeSession`/`updateSession`).
  Un indice « Lié à … » / « Choisissez une personne… » dit si le lien est effectif.
  ⚠️ **Les TROIS payloads `openSession(...)` doivent porter `moderator_email`/
  `rapporteur_email`** (`$sessJsFor`, `$sessJs`, et l'inline du bouton « Modifier » de la
  table) : sinon le panneau rouvre « non lié » alors que la base a l'e-mail. Taper un nom
  à la main **sans** choisir une suggestion **ne lie personne** (l'e-mail est vidé à la
  saisie). Styles combobox : `.ukc-combo-menu` (en flux, pas absolu : le corps du
  slideover a `overflow-y:auto`). Tests : `ProgramPeoplePickerTest`.
  - **Onglet « Programme » (`?tab=apercu`, par défaut) = ÉDITEUR du programme
    synthétique** : un **titre** + des **jours** (`date` + intitulé libre) contenant des
    **lignes** typées (`type` = plénière/atelier/pause/autre, `start`, `end`, `label`,
    `highlight`). Repeater Alpine `programSynthesis` (`app.js`), `<form>` postant les
    tableaux imbriqués `days[i][date]` / `days[i][rows][j][…]`. Stocké dans
    **`edition.settings['program_synthesis']`** (`['title', 'days' => [['date','heading','rows' => [['type','start','end','label','highlight']]]]]`).
    - ⚠️ **C'est CE contenu saisi qui s'affiche** dans la section `#programme` de la
      page publique, **en onglets jour par jour** : admin (aperçu) et public incluent le
      **même partial** `resources/views/partials/program-synthesis.blade.php` (reçoit
      `$synthesis` ; charte UK `.ukc-tabs-scroll`/`.ukc-program-day`, `public.css` chargé
      aussi en admin donc rendu identique). Le contrôleur public passe
      `settings['program_synthesis']` en `$programSynthesis` ; section masquée si vide.
    - **Génération auto (bouton « Enregistrer & générer sessions/créneaux »)** :
      `saveSynthesis` (`admin.program.synthesis`) enregistre ; `generateFromSynthesis`
      (`admin.program.synthesis.generate`) enregistre PUIS crée les sessions/créneaux -
      une ligne *Plénière*/*Autre* datée → 1 session plénière ; une ligne *Ateliers en
      parallèle* → 1 session par atelier (salles distinctes) découpée en créneaux libres
      via `sliceSessionSlots()` (helper extrait de `generateSlots`, réutilisé) ; *Pause*
      ignorée. ⚠️ **Ne remplace QUE ce qu'il avait généré** : les ids créés sont mémorisés
      dans `settings['program_synthesis_generated']` et purgés au prochain « générer » ;
      les sessions/créneaux saisis à la main sont conservés. Le seed pose un exemple (sans
      lancer la génération : la base a déjà son planning détaillé).
  - ⚠️ Le programme vivait sur **deux pages** (`/programme` et
    `/programme/creneaux`) qui géraient les **mêmes ateliers et les mêmes
    sessions** avec deux interfaces, et la première n'était atteignable **ni par
    le menu ni par la palette** - alors qu'elle portait seule la **visio**.
    `admin.program.sessions|slots|reports` sont conservées en **redirection**
    (liens du secrétariat, des documents, signets) : ne pas les supprimer sans
    vérifier les appelants.
  - La **visio** d'une séance se déplie depuis l'onglet Sessions. ⚠️ Son panneau
    est une ligne **sœur** de la ligne de séance : l'état Alpine se pose sur un
    **`<tbody>` par séance** (un `x-data` sur la ligne ne couvre pas sa sœur).
  - « Non planifiées » est devenu la suite de **Créneaux**, « Paramètres
    créneaux » l'onglet **Options**, et l'**Agenda** (FullCalendar) se déplie
    dans **Planning**.
  - ⚠️ **Les rapports de séance appartiennent au SECRÉTARIAT**
    (`admin.secretariat`, poste de travail unifié : lecture / amélioration /
    validation dans un panneau latéral ; la validation verrouille le rapport côté
    rapporteur). Le module Programme n'en porte **aucune copie** - deux écrans pour
    le même objet, c'est deux endroits où chercher. `admin.program.reports` redirige
    vers le Secrétariat.
  - Chaque page de sélecteur d'édition utilise **`currentEditionId()`** du trait
    `ScopesEditionAccess` (il était privé à un seul contrôleur).
- **Fiche d'édition : plus d'onglet « Programme »** (2026-09-28). **Dates clés**
  est passé dans l'onglet **Général**. ⚠️ L'ancien déroulé éditorial `settings['programme']`
  a été supprimé (personne ne le lisait). Le programme public (`#programme` de la page
  colloque) affiche désormais le **programme synthétique saisi** dans l'onglet
  **Programme** du module (`settings['program_synthesis']`), via le partial partagé
  **`partials.program-synthesis`** (`colloque.blade.php` passe `$programSynthesis`).
  - ⚠️ **Le public ne montre PAS le détail des communications** : le programme
    synthétique est un déroulé de haut niveau (cérémonies, pauses, « communications
    en ateliers »…), **pas** la liste des communications ni les modérateurs/rapporteurs.
    Ne pas réinjecter `$commsBySession` ni les rôles dans `#programme`. Test :
    `ProgramPublicTest` (`assertDontSee` le titre d'une communication).

- **BASE vs données de colloque : seeders scindés** (réamorçage ciblé). La BASE que
  possède le super-administrateur - rôles/permissions + compte super-admin
  (`RolesAndAdminSeeder`) et les **réglages** (`settings`) - est **séparée** des
  données de démonstration de colloque, regroupées dans **`ColloqueDemoSeeder`**
  (`ColloqueAvenirSeeder` + `ColloqueAvenirVolumeSeeder`). `DatabaseSeeder` appelle
  RolesAndAdmin **puis** ColloqueDemo.
  - **`php artisan colloques:reseed`** recrée les données de colloque **sans
    `migrate:fresh`** : il vide le domaine « colloque » (liste ordonnée enfant→parent
    dans `App\Console\Commands\ReseedColloques::EVENT_TABLES` : events, event_series,
    event_user, participants, submissions, slots, payments, reviews, formations,
    attestations, claims, meeting_messages, sessions/polls/questions, project_*,
    committee_members…), **purge les comptes de démo** (domaines `@test.tg` /
    `@avenir-durable.tg` / `@example.org`, `--keep-accounts` pour les garder) puis
    rejoue `ColloqueDemoSeeder`. ⚠️ **Ne touche JAMAIS** `settings`, le super-admin
    (`admin@univkara.tg`), les rôles/permissions, `tutorial_*`, `document_templates`,
    `emails`, `media`, `testimonials` : c'est tout l'intérêt vs `migrate:fresh --seed`.
    `--force` (sans confirmation / autorise en production, refusé sinon),
    `--class=` (rejouer un autre seeder de colloque). ⚠️ En AJOUTANT une table au
    domaine « colloque », l'ajouter à `EVENT_TABLES` (sinon elle survit au réamorçage) ;
    une table de **configuration** (réglage/design du super-admin) N'y va jamais.
    Tests : `tests/Feature/ColloqueReseedTest.php`.
- **Un seul colloque de démo, à l'échelle** : `migrate:fresh --seed` ne crée plus
  qu'**un** colloque (le VIIIe conjoint Parakou-Kara, `avenir-durable-2025`), au
  volume d'un vrai événement. `DatabaseSeeder` enchaîne `ColloqueAvenirSeeder`
  (colloque + édition **5 → 9 octobre 2026**, ateliers, staff, relecteurs,
  direct) puis `ColloqueAvenirVolumeSeeder` (**200 communications** réf. `AVD-###`,
  un correspondant **et des co-auteurs** chacune ; statuts variés ; créneaux pour les acceptées ;
  relectures dans tous les états ; **93 % de paiements de participation validés
  manuellement**, passerelle `manual`, reçu numéroté). Montants via
  `Fees::resolveAmount()`, grades via `ParticipantTypes::all()`. Seeders idempotents ;
  le seed prend ~45 s (200 comptes). Un test de garde vérifie les 200 et les 93 %.
  ⚠️ Les anciens seeders de colloques de démo (Showcase, Live, DemoColloque,
  DemoScenario, DemoStaff) ont été **supprimés** : ce colloque est la référence unique.
  - **Module Formations** semé (`ColloqueAvenirSeeder::buildFormation()`) :
    `settings['formation_enabled'] = true` + **une formation publiée** (« Méthodologie
    de la recherche… », **payante 10 000 FCFA**, jeu. 08/10 14h-17h, visio `scheduled`),
    **deux formateurs** (`formateur1@test.tg` modérateur, `formateur2@test.tg`) et
    **6 inscrits** pris parmi les auteurs - **4 réglés** (paiement manuel de nature
    `formation`, reçu `REC-FORM-…`) et **2 inscrits non réglés**. Idempotent. De quoi
    exercer l'espace Formations admin/participant et la page publique `#formations`.
  - ⚠️ **La grille tarifaire du seed LIE les grades aux catégories** (`fees[].grades`)
    et marque « Autres participants » **par défaut** : sans cela `Fees::resolveAmount()`
    renvoyait `null` et les 200 paiements de participation étaient semés **à 0 F**. Les
    montants sortent donc par grade (15 000 / 35 000 / 50 000 FCFA).
  - **Programme du seed = ateliers + plénières SANS conflit** (l'exclusivité des
    plénières est respectée dans les données de démo). Les **ateliers** de volume
    occupent « Salle 1 » à « Salle 6 » (6 en parallèle) par blocs matin **08:30-11:30**
    et après-midi **14:00-17:00** (`ColloqueAvenirVolumeSeeder::buildSessions()`, **4 blocs**
    du **lun. 05/10 au jeu. 08/10**, 24 séances de 3 h - un bloc par jour, un jour
    vide au milieu d'un programme se lisant comme un trou de données). Les **4 sessions
    plénières** (`ColloqueAvenirSeeder::buildPlenaries()`, salle « Grand amphithéâtre »,
    `type=plenary`) sont calées **hors** de ces fenêtres : inauguration **lun. 05/10
    09:00-10:30**, table ronde **mer. 07/10 08:00-10:00**, formation **jeu. 08/10
    14:00-17:00**, clôture **ven. 09/10 11:00-12:30** - dernier jour de l'édition, d'où
    l'absence de bloc d'ateliers ce matin-là. Les **séances de démo** de la base
    (`Session - {atelier}`, rapports/Q&A) sont à **11:30-12:30** (juste avant la pause
    déjeuner 12:30-14:00, laissé libre par le volume), et les séances **en direct** /
    **terminée** sont dans des auditoriums dédiés : aucune ne collisionne en salle avec le
    volume ni ne tombe sur le déjeuner. ⚠️ En touchant à ces horaires, garder ces fenêtres
    disjointes (ateliers, plénières ET pause déjeuner) - sinon la vue Planning affiche des
    conflits dès le seed.
  - ⚠️ **Tout se cale sur `$start`** (`ColloqueAvenirSeeder`, décalages J+0 à J+4) et le
    seeder de volume **relit `date_start`**. Déplacer le colloque = changer `$start`/`$end`,
    jamais des dates en dur ailleurs. Garder une fenêtre de **5 jours** : la clôture est à J+4.
    ⚠️ **RÉTRÉCIR la fenêtre ne se fait PAS en changeant ces deux lignes seules** : en passant
    de 7 à 5 jours il a fallu replier les décalages des plénières (table ronde J+3 → J+2,
    formation J+4 → J+3, clôture J+6 → J+4), les blocs d'ateliers et le **programme
    synthétique** - sinon la clôture tombait deux jours après la fin de l'édition. Deux tests
    de garde : aucune séance hors fenêtre, et aucune journée du programme publié hors fenêtre.
  - ⚠️ **5 communications MINIMUM par séance d'atelier** (`MIN_PAR_SEANCE`), et c'est ce
    minimum qui **dicte le nombre de blocs**, pas le calendrier : 130 communications
    acceptées sur 42 séances en laissaient 3 par séance, et une séance à trois ne démontre
    ni la modération, ni le rapportage, ni le passage en salle. 24 séances en portent 5 ou 6
    - et 6 × 30 min remplit exactement le bloc de 3 h, donc un bloc de plus redescendrait
    à 4. ⚠️ La séance se choisit **au tour de rôle**, pas par `id % n` : le modulo de
    l'identifiant répartissait des identifiants non contigus (une communication sur trois
    est acceptée) et laissait des séances à 3 quand d'autres en avaient 8.
  - ⚠️ **Le créneau est identifié par sa COMMUNICATION** (`edition_id` + `submission_id`),
    pas par le couple séance + communication : une communication se présente une fois, et
    l'ancienne clé créait un SECOND créneau au lieu de déplacer le premier dès que le plan
    changeait.
  - ⚠️ **Le plan peut RÉTRÉCIR**, et `updateOrCreate` ne sait que créer ou mettre à jour :
    `buildSessions()` **supprime** ses séances d'atelier hors plan (et leurs créneaux -
    `slots.session_id` n'a pas de clé étrangère, ils survivraient orphelins). Sans ce
    ménage, un rejeu sur une base déjà semée gardait les 42 anciennes séances et la règle
    des 5 minimum devenait fausse en silence.
  - **Co-auteurs** (`attachCoauthors()` / `attachDemoCoauthors()`, en **seconde passe** :
    une communication ne peut citer que des comptes déjà créés). Chaque communication porte
    son correspondant **et deux co-auteurs inscrits**, plus - une fois sur trois - un
    co-auteur **non inscrit** resté `invited`, qui donne sa matière à l'association différée
    (`LinkPendingAuthorships`, à la création du compte). Les six comptes `commN@test.tg` ont
    chacun un dépôt à eux et deux co-signatures.
    ⚠️ **Les seeders n'appellent PAS `LinkSubmissionAuthors`** : cette action **notifie** -
    un e-mail et un SMS par rapprochement, soit six cents envois pour un seed. Ils écrivent
    les lignes `submission_authors` directement, **dans la forme exacte** que l'action leur
    aurait donnée (c'est `status = 'linked'` + `user_id` que lit « Mes communications »).
    ⚠️ **Jetons déterministes** (`hash('sha256', …)`) : le jeton porte le lien de
    déclinaison, et un `Str::random()` le changerait à chaque rejeu.
  - ⚠️ **`users.phone` est UNIQUE**, et un compte semé peut avoir été **renommé à la main**
    en base de développement : il garde son numéro, et l'insertion du compte attendu échouait
    sur la contrainte. Le scénario étant dans une transaction, c'étaient **200 communications
    qui ne se semaient pas** pour un numéro fictif. Le seeder cède le numéro (compte sans
    téléphone) au lieu de tomber.
  - **Visioconférences dans le jeu de démo** : les trois états sont représentés sans rien
    attendre - une séance **en direct**, une **terminée avec replay** (toutes deux calées sur
    l'**horloge**, pas sur une date fixe : une séance « terminée » posée à date fixe
    s'annoncerait terminée avant d'avoir eu lieu), et des séances **programmées**.
    ⚠️ **TOUTES les séances ouvrent une salle** (`meeting_status` jamais `none`) : deux blocs
    seulement la portaient, et on ne pouvait pas vérifier la visio depuis une communication
    prise au hasard du programme. Les 130 créneaux de communication sont donc tous en
    visio. Cérémonies en visibilité **publique** (seul endroit qui exerce
    `MeetingVisibility::Public`), ateliers réservés aux inscrits.
    ⚠️ **Le seeder n'impose AUCUN `meeting_provider`** : il suit `meetings.default`, et
    `MeetingProviderRegistry` retombe seul sur Jitsi public quand ce défaut n'est pas
    configuré. Le forcer en base écraserait la configuration du site - c'est ce qui
    masquait LiveKit une fois ses identifiants renseignés.
### Écran d'entrée : montré quand il sert, franchi sinon

Il a sa raison d'être la **première fois** : on tombait dans la salle, et la
demande d'autorisation du navigateur arrivait par-dessus une séance déjà
commencée.

- ⚠️ **Mais reposé à chaque actualisation et à chaque reconnexion, il devient un
  péage** : on le franchit sans rien lire, et on le traverse justement au pire
  moment - quand on vient de perdre la salle en pleine séance. `entreeDirecte`
  (règle pure testée) laisse entrer directement si on est **déjà passé par là
  POUR CETTE SALLE** (`localStorage`, clé `vu:<roomName>`) **et** que le
  navigateur accorde encore ce qu'on lui demande.
- ⚠️ **`prompt` n'est pas `granted`**, et c'est tout l'intérêt : un navigateur
  qui n'a pas encore demandé, ou qui a oublié, repasse par l'écran - sinon la
  fenêtre d'autorisation s'ouvre par-dessus la séance, ce que cet écran existe
  pour éviter. Firefox ne connaît pas ces noms de permission : on rend `prompt`,
  et l'écran s'affiche. Mieux vaut un écran de trop que la mauvaise surprise.
- Entrer sans caméra ni micro ne demande aucune autorisation : rien à vérifier.

### Les échanges (Q&A voté, sondages) ont leur SURFACE et leur bouton

⚠️ Ils ont d'abord vécu sur une autre page (poser une question faisait quitter
la visio), puis comme **onglet du panneau latéral** - et c'était encore trop
peu : une colonne de 400px partagée avec le fil, les personnes et la présence ne
tient pas une file de questions **et** des sondages en cours. On passait son
temps à faire défiler, et sur téléphone le tiroir recouvrait la séance.

- **`#btnEch`** dans la barre (pastille `#echCount`), **`#ech`** comme surface :
  deux colonnes au-dessus de 860px, empilées en dessous, **chacune avec son
  propre défilement** - sinon répondre à un sondage oblige à remonter par-dessus
  trente questions.
- ⚠️ **Tri côté serveur : ce qui ATTEND passe devant.** Une question répondue
  mais portée par vingt votes restait en tête de file pour le reste de la
  séance. L'animateur lit de haut en bas : ce qui est fait descend, et les
  masquées (que seul le modérateur voit) descendent plus bas encore.
- **Filtres** (`questionsFiltrees`, règle pure) : Toutes / Sans réponse / Les
  miennes. ⚠️ Un vide **filtré** ne se dit pas comme un vide : annoncer « aucune
  question » alors qu'il y en a trente derrière un filtre, c'est la salle qui
  ment.
- ⚠️ **La pastille ne compte que ce qui attend** (`compteEnAttente`) : une file
  de quarante dont trente-huit sont traitées n'appelle pas l'animateur. Elle
  **respire trois fois** quand une question arrive alors que la surface est
  fermée, puis se tait - un signal permanent cesse d'être un signal.
- ⚠️ **Deux rythmes de relecture** : 10 s surface ouverte (un vote qui monte doit
  se voir), 45 s fermée. Fermée, on relit **quand même** - sinon la pastille
  mentirait jusqu'à ce qu'on pense à ouvrir, et c'est précisément ce qu'elle
  existe pour éviter.
- Rien n'est réécrit côté règles : ce sont les points d'entrée JSON de la page
  autonome, et c'est le **serveur** qui dit qui peut poser, voter et modérer.

### Tableau blanc (bouton « Tableau »)

Une surface de dessin partagée qui recouvre la scène chez tout le monde.
Crayons, gomme, épaisseur, annuler, effacer, et **enregistrement en image**.
Tout passe par le **canal de données** : rien n'est envoyé au serveur, le
tableau vit le temps de la séance. Le jour où il devra rejoindre le compte
rendu, c'est un point d'entrée à ajouter - pas ce module à refaire.

- ⚠️ **Le cadre a un rapport FIXE (16/9) et se centre** (`cadreTableau`). C'est
  la décision qui tient tout le reste : un tableau est un **document**, il doit
  être identique partout. Si chaque poste normalisait sur SA scène, un cercle
  tracé sur un portable deviendrait une ellipse sur l'écran d'à côté, et un
  schéma annoté deviendrait faux. Les marges sont le prix à payer.
- ⚠️ **L'épaisseur se rapporte à la largeur du cadre**, pas au pixel, pour la
  même raison.
- ⚠️ **Le crayon suit la PAROLE, pas le rôle** (`droitDeTracer`, règle pure).
  Le serveur donne `presenter` à **chaque auteur** de communication de la
  séance (`presenterAccountIds()`) : avec `canPublish()`, les six
  communicateurs d'une séance pouvaient tous écrire sur le diaporama de
  celui qui parle. Le crayon va à qui **présente en ce moment** - celui à qui
  la parole a été donnée (`isFloor`, posé à la réception de `mod_role`) ou qui
  partage son écran. Le **modérateur** le garde : il conduit la séance, et
  sans lui le tableau blanc serait inutilisable tant que personne ne présente.
  ⚠️⚠️ **Une SEULE définition de « qui présente »** : `celuiQuiPresente()`,
  partagée par la **scène** et par le **crayon**. Elles l'ont calculée chacune
  de leur côté - la scène prenait « parole donnée OU rôle présentateur », le
  crayon exigeait la parole donnée - et un communicateur affiché
  « Présentateur » **en grand sur la scène** trouvait le bouton Tableau grisé.
  La salle lui disait deux choses contraires en même temps.
  ⚠️ `majDroitDeTracer()` est rappelé depuis **`refreshPeople()`** : c'est la
  liste des personnes qui dit qui présente, et tout y passe (arrivée, départ,
  parole donnée, rôle retiré). L'accrocher aux seuls événements qu'on avait en
  tête en oubliait toujours un.
- ⚠️ **C'est une règle de CONDUITE, pas une barrière.** La frontière
  vérifiable reste le **rôle signé dans le jeton** (`ORDRES_RESERVES`, niveau
  `presenter`), qui interdit déjà le tableau à l'assistance. Qui tient la
  parole se décide sur le canal de données, donc ne s'authentifie pas - et un
  récepteur qui filtrerait là-dessus écarterait des traits légitimes dès que
  sa vue de la parole diffère d'un poste à l'autre. **Un tableau qui perd des
  traits en silence est pire que le mal qu'on soigne.**
- La lecture seule masque la barre **et** les écouteurs vérifient
  `peutTracer()` - sinon il suffisait d'ouvrir la console.
- ⚠️ **`ordreRecevable` dit d'où vient un message, pas ce qu'il contient.**
  `traitRecu` assainit : couleur prise dans la palette, épaisseur bornée,
  coordonnées ramenées dans le cadre, points plafonnés, `Infinity` écarté
  (il passe `!isNaN` et fige le canevas de toute la salle).
- ⚠️ **C'est l'ARRIVANT qui demande** (`board_ask`), pas le propriétaire qui
  devine : envoyé à l'annonce d'arrivée, l'instantané partirait peut-être avant
  que l'arrivant n'écoute. La demande part dans
  `adopteParticipantsPresents()` - le seul endroit qui passe à l'entrée **et**
  après une reconnexion. Seul celui qui a **ouvert** répond (sinon trois
  présentateurs envoient trois instantanés), avec un garde-fou de 3 s par
  demandeur. Revers assumé : s'il a quitté la salle, un arrivant ne reçoit plus
  le tableau.
- ⚠️ **L'instantané se découpe par POINTS** (`lotsDeTraits`) : un tableau d'une
  heure dépasse la taille d'un message du canal, et envoyé d'un bloc il n'arrive
  pas du tout. Un trait plus long qu'un lot part seul - le couper en ferait deux
  traits avec un blanc au milieu.
- ⚠️ **Le dernier message d'un trait porte le trait ENTIER** (`net`), allégé à
  la levée du crayon : sans ce remplacement, les autres postes garderaient la
  version brute et le document ne serait plus le même partout.
- ⚠️ **On ne redessine pas tout à chaque point** (`traceDernierSegment`). À deux
  cents traits, chaque mouvement du crayon en redessinerait deux cents, soixante
  fois par seconde.
- ⚠️ **Pas de `pointerleave`** pour finir un trait : atteindre le bord du cadre
  le couperait en pleine écriture. `pointerup` + `pointercancel`, plus un
  `pointerup` sur la fenêtre si la capture a échoué.
- ⚠️ **Mesuré au harnais** : le titre et la barre d'outils ne flottent pas
  au-dessus du tableau, leur place est **retirée avant** le calcul du cadre -
  posés en surimpression, ils recouvraient le bas de la zone de dessin. Et la
  barre est centrée par `left:0;right:0;margin:auto`, **pas** par `left:50%` +
  `translateX(-50%)` : en positionnement absolu, `left:50%` ne laisse que la
  moitié de la largeur disponible, et la barre se repliait sur deux lignes alors
  que son contenu tenait largement.
- **Barre en icône + mot**, même grammaire que la barre de la salle : le
  `title` seul **n'existe pas au doigt**, et une rangée de six dessins sans
  légende se devine mal. Le mot s'efface sous 640px, où la place rendue au
  tableau vaut mieux qu'une légende ; `title` et `aria-label` restent.
  ⚠️ **Mesuré au harnais** : la pastille du mode actif paraissait **vide** -
  `#visio-room .bd-outils button.on` (fond or à 16%) a la même spécificité
  que la règle du mode et vient plus bas dans la feuille, elle gagnait donc
  le fond en laissant à l'autre la seule couleur du texte. Bleu foncé sur
  fond sombre. D'où le chevron `.bd-outils .bd-mode button.on` (contraste
  remonté à 8,4:1).
- **Annuler / Refaire** (`indexDuDernierTraitDe`, règle pure) : on ne défait
  **que les siens** - deux personnes annotent la même présentation, et
  « Annuler » ne doit pas effacer par surprise la flèche du voisin. Le
  préfixe se compare **avec son tiret**, sinon `alice` reprendrait les traits
  d'`alice2`. ⚠️ Le trait rendu repart **entier** (`net`) : les autres postes
  l'avaient retiré, leur envoyer la suite ne suffit pas. ⚠️ Un trait neuf
  **vide la pile** (la branche reprise est abandonnée), et un `board_clear`
  aussi - rendre un trait sur un tableau qu'on vient de vider ferait
  réapparaître un fantôme.
- ⚠️ **UN TABLEAU APPARTIENT À LA COMMUNICATION DE QUI L'A OUVERT.** Le
  présentateur n'attend personne : il ouvre quand il veut, et c'est SON
  tableau. Quand **quelqu'un d'autre** l'ouvre, on repart d'une surface
  **vierge** - le schéma du communicateur précédent n'a rien à faire sous
  l'exposé du suivant. Rouvrir le sien, en revanche, le retrouve : on referme
  parfois par mégarde, et perdre son tableau pour un clic serait rude.
  ⚠️ C'est pourquoi `fermeTableau` **ne remet pas `parQui` à zéro** : sans
  propriétaire mémorisé, rouvrir le sien reviendrait à l'ouvrir pour la
  première fois - et l'effacerait.
- **Reprendre le tableau d'un autre se confirme** : l'ouvrir pour sa propre
  communication efface son contenu **chez tout le monde**, et rien ne le rend
  (son auteur peut l'emporter en image tant qu'il est à l'écran). Un tableau
  vide se reprend sans rien demander - il n'y a rien à perdre.
- « Effacer » **se confirme** aussi : il emporte le travail de tout le monde.
- Tests : `meeting-pure.test.js` (12 cas).

**Deux modes** (bouton « Tableau | Annoter » dans la barre du tableau) :

- **Tableau** : surface blanche, cadre 16/9.
- **Annoter** : la couche devient transparente et le cadre se cale sur l'**image
  présentée** (`cadreMedia`). Le mode est **deviné à l'ouverture** - un écran
  partagé s'ouvre en annotation, c'est presque toujours pour cela qu'on prend le
  crayon.
- ⚠️ **La salle force `object-fit:contain` pendant qu'on annote.** En `cover`,
  l'image est ROGNÉE, et pas pareil selon le format de chaque fenêtre : deux
  personnes ne verraient pas la même portion du document, et la flèche de l'une
  tomberait à côté chez l'autre. Mesuré au harnais : cadre aligné sur un
  diaporama 4/3 à moins de 2px près, rapport exact.
- ⚠️ **Le repère se calcule à l'identique partout** parce qu'il ne dépend que de
  la taille **intrinsèque** du média (la même pour tous) et de la boîte locale.
- ⚠️ **`loadedmetadata`, pas seulement l'appel direct** : à l'instant où la piste
  est attachée, `videoWidth` vaut zéro. Sans cette attente, l'annotation
  retombait sur le 16/9 du tableau alors qu'un diaporama 4/3 arrivait une
  fraction de seconde plus tard.
- ⚠️ **La toile reste transparente en annotation** : la remplir de blanc
  masquerait la présentation. Revers assumé : la gomme y laisse une trace
  blanche au lieu d'effacer - « Annuler » est l'outil qui défait.
- ⚠️ **L'export compose** l'image de la scène PUIS les traits, à la taille
  **intrinsèque** de la vidéo : exporter la seule couche de dessin rendrait des
  traits flottant sur du vide, et exporter à la taille du canevas rendrait un
  diaporama illisible sur un petit écran. Une piste WebRTC ne salit pas le
  canevas, `toBlob` reste autorisé.
- ⚠️ **La barre d'outils ne peut plus être retirée du cadre** (il doit coller à
  l'image) : elle se loge dans la bande laissée par le format quand il y en a
  une, et s'efface à 40% sinon, jusqu'à ce qu'on la vise.

### Réactions de salle (bouton « Réagir »)

Un emoji qui **monte sur l'écran de présentation**, chez tout le monde, et qui se
pose **en même temps sur la carte de qui a réagi** (opacité réduite, coin
bas-droit). Bouton voisin de « Main » : même intention, deux intensités.
Purement client (canal de données LiveKit) - **rien n'est envoyé au serveur et
rien n'est conservé** : la salle n'a pas à garder trace de qui a applaudi quoi.

- ⚠️ **La bulle ne porte PAS de nom.** C'est la carte qui dit de qui vient la
  réaction, à l'endroit où l'œil cherche déjà la personne ; un nom accroché à
  chaque bulle encombrait la scène pour redire la même chose.
- ⚠️ La marque va sur **SA** carte (`tileLocal` / `tile-<identity>`), jamais sur
  la grande tuile : celle-ci montre qui **parle**, et y poser la réaction de
  quelqu'un d'autre désignerait la mauvaise personne.
- ⚠️ **Le minuteur de la marque précédente est annulé** avant d'en poser une
  nouvelle : sinon il retire la marque de la **seconde**, encore toute fraîche.
- ⚠️ Le **nom recule** (`.tile.a-reagi .name`) le temps de la réaction, sinon
  l'emoji se pose sur la fin d'un nom long. Dans le **bandeau** le nom est
  replié : il ne recule qu'une fois déplié (survol, clavier) - et **au doigt**,
  où il reste ouvert faute de survol. Cette dernière règle doit venir **après**
  celle du bandeau : à spécificité égale, posée plus haut, elle perdait et
  l'emoji se posait sur le micro (mesuré à 375px).

- ⚠️ **À ne pas confondre avec les réactions du fil** (`basculeReaction`,
  `.chip`), qui se posent SUR un message, se comptent et survivent au
  rechargement. Une réaction de salle est un applaudissement : elle passe.
- ⚠️ **Le canal de données est public.** Le récepteur filtre l'emoji sur la
  liste connue (`MeetingMessage::EMOJIS`, déjà exposée par `cfg.chat.emojis`) :
  sans cela, n'importe quel connecté afficherait **en grand, chez tout le
  monde**, le texte de son choix. `textContent`, jamais `innerHTML`.
- ⚠️ **L'anti-rafale (`peutReagir`) s'applique des DEUX côtés** : chez
  l'émetteur pour le bouton, chez le récepteur **par expéditeur** - un client
  modifié ne se prive pas d'un verrou qui ne vivrait que dans sa propre page.
  Il laisse passer une horloge qui recule : c'est un anti-rafale, pas un verrou.
- ⚠️ **`REACTIONS_MAX` et `placeBulle` sont solidaires** : il y a exactement
  autant de places que de bulles vivantes (8). Quand la suite reboucle avant le
  plafond, deux bulles encore à l'écran se posent **exactement** l'une sur
  l'autre. Le test énonce l'invariant.
- ⚠️ **Mesuré au harnais visuel, pas estimé** : 42px entre deux rangées (la
  bulle en fait 38 de haut), 26% d'écart entre deux places d'une même rangée
  pour une bulle large d'au plus 25%, et `min-width:0` sur le nom - sans lui, un
  élément de flex refuse de descendre sous la largeur de son texte et
  `max-width` restait sans effet.
- ⚠️ La couche vit dans **`.stage-main`**, pas dans la page : c'est elle qui
  passe en plein écran. `pointer-events:none`, sinon elle avale les clics.
- ⚠️ **Mouvement réduit** : la bulle ne voyage plus mais **ne disparaît pas** -
  `animation:none` la laisserait figée pour toujours. Elle s'efface sur place,
  et le retrait s'appuie de toute façon sur un **minuteur**, pas sur
  `animationend` (qui n'arrive jamais si l'animation ne se joue pas).
- `aria-hidden` sur la couche : vingt bulles énoncées une à une noieraient le
  lecteur d'écran. Qui veut dire quelque chose **lève la main** - celle-là est
  annoncée, et listée.
- Tests : `meeting-pure.test.js` (6 cas).

### Visioconférence par édition (onglet « Visioconférence » de la fiche)

Même patron que la **passerelle de paiement par édition** : le gestionnaire
renseigne, pour **son** édition, le fournisseur, les identifiants du serveur
(LiveKit / Jitsi) et la **qualité vidéo**. Stocké dans
`edition.settings['meetings']` (seules les valeurs **remplies** sont conservées) ;
**`App\Support\EditionMeetingConfig::apply($edition)`** surcharge
`meetings.default` + `meetings.livekit.*` / `meetings.jitsi.*` au runtime, avec
**repli sur la configuration globale** pour tout champ vide.

- ⚠️ **Toutes les portes d'entrée passent par `EditionMeetingConfig::using()`**,
  qui **restaure** ensuite : une même requête peut toucher plusieurs éditions (une
  liste de séances), et `apply()` n'écrase que les champs remplis - sans
  restauration, une salle s'ouvrirait sur le serveur d'un autre colloque. Le socle
  commun l'applique dans **`JoinsMeetings::avecReglagesVisio()`** (URL de
  connexion, embarquement Jitsi, lecteur LiveKit) ; les trois autres appels
  LiveKit le font explicitement : **Egress** (enregistrer sur le bon serveur),
  **occupation réelle de la salle** (`SessionPresenceApiController`) et le bouton
  « Enregistrer » de la salle.
- **`Meetable::meetingEdition()`** (nouveau au contrat) dit de quelle édition
  relève une salle - sans lui, le socle ne saurait pas quelle configuration poser.

**Qualité vidéo** (`meetings.quality`, défaut **`md`** = 960x540 à 24 i/s) :

- ⚠️⚠️ **RÉGRESSION VÉCUE, puis corrigée.** Le défaut avait été porté à 720p pour
  toute la plateforme. Les trois couches additionnées passaient d'environ
  **1,45 Mb/s à 2,35** de liaison montante : sur un réseau modeste, ce n'est plus
  une image qui saccade, **c'est la salle qu'on perd**. Le défaut est revenu à
  `md`, qui est **exactement** ce que la salle faisait avant d'être réglable.
  Monter reste possible - mais c'est un **choix d'édition**, pas un défaut
  imposé à tout le monde. Ne pas refaire le trajet inverse sans avoir mesuré la
  liaison montante réelle des salles concernées.
- ⚠️ `profilVideo` est une règle pure testée, et le cas Pest
  « garde « md » comme défaut de la plateforme » **fige cette régression**.
- ⚠️ `degradationPreference: 'balanced'` : sans elle, l'encodeur s'obstine sur un
  seul critère quand la liaison faiblit - il tient la définition et hache le
  mouvement, ou l'inverse. Une image qui se dégrade vaut mieux qu'une salle
  qu'on perd.
- ⚠️ **Un débit explicite accompagne la définition** (`videoEncoding.maxBitrate`) :
  laissé au SDK, il se calait sur l'ancienne définition et une capture plus fine
  serait sortie en bouillie.
- ⚠️ Les **couches basses** (180p, 360p) restent publiées : ce sont elles que
  reçoivent les petites vignettes et les connexions faibles, et c'est ce qui rend
  une couche haute abordable. Ne pas les retirer en croyant « simplifier ».
- ⚠️ Le coût retombe sur la **liaison montante de qui diffuse** - d'où un réglage
  **par édition** : sur un réseau modeste, « Économe » (360p) donne une image plus
  stable qu'une haute définition qui saccade.
- Tests : `tests/Feature/EditionMeetingConfigTest.php`.

### Les échanges (Q&A + sondages) = EXCLUSIVEMENT dans la visio

Questions et sondages vivaient sur une **page autonome** (`/session/{id}/echanges`),
atteinte par un lien de la barre du haut de la visio : ⚠️ poser une question
obligeait à **quitter la visio**, donc à cesser de voir et d'entendre ce dont on
voulait parler. Ils sont un **onglet du panneau** (« Échanges »), au-dessus du
chat et des participants.

- ⚠️ **La page autonome a été SUPPRIMÉE** (route `session.engagement`, méthode
  `SessionEngagementController::page`, vue `session/engagement.blade.php` -
  toutes retirées) : les échanges se tiennent **exclusivement dans la salle de
  visio**. `SessionEngagementController` n'expose plus que des **points d'entrée
  JSON** (`questions`/`ask`/`vote`/`moderate`/`polls`/`createPoll`/`votePoll`/
  `togglePoll`), consommés par l'onglet « Échanges » de `resources/js/meeting.js`.
  Ne pas recréer une page HTML : un sondage se répond là où se tient la séance.
- ⚠️ **Aucune règle JSON n'est réécrite** : c'est le serveur qui dit qui peut
  poser, voter et modérer (`abort_unless($role->canJoin())` / `isModerator()`).
  Masquer un bouton n'a jamais protégé personne. L'onglet n'apparaît que si le
  serveur a donné les adresses (`cfg.engagement`, posées sous la même garde
  `canJoin()` que la salle). Tests : `SessionQaTest` (garde sur les endpoints JSON).
- **Rafraîchissement** : toutes les 10 s **panneau ouvert sur cet onglet
  seulement** - la salle entière interrogeant le serveur pour un panneau que
  personne ne regarde, c'est du bruit pur. Entre-temps, une annonce sur le canal
  de données (`type: 'engagement'`) fait relire aussitôt. ⚠️ Elle est **bridée à
  une relecture toutes les 2 s** : n'importe qui peut émettre sur ce canal, et
  sans ce frein un client modifié ferait relire la liste à toute la salle en
  boucle. Ce n'est PAS un ordre réservé - poser une question est le droit de tous.
- **Le compteur de l'onglet** = les questions **en attente**, pas le total : c'est
  la file de la séance, pas l'archive.
- ⚠️ **Les onglets du panneau DÉFILENT à toutes les largeurs** (c'était la règle
  sous 900px) : ils sont désormais jusqu'à cinq, et « Communicateurs » à 80px se
  serait coupé en plein mot.
- Tout est construit en `textContent` (`elt()`) : question, nom d'auteur et
  intitulé de sondage viennent de participants.

### « Séances à modérer » (`/moderation`) = un espace de travail, pas une page

Un seul mot pour une seule chose : le rôle s'appelle **modérateur** partout
ailleurs (`moderator_email`, `MeetingRole::Moderator`, pastille de la salle,
`ModerationController`). L'interface disait « animer » - d'où `/animation` et
« Séances à animer », seuls endroits à employer un troisième verbe. Tout est
aligné sur **modérer** ; `/animation` reste en **redirection** (signets, courriels).

- ⚠️ **La page est fermée à qui ne modère rien** (`abort_unless(isModeratorSomewhere)`).
  Elle n'était gardée que par `auth:account` : tout compte connecté l'ouvrait - vide,
  mais ouverte, et avec elle l'idée qu'il y a là des outils qui ne le regardent pas.
- **Le contenu suit le colloque sélectionné**, comme toutes les pages personnelles.
  ⚠️ **Sauf quand la sélection ne veut rien dire ici** : un modérateur est rapproché
  par **e-mail** et n'est pas forcément **inscrit** à l'édition qu'il modère - le
  sélecteur ne la lui proposerait donc jamais (`CurrentColloque::selectable()` part
  des `Participant`), et la page resterait vide à jamais. Dans ce cas on montre tout
  ce qu'il modère, chaque carte portant déjà le nom de son colloque.
- **Quitter la visio ramène le modérateur à ses séances** (`backUrl`), pas à la page
  publique de déroulement : il sort d'une séance pour enchaîner sur la suivante ou
  pour en écrire le rapport. ⚠️ Le test porte sur **`EventSession::isModeratedBy()`**
  (la séance confiée) et **non sur le rôle en salle** : le staff y est modérateur
  aussi, et n'a pas d'espace personnel où atterrir - il tomberait sur un 403.
- ⚠️ **Les cartes ne portent PLUS le bouton « Espace d'échange (Q&A / sondages) », ni la
  LISTE des communications programmées** (elle pouvait être longue), ni les formulaires
  « Valider le passage » par communication. Les communications d'une séance vivent sur
  leur **propre page** (`account.passage.edit`, `/compte/seances/{session}/passage`), sur
  le modèle de la Présence : un bouton **« Communications programmées »** par carte (avec
  le NOMBRE de communications ; **toujours consultable** - on peut voir le programme de sa
  séance avant qu'elle commence), qui ouvre une page listant les communications ET
  permettant d'en valider le passage. `SessionPassageController::edit` (garde
  `SessionPresence::canManage` : modérateur **ou** rapporteur) ; le constat passe toujours
  par `session.passage` (même garde horaire serveur). (Les échanges Q&A/sondages se
  tiennent **exclusivement dans la salle de visio** - la page autonome a été supprimée,
  cf. « Les échanges = EXCLUSIVEMENT dans la visio ».)
- ⚠️ **Un modérateur ne valide pas un passage avant l'heure de sa séance** : avant le
  début, la page affiche la liste des communications (consultable) mais **masque les
  boutons de validation** (bandeau « Validation pas encore ouverte », pastille « À valider
  dès le début » par ligne) ; `SessionPassageController::store` refuse de toute façon
  (`abort_unless($session->hasStarted())`). Tests : `CommunicationPassageTest` (page +
  garde horaire), `ModerationPageTest` (le bouton renvoie vers la page).
- Tests : `tests/Feature/ModerationPageTest.php`.

### Visio : clôture automatique, isolation des salles, historique des fils

- **Statut de visio réconcilié sur l'HORLOGE** (`programmée → en direct → terminée`),
  sans intervention manuelle. ⚠️ `EventSession::isLive()` lit une colonne **stockée**
  `meeting_status` : rien ne la faisait évoluer avec le temps, donc une séance restait
  « Programmée » jusqu'à ce qu'on la bascule à la main, et une séance de la veille restait
  joignable en « En direct ». Source unique : **`EventSession::syncMeetingStatus()`**, qui
  s'appuie sur deux fenêtres :
  - **Ouverture** (`meetingLiveDue()`) : une visio **programmée** passe « en direct » dès
    `starts_at - meetings.auto_live_lead_minutes` (défaut **30 min** avant le début ; repli
    début de journée si l'heure manque).
  - **Clôture** (`meetingWindowOver()`) : une visio **programmée ou en direct** dont la fin
    effective (`ends_at` ?? fin de journée de `session_day`/`starts_at` ?? `starts_at+2h`) est
    dépassée de `meetings.auto_close_grace_minutes` (défaut **120 min**) passe « terminée »,
    ce qui coupe l'accès (`canJoin`/token/chat-write → 403).
  ⚠️ **N'avance jamais à rebours** : on ne redescend pas « en direct » → « programmée » (le
  staff peut ouvrir une salle en avance), et on ne touche ni à « aucune » (pas de visio) ni à
  « terminée ». Une séance **sans horaire** n'est ni ouverte ni fermée. Deux déclencheurs : la
  **commande planifiée** `meetings:sync` (`routes/console.php`, toutes les 5 min) **et**
  l'**auto-réparation à l'accès** (`MeetingController::join`/`token`, `MeetingChatController`
  chemin écriture, `ModerationController`, onglet Programme **Sessions** et **Discussions**) -
  pour que l'état suive l'horloge même sans planificateur (ex. en dev). ⚠️ `isLive()` **n'est
  pas** modifié : c'est le statut stocké, désormais tenu à jour, qui décide. Tests :
  `MeetingAutoCloseTest`.
- ⚠️ **Accès à la visio d'une séance = PUBLIC par défaut.** `EventSession::meetingVisibilityEnum()`
  retombe sur `MeetingVisibility::Public` (et la colonne `event_sessions.meeting_visibility`
  naît à `public`, migration `2026_10_06_000200`) : tant que le gestionnaire n'a pas restreint
  l'accès « Participants inscrits » (onglet VISIO du programme, `admin.program.sessions.meeting-visibility`),
  la salle est ouverte à tous. ⚠️ **La FORMATION, elle, reste réservée aux inscrits** par
  nature (`Formation::meetingVisibilityEnum()` défaut `Participants`, et sa visio n'a pas de
  mode public) - ne pas aligner les deux. Un test qui veut exercer l'accès restreint d'une
  séance doit poser `meeting_visibility => 'participants'` explicitement (cf. `SessionQaTest`,
  `MeetingTest`). Tests : `MeetingTest`.
- **Page « hors salle » = SALLE D'ATTENTE adaptée au statut** (`meeting/join.blade.php`,
  branche hors LiveKit). Elle n'affiche plus un message générique « La visio n'est pas en
  cours / Revenez à l'heure prévue » : le contrôleur passe `meetingStatus` (enum) et la vue
  rend une carte par état - **programmée** (icône agenda + « Début prévu » + la page
  **se recharge toute seule toutes les 60 s** pour entrer dès l'ouverture auto de la salle),
  **terminée** (replay si dispo), **réservée** (accès inscrits), **connexion requise**
  (invité sur séance live réservée), **salle en préparation** (live sans lien fournisseur),
  **aucune visio**. Pastille de statut dans l'en-tête (`MeetingStatus::pill()/label()`),
  charte UK (`.mlobby-*`, styles en tête de la branche). ⚠️ **Pas de `use` dans le bloc
  `@php` d'une vue** (erreur de compilation « unexpected token use ») : l'enum est écrite en
  nom complet `\App\Enums\MeetingStatus::…`, y compris dans les `@if`.
- ⚠️ **Nom de salle TOUJOURS unique par séance** (`EventSession::meetingRoom()` /
  `Formation::meetingRoom()`). `meeting_room` n'est qu'une **étiquette** : s'il était employé
  tel quel comme nom de salle LiveKit/Jitsi, deux séances portant la même (« Salle 3 »
  recopié) partageraient la même salle - participants, chat instantané et tableau blanc de
  l'une débordant chez l'autre. On suffixe donc toujours par l'événement + la séance : avec
  **2 à 6 salles simultanées**, aucune ne collisionne. (Salles LiveKit éphémères → renommer
  n'a pas d'effet de bord.) Le « reset » au changement de salle est déjà assuré par
  l'architecture (onglet par séance `geves-visio-<key>-<id>`, rechargement complet,
  `room.disconnect()` au départ, fil archivé **par séance**). Tests : `MeetingTest`.
- **Historique des fils de discussion (back-office)** : onglet **« Discussions »** du module
  Programme (`?tab=discussions`, cap `editions_read`). Liste les séances ayant une visio ou
  au moins un message (nombre de messages, dernier) ; `admin.program.discussions.show`
  rend le fil complet, `admin.program.discussions.export` le `.txt`. ⚠️ **Lecture directe de
  `MeetingMessage`** (indépendante de `canJoin`/live) → consultable **après la visio ET après
  l'événement**, là où l'API de la salle (`meeting.chat.*`) reste gardée par l'accès à la
  salle. Mise en forme texte partagée : **`App\Support\MeetingTranscript::toText()`** (réutilisée
  par l'export de la salle). Tests : `ProgramDiscussionsTest`.

- **Le nombre de connectés, dans la barre du haut.** Il ne se lisait **nulle
  part** en mosaïque : le compte du bandeau n'existe qu'en vue présentateur, et
  seulement une fois celui-ci déplié. La barre du haut, elle, ne disparaît dans
  aucune disposition ni à aucune largeur. ⚠️ Le compte vient de `people`, qui
  suit les arrivées et les départs - pas des vignettes, dont le nombre dépend de
  ce qui est AFFICHÉ. Le chiffre se lit seul ; la phrase qui le nomme
  (`phraseConnectes`, accord compris) est dans son `title` / `aria-label`.
- ⚠️ **Le bandeau des participants part REPLIÉ, et rien ne l'ouvre tout seul.**
  Il se dépliait à chaque arrivée - pour qu'une scène vide ne cache pas trois
  personnes connectées - mais il s'ouvrait alors par-dessus la séance sans
  qu'on l'ait demandé, et il fallait le refermer à la main. Son en-tête porte
  déjà le compte (« Participants (3) ») ; l'ouvrir est un geste. La bascule
  **Vue** n'y touche plus non plus : elle le **refermait de force** au retour
  en vue présentateur, y compris pour qui venait de l'ouvrir. Son ouverture
  n'appartient qu'à son propre bouton. Cas de garde dans `MeetingTest`.
- **Le nom se replie dans le bandeau** : la carte n'y fait que 150px (118 puis
  96 sur téléphone), et l'étiquette l'occupait presque entière pour un nom
  tronqué au tiers. Ne reste que l'état du micro ; le nom revient **au survol**
  de la carte. ⚠️ `max-width` et non `display:none` - il est replié, pas retiré,
  donc toujours énonçable par un lecteur d'écran. ⚠️ `focus-within` pour le
  clavier (couper le micro de quelqu'un sans le nommer), et **au doigt il reste
  affiché** : là où le survol n'existe pas, un nom qui ne se montre qu'au survol
  est introuvable.
- **Entrer et tenir l'horaire** (audit visuel, quatrième lot) :
  - **Écran d'entrée** (`ecranDEntree`) : aperçu caméra, **niveau du micro**,
    choix des périphériques, avant de se connecter. ⚠️ Seulement pour qui
    **diffuse** : un auditeur ne publie ni son ni image, lui dresser un mur
    retarderait tout le monde pour rien. ⚠️ Et les défauts **reprennent ce que la
    salle faisait déjà** - elle ouvrait caméra ET micro d'office, sans rien
    demander ; l'écran ne change pas ce qui se passe, il le montre et le rend
    révocable. ⚠️ L'aperçu doit être **arrêté avant de connecter** (`fermeApercu`)
    sinon la caméra reste prise et LiveKit ne peut plus l'ouvrir.
  - Le périphérique choisi est porté dans `videoCaptureDefaults.deviceId` /
    `audioCaptureDefaults.deviceId` : sans cela, LiveKit reprend celui que le
    navigateur juge par défaut - pas celui qu'on vient de vérifier. Les choix sont
    gardés en `localStorage`, lecture ET écriture sous `try` (navigation privée).
  - **Temps de parole** (`chrono`) : le créneau du programme donne la durée prévue
    (`minutes`, `null` sans horaire de fin - il n'y a alors rien à dépasser).
    L'alerte tombe au **dernier cinquième**, pas au dépassement : à ce moment-là,
    la personne conclut déjà.
    - ⚠️ Le modérateur tient le compte et diffuse un **nombre de secondes**, pas
      une heure de départ : deux machines n'ont pas la même horloge, et un écart
      afficherait un dépassement imaginaire. Réémis toutes les 5 s, et aussitôt
      pour un arrivant.
    - ⚠️ `floor_time` est un **ordre réservé au modérateur** (cf. `ORDRES_RESERVES`)
      - sinon n'importe qui affiche « 25 min » chez tout le monde et fait écourter
      une communication dans les temps.
    - ⚠️ Visible pour le modérateur et pour **qui a la parole** seulement : les
      deux qui peuvent agir dessus.
- **Ce que la salle doit ÉNONCER et épargner** (audit visuel, troisième lot) :
  - **`prefers-reduced-motion`** était ignoré dans la salle, alors que le reste du
    projet le respecte (`ukc.css`, `admin.css`, `public.css`). Deux animations
    tournaient **en boucle** pendant toute une séance : le halo du bouton
    d'enregistrement et la pastille d'état. ⚠️ Rien n'est caché, seul le mouvement
    s'arrête : le rouge et le mot « REC » disent l'enregistrement, le texte à côté
    de la pastille dit ce qu'on attend. L'anneau reprend son contour entier, un
    anneau amputé n'ayant plus de sens une fois immobile.
  - **Ce que la salle dit à voix haute** : `role="status"` sur l'infobulle et sur
    l'état de connexion, `role="alert"` sur le bandeau de reprise - perdre la salle
    interrompt ce qu'on faisait, une coupure de micro décidée par le modérateur
    non. « Votre micro a été coupé » ne parvenait à personne qui ne regarde pas.
  - **Échap ferme ce qui est ouvert**, du plus intérieur au plus extérieur :
    confirmation, choix de réaction, menus, réponse en cours, puis le tiroir
    (**sous 900px seulement** : au-dessus il fait partie de la page). Il ne fermait
    que la confirmation. ⚠️ L'ordre est la règle : on ne referme pas le panneau de
    quelqu'un qui voulait fermer un menu.
  - ⚠️ **Le choix de réaction n'était PAS rogné sur le premier message** comme
    l'annonçait l'audit : les 14px de marge du fil l'absorbent. Le vrai cas,
    mesuré, est un fil **remonté** où le bord haut d'un message affleure celui du
    panneau. D'où une mesure dans `ouvreChoixReaction`, et non un comptage.
- **Ce que la salle doit DISTINGUER** (audit visuel, second lot) :
  - **Une teinte par personne**, tirée de son nom (`teinteDuNom`). Les pastilles
    portaient toutes le même dégradé : huit caméras fermées, huit disques
    identiques. ⚠️ Seule la **teinte** varie ; la feuille de style fixe saturation
    et clarté basse pour que le blanc des initiales garde son contraste quelle que
    soit la couleur tirée. Et la pose passe par **un seul** `poseAvatar()` : une
    teinte écrite à deux endroits sur trois donnerait deux couleurs à la même
    personne dans la même salle.
  - **Le rôle sur la vignette**, pastille dans la ligne du nom, pour le modérateur
    et le communicateur **seulement** - « Auditeur » répété quarante fois n'apprend
    rien et mange la place du nom. Masqué dans le bandeau (150 px : le nom passe
    d'abord).
  - **La ligne de la liste se range par un ÉCART**, plus par des « · ».
    ⚠️ Et la règle s'écrit `#visio-room .person .meta > span`, avec le **chevron** :
    sans lui elle est plus spécifique que celles du rang, du micro et du réseau
    qu'elle contient, et les repeint tous en gris - un rang gris sur fond or.
    Vérifié en lisant les couleurs calculées, pas à l'œil.
  - **La barre d'outils porte des mots** (`data-nom`, un mot) : les intitulés ne
    vivaient que dans `title`, qui **n'existe pas au doigt**. ⚠️ `data-nom` et non
    `title` : « Plus d'options » sous une icône de 44 px disloquerait la barre ; le
    `title` entier reste pour le survol, le lecteur d'écran et la feuille « Plus
    d'options ». Les mots s'effacent en téléphone couché, où la hauteur rendue à la
    scène vaut mieux qu'un mot.
  - ⚠️ **Deux « états vides » annoncés à l'audit n'existent pas** : le fil porte
    toujours son message d'ouverture, et la liste contient toujours au moins
    soi-même. Seul le panneau des communicateurs pouvait s'ouvrir nu.
- **Ce que la salle doit MONTRER** (audit visuel, premier lot). Quatre manques qui
  ne tenaient pas à une règle mais à ce que l'œil cherche pendant une séance :
  - **Qui parle** (`ActiveSpeakersChanged`, qui n'était pas écouté) : liseré vert
    sur la vignette, ligne teintée dans le panneau. ⚠️ La règle est posée APRÈS
    `hand-up` dans la feuille de style : à spécificité égale, la dernière gagne -
    une main levée est une demande, la parole est un fait. Et la fonction
    **ne reconstruit rien**, elle déplace une classe (l'événement tombe plusieurs
    fois par seconde ; rebâtir la liste ferait sauter le défilement du panneau).
  - **Son propre reflet** : `scaleX(-1)` sur sa seule image. Sans lui, lever la
    main droite l'affiche à gauche. ⚠️ Jamais sur un partage d'écran (le texte
    partirait à l'envers) ni sur l'image des autres ; la bulle PiP porte TANTÔT sa
    caméra TANTÔT celle du présentateur distant, d'où la classe `soi`, posée sur
    une comparaison de pistes et non sur une devinette.
  - **Le réseau d'un participant** (`ConnectionQualityChanged`, pas écouté non
    plus) : une image qui gèle ne disait pas si l'autre décrochait ou soi.
    ⚠️ Rien ne s'affiche tant que tout va bien (cf. `niveauReseau`) : un indicateur
    posé en permanence sur tout le monde ne se remarque plus. Et **deux dessins**,
    pas un seul recoloré - à 13 px la couleur seule ne sépare pas « instable » de
    « perdu », et l'ambre des barres faibles est celui de la main levée d'à côté.
  - **Le fil ne s'arrache plus** : il ne suit le bas que si on y était déjà
    (`enBasDuFil`, mesuré AVANT d'ajouter le message), sinon un bandeau annonce ce
    qui attend. ⚠️ Deux comptes distincts à ne pas confondre : `nonLus` (le fil
    n'était pas sous les yeux, pastille sur l'onglet) et `aLireEnBas` (il l'était,
    mais on lisait plus haut).
- **Tests JavaScript : Vitest** (`npm run test:js`, `npm run test:js:watch`).
  Premier outillage de test côté client du projet - jusque-là, **aucune ligne de
  JS n'était couverte**.
  - **`resources/js/meeting-pure.js`** rassemble les règles de la salle qui ne
    dépendent ni du DOM, ni de LiveKit, ni d'une connexion : initiales, troncature,
    identifiants, lecture de la métadonnée, **table des ordres autorisés**, file des
    mains levées, grille de la mosaïque. ⚠️ Elles vivaient dans la fermeture de
    `meeting.js` (~1 600 lignes), donc hors d'atteinte de tout test - alors que ce
    sont elles qui portent les décisions les plus corrigées de ce module.
  - **Déplacer une décision dans ce fichier, c'est la rendre vérifiable.** Les cas
    de `meeting-pure.test.js` ne sont pas décoratifs : chacun fige un défaut
    constaté en salle réelle (un ordre forgé par un simple participant, quatre
    visages écrasés sur une rangée, un rang de main qui laisse un trou, un nom
    hostile dans des initiales).
  - `meeting.js` ne garde que des **adaptateurs** minuscules (par exemple
    `ordreRecevable(type, participant)` qui donne le rang à la règle pure).
  - ⚠️ `meeting.js` **ne passe pas `prettier --check`**, et ne le passait pas avant :
    le reformater en bloc noierait toute relecture. Les fichiers neufs, eux, sont
    conformes.
- **Le fil, une fois conservé, demande ce qui va avec** (troisième lot de l'audit) :
  - **Messages non lus** : compteur sur l'onglet Chat **et** pastille sur le bouton
    « Panneau ». ⚠️ Le tiroir est fermé par défaut sous 900px et le panneau s'ouvre
    souvent sur un autre onglet : un message arrivait sans que rien ne le signale.
    Le fil **rejoué** à l'arrivée ne compte pas comme non lu.
  - **Modération** (`meeting.chat.delete`, modérateur de la séance) : un message
    déplacé resterait sinon dans le fil pour **toutes les prochaines arrivées**. Le
    retrait part aussi sur le canal de données, pour disparaître en direct.
  - **Compte rendu** (`meeting.chat.export`, modérateur) : le fil s'emporte en texte
    daté. Les questions posées en séance ont une valeur pour le PV, et rien ne
    permettait de les en sortir.
  - ⚠️ **Une communication se rattache à son auteur par IDENTIFIANT**
    (`metadonnee()` → `accountId` dans la métadonnée du jeton, `matchComm`). Le
    rattachement se faisait par **sous-chaîne du nom de famille** : deux homonymes
    dans la salle, et le modérateur donnait la parole à la mauvaise personne. Le nom
    ne subsiste qu'en filet, pour les co-auteurs sans compte.
  - **Le micro se lit en icône** dans la liste, pas en toutes lettres : « micro
    coupé » répété sur chaque ligne pesait plus que le nom qu'on y cherche. Le texte
    reste dans `title`/`aria-label`. ⚠️ Le `innerHTML` du porteur d'icône ne
    contredit pas la règle des listes : il ne reçoit qu'une **constante du code**.
  - `#chatLog` est une zone `role="log" aria-live="polite"` : un lecteur d'écran
    annonce les messages sans qu'on ait à revenir lire le panneau.
- **Tenue de la salle en conditions réelles** (second lot de l'audit) :
  - **`App\Meetings\MeetableRegistry`** est le seul endroit qui dit quels objets ont
    une salle (`session`, `formation`) et traduit dans les deux sens, URL ↔ objet.
  - ⚠️ **On n'écrit dans le fil que pendant la séance.** L'API ne vérifiait que le
    rôle, alors que la page exige rôle **et** séance en direct : on pouvait
    alimenter le fil d'une séance terminée en appelant l'adresse directement. La
    LECTURE, elle, reste ouverte à qui peut entrer.
  - ⚠️ **L'envoi d'un message est idempotent** (`uid` unique par salle, index en
    base) : un réseau qui bégaie rejouait l'envoi, et deux lignes portaient le même
    identifiant - une réaction ne savait plus laquelle viser.
  - ⚠️ **Le débit du fil se compte PAR PERSONNE, jamais par IP**
    (`AppServiceProvider::limiteLeFilDeVisio`, limiteurs `visio-chat` et
    `visio-reaction`). Une cérémonie publique se suit depuis le campus, donc depuis
    quelques adresses partagées : un limiteur par IP ferait taire toute une salle
    parce qu'une personne écrit vite.
  - **Reprise après coupure** (route `meeting.token`, `reconnecte()`) : `Disconnected`
    signifie que le SDK a **renoncé**, et il ne restait qu'à recharger la page en
    pleine séance. Un bandeau propose « Reconnecter », qui **redemande un jeton**
    (celui d'origine a pu expirer - 2 h - et c'est précisément le cas des séances
    longues) et republie les pistes locales. Les droits sont recalculés : un rôle
    retiré ne revient pas par la porte du renouvellement.
  - ⚠️ **Après une reconnexion, il faut REPRENDRE l'état de la salle**
    (`adopteParticipantsPresents`) : les événements d'arrivée ne rejouent pas pour
    les personnes déjà présentes. Sans cela la salle revenait vide de tout le monde
    sauf de soi - défaut trouvé en essayant la reconnexion, pas en relisant le code.
  - **Un délai borne l'API Egress** (`connectTimeout(5)`/`timeout(15)`) : sans lui,
    un service d'enregistrement muet immobilisait le worker PHP, et c'est le
    modérateur qui attendait en pleine séance.
- **Sécurité de la salle de visio** - trois règles qui ne se négocient pas :
  - ⚠️ **Aucune liste de la salle ne se construit en collant du texte dans du
    balisage.** Participants, communicateurs et présence étaient composés par
    concaténation puis posés en `innerHTML`, avec dedans des **noms et des titres
    venus de la base**. Le profil n'impose qu'une longueur maximale : un inscrit
    qui écrivait `<img src=x onerror=…>` dans son nom faisait exécuter son code
    chez **tous les participants**, et dans le panneau du modérateur. Tout passe
    par `elt()` (`textContent`). Le seul endroit qui compose encore du balisage
    est la boîte de confirmation, dont le texte inséré passe par `texteEchappe()`.
    `initials()` filtre aux lettres et chiffres, pour la même raison.
  - ⚠️ **Les ordres reçus sur le canal de données sont vérifiés** (`ORDRES_RESERVES`,
    `ordreRecevable`). Rien ne les contrôlait : n'importe quel participant pouvait,
    depuis la console de son navigateur, couper le micro de toute la salle, retirer
    la parole, marquer une communication effectuée ou afficher un faux bandeau
    « enregistrement ». Le canal est **public pour tous les connectés** : c'est au
    RÉCEPTEUR de ne pas obéir à n'importe qui. Le rang est lu dans la **métadonnée
    du jeton** (`roleAnnonce`) et surtout **pas** dans `people[].role`, que ces mêmes
    messages modifient - on authentifierait un ordre avec ce que le précédent a écrit.
  - ⚠️ **Un visiteur sans compte doit rester distinct des autres**
    (`MeetingChatController::signature`) : tous partageaient la clé `anon`, si bien
    que le deuxième visiteur **retirait** la réaction du premier. La session HTTP les
    distingue, hachée pour ne pas recopier un identifiant de session dans une table.
- **Conduite de séance dans la salle** (`resources/js/meeting.js`) :
  - **Le micro s'ouvre AVEC la présentation.** Donner la parole promeut en
    `presenter` (message `mod_role`) : le poste visé ouvre alors son micro de
    lui-même (`ouvreMonMicro`). ⚠️ Sinon la salle entière regarde quelqu'un parler
    en silence, et personne ne sait d'où vient la panne. La personne reste libre
    de se recouper juste après : c'est son micro. (À l'entrée dans la salle, un
    modérateur ou un communicateur a déjà micro **et** caméra ouverts.)
  - ⚠️ **L'état des micros se LIT de la salle, il ne se devine pas**
    (`syncMicStates` / `microActif`, sur `TrackMuted`/`TrackUnmuted`/
    `TrackPublished`/`TrackUnpublished`). `micOn` n'était écrit que par les actions
    du modérateur : il disait ce qu'il avait **demandé**, pas ce que la personne
    faisait. Quelqu'un qui se coupait restait affiché « micro ouvert », et le
    bouton proposait de couper un micro déjà coupé.
  - **Mains levées numérotées** (`leveLaMain` / `mainsLevees` / `refreshHands`) :
    badge **✋ n** sur la tuile, rang dans la liste, compteur sur l'onglet
    Participants, et une infobulle à chaque main qui se lève. ⚠️ Le rang vient d'un
    **compteur local**, pas de l'horloge : deux postes n'ont pas la même heure,
    alors que les messages arrivent dans l'ordre d'émission. Il se **recalcule** à
    chaque affichage, donc une main baissée renumérote les suivantes au lieu de
    laisser un trou. ⚠️ Une main levée dans un panneau fermé n'existe pas : d'où la
    tuile et l'onglet, pas seulement la liste.
  - **Les gestes sans retour se confirment** (`confirme()`, boîte `#vModal`) :
    « Marquer effectuée » part à toute la salle et désactive le bouton. ⚠️ Pas
    `window.confirm` : il gèle la page (la visio continue sans qu'on puisse rien
    faire), sort visuellement de la salle et n'est pas garanti en contexte embarqué.
  - **Le panneau latéral se règle en largeur** (variable `--vside`, poignée
    `#sideGrip`, `poseLargeurPanneau`) : 400px par défaut, **360 au minimum**,
    jamais plus de la moitié de la salle, retenue d'une séance à l'autre
    (`localStorage`), double-clic pour revenir au défaut, flèches au clavier.
    ⚠️ 340px fixes ne suffisaient pas : ce panneau porte trois usages très
    différents (chat, participants, pointage de présence avec nom **et**
    institution), et aucune largeur ne les sert tous. ⚠️ Au redimensionnement de
    la fenêtre on repart de la largeur **retenue** et non de la courante : réduire
    la fenêtre borne le panneau, mais ne doit pas effacer le choix de la personne
    quand la place revient.
  - **L'étiquette des vignettes porte le micro** (`.mic-dot`, `majMicroTuiles`) :
    nom en 11px (12,5 occupait la moitié de la vignette et tronquait les noms au
    tiers) suivi d'une pastille micro ouvert / coupé. ⚠️ Elle est **cliquable** :
    le modérateur coupe ou rend un micro depuis la vignette, chacun depuis la
    sienne (qui repasse par le bouton de la barre, lequel porte les autorisations
    navigateur). Couper un micro demandait sinon d'ouvrir le panneau, de trouver
    la personne, puis son bouton.
  - **Le fil du chat est ARCHIVÉ et rejoué à l'arrivée** (`meeting_messages`,
    `MeetingChatController`, routes `meeting.chat.*`). ⚠️ Le chat ne vivait que sur
    le canal de données : qui rejoignait en cours de séance - ou rechargeait sa
    page - trouvait une salle muette, et les questions posées avant lui
    n'existaient plus nulle part. Le canal de données reste pour l'**instantané**
    (les personnes déjà connectées), la base pour la **mémoire**. Un trait
    « Vous rejoignez la salle » sépare le fil rejoué de la séance en cours.
  - ⚠️ **Le nom de l'auteur est calculé par le SERVEUR**
    (`Meetable::meetingDisplayName`, désormais publique et au contrat) : reçu du
    client, chacun signerait ses messages du nom d'un autre. Même règle que le nom
    affiché dans la salle, sinon le même message aurait deux auteurs.
  - **Réactions** (`meeting_message_reactions`, `MeetingMessage::EMOJIS`) : liste
    d'emojis **fermée** côté serveur, et **une seule réaction par personne et par
    message** - unicité `(message, réacteur)` tenue par la **base**. ⚠️ L'unicité
    portait d'abord aussi sur l'emoji : la même personne empilait 👍 ET ❤️ ET 👏,
    et pesait à elle seule plus que trois autres. Le même emoji retire la sienne,
    un autre la **remplace** (la réponse porte `replaced`, pour que les autres
    salles ajustent leur décompte sans recharger le fil). ⚠️ Le « réacteur » est
    le **compte** (ou le membre du staff), jamais l'identité LiveKit : celle-ci
    change à chaque connexion, et la même personne compterait deux fois en
    rechargeant sa page. L'affichage est optimiste, puis écrasé par le décompte
    du serveur.
  - **Le fil suit l'accès à la salle** : `Meetable::canJoin()`, et un `type`
    résolu depuis une **liste fermée** (`MeetingChatController::TYPES`) - jamais
    une classe construite à partir de l'URL.
  - **Chat : on répond à un message** (`preparerReponse` / `addChat`). La citation
    **voyage avec le message** (auteur + extrait de 120 car.) au lieu d'être
    retrouvée par identifiant : ⚠️ un participant arrivé APRÈS l'original n'a pas
    l'historique de la salle (rien n'est stocké côté serveur) et ne comprendrait
    pas la réponse. Cliquer la citation remonte à l'original quand on l'a.
  - ⚠️ **Le chat se construit en `textContent`, jamais en `innerHTML`** : message,
    nom d'affichage et citation viennent d'autres participants. L'ancienne version
    remplaçait `<` et `>` dans une chaîne injectée en `innerHTML` - une liste
    noire, donc fausse par construction.
  - ⚠️ Ces ordres passent par le **canal de données** (coopératif, tous les postes
    exécutant notre code), pas par l'API serveur de LiveKit : un client modifié
    pourrait les ignorer. Acceptable pour une conduite de séance, à revoir si la
    coupure de micro devient une mesure de police.
- **Rôle dans la salle de visio : il voyage en MÉTADONNÉE du jeton**
  (`LiveKitProvider::token()` → `metadata` = `{"role":…}`, vocabulaire unique dans
  **`MeetingRole::roomRole()`** : `moderator` / `presenter` / `audience`).
  - ⚠️ **LiveKit ne diffuse rien d'autre.** Les droits du jeton (`video.canPublish`,
    `roomAdmin`) ne sont connus que du serveur et du porteur : **les autres
    participants ne les voient pas**. Sans métadonnée, chaque client retombait sur
    « auditeur » **pour tout le monde** - le modérateur de la séance apparaissait
    auditeur dans la liste, et la scène, n'ayant aucun présentateur à montrer,
    annonçait « en attente de la caméra » **pendant que les caméras tournaient**.
  - ⚠️ Un changement de jeton ne rattrape **pas** les connexions en cours : une
    salle ouverte avant la correction garde ses anciens rôles jusqu'au rechargement.
  - **La scène ne doit jamais mentir sur ce qu'elle voit** (`showCurrentPresenterInStage`) :
    à défaut de présentateur désigné elle montre **quelqu'un qui diffuse vraiment**
    (`premierDiffuseur`, modérateur d'abord) plutôt qu'un écran vide ; et elle ne dit
    « caméra désactivée » que si la publication est **coupée** - une piste pas encore
    abonnée donne « connexion de la caméra… », corrigée seule à l'arrivée de l'image.
  - ⚠️ **Couper sa caméra n'est pas se désabonner** : `TrackMuted`/`TrackUnmuted`
    doivent être écoutés, sinon la tuile garde une image gelée et la scène reste sur
    l'état précédent. Et on retire alors l'image avec **`clearVideo`, jamais
    `clearMedia`** : le son de la personne est attaché à la **même tuile** et partait
    avec l'image - éteindre sa caméra rendait muet.
- **Salle de visio sur téléphone et tablette** (`resources/views/meeting/join.blade.php`,
  `resources/js/meeting.js`) : la salle est une **application plein écran** (`position:fixed`),
  pas une page qui défile. Trois seuils, dans cet ordre dans la feuille :
  **≤900px** le panneau latéral devient un **tiroir** par-dessus la scène (voile + croix,
  fermé au départ), **≤767px** la barre d'outils se compacte et ses commandes secondaires
  passent dans une feuille **« Plus d'options »**, **≤640px** la barre du haut se réduit
  (pastille d'état sans texte, « Quitter » du haut retiré - il est déjà en bas).
  Un bloc **téléphone couché** (`max-height:480px`) amincit les deux barres : c'est
  l'orientation dans laquelle on regarde une visio.
  - ⚠️ **Les hauteurs des deux barres sont des variables** (`--vtop`, `--vtoolbar`) :
    une dizaine de règles s'y accrochent (bandeau d'état, menus, infobulle, panneau de
    volume). Écrites en dur, elles laissaient tous ces éléments à l'ancienne hauteur dès
    que les barres se compactaient.
  - ⚠️ **L'adaptation étroite doit venir EN DERNIER dans la feuille.** Elle était écrite
    avant `.side{display:flex}` : à spécificité égale c'est la dernière règle qui gagne,
    donc la base rouvrait le tiroir. Sur téléphone, le panneau s'affichait **tout seul
    par-dessus les visages** dès l'entrée dans la salle, et la bascule marchait à l'envers.
    Une adaptation responsive ne se place pas « près de la règle qu'elle corrige ».
  - ⚠️ **Les commandes de la feuille ne sont pas RECOPIÉES, elles sont DÉPLACÉES**
    (`rangeSecondaires`, `SECONDAIRES`) : un second jeu de boutons voudrait dire deux
    écouteurs et deux états à tenir d'accord (micro coupé, enregistrement en cours).
    Leur `title` sert d'étiquette dans la feuille (`content:attr(title)`) - rien à écrire
    deux fois. **Au retour en grand écran, on remet du dernier au premier** : l'ancre d'un
    bouton est souvent le bouton suivant, qui doit déjà avoir retrouvé sa place.
  - ⚠️ **Rien ne doit se comprimer jusqu'à zéro** : la droite de la barre du haut
    (`flex-shrink:0`) réclamait 405px sur 375 et écrasait le **titre de la séance à zéro
    pixel** tout en débordant de l'écran. D'où `flex:1` à gauche **et** du contenu retiré
    à droite - l'un sans l'autre ne suffit pas.
  - **`100dvh` + `env(safe-area-inset-*)`** : `inset:0` fait confiance au grand viewport,
    la barre du navigateur recouvrait la barre d'outils et « Quitter » devenait
    inatteignable. `overscroll-behavior:contain` évite qu'un défilement du chat déclenche
    « tirer pour recharger ».
  - Seuil de la barre d'outils à **767px et non 640** : les dix commandes réclament près
    de 700px, ce que l'iPad en portrait (768) offre encore.
  - Garde-fou : `tests/Feature/MeetingTest.php` vérifie la présence des crochets
    (`sideScrim`, `btnCloseSide`, `btnMore`, `moreSlot`) - sans eux le script ne peut plus
    ni fermer le tiroir ni déplacer les boutons.
- **Logos de la plateforme** : réglables dans **Réglages › Général**, servis par
  **`App\Support\Branding`**. **Deux formes**, chacune avec une version pour
  fonds sombres **facultative** :
  - **`wide()` / `wideWhite()`** - lockup complet (marque + nom), là où la largeur
    existe : **pied de page public**, **connexion** et **création de compte**,
    **bandeau (hero) des pages d'événement**.
  - **`mark()` / `markWhite()`** - **monogramme carré**, là où le logo côtoie du
    texte ou se réduit à une colonne d'icônes : **en-tête public**, les **deux
    menus latéraux** (back-office, espace participant) y compris réduits, et la
    **barre de la salle de visio** (fond sombre → `markWhite()`). C'est
    aussi l'`apple-touch-icon` (icône carrée ; les favicons 16/32 restent figés,
    ils exigent ces tailles).
  - ⚠️ **Aucune surface ne redessine la marque elle-même.** La salle de visio
    affichait un « UK » écrit en dur dans le gabarit : le logo réglé dans
    Réglages changeait partout sauf là. Un carré de texte est vite écrit, et c'est
    exactement ce qui fait mentir la configuration.
  - ⚠️ **Choisir la forme par la place disponible, pas par le fond** : un lockup
    large dans un menu réduit à 78 px est illisible, un monogramme seul dans un
    pied de page perd le nom de l'établissement.
  - **Blanchiment automatique, en SECOURS seulement** : sans version claire
    téléversée, la surface sombre sert le **même fichier** avec la classe
    **`.logo-auto-white`** (`brightness(0) invert(1)`), posée quand
    **`Branding::whitensOnDark($cléClaire)`** est vrai. ⚠️ **Ce filtre met TOUTE
    la matière opaque en blanc** : les **évidements** (lettres détourées dans un
    écusson) disparaissent, le logo devient une silhouette pleine. Une vraie
    version claire est un **dessin** - aucun filtre ne la déduit du logo couleur,
    d'où la case facultative. Faux dès qu'une version claire existe (on respecte
    le dessin de l'admin) et faux sur les fichiers livrés : `logo-uk-white.png`
    est déjà dessiné en blanc, et blanchir le monogramme livré en ferait un aplat.
  - **Composition « monogramme + filet + nom »** (en-tête public et menus latéraux
    déployés) : `<img class="brand-logo--mark">` + `<span class="brand-rule">` +
    le texte. Le filet (2 px de large, 26 px de haut, centré) et le nom reprennent
    **`var(--ukc-blue)`** (`#223ea4`), la couleur du logo - c'est ce qui aligne
    les deux. Menu **réduit** : le filet et le texte disparaissent avec
    `.logo-sub` (même règle de masquage), il ne reste que le monogramme, centré.
  - **Repli** sur les fichiers livrés dans `public/img` si rien n'est téléversé ou
    si le fichier réglé a disparu - jamais d'image cassée. Les fichiers vivent sur
    le disque `public` (`branding/`) et le précédent est **supprimé** à chaque
    remplacement (un logo n'a pas d'historique, et l'ancien resterait servi
    publiquement). ⚠️ Ne plus écrire `asset('img/logo-uk.png')` en dur.
  - **Écran de réglages = une carte par forme** (`.ukc-logo-card`), chacune avec
    ses deux dépôts (`.ukc-logo-upload`, même grammaire que la photo de profil) :
    aperçu **sur le fond réel** - blanchiment compris, donc un mauvais rendu se
    voit avant d'enregistrer -, puis *Remplacer* et *Supprimer* (= rendre la main
    au logo livré ; il n'y a jamais « pas de logo »). La remise à zéro passe par
    un **champ caché** `{clé}_remove` piloté par le bouton, qui met aussi l'aperçu
    à jour. ⚠️ La **clé du réglage sert aussi de nom de champ** (`Branding::KEYS`,
    `Branding::FORMS` pour l'appariement clair → sombre) : une seule source.
  Tests : `tests/Feature/PlatformLogoTest.php`.
- **Habillage d'une édition = bandeau (hero) + bannière officielle**, réglés dans
  l'onglet **Apparence** de la fiche d'édition, l'un sous l'autre. Chacun accepte
  un **téléversement OU une URL** (une partie des éditions pointe vers une affiche
  hébergée ailleurs) ; **le fichier l'emporte**, c'est l'action la plus explicite.
  Source unique : **`App\Support\EditionArtwork::resolve()`** (fichier, URL saisie,
  valeur précédente, drapeau de suppression → valeur à stocker).
  - **Où ça vit** : le bandeau dans `edition.settings['hero_image']`, la bannière
    dans la **colonne `editions.banner_url`** (c'est une image de l'édition
    elle-même, reprise dans la liste des éditions passées).
  - ⚠️ **On ne supprime du disque que NOS fichiers** (`editions/` sur le disque
    `public`) : une URL externe ne nous appartient pas. Et ré-enregistrer la fiche
    sans toucher au champ ne doit rien supprimer - d'où la comparaison avec la
    valeur précédente dans `resolve()`.
  - ⚠️ La fiche d'édition poste **tous les onglets d'un coup** : un enregistrement
    qui ne renvoie pas le champ image ne doit pas effacer l'habillage (test de
    garde « garde l'habillage quand on enregistre un autre onglet »).
  - ⚠️ Le `<form id="editionForm">` est en **`enctype="multipart/form-data"`** :
    sans ça, les fichiers n'arrivent jamais et l'échec est silencieux.
  - **Bannière activable** : case `banner_enabled` (settings), **dans le bloc de
    l'image qu'elle gouverne** - elle vivait sous les couleurs, on la croyait
    absente. Le bloc passe en gris quand elle est décochée (`.artwork-field.is-off`).
    Section publique `#banniere` masquée en conséquence (défaut : affichée).
  - La bannière publique a un **espacement interne** (`padding` sur
    `.ukc-banner-frame`) : l'image est **montée dans le cadre**, le dégradé de la
    charte faisant passe-partout, plutôt que collée aux bords.
  - ⚠️ **`aspect-ratio` + `max-height` RÉDUIT la largeur** : `.ukc-banner-frame`
    (21/9, plafond 420 px) se dessinait à 980 px (= 420 x 21/9) et calée à gauche
    de son conteneur. Il faut une **largeur définie** (`width: 100%`) pour que ce
    soit la hauteur qui se recadre, l'image étant en `object-fit: cover`. Le piège
    vaut pour toute boîte à ratio dans un conteneur flex.
  - **Vidéo de fond du bandeau** : même bloc d'habillage (téléversement ou URL),
    MP4/WEBM. ⚠️ Trois branchements manquaient et la fonctionnalité ne marchait
    pas : la case **« Activer la vidéo de fond » était ignorée** (toute URL
    enregistrée se jouait), **`hero_video_muted` n'était jamais lu**, et le
    `type` du `<source>` était **codé en dur en `video/mp4`** (un .webm ne se
    lisait pas). Le contrôleur public ne passe `heroVideo` que si
    `appearance.hero_video_enabled`, et l'image du bandeau sert de **`poster`**
    pendant le chargement.
  - ⚠️ **Le plafond de téléversement est celui de PHP**, pas une constante :
    `EditionArtwork::maxUploadKb()` lit `upload_max_filesize`/`post_max_size` et
    sert **à la fois** la règle `max:` et l'aide affichée. Une règle plus
    généreuse serait un piège : PHP coupe la requête avant la validation et
    l'admin reçoit une page vide au lieu d'un message. En dev (Herd) la limite
    est de **2 Mo** - pour une vraie vidéo de fond, relever
    `upload_max_filesize` et `post_max_size` dans le `php.ini` de Herd, ou
    héberger la vidéo ailleurs et coller son URL.
  - ⚠️ **L'« Actualité (encart du hero) » a été SUPPRIMÉE** (`news_enabled`,
    `news_text`, `news_url`) : ces trois réglages s'enregistraient et se
    relisaient dans le formulaire, mais **aucune vue publique ne les affichait** -
    un aller-retour admin → admin, comme l'ancien déroulé éditorial `programme`.
    Ne pas les réintroduire : le besoin est déjà couvert deux fois, par le
    **bandeau promotionnel** (message court épinglé en haut de la page colloque)
    et par le **module Annonces** (`admin.announcements.*`, actualités datées,
    affichées sur la page Déroulement).
  - Le **bandeau promotionnel** (`settings['promo']['text'|'url'|'cta']`) était le
    cas inverse : **affiché** en haut de la page du colloque mais **éditable nulle
    part**. Il a désormais sa section dans l'onglet **Apparence**, à la place de
    l'encart supprimé. Vide, il ne s'affiche pas.
  Tests : `tests/Feature/EditionArtworkTest.php`.
### Concepteur de documents : la maquette DOIT survivre à l'enregistrement

Le document délivré ne ressemblait pas à celui dessiné dans le concepteur, pour
deux raisons distinctes.

1. **Le modèle enregistré était plus pauvre que la maquette.**
   `serializeLayout()` (`template-designer.js`) aplatit le DOM du concepteur en
   quatre types (`text`, `rect`, `image`, `qr`) : ⚠️ **tout ce qui n'est pas
   repris y est perdu**. Étaient jetés - et sont désormais conservés de bout en
   bout (JS → `LayoutSanitizer` → `LayoutRenderer`) : la **police**
   (`LayoutSanitizer::FONTS`, liste blanche par **clé** - jamais une déclaration
   CSS libre, qui finirait dans un attribut `style`), l'**italique**, le
   **souligné**, les **capitales**, l'**interlignage**, l'**interlettrage**, les
   **coins arrondis** et les **filets** d'un rectangle, et le **fond de page**
   (`bg` était codé en dur à `#ffffff`). ⚠️ Avant d'ajouter une possibilité au
   concepteur, l'ajouter aussi aux trois couches - sinon elle ne survit pas à
   « Enregistrer ».
   ⚠️ Une image n'était retenue que si son `src` était une **URL absolue** : un
   logo téléversé (`/storage/…`) retombait dans la branche « texte » et
   disparaissait. `LayoutSanitizer::src()` accepte désormais les chemins servis
   par le site, et refuse `data:`.
   ⚠️ **Et les images téléversées étaient embarquées en `data:` base64.** Elles
   survivaient à l'instantané de l'éditeur - donc elles restaient visibles dans
   le concepteur - mais `src()` refuse `data:` : l'élément était **jeté de la
   mise en page enregistrée**, et les logos disparaissaient du document délivré,
   en silence. Le concepteur **téléverse** donc ses images
   (`admin.documents.templates.upload` → disque `public`, `documents/templates/`)
   et n'enregistre que leur adresse. Une migration
   (`2026_10_02_000400_restore_document_template_images`) répare les modèles déjà
   enregistrés en récupérant les images de l'instantané.
   ⚠️ **L'image de FOND n'était pas enregistrée du tout** (`bg` ne portait que la
   couleur) : une attestation dessinée sur un fond illustré - le cadre couleurs
   UK, par exemple - ressortait sur du blanc. Elle a désormais sa clé `bg_image`
   (JS → sanitizer → renderer), et la migration
   `2026_10_02_000600_restore_document_template_background` la récupère depuis
   l'instantané pour les modèles déjà enregistrés (seul endroit où le fond avait
   survécu).
   ⚠️ **dompdf ne télécharge aucune image distante** (`enable_remote` est faux) :
   `LayoutRenderer::render(..., pdf: true)` résout les adresses servies par le
   site en **chemins de fichier**, sans quoi les logos sortent du PDF alors
   qu'ils s'affichent sur la page.
   ⚠️ **Trois autres propriétés que dompdf n'applique pas**, et qui faisaient
   diverger le PDF téléchargé de la page :
   - **`object-fit`** : il étire l'image aux dimensions de sa boîte. Un logo
     large dans une boîte plus carrée sortait écrasé. En mode PDF,
     `renderImage()` calcule la boîte lui-même (`getimagesize()`) et centre
     l'image à ses proportions.
   - **`text-transform`** : les capitales se perdaient. En mode PDF, le texte
     est mis en capitales **côté serveur**.
   - **les dégradés CSS** : ⚠️ dompdf **ne trace aucun dégradé** (il ne peint que
     la couleur de fond). Trois fonds du concepteur - « Bandes UK », « Simple »
     et « Or discret » - étaient des dégradés posés par une **classe** sur la
     page : le concepteur les affichait, mais la mise en page enregistrée ne
     retient qu'une **image** de fond, donc le document délivré ressortait blanc.
     Ils sont désormais des images (`public/img/bg-attestation-{bands,simple,gold}.png`,
     reproduites au pixel depuis la CSS), comme les deux autres fonds. Migration
     `2026_10_02_000700_restore_document_template_preset_background`.
     ⚠️ **Un fond ajouté au concepteur doit être une image**, jamais un dégradé.
   - **les graisses** : ⚠️ dompdf ne garde que **quatre variantes par famille**
     (normale, grasse, italique, grasse italique). Les six graisses de Jost s'y
     écrasaient deux à deux : un texte dessiné en 500 ressortait en 400, un titre
     en 900 en 700. `App\Documents\DocumentFonts` (source unique des fontes, lue
     aussi par `documents/_fonts`) déclare pour le PDF **une famille par fonte**
     (« Jost 500 »), que `renderText()` vise directement avec `font-weight:normal`.
     ⚠️ Et la pile de polices ne passe **pas** par `e()` : il échappe l'apostrophe
     en `&#039;` et le moteur ne reconnaît plus la famille (`cssValue()`).
   - **le SVG** : ⚠️ dompdf **ne rend pas le SVG** du QR - ni en ligne, ni par
     `<img>`, ni en `data:`. Le QR était donc **absent du PDF téléchargé** alors
     qu'il s'affiche sur la page, et une attestation imprimée sans QR n'est plus
     vérifiable. En mode PDF, `qrBoxes()` dessine la **même matrice**
     (BaconQrCode, niveau L, comme le SVG) en rectangles, les modules d'une même
     ligne étant fusionnés.
   - **`line-height`** : ⚠️ dompdf ne le rapporte **pas au corps du texte** mais
     à la hauteur propre de la police - `(ascendante - descendante + interligne)
     / em`, multipliée par son `font_height_ratio` (1,1). **Avec Jost (1,445 em)
     l'interligne sortait 59 % trop grand**, et un paragraphe de deux lignes
     perdait la seconde, coupée par sa boîte - c'est ainsi qu'une phrase entière
     disparaissait du PDF sans un mot. `fontHeightRatio()` lit la métrique dans
     le fichier de police (FontLib) et l'interligne est compensé ; le texte ne
     **rogne plus** en mode PDF (mieux vaut déborder que disparaître).
     ⚠️ Une police ajoutée à `LayoutSanitizer::FONTS` **et hébergée** doit être
     déclarée dans `LayoutRenderer::PDF_FONTS`, sinon son interligne repart de
     travers dans le PDF.
2. **Les polices n'étaient chargées que dans le concepteur.** Il tirait Jost et
   Playfair Display de Google Fonts ; ni l'aperçu, ni l'attestation délivrée, ni
   le PDF ne le faisaient - la maquette ressortait en police système, avec
   d'autres métriques et du texte qui ne tenait plus dans sa boîte. D'où le
   partial **`documents/_fonts.blade.php`**, à inclure **partout** où une mise en
   page du concepteur est rendue (aperçu, attestation, planche de badges, carte
   du participant, PDF).
   - Les fichiers sont **hébergés par le site** (`public/fonts`), pas tirés d'un
     CDN : ⚠️ **dompdf ne va chercher aucune feuille de styles distante**, et
     c'est lui qui produit le PDF téléchargé. Deux formats pour la même raison :
     **woff2** (navigateurs) et **ttf** (dompdf, qui ne lit pas le woff2).
   - `@include('documents._fonts', ['pdf' => true])` n'émet que des **chemins de
     fichier absolus**, que dompdf résout sur le disque (dans son `chroot`, la
     racine du projet).
   - ⚠️ **`storage/fonts` doit exister et être inscriptible** : dompdf y met en
     cache les métriques (`.ufm`) de chaque police embarquée, et son absence fait
     tomber la génération du PDF en erreur 500.
   - Les graisses déclarées sont **exactement** celles du concepteur : une graisse
     absente serait synthétisée par le moteur, ce qui change encore le rendu.
   Tests : `tests/Feature/DocumentTemplateTest.php`.
3. **Les jetons de la palette n'étaient pas servis.** Deux syntaxes cohabitent -
   `{cle}` (schéma historique des attestations) et **`[espace.cle]`** (celle que
   propose la palette du concepteur et qu'emploient ses modèles livrés). Les
   contrôleurs ne fournissaient que la forme plate : un modèle dessiné à la
   palette sortait avec `[recipient.name]` **vide**, et `[certificate.issued_on]`
   **affiché tel quel** (l'expression régulière ignorait le souligné).
   **`App\Documents\DocumentVars::expand()`** est la table d'alias unique, et
   elle est appliquée **dans `LayoutRenderer::render()`** - pas chez les
   appelants, qui avaient chacun leur liste partielle. ⚠️ Un jeton ajouté à la
   palette doit l'être aussi dans `DocumentVars`, sinon il ne vaudra que dans
   l'aperçu.
4. ⚠️ **Le nom du modèle ne s'enregistrait pas** : le champ de l'en-tête du
   concepteur n'était ni transmis par le JS ni écrit par `save()`, alors que le
   message de confirmation citait le nouveau nom. Il l'est désormais, et un nom
   **vide n'écrase pas** celui déjà enregistré.
   Tests : `tests/Feature/DocumentDesignerTest.php`.
5. ⚠️ **« Des caractères ne s'affichent pas » = la source n'en avait pas.** Les
   modèles livrés par le concepteur étaient rédigés en **ASCII** : ni accents ni
   apostrophes (« Decernee a », « l Universite », « la presente attestation lui
   est delivree », « comite d organisation »). Rien n'était filtré à l'affichage -
   le texte d'origine était ainsi, et les attestations délivrées aussi. Source
   corrigée, **et migration `2026_10_02_000100_fix_document_template_typography`**
   pour les modèles **déjà enregistrés**, qui gardaient l'ancien texte (elle ne
   réécrit QUE ces phrases exactes : un texte personnalisé par un gestionnaire ne
   doit pas changer sous ses pieds). En français, **les capitales s'accentuent**
   (UNIVERSITÉ, MODÉRATION).
6. **Le concepteur parle français.** Son interface était un mélange d'anglais
   (« Save », « Layers », « Add heading », « Paper Size ») et de français sans
   accents, alors que tout le back-office est en français. Libellés, infobulles
   et messages uniformisés.

### `/attestation/{uuid}` = la page que le bénéficiaire partage

C'est l'adresse que porte le **QR code** du document et celle qu'un titulaire
envoie à un recruteur : elle doit tenir debout seule. Elle n'affiche donc plus
seulement le document imprimable, mais **le document, qui l'a délivré, ce qu'il
certifie et comment le vérifier** (modèle : les pages de vérification type
Certifier/Credsverse).

- **Le document d'abord**, rendu par le modèle enregistré comme ailleurs
  (`LayoutRenderer` + `documents._fonts`), puis **Bénéficiaire** (partage, ajout
  au profil LinkedIn, PDF, copie du lien), **Vérification** (état, date
  d'émission, identifiant, contrôle sur place) et **Délivré par** +
  **Ce que certifie ce document**.
- ⚠️ **Une attestation n'a QU'UNE adresse** : `/attestation/{uuid}`. Une
  attestation **révoquée** l'annonce en tête - un document révoqué ne doit pas
  pouvoir se présenter comme valide.
- ⚠️ **Un intitulé sans valeur n'est pas affiché** (`documentFacts()` filtre les
  vides) : une ligne « Thème : » vide donne l'impression d'un gabarit cassé.
- Le document est **mis à l'échelle de son cadre** par un petit script de la vue
  (`.ukc-cred-doc`, largeur réelle en `data-doc-width`) : une mise en page est
  dessinée à une largeur fixe, et on n'impose pas de défilement horizontal au
  visiteur. Le cadre **épouse** le document (`--doc-w`), sinon une bande blanche
  reste à côté d'une mise en page plus étroite que la colonne.
- Styles `.ukc-cred-*` dans `resources/css/public.css` (charte UK : le public
  n'est jamais habillé en Tailwise). `@media print` ne laisse sortir que le
  document.
- **Signataire** : `[signatory.name]` / `[signatory.role]` n'avaient aucune
  source - la ligne de signature sortait **vide** sur le document délivré alors
  que le concepteur montrait un exemple. Ils viennent des réglages
  `attestations.signatory` / `signatory_role` de l'édition (onglet **Options**
  des attestations).
- ⚠️ **Papier blanc sur page blanche** : rien ne distinguait le document de la
  page. Le cadre porte un filet et une ombre portée, et le fond de page un gris
  franc (`#eef1f6`). Colonne de gestes à **350 px**, page à **1300 px**.
- ⚠️ **Les valeurs de rendu ont une source unique** : `App\Documents\AttestationVars`.
  La page et le PDF dressaient la même liste chacun de leur côté, donc un jeton
  ajouté à l'une manquait à l'autre.
- ⚠️ **La vérification se fait SUR CETTE PAGE** : le bouton interrogeait une
  autre adresse, et le visiteur quittait le document pour lire un verdict. Il
  appelle `attestation.verify.json` et le résultat s'écrit dans un panneau de
  la page. Le contrôle est **refait** (registre + signature), ce n'est pas un
  repli du texte déjà affiché. Pour la même raison, **le QR du document pointe
  vers `/attestation/{uuid}`** et non plus vers `/verifier/{uuid}` : on scannait
  une attestation pour atterrir sur un verdict, sans le document.
- ⚠️ **`/verifier/{uuid}` N'EXISTE PLUS** (route supprimée) : elle faisait double
  emploi avec la page de l'attestation, qui porte le document **et** sa
  vérification. Tout ce qui la citait désigne maintenant `/attestation/{uuid}` -
  **y compris le `credentialStatus.id` des attestations signées**
  (`AttestationCertifier::buildCredential()`) et le `verify_url` du VC
  téléchargeable.
- ⚠️ **Une signature déclare une ADRESSE : la changer périme les signatures.** Les
  attestations signées avant ce changement déclaraient `/verifier/{uuid}`, qui ne
  résout plus - un vérificateur tiers suivant ce `credentialStatus.id` tombait sur
  un 404. Le stock (5 attestations) a été **re-signé**. ⚠️ **Le même travail sera
  à refaire à la mise en production** : `APP_URL` passera de `geves.test` au vrai
  domaine, et toutes les signatures déclareront encore `geves.test`. Remède :
  `php artisan attestations:sign --force` (il re-signe TOUT le stock, y compris
  les attestations non signées - l'émission est en fail-open).
- `/verifier` (**saisie d'un code**) demeure : on y arrive sans identifiant, elle
  ne double rien. Elle mène à `/attestation/{uuid}`, et ⚠️ **vérifie que le code
  existe avant d'y envoyer** : un code inconnu reste sur le formulaire avec un
  message et la saisie sous les yeux - la faute de frappe se corrige là où elle a
  été faite, pas sur un 404.
- **Gestes au survol du document** (partage, LinkedIn, PDF, vérification), en
  surimpression. ⚠️ Le survol n'existe pas au doigt : la colonne de gauche porte
  les mêmes actions, et la surimpression est masquée en `@media (hover: none)`.
  Un seul partial, `attestations/_actions.blade.php`, pour que les deux ne
  divergent pas. ⚠️ **Voile légèrement sombre, sans flou** : flouter le document
  revient à cacher ce qu'on vient regarder.
- **Voir en grand** : réduit à la largeur d'un téléphone, un document dessiné à
  900 px n'est plus lisible. Le bouton loupe l'ouvre à l'échelle de l'écran, et
  il reste **visible en permanence** sans survol.
- ⚠️ **Le QR de vérification encodait une adresse FIXE et sans identifiant**
  (`DEFAULT_QR_URL` = « …/attestation-verify ») : scanner deux attestations
  différentes menait au même endroit, qui ne vérifiait rien. Il encode le jeton
  `{verify_url}`, et l'aperçu du concepteur affiche l'adresse d'exemple (un
  jeton ne se scanne pas). Migration
  `2026_10_02_000500_fix_document_template_qr_target` pour les modèles
  enregistrés ; une adresse **personnalisée** (QR « Custom ») n'est pas touchée.
  Un test de garde interdit le retour d'une adresse codée en dur.
- ⚠️ **L'instantané de l'éditeur ne retenait pas l'adresse du QR** (`qrUrl`) : à
  la réouverture le jeton était perdu et l'image gardait l'adresse d'alors. Elle
  fait désormais l'aller-retour, et le QR est redessiné à la restauration.
  Tests : `tests/Feature/AttestationPageTest.php`, `tests/Feature/DocumentDesignerTest.php`.
### Onglets d'une page = pilules + URL

Toutes les sous-navigations du back-office sont en **pilules**
(`.ukc-nav-docs`) et **portées par l'URL** (`?tab=`), donc partageables par lien.
⚠️ `.ukc-tabs` (onglets soulignés) ne subsiste que sur **Documents ›
Attestations/Livret** : ne pas s'en servir de modèle.

Deux mises en œuvre, selon la page :

1. **Navigation serveur** (Programme, Soumissions) : chaque onglet est une URL
   rendue par le contrôleur, définie une seule fois dans un `_nav.blade.php`.
   C'est le cas général.
2. **Réécriture d'URL sans rechargement** - composant Alpine **`ukcUrlTabs`**
   (`app.js`) : le clic bascule le panneau et réécrit l'URL (`pushState`), et les
   flèches du navigateur suivent (`popstate`). ⚠️ **Réservé aux pages dont tous
   les panneaux vivent dans UN SEUL formulaire** : **Réglages** et la **fiche
   d'édition**. Une vraie navigation y perdrait les saisies non enregistrées des
   autres onglets. Les onglets restent de vrais `<a href>` (ouvrables dans un
   nouvel onglet), neutralisés par `@click.prevent`.

- **Réglages** : onglets et libellés dans **`SettingController::TAB_LABELS`**
  (`TABS` pour la validation de `?tab=`), parcourus par
  `admin/settings/_nav.blade.php`. ⚠️ `?tab=` **filtré sur les clés connues** :
  une clé fantaisiste n'afficherait aucun panneau et la page paraîtrait vide.
- ⚠️ **Les Agents IA sont un ONGLET** (`?tab=agents-ia`), plus une page : la
  route `/admin/reglages/agents-ia` est une **`permanentRedirect`** (signets,
  liens déjà envoyés), `SettingAgentController` n'a plus que son `update()`, et
  l'éditeur vit dans `admin/settings/_agents.blade.php`. Son **formulaire est
  distinct** de celui des réglages (PUT sur sa propre route) : il est donc placé
  **hors** du formulaire général - deux formulaires imbriqués ne s'enregistrent
  pas. Le bouton « Enregistrer les réglages » est masqué sur cet onglet, sans
  quoi deux boutons d'enregistrement voisinaient sans dire ce qu'ils couvrent.
  Tests : `tests/Feature/AdminSettingsTest.php`, `tests/Feature/AiAgentsTest.php`.
- ⚠️ **Avatar d'agent = galerie fournie OU image téléversée (tuile « + »).** Les huit
  visages vivent dans **`public/img/agents/`** (`marc-*` hommes, `awa-*` femmes), déclarés
  dans **`AiAgents::GALLERY`**. Marc (copilote admin) a `marc-1` par défaut, l'agent public
  (Gesco) `awa-1` ; pour un nouvel agent, l'admin pioche parmi les huit (`galleryForJs()`
  alimente l'éditeur Alpine `agentsEditor`) **ou téléverse la sienne** via la **tuile « + »**
  en fin de galerie (aperçu local `agent.upload` = objectURL, non posté ; seul le fichier
  `avatars.$i` l'est). On stocke donc soit un **chemin de galerie** (`img/agents/…`), soit
  un **chemin de téléversement** (`ai-agents/…` sur le disque `public`), soit vide.
  `SettingAgentController` valide le champ texte `avatar` ∈ galerie ∪ '' ∪ avatars **déjà
  enregistrés** (⚠️ tolérance depuis les agents STOCKÉS, jamais la requête qui s'autoriserait
  elle-même) ; un **fichier** envoyé l'emporte, et l'**ancien fichier** devenu orphelin est
  supprimé (jamais une image de galerie). Rendu via **`AiAgents::avatarUrl()`** (source
  unique) : `img/…` → `asset()`, URL absolue telle quelle, sinon repli `Storage::url()`.
  L'**aperçu de l'éditeur est agrandi (96px)** pour le distinguer des vignettes (52px).
  ⚠️ Avatar téléversé : **pas de SVG** (servi depuis notre domaine, peut porter du script).

### Actions IA du back-office = AJAX + voile de travail

Une analyse IA dure dix à soixante secondes. Sans retour visuel, l'admin
recliquait ou quittait la page en croyant à un blocage ; et une génération qui
rechargeait la page faisait perdre la saisie en cours.

- **Voile de travail** : **`window.ukcAiVeil`** (`show({title, steps})`,
  `done(message, ok)`, `hide()`) couvre l'écran du bleu de la charte, fait
  défiler les étapes et compte le temps écoulé. ⚠️ `z-index: 120`, **au-dessus
  des modales (100) et des slideovers (60)** : une analyse lancée depuis un
  panneau doit le couvrir. **Aucun clic ne le ferme** - on ne propose pas
  d'annuler ce qu'on ne sait pas interrompre côté serveur. Compteur `depth`
  pour les appels imbriqués, et retrait forcé au retour arrière (bfcache).
- **Déclenchement déclaratif** : l'attribut **`data-ai`** sur un formulaire *ou*
  sur le bouton cliqué suffit ; `data-ai-title` et `data-ai-steps` (étapes
  séparées par `|`) règlent le texte. ⚠️ Avec `data-confirm`, le voile se lève
  **après** la popup (sinon il la recouvrirait, et resterait affiché en cas de
  renoncement) : le point d'accroche est dans le gestionnaire de confirmation,
  car `form.submit()` ne déclenche aucun évènement `submit`.
- **Passage en AJAX** : ajouter `data-ajax` + `data-ajax-target="<clé>"` au
  formulaire et envelopper le bloc de résultat dans `data-ajax-root="<clé>"`.
  Fait pour **Rapports › Export personnalisé (IA)** (`ai-report`) et
  **Secrétariat › Générer un document** (`sec-doc`). Les panneaux de la **fiche
  d'édition** et des **formations** étaient déjà en `fetch` : ils appellent
  désormais le voile.
- ⚠️ **`ukcAjaxSubmit` lit la réponse du POST**, qui est déjà la page
  rafraîchie (fetch suit la redirection), **messages flash compris**. Le second
  GET d'avant consommait le flash et vidait le bloc de résultat - c'est ce qui
  aurait cassé toutes ces actions IA, dont le résultat passe précisément par un
  flash. Repli sur un GET seulement si la racine est absente de la réponse.
- ⚠️ Un bloc rafraîchi **ne doit pas contenir le formulaire** quand la saisie
  doit survivre à une erreur ; sinon, flasher l'entrée (`back()->withInput()`)
  et repartir de `old()` dans l'état Alpine (fait pour le Secrétariat).
- **Paramétrage assisté de la fiche d'édition** : glisser-déposer réel (la zone
  s'appelait « Déposer un document » mais ne réagissait qu'au clic), documents
  cumulatifs et retirables un par un (8 maximum), et surtout un **rappel
  explicite** après « Appliquer au formulaire » - ⚠️ l'IA **ne fait que remplir
  les champs**, la fiche reste à enregistrer, et l'admin partait en croyant
  l'édition créée.
- ⚠️ `admin.editions.ai-assist` (variante non-JSON de `aiAssistJson`) **n'a plus
  aucun appelant** depuis le passage du panneau en fetch : ne pas s'en servir de
  modèle pour une nouvelle action.

### Présence d'une séance = pointée par le modérateur OU le rapporteur

- ⚠️ **Deux verdicts : présent ou absent.** « Excusé » a existé et a été retiré
  (migration `2026_10_04_000100`, lignes versées dans `absent`). Il ne pesait
  **rien en aval** - seul `present` compte dans le PV (`SessionReportController`)
  et dans l'attestation (`DocumentController`) - mais il ajoutait un troisième
  choix à trancher pendant qu'on appelle des gens à la voix. Un motif d'absence
  se dit dans le compte rendu, pas dans une colonne qui ne sert à aucun calcul.
  ⚠️ Les anciennes lignes deviennent `absent` et **non** « non pointé » : un
  modérateur avait regardé sa salle, c'est un verdict, pas un blanc - et le
  remettre à zéro ferait remonter le compteur des gens à appeler.
  ⚠️ `SessionAttendance::STATUSES` est la **source unique** : les deux
  contrôleurs valident sur ses clés, donc y remettre une valeur l'ouvre partout.

`/compte/seances/{session}/presence` (`Account\SessionAttendanceController`,
table `session_attendances`, modèle `SessionAttendance`). Entrées depuis
**Séances à animer** et **Séances à rapporter**.

- **Les deux rôles y ont droit.** Selon les séances, c'est le modérateur qui
  anime pendant que le rapporteur tient la liste, ou l'inverse. Imposer un seul
  des deux aurait condamné la moitié des séances au papier.
- ⚠️ **On ne pointe QUE les auteurs des communications de la séance**
  (`App\Support\SessionRoster`), **jamais l'effectif de l'édition**. La première
  version listait les 215 inscrits : illisible, et elle demandait au modérateur de
  se prononcer sur des gens qui n'ont rien à faire dans sa salle. La liste tient
  donc sur un écran - **ni recherche ni pagination**, on appelle des gens, on ne
  fouille pas un annuaire.
  - C'est aussi la **garde d'écriture** : `update()` n'accepte que les inscrits de
    la liste de la séance, pas ceux de l'édition. Un identifiant posté n'est pas
    une autorisation.
  - Une personne qui signe **deux communications** de la même séance n'a qu'**une**
    ligne : deux donneraient deux verdicts contradictoires sur une même présence.
  - ⚠️ Un **co-auteur sans inscription** sur l'édition est affiché mais **non
    pointable** : le cacher ferait croire à une liste d'auteurs tronquée, et il n'y
    a aucune inscription à laquelle rattacher une présence.
  - Une séance **sans communication** (plénière, cérémonie) affiche un état vide
    qui l'explique - elle ne retombe pas sur la liste de l'édition.
- **La page de présence RAMÈNE dans la salle** (`MeetableRegistry::tabFor()`) :
  on vient d'y pointer des gens pendant qu'on anime, repasser par la liste des
  séances est un détour que personne ne fait.
  - ⚠️ **L'onglet de la salle porte un nom** (`window.name`, posé par
    `meeting/join.blade.php` pour les DEUX lecteurs), et le lien de retour le
    pose en `target` : le navigateur **réutilise** cet onglet au lieu d'ouvrir
    une seconde salle - deux connexions, deux vignettes et de l'écho. Le nom a
    **une seule définition** ; deux chaînes construites chacune de leur côté se
    décaleraient un jour, et l'onglet cesserait d'être retrouvé sans que rien ne
    casse visiblement.
  - ⚠️ **Pas de `rel="noopener"`** sur le lien de la salle vers la page, et
    c'est délibéré : la page ouverte est la nôtre, même origine, et elle a
    besoin de `window.opener` pour remettre l'onglet de la salle devant **sans
    la recharger**. Un rechargement en ferait sortir puis rentrer le modérateur
    sous les yeux de toute l'assistance.
  - ⚠️ `focus()` sur un autre onglet **peut être ignoré** selon le navigateur.
    D'où une vérification et non une supposition : 400 ms plus tard, si la page
    est encore `visible`, c'est que le clic n'a rien fait - on suit alors le
    lien. **Un bouton qui ne fait rien est pire que le rechargement qu'on
    voulait éviter.**
- **La présence se tient AUSSI depuis la salle** (onglet « Présence » du lecteur,
  `SessionPresenceApiController` + `resources/js/meeting.js`) : l'aller-retour vers
  `/compte/seances/{id}/presence` obligeait à quitter la séance au moment précis
  où l'on regarde les gens. ⚠️ **Les règles ne sont pas redéfinies là-bas** : qui
  peut pointer (`SessionPresence::canManage()`), quand (`::open()`) et sur qui
  (`SessionRoster`) ont **une seule** implémentation - trois endroits qui
  décideraient chacun de leur côté finiraient par diverger. L'onglet n'apparaît que
  si le serveur a donné les adresses : c'est lui qui décide, pas le navigateur.
- **Entrer dans la visio VAUT présence** (`App\Support\SessionPresence`), par deux
  chemins complémentaires : `MeetingController::join()` au passage de la porte, et
  **`::markConnected()` sur l'occupation RÉELLE de la salle** lue par
  `App\Meetings\LiveKitRoom` (API LiveKit `ListParticipants`), rafraîchie tant que
  le panneau est ouvert. Même cadrage et mêmes bornes que le pointage manuel.
  - ⚠️ **Le rapprochement passe par l'IDENTITÉ LiveKit**, `ukp-{participant}-{aléa}`
    (`LiveKitRoom::identityFor()`), **jamais par le nom affiché** : celui-ci se
    change depuis la salle. L'aléa est obligatoire - LiveKit refuse deux connexions
    de même identité, et on ouvre la salle sur deux appareils.
  - ⚠️ **Le jeton d'admin doit nommer la salle EXACTE** : `room: '*'` est refusé
    (401 « permissions denied »). Et un **404 « room does not exist » n'est pas une
    panne** : LiveKit ne crée la salle qu'à la première connexion, donc 404 = salle
    vide (`[]`), tandis qu'une vraie panne rend `null` = **on ne sait pas**. Le
    panneau n'affiche « En salle » que si l'information est connue : ne jamais
    laisser croire que personne n'est là quand on l'ignore.
  - ⚠️ Sans LiveKit configuré, il ne reste que l'entrée depuis GEVES : **Jitsi
    public n'expose aucune API de liste de participants**. D'où le libellé « Entré
    en visio » - on ne fait pas dire à une donnée plus qu'elle ne sait.
  - ⚠️ **Un verdict déjà posé n'est JAMAIS écrasé.** Quelqu'un a regardé la salle ;
    un clic sur « Rejoindre » ne vaut pas contre ce constat, et faire repasser un
    « absent » à « présent » dans le dos du modérateur lui ferait signer un chiffre
    qu'il n'a pas vu.
- **Le pointage automatique s'affiche À MI-CHEMIN, et se confirme d'un clic**
  (`gestePointage`, règle pure). Il compte **déjà** dans « Présents » : rien
  n'est perdu si personne ne le confirme. Mais il dit seulement que quelqu'un
  est entré dans la salle, pas que le modérateur l'a constaté - et c'est lui qui
  endosse le chiffre dans le PV. D'où le bouton **plein** (constaté) contre
  **pointillé** (déduit, `aria-pressed="mixed"`), la pastille « À confirmer N »
  qui disparaît une fois tout vu, et la bascule :
  - ligne **automatique**, même verdict → **confirme** (`source` passe à `manual`,
    `marked_by` prend le modérateur) ;
  - ligne **de sa main**, même verdict → **retire** (défaire une erreur sans
    quitter la salle) ;
  - autre verdict → **pose**, dans les deux cas.
  ⚠️ Sans cette distinction, le geste le plus naturel - appuyer sur le verdict
  qu'on approuve - **effaçait** la présence au lieu de l'endosser.
  ⚠️ Retirer un pointage sur quelqu'un **encore en salle** le fait revenir en
  pointillé au rafraîchissement suivant : `markConnected()` reconstate une
  connexion qui est toujours vraie. Ce n'est pas un bug - le fait machine tient,
  seul l'aval humain a été retiré. Pour écarter quelqu'un, on pose « Absent »,
  qui est un verdict et que rien n'écrase.
- **Les étiquettes SONT les filtres** (`presencesFiltrees`, `filtrePresenceTenable`,
  règles pures). On lit « Absents 3 » et on veut voir lesquels : demander de le
  redire ailleurs serait une étape pour rien. « Attendus » est le chemin de
  retour vers la liste entière, non pointés compris - ce sont eux qu'il reste à
  appeler.
  ⚠️ **« À confirmer » peut disparaître sous le doigt** : c'est souvent le
  dernier clic du modérateur qui l'escamote, pendant qu'il la regarde. Le filtre
  **se replie** alors sur « Attendus » - sinon il resterait une liste vide et
  plus aucune étiquette pour en sortir.
  ⚠️ `compteAConfirmer()` est défini **par** le filtre, pas à côté : le nombre
  sur l'étiquette et la longueur de la liste qu'elle ouvre doivent être le même
  calcul.
  ⚠️ Le filtre choisi **survit au rafraîchissement** (20 s) : repartir sur
  « Attendus » toutes les 20 secondes ferait sauter la liste sous les yeux.
  ⚠️ Bordure transparente sur TOUTES les étiquettes : réservée à l'allumée, elle
  décalerait la rangée au moment du clic.
  ⚠️ **Défaut gelé par un cas** : les trois boutons portaient `.on` sans
  `data-st`, alors que la feuille colore `.on[data-st=…]`. Le verdict était
  enregistré **sans jamais se voir** - trois boutons identiques sous un compteur
  qui disait « Présents 1 ». Les deux moitiés de l'état (classe et attribut) se
  posent désormais au même endroit.
  - Colonne **`source`** (`manual` / `meeting`) : la page signale les lignes posées
    par la visio, parce que le modérateur **endosse ces chiffres dans le PV**.
    Dès qu'il enregistre, la ligne repasse `manual` - même s'il ne fait que
    confirmer, elle est de sa main.
- ⚠️ **Une table `session_attendances` a existé puis été supprimée**
  (migration `2026_09_17_000200`) : elle n'était alimentée QUE par le **scan de
  badge**, retiré. Elle revient avec une source humaine - ne pas la relire comme
  un reliquat du scan.
- ⚠️ **Trois verdicts, et « non pointé » n'en est pas un** : `present`, `absent`,
  `excused`. **Pas de ligne = pas encore appelé**, ce qui n'est pas la même
  information qu'un absent **constaté**. D'où le compteur `unmarked`, rapporté aux
  **attendus de la séance** : « 12 présents » ne dit rien sans savoir 12 sur combien.
- ⚠️ **L'enregistrement est INCRÉMENTAL et non destructif** : `update()` ne touche
  QUE les participants présents dans la requête. On pointe par petits lots pendant
  la séance (liste paginée, recherche en cours) - une sauvegarde qui remettrait à
  zéro ce qui n'est pas à l'écran effacerait le lot précédent. La vue le **dit** à
  l'utilisateur, sinon la prudence lui ferait tout ressaisir.
- **Fenêtre** : ouvert au début de la séance (`hasStarted()`), fermé par l'archivage
  de l'édition et par la **validation du rapport** par le secrétariat - le PV cite
  ces chiffres, ils ne doivent plus bouger après. Lecture toujours possible.
- ⚠️ **`Participant::wasPresent()` s'appuie dessus**, et c'était un trou : depuis le
  retrait du scan, **rien n'écrivait `checked_in_at`**, donc l'option « présence
  requise » des attestations de participation ne laissait passer **personne**. La
  présence en séance est désormais la source réelle. Le filtrage se fait **en SQL**
  (`participationTargets()`), pas par `filter()` sur une collection : une édition
  compte des centaines d'inscrits.
- ⚠️ **Un comptage l'emporte sur une estimation** : le PV (`documents.session-pv`,
  section `presents`) affiche les présents **pointés** dès qu'il y en a, et
  l'« affluence estimée » du rapporteur seulement à défaut - en disant lequel des
  deux il montre. Le champ estimé reste : une plénière de 300 ne se pointe pas.
- Tests : `tests/Feature/SessionAttendanceTest.php`.

### Programme synthétique = un onglet par JOUR

- `partials/program-synthesis.blade.php`, **partagé** par la page publique et
  l'aperçu de l'onglet Programme : l'admin doit voir exactement ce qui est publié.
- ⚠️ **Un onglet = un JOUR, pas un bloc de saisie.** Une journée se saisit souvent
  en deux blocs (« Matin », « Après-midi ») : un onglet par bloc affichait quatre
  pastilles « Matin » côte à côte, et le visiteur qui cherchait le 3 octobre ne
  savait pas laquelle ouvrir. Les blocs d'une même date sont **regroupés** et leur
  intitulé devient un **intertitre** dans le jour.
- ⚠️ **Jamais trié** : les jours sortent dans l'ordre où l'admin a rangé ses blocs,
  flèches à l'appui. C'est lui qui décide de la succession.
- ⚠️ Un bloc **sans date** garde son propre onglet : rien ne dit à quel jour il
  appartient, et le coller au précédent inventerait une information.
- ⚠️ **Mesuré** : le surtitre « Jour N » à `opacity: .72` tombait à **2,8:1** sur
  les onglets inactifs (gris clair sur blanc, en 10,5px). La hiérarchie se fait à
  la taille et à la graisse, qui ne coûtent aucun contraste - tout est ≥ 7,5:1.
- ⚠️ **Mesuré** : le jour s'étalait sur TOUTE la largeur du conteneur (1316px à
  1391px de fenêtre), sans respiration latérale - la date collée à gauche,
  « 1 moment » collé à droite, à quatorze cents pixels l'un de l'autre. Le jour
  est posé sur une **surface** (carte blanche, ~30px de marge intérieure) et le
  bloc est **borné à 960px** : passé ~900px, l'œil perd la ligne entre l'heure et
  son libellé. La borne vaut pour les onglets, qui sont dans le même bloc.
- ⚠️ L'aperçu de l'onglet admin **garde la même surface**, sur un fond teinté : sa
  raison d'être est de montrer exactement ce qui est publié, et l'y retirer parce
  qu'il vit déjà dans une carte ferait diverger les deux rendus - ce que le
  partage de ce partial sert précisément à éviter.
- ⚠️ **Mesuré** : `.prog-public-preview` était bridé à **760px** dans une carte de
  **1114px** - plus de quatre cents pixels de vide à droite. Le bridage est levé :
  c'est la largeur de la page publique (960px) qui décide, et c'est bien elle que
  l'aperçu doit montrer. Un bridage propre à l'admin le ferait mentir.
- ⚠️ **Mesuré** : à 20px de rembourrage, sept onglets de jour totalisaient 958,7px
  dans une piste de 960 - **un pixel** de marge en public, et 13px de trop dans
  l'aperçu admin (le zoom 90% ne rend pas les mêmes largeurs), où « Jour 7 »
  tombait seul sur une seconde ligne. À 16px, les deux gardent ≈ 100px de jeu.

### `/verifier` est de la famille de la page d'attestation

Les deux répondent à la même question - « ce document est-il authentique ? » -
et l'une mène à l'autre : la saisie de code emprunte donc `.ukc-cred` (fond
dégradé, barre de marque, pastille d'état, cartes à surtitre), et non l'en-tête
et le pied de page du site. On y arrive un diplôme à la main, pas en naviguant.

- ⚠️ **`--ukc-mono` n'est PAS une chasse fixe** : `ukc.css` la définit comme
  `var(--ukc-font)`, un alias de la police du site. Tout ce qui s'en remet à
  elle - références, numéros de reçu, identifiants d'attestation - s'affiche en
  proportionnel malgré le nom. Le champ de `/verifier` nomme donc sa pile
  monospace : y recopier 36 caractères est le propos même du champ.
- ⚠️ **Mesuré à 375px** : ces 36 caractères ne tenaient pas dans un champ de
  302px à 14,5px - le code collé défilait dans sa boîte et ne pouvait plus se
  relire en entier. La taille suit la largeur de l'écran (`clamp`).
- ⚠️ La colonne fait **920px** (cartes de 840) : ce sont les CARTES qui
  s'élargissent, pas les lignes de texte. À 760px la prose était déjà à **74
  caractères par ligne**, le haut du confortable ; elle est donc bornée à 62ch
  tandis que le champ et le bouton prennent toute la carte - un formulaire se
  remplit, il ne se lit pas. Mesuré après : 62 caractères.

### Le nom court se saisit SANS l'année

La barre collante de la page colloque affiche `nom court · année` : l'année est
ajoutée par la vue, elle ne se saisit pas.

- ⚠️ L'exemple proposé sous le champ était « CSIUK 2026 », ce qui poussait
  justement à la saisir - une édition s'est retrouvée annoncée **« Colloque
  Avenir durable 2025 · 2026 »**, deux années contradictoires sur la même ligne.
  Un placeholder est un **exemple à copier** : s'il montre la faute, elle est
  commise.
- Le champ affiche désormais **la ligne telle qu'elle sortira**, et prévient
  quand le nom court se termine par une année.
- Ce bloc est aussi le lien **« retour en haut »** de la page et porte la pastille
  d'état : le retirer coûterait plus que l'année qu'on y lisait en double.

### Onglets de la fiche d'édition

- **Onze onglets dans UN seul écran** (`admin/editions/form.blade.php`), pilotés
  par `?tab=` **sans navigation** : les panneaux vivent dans le même formulaire,
  recharger perdrait les saisies des autres onglets.
- **« Intégrations » réunit paiement et visioconférence** : deux formulaires de
  même nature - brancher l'édition sur un service extérieur - qui faisaient
  chercher au même endroit deux fois. Chacun **garde sa sauvegarde** : les fondre
  ferait d'une correction de clé d'API une réécriture des deux configurations.
- **« Traductions » a rejoint « Apparence »** : les deux faces de ce que le
  visiteur voit du site public.
  - ⚠️ Le panneau des traductions reste **hors du formulaire principal** (une
    locale par formulaire, un bouton par langue) : imbriquer un formulaire dans
    un autre n'existe pas en HTML. D'où **deux panneaux frères** qui s'allument
    sur le même onglet - et un rappel explicite que les traductions ont leur
    propre bouton, sinon on croit que « Enregistrer » les emporte.
- ⚠️ **Les anciens noms d'onglets sont traduits** (`payment`/`meetings` →
  `integrations`, `translations` → `appearance`). Un lien partagé, un favori ou
  une session ouverte porte l'ancien nom : sans cela, la page s'ouvre sur un
  onglet qui n'existe plus - aucune pilule allumée, **aucun panneau affiché**.
- ⚠️ **Le cadre blanc d'un monogramme est INVISIBLE sur une barre claire** : son
  rembourrage ne se lit pas comme une marge intérieure mais comme du vide, qui
  s'ajoute à la gouttière du groupe. Dans la marque des barres latérales, 6px de
  rembourrage + 10px de gouttière mettaient le logo à 16px du filet quand le
  texte n'en était qu'à 10 - l'écart de gauche paraissait moitié plus grand. Le
  cadre épouse l'image, et seule la gouttière espace. **Mesuré : 10 / 10.**
  (L'en-tête public, lui, n'a pas de cadre : il était déjà à 12 / 12.)
- **Répéteurs de la fiche** (membres de comité, tarifs, partenaires) : une carte
  par entrée, champs **étiquetés** (pas seulement des indications de saisie, qui
  disparaissent une fois remplies), et la pastille destructive **partagée**
  (`ukc-btn--icon danger`, déjà utilisée par le programme) - une couleur inventée
  au cas par cas, et deux écrans ne diraient plus la même chose du même geste.
  - Le cadre du logo d'un partenaire **est le bouton de téléversement** : on
    téléverse un fichier OU on colle une URL, et **l'aperçu s'affiche dans les
    deux cas** - c'est lui qui dit si l'adresse pointe bien sur une image, un
    logo absent du bandeau public se découvrant sinon après publication. Une URL
    qui ne charge pas se dit (« Image introuvable ») au lieu de laisser l'icône
    cassée du navigateur, qu'on prend pour une panne de la fiche.
  - ⚠️ **Hauteur du cadre = hauteur des champs** (`align-items: stretch` sur la
    ligne, `flex: 1` sur le cadre), jamais une valeur fixe : à 76px la colonne de
    gauche s'arrêtait au milieu de la seconde ligne de champs.
  - ⚠️ **La liste se réordonne** (flèches), et son ordre **est** celui du bandeau
    public. Deux conséquences, toutes deux coûteuses si on les oublie :
    - Côté client, la clé de `x-for` est **l'identifiant de la ligne** (`p.uid`),
      jamais son index : Alpine recycle les nœuds à clé égale, et le fichier déjà
      choisi dans un champ de téléversement resterait sur place pendant que la
      ligne se déplacerait - **on enregistrerait le logo d'un partenaire chez son
      voisin**. Vérifié au navigateur : le fichier suit bien sa ligne.
    - Côté serveur, le ménage des logos devenus orphelins se fait sur les **deux
      listes entières** (`EditionArtwork::forgetMissing`), jamais ligne à ligne :
      après un déplacement, l'index 0 ne désigne plus le même partenaire.
  - ⚠️ **Pas de SVG en téléversement** : il serait servi depuis notre domaine et
    peut porter du script - un logo de partenaire vient d'un tiers.
  - ⚠️ Le survol rouge générique de la fiche vise les **retraits** ; sous `.p-acts`
    il est ramené au bleu pour les flèches, qui ne détruisent rien.
  - ⚠️ Le champ **« Niveau » ne sort nulle part** : le bandeau public n'affiche que
    logo, nom et lien. Il porte donc une pastille « interne » - sans elle, on croit
    à un classement qui n'existe pas.
  - ⚠️ **Mesuré au harnais** : à 375px, la grille à trois colonnes réduisait les
    champs à 144px et tronquait les noms longs **au milieu d'un mot**. Sous 560px,
    l'aperçu prend donc toute la ligne.
- ⚠️ **PIÈGE BLADE, payé trois fois ici** : ne jamais écrire les directives de bloc
  PHP de Blade en toutes lettres dans un `{{-- commentaire --}}`. Blade extrait
  les **blocs de code avant** de retirer les commentaires : il prend le texte du
  commentaire pour du vrai code, avale tout ce qui précède, et la page tombe sur
  une « variable indéfinie » ou un `endif` orphelin **très loin de la cause**. De
  même, un bloc `php … endphp` posé juste après une directive PHP en ligne se
  télescope avec elle : ce fichier n'utilise **que** la forme en ligne.
  - ⚠️ Et **jamais la forme courte d'Alpine avec une arobase** quand le nom
    d'événement est aussi une directive Blade : `@error` sur une image a été
    compilé comme l'erreur de validation de Blade, ouvrant une condition jamais
    refermée. On écrit `x-on:error`.

### Règles métier - limites communications & attestations

- **Limite de communications** (`settings.max_communications`) : compte **seulement** les
  communications où le compte est **auteur correspondant** (participant déposant,
  `Submission::usedCountFor()`). Illimité en tant que **co-auteur**.
- **Types d'attestation proposés PAR DÉFAUT** (hub Documents, `DocumentHubController::$typeMeta`) :
  communication, participation, modération, rapporteur, formation, animation de
  formation. ⚠️ **« soumission » et « acceptation » ne sont PLUS offerts d'office** -
  ils restent dans `Attestation::TYPES` (données existantes) mais ne sont ni créés
  (`DocumentTemplate::ensureFor`) ni listés. Ne pas les réintroduire dans `$typeMeta`
  sans demande.
- **Modération / rapporteur = UNE attestation par PERSONNE et par événement** : le
  bénéficiaire est une personne qui a **modéré** (resp. **rapporté**) **au moins une
  séance**, dédoublonnée. Éligibilité via `DocumentHubController::sessionRoleTargets()`
  (rapprochement par e-mail sur `event_sessions.moderator_email` / `rapporteur_email`,
  pas sur `participant.role`), émission idempotente par (événement, participant, type).
  ⚠️ Ce n'est PAS « par séance » : quelqu'un qui modère trois séances n'a qu'une
  attestation. Test : `DocumentsTest` (« émet UNE attestation de modération par
  personne… »).
- **Attestation de communication = par communication ET par personne** : sur chaque
  communication acceptée, l'auteur correspondant **et** chaque co-auteur **payé** ont une
  attestation à leur nom, liée à la communication (`attestations.submission_id`). Deux
  plafonds : `settings.coauthors_max` (co-auteurs attestés / communication) et
  `settings.attestation_max` (attestations de communication / personne - quand on dépasse,
  on **privilégie les communications où la personne est auteur correspondant**, puis les
  autres par ordre chronologique). Calcul dans `App\Support\CommunicationAttestations` ;
  émission via `IssueAttestation::handle($participant, 'communication', $submission)`.
- **En ligne ou sur papier** (`App\Support\AttestationPolicy`, option
  `attestations.delivery` par édition, **en ligne par défaut**) :
  - **En ligne** : le modérateur **valide le passage** d'une communication pendant
    la séance, et ses auteurs reçoivent aussitôt leur attestation (in-app,
    e-mail, SMS si ouvert) ; chacun la retrouve dans « Mes documents ».
  - **Papier** : le passage se constate quand même - il dit qui est monté à la
    tribune - mais **rien n'est émis ni envoyé** : le secrétariat émet le lot,
    le relit et l'imprime. L'attestation reste **consultable** dans l'espace du
    participant : lui fermer aussi cette porte n'y renverrait que ses questions.
  - ⚠️ **Un seul endroit décide** (`AttestationPolicy`). Trois chemins lisent ce
    choix - émission en masse, validation du passage, annonce au bénéficiaire -
    et chacun en tirerait son interprétation si le réglage était relu brut.
  - ⚠️ **SMS fermé par défaut** (`attestations.sms`) : chaque envoi est facturé, et
    une édition de deux cents communications en enverrait autant sans que personne
    ne l'ait décidé. Rien ne part non plus sans passerelle configurée - le
    fournisseur « log » est celui du développement.
- **Passage d'une communication = fait tenu en base** (`slots.presented_at` /
  `presented_by`, `ValidateCommunicationPassage`). La salle le marquait déjà
  (« Marquer effectuée ») mais **seulement dans les navigateurs** : la marque
  partait sur le canal de données et mourait avec la séance.
  - ⚠️ **Deux portes, un seul geste** : l'onglet Communicateurs de la visio **et**
    la page de modération (une séance en salle n'a pas de visio, et c'est pourtant
    là que les communications ont lieu). Même contrôleur, même garde.
  - ⚠️ **Le créneau doit appartenir à la séance** : sans cette vérification, le
    modérateur d'une salle validerait le passage d'une communication tenue
    ailleurs - et en enverrait les attestations.
  - ⚠️ **Irréversible côté séance**, et idempotent. Une attestation partie ne se
    reprend pas : elle est signée et porte un QR de vérification. L'erreur se
    répare au back-office, en **révoquant**.
  - ⚠️ Le constat est posé **avant** l'émission et séparément : si l'émission
    échoue à mi-parcours, le passage reste constaté (il a bien eu lieu). L'inverse
    ferait disparaître le fait parce qu'un envoi a échoué.
  - ⚠️ Mêmes ayants droit que la présence (`SessionPresence::canManage`) :
    modérateur **ou** rapporteur. Le réserver au seul modérateur laisserait sans
    recours une séance qu'il a dû quitter.
- **Qui reçoit une attestation de communication** (`attestations.communication_scope`) :
  **tous les auteurs concernés** (défaut) ou **seulement les pointés présents** à la
  séance où elle est programmée. ⚠️ Le filtre s'applique **avant** le plafond par
  personne : écarter d'abord les absents laisse la place à une communication où la
  personne était là. ⚠️ Une communication **sans séance au programme** n'est jamais
  écartée - c'est au programme qu'il manque quelque chose, pas à l'auteur.
  ⚠️ Les deux chemins (masse et séance) lisent la même option, sinon le bouton
  utilisé changerait la liste des bénéficiaires.
  ⚠️ **Le plafond par personne se compte différemment** selon le chemin : en masse,
  on classe tout (priorité auteur correspondant) ; en séance, on compte le **déjà
  émis**, parce qu'on ne voit pas les passages à venir et que ce qui est parti est
  parti. **Premier passé, premier servi.**
- **Côté bénéficiaire, le papier donne un SPÉCIMEN** : l'attestation se **voit**
  et se **vérifie** dans son espace, mais ne se télécharge ni ne se partage -
  l'exemplaire qui fait foi est celui qu'on lui remet. Sans cette coupure, deux
  documents identiques circuleraient, dont un que l'émetteur n'a jamais délivré.
  - ⚠️ **`/attestation/{uuid}/pdf` est fermé au public** en mode papier, sinon la
    restriction serait cosmétique. Le **back-office garde l'accès** : c'est lui
    qui imprime (cf. le lot).
  - ⚠️ **Le spécimen EST le document imprimé, plus la mention.** C'est sa mise en
    page que le bénéficiaire vient regarder : un aperçu qui diffère de l'original
    ne lui apprend rien et lui ferait découvrir le vrai document le jour de la
    remise. Les deux sortent du **même modèle enregistré**, par le **même
    moteur** ; le drapeau `pdf` de `LayoutRenderer` ne change que l'embarquement
    des images et des polices (dompdf ne va rien chercher à distance) - **jamais
    une position, une taille ni un contenu**. Gelé par un cas qui compare les
    deux rendus, géométrie et texte.
    - ⚠️ **Seule exception connue, et documentée** : le QR est un **SVG** à
      l'écran et des **blocs** dans le PDF (dompdf ne rend pas le SVG, cf.
      `qrBoxes()`). Même cadre, même emplacement, autre tracé - le cas compare
      donc les positions **entières** (éléments de mise en page et cadre du QR)
      et laisse de côté les modules, aux coordonnées décimales.
  - ⚠️ **La mention se pose PAR-DESSUS le rendu**, jamais dans la mise en page :
    le même modèle sert au lot que le secrétariat imprime, et une marque qui y
    entrerait sortirait sur les **originaux**.
  - ⚠️ **Mesuré au harnais** : `font-size: clamp(42px, 13%, 150px)` se calcule sur
    la police **héritée**, pas sur la largeur - le filigrane restait figé à 42px
    sur un document de 1000px. Il se dimensionne désormais sur `--doc-w`, que la
    vue porte déjà (et qu'il a fallu poser **aussi** sur la vue agrandie).
  - ⚠️ Le sens n'est **pas porté par le filigrane seul** (il est `aria-hidden`,
    et volontairement pâle pour ne pas masquer le texte) : un encadré l'explique,
    et la ligne de « Mes documents » dit si l'exemplaire a été remis ou reste à
    retirer.
- **« Mes documents » montre TOUTES les attestations de l'édition** : une par
  communication (`keyBy('submission_id')`) et une par formation suivie **ou
  animée**, avec son intitulé.
  - ⚠️ **Défauts gelés par des cas** : indexées par *type*, l'attestation de la
    dernière communication s'affichait en face de **toutes** les autres (deux
    communications, un seul document) ; et la page ne montrait qu'**une** ligne
    « Attestation de formation », sans dire laquelle - qui avait suivi trois
    ateliers pouvait croire les deux autres perdues.
  - ⚠️ Le titre de la formation vient de **`meta`** (`Attestation::formationTitle()`),
    figé à l'émission : une attestation doit dire ce qu'elle disait le jour où
    elle a été délivrée, pas ce que le catalogue dit aujourd'hui.
- **Sortie PAPIER** (`AttestationExportController`) : le mode physique existait
  sans le geste qui le rend tenable - il fallait ouvrir les attestations **une par
  une** à leur adresse. Trois sorties, et une trace :
  - **Lot PDF** (`admin.documents.attestations.lot`) : une attestation par page,
    prêt à imprimer. ⚠️ **Un seul type par fichier** - la taille de page vient de
    la mise en page du modèle, et un lot mêlant A4 paysage et A5 portrait
    sortirait rogné. ⚠️ **Lots bornés** (`AttestationExportController::LOT`) :
    chaque attestation est composée élément par élément, et un millier d'un coup
    épuiserait la mémoire du générateur **au milieu** du rendu - page blanche
    après dix minutes. Un lot borné échoue vite, ou réussit vite.
  - **Liste d'émargement** (`…attestations.emargement`) : le pendant papier de
    l'accusé de réception. Pas de découpage en lots (c'est un tableau), en-tête
    répété sur chaque page - une liste qui se signe sur plusieurs feuilles ne se
    lit plus sans ses titres de colonnes.
  - **Remise en main propre** (`attestations.delivered_at` / `delivered_by`) :
    ⚠️ **émettre n'est pas remettre.** Sans cette trace, une attestation imprimée
    attend dans une chemise sans que personne sache ce qui reste à distribuer.
    **Réversible**, contrairement au passage d'une communication : se tromper de
    ligne au guichet est banal, et une remise fausse ferait croire que quelqu'un a
    déjà son document.
  - ⚠️ **Les exports portent sur les filtres de l'écran** (`App\Support\AttestationQuery`,
    partagé avec la liste) : on imprime ce qu'on regarde. Deux constructions de
    requête finiraient par diverger, et le lot ne correspondrait plus à la liste -
    sans rien pour s'en apercevoir.
- **La page qui émet dit ce que l'émission déclenche** : bandeau du mode de
  délivrance, nombre de destinataires **et** d'écartés (`CommunicationAttestations::preview()`),
  confirmation chiffrée. ⚠️ Un nombre qui baisse sans explication se lit comme une
  panne - et on réimprime pour rien. ⚠️ Le bouton « Émettre et exporter » ne
  générait **aucun PDF** : il émettait et rendait la main. Il mène désormais à la
  liste délivrée filtrée sur le type, où les lots attendent.
- **Documents = modèles éditables** : badges/attestations utilisent le **modèle conçu par
  l'admin** (concepteur `admin/modeles`, `TemplateDesignerController` : génération IA +
  JSON éditable + aperçu ; `DocumentTemplate` par édition/kind/type, rendu
  `App\Documents\LayoutRenderer` avec variables `{…}`). ⚠️ **Plus aucun repli implicite** :
  tout document vient d'un modèle **enregistré**, créé au besoin par
  **`DocumentTemplate::ensureFor()`** à partir de `LayoutSanitizer::default()`. Les anciennes
  vues de repli (`attestations.document`, `documents.badges`, `pdf.attestation`) ont été
  **supprimées** - ne pas les réintroduire : un document que l'admin ne peut ni voir ni
  modifier dans le back-office est un piège. Supprimer un modèle le **réinitialise**. Placeholders dispo dans `TemplateDesignerController::VARIABLES` - dont
  **`{communication}` / `{communication_ref}`** pour nommer la communication, et
  **`{formation}` / `{formation_theme}` / `{formateurs}`** pour une attestation de
  formation. **Signatures des formateurs** : élément de mise en page **`signatures`**
  (`LayoutSanitizer::ELEMENT_TYPES`) rendu par `LayoutRenderer::renderSignatures()` à
  partir de `$vars['signatures']` - seule valeur **structurée** des variables (les
  autres sont des chaînes). La signature de chaque formateur se téléverse sur la page
  de la formation (`formation_trainers.signature_path`) et est **figée dans
  `attestations.meta` à l'émission** : l'attestation ne change pas si l'équipe évolue.
  - **Aperçu attestation/badge = le CONCEPTEUR en mode APERÇU VERROUILLÉ** (pour voir
    EXACTEMENT le design du modèle, polices/logos/mise en forme compris) : le bouton « Aperçu »
    ouvre `admin.documents.templates.preview` (`TemplateDesignerController::preview` →
    `renderDesigner($request, true)`), qui sert la **même page d'éditeur** avec
    `UKC_EDITOR.preview = true`. Le JS (`template-designer.js` / `badge-designer.js`) bâtit le
    canevas (depuis `editor_state` ou le template intégré du type) puis **ouvre automatiquement
    l'overlay d'aperçu** (clone exact du canevas, jetons remplis par `fillTokens` avec
    `sampleVars()`), chrome d'édition masqué ; « Fermer » revient aux Documents (`backUrl`).
    ⚠️ On NE reconstruit PAS le rendu côté serveur (`LayoutRenderer`) pour l'aperçu : il serait
    approximatif (logos/typographie). Le **reçu** suit la **même grammaire d'aperçu verrouillé**
    (`admin.payments.receipt.sample`, `ReceiptController::sample`, Payment fabriqué non persisté) :
    le reçu réel (partial unique `receipts/_document.blade.php`, partagé avec la vue imprimable)
    est rendu avec des valeurs d'exemple dans un **cadre sombre** (`#1e293b`), tag « Aperçu du reçu -
    valeurs d'exemple », bouton **« Fermer »** (+ Échap, clic sur le fond) qui ramène aux paiements
    (`backUrl`), actions imprimer/PDF masquées. ⚠️ Pas besoin de concepteur côté reçu : son design
    EST la vue Blade, donc la rendre suffit à montrer le final. Le **livret** ouvre son **PDF en
    inline** (`?format=pdf&inline=1`, `DocumentController` `stream()` vs `download()`). Tests :
    `DocumentPreviewTest`.
- **Livre des résumés (livret)** : communications acceptées groupées par axe, trois sorties
  depuis `admin.documents.livret` (`DocumentController::livret`) : **aperçu HTML**,
  **.docx** (`?format=docx`) et **PDF** (`?format=pdf`, dompdf, vue autonome
  `documents/livret-pdf.blade.php`). Le .docx est **`App\Documents\LivretDocx`** (porté de
  uk-colloques `booklet.php`). ⚠️ **Structure par défaut calée sur le livret de référence
  UK/UP** : couverture tricolore → avant-propos (optionnel, IA possible) → **« Différentes
  commissions et programmes du colloque »** (comités **numérotés** dans l'ordre supervision/
  organisation/scientifique, puis **« N. Programme synthétique du colloque »** rendu depuis
  `settings['program_synthesis']`) → **Programme** détaillé (communications/créneaux par jour,
  « Horaire | Communication + auteurs | Salle / Session ») → **Résumés** par axe (réf., titre,
  auteurs + affiliations en exposant, **« Auteur correspondant : … Email : … Tél : … »**,
  « RÉSUMÉ : », mots-clés) → **index des auteurs** (renvoi vers la réf.) → **pied de page
  paginé**. Pas de sommaire TOC Word par défaut (l'ancien `addToc` a été retiré).
  Options `livret.*` dans l'onglet **Apparence du livre** (vue `admin/documents/attestations.blade.php`) :
  polices, tailles, couleurs, alignements, `number_abstracts`, `author_index`, `page_numbers`,
  thème/dates, logo de couverture. ⚠️ **PhpWord n'échappe pas** le texte : `Settings::setOutputEscapingEnabled(true)` est obligatoire (un « & » casse le .docx).
  - **Sections de tête GÉNÉRIQUES, masquées si vides** (onglet **Livre des résumés → Contenu du livre**,
    `saveLivretOptions`) : en plus de l'avant-propos, le livret peut porter un **haut patronage**
    (`livret.patronage`, lignes centrées), des **remerciements** (`livret.acknowledgements`, une ligne
    par destinataire → liste à puces ; titre `livret.ack_title`), un **mot de bienvenue**
    (`livret.welcome_title`/`welcome_text`/`welcome_signature`) et un **mot du président**
    (`livret.president_title`/`president_text`/`president_signature`). ⚠️ **Chaque section ne s'affiche
    que si son champ est rempli** (sinon rien ne s'insère) - c'est générique (tout colloque), pas
    spécifique au modèle FSS qui a inspiré ces champs. Rendu dans **`LivretDocx`** (helper unique
    `addMessage()` pour tous les « mots » signés ; `addPatronage()`/`addAcknowledgements()`) **et** dans
    le **PDF** (`documents/livret-pdf.blade.php`). L'aperçu HTML (`documents/livret.blade.php`) reste
    volontairement minimal (il n'affiche déjà ni comités ni programme) : le .docx et le PDF sont les
    sorties fidèles. Les **modèles .docx personnalisés** ne reçoivent pas ces blocs (l'admin met ce
    contenu directement dans son propre .docx).
  - **Couverture SOBRE** (plus de bandeau tricolore) dans les trois rendus (.docx, PDF, aperçu HTML).
  - **Deux styles intégrés SÉLECTIONNABLES** (réglage `livret.template`, `LivretDocx::STYLES`,
    sélecteur « Modèle actif » de l'onglet **Modèles de document**) : **`default`** = « Modèle par
    défaut » (commissions numérotées « Différentes commissions… », **programme par jour** en tableau) ;
    **`fss`** = « Modèle 2 - Journées scientifiques » (comités en titres simples « Comités »,
    **programme par jour → salle → séance** fidèle au livret FSS 2021 : chaque séance = horaire +
    intitulé + modérateur, puis ses communications « Titre, INTERVENANT » depuis `event_sessions`/
    `slots`). `LivretDocx` prend un paramètre `$style` ; `DocumentController::livretStyle()` le résout,
    et `selectedLivretTemplate()` traite `default`/`base`/`fss` comme des styles intégrés (≠ modèle
    .docx importé). ⚠️ Le style intégré n'agit que sur le **.docx** (comme les modèles personnalisés) ;
    l'aperçu HTML et le PDF gardent la mise en page par défaut. Les deux styles partagent les sections
    de tête génériques ci-dessus. Les titres portent `keepNext` (pas de titre orphelin en bas de page).
  - **Gabarits .docx personnalisés** (porté de `booklet-templates.php` `ukc_bt_render`) :
    l'admin télécharge le **modèle de base** à balises (`admin.documents.livret.base-template`
    → `LivretTemplate::downloadBase`, vrai .docx), le personnalise dans Word en gardant les
    balises, le re-téléverse (`saveLivretTemplate` → `edition.settings['livret.templates']`),
    puis le sélectionne (`livret.template`). À la génération .docx, **`App\Documents\LivretTemplate`**
    ouvre le modèle (ZipArchive + DOM), remplace les balises **bloc** seules sur leur ligne
    (`[[AVANT_PROPOS]]`, `[[COMITES]]`, `[[SOMMAIRE]]`, `[[PROGRAMME]]`, `[[RESUMES]]`) par les
    **fragments OOXML** de `LivretDocx::blockXml()` (formatage DIRECT, indépendant des styles
    du modèle), et les balises **en ligne** (`[[TITRE]]`, `[[EDITION]]`, `[[THEME]]`, `[[DATES]]`,
    `[[LIEU]]`, `[[ANNEE]]`, `[[NB_RESUMES]]`, via `LivretDocx::inlineValues()`). Repli sur
    `LivretDocx` quand aucun modèle n'est sélectionné/présent (`selectedLivretTemplate()`).
    Un modèle importé se **supprime** (`deleteLivretTemplate`, route `admin.documents.livret.template.delete` ;
    fichier effacé + repli sur `default` s'il était actif).
    Tests : `tests/Feature/LivretTest.php` (contenu réel du .docx/PDF, fusion des balises).
  - **UI du volet (onglet Documents → Livre des résumés, 3 sous-onglets)** : *Livre des résumés*
    (panneau de génération Word/PDF/aperçu/harmonisation + modèle actif en pastille, bandeau KPI,
    « Contenu du livre » en cartes par section avec badge Affiché/Masqué), *Modèles de document*
    (modèles intégrés vs personnalisés, actif mis en évidence, import/suppression), *Apparence du livre*.
    ⚠️ La vue `admin/documents/attestations.blade.php` utilise des **`@php(...)` en ligne** : n'y
    ajoutez **pas** de bloc `@php … @endphp` (mélange = compilation Blade cassée, cf. Pièges) - préparez
    la donnée dans le contrôleur (`DocumentHubController::livretViewExtras()`).
- **Remboursement → révocation des attestations** : une attestation atteste d'un droit
  acquis par un paiement. `App\Actions\RevokeAttestationsOnRefund` (appelée par
  `admin.payments.refund`) **révoque** (jamais ne supprime - l'attestation reste
  vérifiable publiquement, signalée révoquée) : *participation* + *communication* si
  le remboursement porte sur les frais de participation, *formation* si c'est une
  formation. ⚠️ **Seulement s'il ne reste aucun paiement réglé couvrant le même
  droit** (double règlement, ou autre formation encore payée : la vérification est
  faite `formation_id` par `formation_id`). L'attestation *formation_animation* n'est
  jamais concernée : un formateur ne paie pas. Le remboursement remet aussi
  l'inscription à la formation en « registered ».
- **Certification cryptographique des attestations** (identité vérifiable) : chaque
  attestation est scellée à l'émission par une **signature EdDSA (Ed25519)** de
  l'émetteur, sous forme de **Verifiable Credential W3C sécurisé en JWS compact**
  (« VC-JWT », via `firebase/php-jwt` - pas de crypto maison ; **pas** de JSON-LD
  `Ed25519Signature2020` faute de canonicalisation RDF fiable en PHP). Cœur :
  `App\Documents\AttestationCertifier` (sign/verify/didDocument/jwks). Clé privée =
  **graine hors dépôt** (`config/attestations.php` ← `ATTESTATION_SIGNING_KEY`, générée par
  `php artisan attestations:keygen`, **jamais** en base) ; clé publique publiée via
  `did:web` (`/.well-known/did.json`, `/colloques/did.json`) + `/.well-known/jwks.json`.
  Preuve stockée en colonnes dédiées (`attestations.credential`/`key_id`/`signed_at`).
  **Fail-open à l'émission** (sans clé → attestation émise non signée) ; **fail-closed
  sur la preuve** (« signature vérifiée » seulement si elle vérifie vraiment). La
  révocation reste un état DB (une signature ne se « défait » pas). Page publique
  `/attestation/{uuid}` : vérifie la signature + existence + non-révocation, et propose le
  **VC JSON téléchargeable** (`/attestation/{uuid}/credential.json`). Rattrapage du stock :
  `php artisan attestations:sign`. Réglages `attestations.did_*` / `crypto_suite`
  (EditionSettings) désormais **effectifs**.
- **Témoignages** (page d'accueil) : table `testimonials` (modèle `App\Models\Testimonial`,
  scope `forHome()`). **Dépôt par un participant connecté** (`/mes-temoignages`,
  `Account\TestimonialController`) → statut `pending` + consentement obligatoire ; l'admin
  peut aussi saisir directement. **Modération plateforme** (`Admin\TestimonialController`,
  capacité globale **`testimonials`** dans `StaffRoles`, route `/admin/temoignages`) :
  approuver/refuser, **mettre à la une** + **réordonner**. Bouton **« Proposer la sélection
  (IA) »** = `App\AI\ScoreTestimonials` (à la demande, `AiManager::extract`) note l'impact et
  **pré-remplit** `featured`/`position` ; **l'admin garde le dernier mot**. L'accueil
  ([home.blade.php](resources/views/home.blade.php), `HomeController`) n'affiche que les
  `approved` + `featured` (triés `position`, `impact_score`) ; section masquée si vide.

### Programme - onglet Planning = board « salles × temps »

L'onglet **Planning** (`admin/programme?tab=planning`) est un **board CSS grid** : colonnes =
salles (une par atelier daté), lignes = tranches de 30 min (axe horaire à gauche). Les
**plénières** sont des **bandeaux pleine largeur** (dégradé `--ukc-blue-dark→blue→bright`,
badge or) traversant toutes les colonnes → l'exclusivité est visible à l'œil ; les
**ateliers** occupent leur colonne de salle **en parallèle**. Classes `.plan-*` dans
`admin.css` (thème clair admin, Public Sans/Jost via `.ukc-shell`). Placement calculé en
Blade (`$rowLine`, colonnes = salles distinctes triées). Sessions sans horaire listées à
part (« Sessions sans horaire »). Pool **« À planifier »** filtrable (via `tableFilters`).
Bascule **Jour / Semaine** (Alpine `pview`, mémorisé en `localStorage['ukcPlanView']`) :
la **vue Semaine** est un **agenda par jour** (`.plan-weekgrid`, une **colonne par jour**,
classes `.wk-*`), **PAS** une grille time-alignée. ⚠️ L'ancienne vue Semaine time-alignée
(`.plan-week`/`.week-*`, chaque bloc positionné par `grid-row` selon l'heure) **empilait les
bandes qui se chevauchent l'une SUR l'autre** (ex. 08:30-11:30 et 09:00-11:00) : elles se
cachaient et les chips débordaient. Désormais chaque colonne-jour empile ses **blocs dans
l'ordre chronologique** (aucun chevauchement possible) : plénières = blocs bleus (badge or),
ateliers = blocs blancs regroupés par tranche horaire, avec **une chip par atelier** libellée
**« Atelier N · 📍 Salle X »** (`.wk-chip .a` = nom court de l'atelier via `Str::before(title, ' -')`,
`.wk-chip .r` = salle avec pictogramme). ⚠️ Ne PAS remettre le compteur brut `placés/total`
sur la chip : il était incompris (« 1/1 », « 2/2 ») - il vit désormais dans l'**infobulle**
(survol). Les **conflits de salle** du jour (même salle réelle, horaires qui se
chevauchent) sont recalculés côté Semaine et **bordés d'orange** (chip `.wk-chip.conflict`,
bloc `.wk-block--band.room-conflict`), comme en vue Jour. C'est une vue d'ensemble en
lecture/édition-session (le placement des communications reste en Jour).
- **Salle (vue Jour)** : la **plénière** (bandeau plein largeur, sans colonne de salle)
  affiche sa salle dans sa ligne meta (`📍 room`). Les **cartes d'atelier** n'affichent
  **pas** la salle (⚠️ ne pas la remettre) : la colonne du board porte déjà le nom de salle
  (en-tête collant), donc c'est redondant.
- **Tous les formulaires du Programme = panneau latéral (slideover)**, plus de `.ukc-modal` :
  `openAtelier/openSession/openSlot/openSpace/openRoom` posent `modal='…'` et
  `_modals.blade.php` rend un `<x-ukc.slideover open="modal==='…'">` par cas (atelier, session,
  créneau, salle, espace), chacun avec un `<form id="…Form">` et le bouton *Enregistrer* dans
  le pied via `form=`. Même schéma que « Nouvelle tâche » (Projet) / l'éditeur de formulaire.
- ⚠️ **Plus de bouton « Nouveau créneau »** dans la barre d'actions de `admin/programme` (et
  l'évènement `ukc-new-slot` a été retiré) : on crée/place les créneaux depuis le board
  (clic sur un créneau libre, glisser-déposer) et l'onglet Créneaux.
- **Pause déjeuner = exclusive, comme une plénière** : dessinée en **bandeau plein largeur**
  (`.plan-break`, vue Jour ; bloc `.wk-block--break` dans le fil de la vue Semaine) d'après
  les paramètres de créneaux (`settings.program.params.lunch{,_start,_end}`), et **bloquée à
  la sauvegarde** de toute session/créneau qui la chevauche via
  **`ProgramConflicts::lunchClash()`** + `breakMessage()` (appelé après le contrôle plénière
  dans `storeSession/updateSession/storeSlot/updateSlot`). Rien ne se tient en parallèle du
  déjeuner.
- **Les paramètres de créneaux (onglet Options) SONT appliqués** : `generateSlots()` lit
  `settings.program.params` et respecte la durée orale (`oral_min`), la **transition**
  (`gap_min`) entre créneaux, la **pause-café** après N créneaux (`auto_break`/`break_after`/
  `break_min`) et **saute la pause déjeuner** (`lunch*`) - aucun créneau généré ne tombe sur
  une pause. Tests : `ProgramSlotsTest` (pause déjeuner + pause-café).
Les créneaux « libres » et le pool ouvrent le slideover (`openSlot`/`openSession`).
L'ancien agenda FullCalendar de cet onglet (+ `program-calendar.js`, `_session-block`,
`$calendarEvents`) a été **supprimé** (redondant).
- **Glisser-déposer** (app.js, listeners délégués globaux) : on tire une communication
  (item du pool `[data-sub-id]` OU créneau placé) vers un créneau **libre**
  (`.plan-slot.free[data-assign-url]`). Le drop crée un formulaire **`data-ajax`
  éphémère** (POST `slots.assign`, CSRF depuis `<meta name="csrf-token">`) ciblant
  `data-ajax-root="prog-planning"` → board + pool rafraîchis d'un bloc. HTML5 DnD =
  **desktop** ; le **clic** (modale) reste le repli tactile. La règle plénière est déjà
  garantie (un créneau libre n'existe que dans un atelier).
- **Surlignages de conflit (vue Jour uniquement, calculés en Blade)** : deux repérages
  visuels distincts, sans bloquer l'affichage. (1) **Conflit d'auteur** (`$conflictSlots`,
  **rouge**) : un même auteur correspondant sur deux créneaux placés qui se chevauchent
  (télescopage physique) → le créneau (`.plan-slot.placed.conflict`) est surligné + drapeau.
  (2) **Salle en double** (`$roomConflicts`, **orange**) : deux **séances** horodatées qui
  se chevauchent dans la **même salle réelle** (`$realRoom` = `room` ou salle de l'atelier ;
  une salle « À définir » n'est **pas** comptée) → la carte de séance
  (`.plan-ev--atelier.room-conflict` / `.plan-ev--plenary.room-conflict`) est bordée
  d'orange + icône `alert-triangle`. Chacun a son bandeau récap en tête de jour
  (`.plan-conflict-banner` rouge / `.plan-conflict-banner--room` orange). Rappel métier :
  les ateliers **parallèles** ne sont légitimes que dans des salles **distinctes** ; deux
  ateliers dans la même salle au même moment = double-booking. Ce cas **n'est pas bloqué à
  la sauvegarde** (seule la règle plénière l'est) : la détection le rend visible. Le seed de
  démo en contient (deux séances d'un même atelier partagent une salle sur un créneau qui se
  chevauche).

### Programme - exclusivité des plénières / ateliers parallèles

Règle métier : une **session plénière est EXCLUSIVE** (aucune autre activité au même
moment) ; les **ateliers peuvent se dérouler en parallèle** entre eux. Source unique :
**`App\Support\ProgramConflicts::blockingSession()`** (chevauchement interdit dès qu'une
plénière est impliquée ; deux ateliers peuvent se chevaucher). Appliqué **en blocage** à
la sauvegarde des sessions et des créneaux (`ProgramController::storeSession`/`updateSession`/
`storeSlot`/`updateSlot` → `back()->with('ukc_error', …)`). `generateSlots` ne découpe **pas**
les plénières (exclusives, sans communication orale). L'affectation auto autorise les
ateliers simultanés mais **jamais un même auteur sur deux créneaux qui se chevauchent**.
Côté public (page colloque + Déroulement), les ateliers simultanés portent un badge
**« En parallèle »**. Tests : `ProgramPlenaryExclusivityTest`.

### Secrétariat = TROIS onglets portés par l'URL

`SecretariatController` + `admin/secretariat/_nav.blade.php` (pilules `.ukc-nav-docs`,
`['current' => …]`). **Ordre des onglets : Documents · Secrétariat assisté · Rapports de
séance** (le défaut `admin.secretariat` reste le tableau de bord des rapports, même s'il
est listé en dernier) :

- **Rapports de séance** (`admin.secretariat`, vue `admin/secretariat.blade.php`) =
  **poste de travail unifié**, **scopé par événement** (sélecteur « Événement » en tête,
  `?event_id=`, défaut `currentEditionId()` - comme les onglets Documents et Assisté).
  Quatre cartes KPI **cliquables = filtres** (`à valider` / `en attente` / `validés` /
  `toutes`) sur **un tableau unique** (séance · rapporteur · statut · date · action),
  filtré côté client (Alpine). Statut calculé une fois dans `index()` (`to_validate` →
  `awaiting` → `scheduled` → `validated`, rang de priorité). ⚠️ **Cliquer « Validés »
  montre TOUS les rapports validés de l'événement** (il n'y a plus de « 6 récents » ni de
  second écran).
  - ⚠️ **Les trois onglets du Secrétariat portent le même sélecteur d'événement en tête**
    (Rapports `?event_id=`, Documents `?doc_event=`, Assisté `?assist_event=`) : le
    vocabulaire est « Événement », jamais « Colloque » (le modèle est typé).
  - ⚠️ **Lecture / amélioration / VALIDATION se font SUR PLACE**, dans un **panneau
    latéral** (`<x-ukc.slideover>`, état `{ open, cur }`, `show(row)` pose `cur` via
    `@js($rowData)` et `open=true`). Le panneau édite synthèse/points/affluence
    (`<form :action="cur.action">` → `admin.program.reports.save`) et, pour un rapport
    validé, propose **Rouvrir au rapporteur** (`cur.reopen` → `admin.program.reports.reopen`,
    `data-confirm`). Mêmes endpoints de mutation qu'avant (dans `ProgramController`).
  - ⚠️ **Il n'y a plus de page `/secretariat/rapports` par événement** : elle doublait
    ce tableau de bord (deux écrans « Rapports de séance » avec cartes + liste). La route
    `admin.secretariat.reports` **redirige** vers `admin.secretariat` (anciens liens,
    signets, e-mails, et redirection de `admin.program.reports`). Ne pas la recréer en
    page. Vue `admin/secretariat/reports.blade.php` **supprimée**.
  - ⚠️ **« Relancer » = notification SILENCIEUSE, pas un `mailto`** (POST
    `admin.secretariat.remind` → `SecretariatController::remind`). Au clic : une
    notification in-app au compte du rapporteur (`Notifier::account`, kind
    **`report_reminder`**) + un e-mail rendu depuis le **modèle éditable**
    `report_reminder` (`EmailTemplates::forReportReminder`, `CampaignMail`), gaté par la
    préférence e-mail. ⚠️ Le kind est déclaré **`hasMail => true`** dans
    `NotificationKinds` pour que la notification n'envoie PAS son e-mail générique (sinon
    doublon avec le modèle). Le modèle de relance se personnalise **parmi les modèles de
    mails** (onglet E-mails). Ne pas revenir à un `mailto:` que l'admin composerait.
  - ⚠️ **Les rapports de séance s'EXPORTENT depuis le module Rapports** (`/admin/rapports`) :
    dataset **`session_reports`** dans « Exports prêts » (CSV/XLSX, `ReportController::export`)
    et périmètre **`reports`** de l'« Export personnalisé (IA) » (lu par l'IA, inclus aussi
    dans le périmètre « Événement »). Lignes/en-têtes dans
    `EventReport::sessionReportRows()`/`sessionReportHeaders()` (une ligne par séance avec
    rapporteur : état, affluence, synthèse, points saillants).
- **Documents** (`admin.secretariat.documents`, vue `admin/secretariat/documents.blade.php`)
  = cinq documents logistiques **sans IA**, remplis depuis les données réelles et
  **imprimables** : liste de présence, feuille d'émargement, affiche directionnelle,
  ordre du jour, chevalets nominatifs. Sélecteur d'événement (`?doc_event=`), une carte
  par document ouvrant un **slideover** de paramètres (quelques champs : séance/jour,
  en-tête libre, lignes vierges, « pour qui »…). Le slideover est un `<form method="GET"
  target="_blank">` vers **`admin.secretariat.documents.print` (`/…/{event}/{type}/imprimer`)**
  qui rend une page autonome `secretariat/print/{type}.blade.php` (layout partagé
  `_layout`, barre Imprimer/Fermer `window.print()`, `@media print`). Données par type :
  `SecretariatController::printData()` (participants, séances du jour, salles groupées,
  modérateurs/rapporteurs distincts). Types + libellés : `SecretariatController::PRINT_TYPES`.
  ⚠️ Ces documents sont des **pages HTML imprimables** (pas de dompdf) : rapides,
  déterministes. Tests : `SecretariatDocumentsTest`.
  - **En-tête des documents conçu une fois, PAR ÉVÉNEMENT** : carte « En-tête des
    documents » en tête de l'onglet (slideover `panel==='header'`, POST
    `admin.secretariat.documents.header` → `saveDocHeader`). Réglages
    `secretariat.doc_org` (surtitre/organisation, repli `config('app.name')`),
    `doc_subtitle` (mention), `doc_logo` (bool, `Branding::mark()`), `doc_align`
    (left/center), via `EventSettings`. Résolus par `SecretariatController::docHeader()`
    et rendus par le **partial partagé `secretariat/print/_head.blade.php`** (inclus par
    presence/emargement/ordre_jour avec `title`+`sub`). ⚠️ Ne pas réécrire un en-tête en
    dur « {app} · {type} » dans une vue d'impression : passer par `_head`.
  - **Liste de présence / émargement : case « Liste vierge »** (`blank=1` + `rows`) =
    aucun participant pré-rempli, uniquement des lignes numérotées vides à remplir à la
    main. Sinon participants triés par nom (émargement garde ses `extra` lignes en plus).
    Lu par `printData()` ; `extraRows` sert aussi bien aux lignes vierges qu'au mode
    pré-rempli.
  - ⚠️ **La barre de `secretariat/print/_layout` est au bleu de la charte** (dégradé
    `--navy #14205a → --blue #223ea4`, comme les héros), pas l'ardoise. Bouton Imprimer
    en or charte.
  - ⚠️ **`tw-stat__icon` est en `position: absolute`** (badge d'angle des cartes stat :
    `top/right`). Dans une carte qui n'est **pas** `position: relative` (ici
    `.sec-doc-card`), l'icône s'échappe vers le **coin haut-droit de la page** et se pose
    par-dessus l'avatar - c'était le « carré gris » signalé. Toute carte réutilisant
    `tw-stat__icon` en icône **inline** doit la repasser en `position: static` (cf. règle
    `.sec-doc-card .tw-stat__icon` dans `tailwise.css`), ou poser `position: relative` sur
    la carte.
- **Secrétariat assisté** (`admin.secretariat.assistant` GET + `admin.secretariat.assist`
  POST, vue `admin/secretariat/assistant.blade.php`) = génération **IA** sur mesure
  (prompt/modèle joint), conservée pour les documents non standard. Presets
  `SecretariatController::PRESETS`.

### Architecture & sécurité

- **RBAC à deux niveaux** : staff `User` global (spatie/permission) vs `Participant`
  **par édition** (capacités = fonction affectée, middleware `edition.cap` ; l'admin est
  super-admin). Toujours vérifier la capacité côté serveur.
- Multi-guard : **`web`** (back-office) et **`account`** (participants).
- Logique métier réutilisable → une **Action** dans `app/Actions` (testable), pas dans
  le composant Livewire. Gardes d'autorisation dans `mount()` (`abort_unless`).
- Écritures multi-étapes clés sous `DB::transaction` (paiement anti-doublon avec
  `lockForUpdate`).
- **IA** : couche `App\AI` (contrat `AiProvider`, `AiManager` = résolution + failover,
  `AiRouter` = routage fast/deep). Le copilote back-office exécute de vraies actions
  (`App\AI\Copilot`) **après autorisation explicite** + capacité RBAC revérifiée.
  - ⚠️ **Copilote admin (« Marc ») = UN SEUL chemin : l'action exécutable.** Le
    mécanisme de « proposition » descriptive (boutons *Approuver / Modifier avant /
    Refuser* + route `admin.ai.decide` + clé `proposal` dans la réponse de `AskAdmin`)
    a été **retiré** : approuver une proposition ne faisait que journaliser une ligne,
    sans rien exécuter - un cul-de-sac incompris. Désormais `AskAdmin::handle` renvoie
    `{ answer, action }` (plus de `proposal`), et le prompt impose à Marc : dès que la
    demande correspond à une action de `CopilotActions`, il **propose cette action**
    (carte *Autoriser et exécuter* → `admin.ai.execute`, qui exécute réellement côté
    serveur) au lieu de décrire une démarche manuelle ; sinon il répond **en texte**
    (marche à suivre, onglet concerné), sans faux bouton à valider. Ne pas réintroduire
    la « proposition » : un conseil se donne dans le texte, une opération s'exécute via
    une action. Tests : `AdminAiAssistantTest`, `CopilotActionsTest`.
  - ⚠️ **Cohérence des nombres (prompt `AskAdmin`)** : quand Marc propose une action, le
    chiffre qui fait foi est le **compte réel de l'action** (son « état actuel »), pas un
    sous-total recalculé par le modèle. Le prompt lui interdit de **fabriquer un
    sous-découpage** qu'il ne peut pas justifier exactement (« N ont une communication
    refusée », listes nominatives de « ceux qui… ») - c'est là qu'apparaissent les comptes
    faux (nombre annoncé ≠ noms cités). Garde-fou de prompt (non déterministe) : le test
    `AdminAiAssistantTest` vérifie seulement qu'il est **injecté** dans le prompt.

### Contexte des assistants IA : programme, séances, salles, créneaux

Ce que chaque assistant « sait » vient de blocs de contexte injectés dans son prompt.
Sources, pour ne pas les redéclarer à chaque fois :

- **`App\Support\EventKnowledge::brief($event)`** = base de connaissance **publique**
  partagée (identité, axes, échéances, règles, tarifs, pratique, **programme**, contact,
  FAQ). Lue par l'assistante **Gesco** (`AskGesco`) ET le **copilote admin** (`AskAdmin`).
- **`App\Support\ProgramKnowledge`** (⚠️ même namespace `App\Support`) :
  - **`overview($event)`** = vue d'ensemble PUBLIQUE des séances (par jour : type,
    libellé, horaire, salle, modérateur/rapporteur, nb de communications). **Incluse dans
    `EventKnowledge::brief`** → les deux assistants l'ont.
  - **`planning($event, $max=80)`** = planning DÉTAILLÉ des créneaux de communication
    (horaire, salle, titre + réf + auteur, drapeaux REPROGRAMMÉ / passage constaté).
    **Réservé au copilote admin** (`AskAdmin` l'ajoute aux « CHIFFRES RÉELS ») - ce sont
    des données de travail, pas de l'info publique. Borné pour tenir dans le budget de
    contexte. ⚠️ Nommer les auteurs via `data_get($slot,'submission.participant.user.full_name')`
    (relations traitées non-nulles par Larastan → pas de `?->` à gauche d'un `??`).
- **Assistante publique - programme PERSONNEL** (`GescoController::scheduleContext`) :
  pour un participant CONNECTÉ, ses créneaux de passage (« quand/où je communique »,
  séance, salle, horaire), + le passage constaté, + **la reprogrammation** (avant →
  après, et quand). Le prompt de Gesco l'autorise explicitement à répondre là-dessus.
  ⚠️ Jamais les créneaux d'un AUTRE participant (filtré sur ses `participant_id`).
- **Assistante publique - ENGAGEMENTS personnels** (`GescoController::engagementContext`) :
  toujours pour le participant CONNECTÉ, ses **formations** suivies (titre, date, réglée
  ou non), ses **rôles de séance** (modérateur / rapporteur, rapprochés par e-mail), ses
  **relectures** confiées (à rendre / en retard, `reviewer_id` = id Participant), ses
  **réclamations / incidents** déposés (référence + état). Même garde d'étanchéité : ne
  lit QUE les données du compte (par `user_id` / e-mail / ses `participant_id`).
- **Copilote admin - ANNUAIRE NOMINATIF** (`App\Support\AdminDirectory::digest`, injecté
  dans `AskAdmin` uniquement) : noms, prénoms et **e-mails** des participants (grade, rôle,
  institution, payé/à régler), des **paiements** (reçu, payeur + e-mail, montant, méthode,
  statut) et des **auteurs correspondants** (réf, titre, e-mail, axe, statut). Réutilise les
  lignes d'export d'`EventReport` (source unique). ⚠️ **JAMAIS côté Gesco** (données de
  tiers) ; le prompt admin autorise explicitement à les communiquer DANS le périmètre de
  fonction et du colloque. ⚠️ **Borné** (participants 200, paiements 120, auteurs 200 ; le
  prompt est reconstruit à chaque message) : au-delà, renvoi vers les filtres/export des
  pages dédiées plutôt que gonfler le contexte. Test : `ProgramKnowledgeTest` (présent côté
  admin, absent côté Gesco).
- **Copilote admin - pilotage élargi** (`AskAdmin`, bloc « CHIFFRES RÉELS ») : en plus des
  soumissions/paiements/recettes, il reçoit les **relectures** (`EventReport::reviewStats`),
  les **rapports de séance** (validés / attendus, `SessionReport` + `counts()`), les
  **formations** (si `Formation::offeredIn`, inscriptions réglées), les **réclamations
  ouvertes** et le **projet EN RETARD** (`ProjectMilestone`/`ProjectTask::isOverdue()`,
  jalons + tâches dont l'échéance est dépassée). Le prompt cadre déjà la réponse sur la
  FONCTION de l'utilisateur (un comptable reste sur les finances) : enrichir le contexte
  ne contourne pas ce périmètre. ⚠️ Réutiliser `EventReport` (source unique des agrégats),
  ne pas recompter à la main.
- **Assistante publique - MONTANT dû** (`GescoController::paymentDueContext`) : pour chaque
  événement où le participant a une communication acceptée non réglée, le **montant exact**
  (résolu depuis son grade par `Fees::resolveAmount`, repli `highestAmount` - EXACTEMENT
  comme la page de paiement), le **tarif applicable** et l'échéance pratique (début de
  l'événement, `date_start` - il n'y a pas de colonne d'échéance de paiement dédiée). Gesco
  ne disait auparavant que « à régler » sans chiffre.
- **Historique de reprogrammation = `slots.schedule_log`** (JSON, migration
  `2026_10_06_000100`). Capturé **automatiquement** par l'évènement `Slot::updated` :
  dès qu'un créneau portant une communication (`submission_id > 0`) et **déjà horodaté**
  voit son horaire, sa salle ou sa séance changer, une entrée `{at, from, to}` est
  ajoutée (écriture `saveQuietly`, 10 max). ⚠️ Un **placement initial** (pas d'horaire
  précédent) n'est PAS une reprogrammation. ⚠️ Capture dans le **modèle**, pas dans les
  contrôleurs → toute modif (manuelle, glisser-déposer, affectation auto) est tracée ;
  une mise à jour en **masse** par le query builder (`whereIn()->update()`) ne déclenche
  pas l'évènement (cas marginal : attribution de salle en lot). Helpers
  `Slot::wasRescheduled()` / `lastReschedule()`. Tests : `ProgramKnowledgeTest`.
- **Notification PROACTIVE de reprogrammation** (`App\Actions\NotifySlotRescheduled`,
  kind **`schedule_change`**, in-app + e-mail générique selon préférence, icône
  `calendar`, URL vers la fiche de la communication). ⚠️ **Jamais déclenchée depuis
  l'évènement du modèle** (le seed de 200 communications et les rejeux spammeraient -
  même piège que `LinkSubmissionAuthors`) : l'évènement pose seulement le **drapeau
  transitoire `Slot::$rescheduledNow`**, et c'est le contrôleur déclenché par un humain
  (`ProgramController::updateSlot`) qui appelle l'action. L'action est **sans effet** si
  le drapeau est faux (placement initial, pas un déplacement) ou si l'auteur n'a pas de
  compte. L'affectation auto (`autoAssignSlots`) ne fait que du **placement initial** et
  ne notifie donc pas. Pour notifier depuis un nouveau chemin de reprogrammation,
  rappeler `NotifySlotRescheduled` après l'`update()` (ne pas recâbler l'évènement).

### Tests

- `RefreshDatabase` global (base `:memory:`). Fabriques partagées dans `tests/Pest.php`
  (`openEdition()`, `verifiedAccount()`, `adminStaff()`, `seededSubmission()`) - ne pas
  les redéclarer dans un fichier de test.
- **Ajouter des tests Pest** pour toute nouvelle fonctionnalité. Après suppression d'un
  composant, régénérer la baseline PHPStan.

## Pièges fréquents

Voir la liste complète en [§8 de PASSATION.md](PASSATION.md). Les plus courants :

- **Livewire 4** : ne pas nommer une action `transition` (réservé) ni une variable de
  vue `slots` (injectée par Livewire).
- **Colonnes `nullable` traitées comme « 0 »** (`slots.submission_id`, `revision_of`) :
  forcer `=> 0` à la création, un modèle fraîchement `create()` ne reflète pas les
  défauts SQL en mémoire.
- **Paiement en ligne** : le statut « payé » vient **toujours** d'une vérification
  serveur ou d'un webhook à signature vérifiée (fail-closed), jamais d'un paramètre
  d'URL de retour.
- **Ne jamais nommer l'agrégateur de paiement côté payeur** (FedaPay, CashPay,
  SEMOA…) : les libellés de `PaymentGateway::label()` et les textes des pages de
  paiement restent génériques (« Mobile money / carte bancaire »). Le nom de
  l'agrégateur n'apparaît que dans la **configuration admin** de l'édition. Un test
  (`PaymentOperatorTest`) garde cette règle.
- **Nature d'un paiement** : `Payment::flow()` reste la valeur **technique**
  (`participation` par défaut, `formation`, `coauthor`) qui pilote la garde
  anti-double de `ConfirmPayment` - **ne pas la changer**. Côté affichage il n'y a
  que **deux natures** (`Payment::FLOWS`) : **`displayFlow()`** range le co-auteur
  avec la **participation** (il règle pour participer), la nuance n'apparaissant
  que dans `flowDetail()`. La page `admin/paiements` affiche une colonne **Objet**
  et un **filtre par nature** (Participation englobe les co-auteurs) ;
  les moyens sont regroupés (`Payment::methodGroups()`) pour ne plus mélanger
  encaissement manuel et parcours en ligne.
- **Opérateur mobile money** : choisi **chez nous** avant la redirection
  (`<x-ukc.pay-operator :gateways="…" />` → `Payment::setOperator()` →
  `meta['operator']` → `mode` chez le prestataire), pour que sa page arrive
  pré-sélectionnée. `OnlineGateway::mobileOperators()` liste les codes ciblables
  (vide si la passerelle ne sait pas le faire).
- **Téléphone obligatoire à l'inscription ET au profil** (`account.register.store`,
  `account.profile.update`) : il sert
  d'identifiant fort pour le rapprochement (auteurs, formateurs) et pré-remplit la
  passerelle. Les tests qui postent le profil doivent l'inclure.
- **Identité venant d'un compte = jamais la saisie libre** : dès qu'un identifiant
  fort (e-mail **ou** téléphone) correspond à un `User`, le serveur **impose** le
  nom/e-mail/téléphone du compte (co-auteurs via `AuthorMatcher`, formateurs via
  `Admin\FormationController::trainerAttributes()`). Cela évite les fautes de nom et
  l'usage de l'e-mail d'un tiers. Le pré-remplissage côté client n'est qu'un confort.
- ⚠️ **« A payé » = paiement de PARTICIPATION** : un paiement de formation est
  rattaché au **même `Participant`**, donc un `payments()->where('status','paid')`
  brut le compte et débloque à tort badge, attestations et accès. Toujours passer
  par **`Participant::participationPayments()`** (ou le scope
  **`Payment::forParticipation()`**), qui exclut `meta.flow = 'formation'` et
  **inclut le co-auteur** (il règle bien pour participer). Les totaux **financiers**
  (recette encaissée) gardent au contraire tous les paiements.
  **`PaymentRightsGuardTest` scanne `app/` et `resources/views/`** et échoue sur tout
  contrôle « payé » non filtré (il donne fichier:ligne). Un total financier légitime
  s'ajoute à la liste `$allowed` de ce test, avec sa justification.
- **Avant d'engager un paiement** : le **profil doit être complet** -
  **`User::profileComplete()`** (prénom, nom, **téléphone**, affiliation + e-mail
  vérifié ; `missingProfileFields()` donne les libellés manquants). Les coordonnées
  envoyées à la passerelle passent par **`Payment::payerContact()`** (participant,
  **repli sur le compte unifié**) pour **pré-remplir** sa page - le téléphone est
  indispensable au mobile money.
- **`@php(...)`/`@json([...])` multi-ligne** dans une vue peut casser le compilateur
  Blade - préparer la donnée dans le contrôleur/composant.
- ⚠️ **`@php(...)` en ligne AVANT un bloc `@php … @endphp`** dans la même vue :
  Blade traite les blocs bruts en premier et avale le `@php(` en ligne comme
  ouverture du bloc → il compile `<?php(` **non terminé**, tout ce qui suit
  devient du PHP brut (les `@if` ne sont plus compilés) et la page tombe sur un
  « Undefined variable » à des dizaines de lignes de là. Dans une vue qui
  contient déjà un bloc `@php … @endphp`, **utiliser la forme bloc partout**
  (`@php $x = …; @endphp`). L'inverse (en ligne *après* le bloc) fonctionne.
- **Directive Blade collée à un mot** : `texte@if ($x)` **n'est pas compilé** (Blade
  exige un caractère non-alphanumérique avant `@`), mais le `@endif` l'est → `endif`
  orphelin et **fatal `unexpected token "elseif"`** plus loin dans la vue. Mettre une
  espace avant la directive, ou (mieux) sortir la condition dans une expression
  `{{ $x ? ... : ... }}`.
- **`settings.fees`** = **liste de `['label', 'amount', 'grades', 'default']`** : ⚠️ le
  **`label` est un intitulé libre** (« Doctorants et assimilés »), il ne sert PAS au
  rapprochement. C'est **`grades`** (liste de `ParticipantTypes::all()`) qui rattache
  la catégorie aux participants, et **`default`** qui désigne le tarif de repli.
  `Fees::resolveAmount()` cherche dans cet ordre : grades rattachés → libellé
  exactement égal au grade (grilles anciennes) → catégorie par défaut. Une catégorie
  sans grade ni défaut n'est jamais appliquée (l'écran d'édition le signale), et
  **sans AUCUNE catégorie par défaut, un grade non couvert tombe sur le montant le
  plus élevé** de la grille (repli `Fees::highestAmount()` chez tous les appelants) :
  un doctorant se retrouve facturé au tarif « Autres participants » sans que
  personne le voie. L'onglet « Soumissions & tarifs » le dit maintenant en clair.
  Ancienne forme `['label' => string, 'amount' => float]` (jamais un
  tableau associatif `grade => montant`). Toujours lire via **`App\Support\Fees::grid()`**
  / **`Fees::normalize()`** (qui ignorent les entrées malformées) - jamais l'accès brut,
  sinon `$fee['label']` sur une valeur scalaire fait planter la page publique en 500.
### ⚠️ Le tarif NE SE CHOISIT PAS, il se déduit du grade

C'est la règle du logiciel (`Fees::resolveAmount`) ; trois écrans disaient
l'inverse et ont été repris :

- **Page publique, grille des tarifs** : un bouton « Choisir » par carte et une
  pastille « Recommandé » posée sur la carte du MILIEU - un index, pas un
  conseil. ⚠️ La règle se lit **dans les cartes** (les grades couverts y
  figurent) : le bandeau qui la répétait au-dessus de la grille a été retiré,
  il doublait ce que la grille montre déjà. Ne pas le réintroduire (un cas Pest
  le gèle). Les cartes **renseignent** (qui est concerné, à quel prix),
  avec les grades couverts dedans, les avantages dits UNE fois sous la grille, et
  **un seul** appel à l'action. Quand aucune catégorie ne déclare de grade, la
  page le dit au visiteur : le tarif lui sera confirmé à l'inscription - sinon il
  reste devant trois prix sans savoir lequel est le sien.
- **Co-auteurs** : voir la section suivante - ce parcours n'encaisse plus.
- ⚠️ **Rien de personnalisé sur la page publique** (pas de « votre tarif » pour
  qui est connecté) : elle doit rester identique pour tout le monde, donc
  cacheable en HTTP - cf. l'en-tête de `ColloqueController`.

### ⚠️ UN SEUL chemin de règlement pour un co-auteur : son compte

`/colloque/{slug}/co-auteur` est une **porte d'entrée**, plus un parcours de
paiement. Elle confirme par e-mail que des communications acceptées citent la
personne, puis l'envoie créer son compte ou s'y connecter ; un visiteur déjà
connecté est redirigé droit sur `account.payment`.

- **Pourquoi** : le chemin par compte existe déjà et il est plus riche -
  `LinkSubmissionAuthors` rapproche chaque co-auteur des comptes dès la
  soumission (sinon invitation à en créer un, réactivée à l'inscription par
  `LinkPendingAuthorships`), la communication apparaît dans « Mes
  communications », l'autorité se décline, l'attestation se retrouve dans
  « Mes documents ».
- ⚠️ **Les deux parcours créaient chacun leur participant** - l'un par e-mail
  **sans `user_id`** (`UpsertCoauthorParticipant`, supprimée), l'autre par
  `user_id` (`UpsertParticipantForAccount`) - et **rien ne les rapproche** : il
  n'existe aucune contrainte d'unicité sur `(edition_id, email)`. **Mesuré** :
  payer sur la page publique puis créer son compte laissait **deux
  participants** pour la même personne sur la même édition. Chiffres faussés, et
  surtout l'attestation émise sur le participant sans compte - donc
  **introuvable dans l'espace de l'intéressé**. Dans l'autre ordre il n'y avait
  pas de doublon, la page réutilisant la ligne existante : le défaut dépendait
  de l'ordre, donc il passait inaperçu.
- ⚠️ **L'URL est conservée** : les e-mails d'acceptation déjà partis la portent,
  et un lien mort aurait coûté des règlements. L'e-mail d'acceptation
  (`NotifyCoauthorsForPayment`) est **gardé aussi** - c'est lui qui annonce
  « votre communication est acceptée, vos frais sont dus », ce que l'invitation
  envoyée à la soumission ne dit pas - mais il pointe désormais sur la porte
  d'entrée et non plus sur un encaissement parallèle.
- ⚠️ L'état **« frais déjà réglés »** reste sur la page : le retour de passerelle
  d'un règlement co-auteur **ancien** (`meta.flow = coauthor`) y atterrit encore
  (cf. `PaymentController::return`).
- L'adresse voyage dans le lien d'inscription (`?email=`) : c'est elle qui
  rattache les communications au compte créé.
- **Présentation** : la page emprunte la grammaire des autres pages publiques -
  `partials.public-header`, `section.page-hero`, `ukc-section`, barre d'étapes
  `ukc-steps`, puis `partials.colloque-footer`. On y arrive par un lien reçu par
  e-mail, souvent **sans savoir de quel colloque il s'agit ni comment en sortir** :
  le bloc centré sans en-tête ni pied de page qu'elle était ne le disait pas.
- ⚠️ **MESURÉ** : `background: currentColor` dans le même bloc que `color: #fff`
  résout le fond sur le `color` qu'on vient de poser - la pastille de l'étape
  active sortait **blanche sur blanc**, numéro invisible. On nomme chaque fond,
  état par état (9,16:1 pour l'étape active).
- ⚠️ `.ukc-card h2` est en `space-between` **pour le back-office**, où une action
  vit à droite du titre. Sur une page publique il n'y en a aucune et le titre
  partait à l'autre bout de la carte, loin de son icône : les pages publiques
  (portée `.site-header ~ …`) le regroupent à gauche. Corrige aussi
  « Vérifier une attestation », qui portait le même défaut.
### Densité des back-offices : zoom 90%

`html.ukc-dense { zoom: .9 }` (`ukc.css`), posé par les layouts `admin` et
`participant` via l'attribut `html-class` de `x-layouts.app`.

- ⚠️ **Sur `<html>`, jamais sur le shell** : le `zoom` d'un sous-arbre met à
  l'échelle les longueurs qu'il contient **sans toucher au viewport**, donc un
  `min-height: 100vh` ne couvrirait plus que 90% de l'écran et la barre latérale
  fixe s'arrêterait avant le bas. Sur la racine, c'est le bloc conteneur initial
  qui est mis à l'échelle - exactement ce que fait le zoom du navigateur, qui
  reste d'ailleurs cumulable. **Vérifié** : barre latérale 698,4px pour une
  fenêtre de 698px, aucun débordement horizontal.
- ⚠️ Les **media queries** continuent de mesurer la fenêtre réelle : les points
  de rupture se déclenchent un peu tôt, dans le sens prudent (mise en page
  étroite avec de la place en trop).
- Le **public** garde l'échelle du navigateur du visiteur.
- **Pint** applique un style projet-wide (concat sans espaces `'a'.$b`) - lancer `pint`
  avant de commiter.

### Scrollbar du back-office = Perfect Scrollbar (overlay façon bizzcart)

Deux mécanismes, complémentaires :

- **Perfect Scrollbar** (lib JS, `app.js` → `initPerfectScrollbars`) pour les
  **conteneurs défilants à hauteur bornée** : la **navigation latérale**
  (`.ukc-sidebar-nav`) du back-office **et** de l'espace participant, plus tout
  élément portant **`data-ps`**. C'est lui qui donne l'overlay fin qui
  **apparaît en fondu au survol / pendant le défilement** (comportement identique
  à la barre latérale de l'admin bizzcart). Instance mémorisée sur l'élément
  (`el.__ps`), mise à jour via un `ResizeObserver` + au `resize` fenêtre, et PS
  est **désactivé sur pointeur grossier** (`pointer: coarse`) où le défilement
  tactile natif suffit. Thème dans `ukc.css` (bloc « Perfect Scrollbar ») : rail
  transparent, pouce 6px arrondi - **clair** `rgba(255,255,255,.28/.45)` sur la
  nav sombre (un pouce noir bizzcart y serait invisible), **noir** `rgba(0,0,0,.2/.35)`
  pour les `[data-ps]` sur fond clair. ⚠️ Nos sélecteurs portent `.ukc-shell`
  (2 classes) → ils priment sur ceux de la lib (`.ps__thumb-y`, 1 classe) quel que
  soit l'ordre des feuilles. ⚠️ **Jamais sur la fenêtre** : PS ne gère que des
  conteneurs à `overflow` masqué qu'il pose lui-même.
- **Scrollbar natif stylé** (CSS pur, `ukc.css`, `html.ukc-dense` + `.ukc-shell *`)
  pour tout le reste, **le défilement de page compris** (que PS ne peut pas
  prendre) : même palette bizzcart (pouce `rgba(0,0,0,.2)`, rayon 6px, piste
  transparente). C'est le repli quand PS ne s'applique pas (public = scrollbar
  natif du navigateur, ni `ukc-dense` ni `.ukc-shell`).

### Tutoriels vidéo : rendu autonome et simulations par étape (2026-10-09)

- Une demande depuis `admin/prise-en-main` ne dépend plus d’un worker Laravel
  externe : `TutorialVideoWorkerLauncher` lance `tutorial-videos:render-pending`,
  lequel revendique puis produit les demandes `queued` directement en base.
  Ainsi, un administrateur déclenche le rendu serveur sans intervention Codex.
- Chaque vidéo reprend le **monogramme compact** (`app_logo_mark`, avec repli sur
  `public/img/apple-touch-icon.png`), jamais le lockup large.
- Chaque étape porte une simulation d’interface contextualisée. Ce sont des
  maquettes explicatives fidèles à la charte GEVES, jamais des captures
  authentifiées. La bande son locale est une composition instrumentale originale
  normalisée pour rester audible.
