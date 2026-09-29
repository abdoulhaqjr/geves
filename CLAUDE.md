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

## Toolchain - ⚠️ PHP/Composer/artisan ne sont PAS dans le PATH

Ils sont fournis par **Laravel Herd** sous forme de shims `.bat`. Pour toute commande
(Git Bash) :

```bash
export PATH="$HOME/.config/herd/bin:$PATH"
php.bat artisan ...        # PHP 8.4
composer.bat ...           # Composer
```

Node/npm sont dans le PATH. Le site est servi par **Herd** sur **http://gesco.test**
(le domaine de dev et le dossier local restent `gesco`, malgré le renommage GEVES).

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

## Voir le rendu

Builder les assets (`npm run build`) puis ouvrir **gesco.test** - **ne pas** compter
sur le serveur Vite dev (bloqué en cross-origin ici). Le navigateur intégré exige une
approbation par action sur `gesco.test` ; `curl` (statut HTTP + `grep`) est un contrôle
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
- **Conflit d'intérêts « même institution » désactivable** : case dans l'onglet
  **Options** de la fiche d'édition (`settings['options']['coi_ignore_same_institution']`,
  lue par `ConflictOfInterest::ignoresSameInstitution()`). Utile quand
  l'événement est porté par une seule université - sinon la règle signale
  presque toutes les paires. Les autres règles restent actives.
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
- **Module Programme = un seul écran, sept onglets** (2026-09-28) : *Planning ·
  Ateliers · Sessions · Créneaux · Espaces · Affectation auto · Options*,
  pilotés par l'URL (`?tab=`) et définis **une seule fois** dans
  `resources/views/admin/program/_nav.blade.php`. Route canonique
  **`admin.program.index`** (`/admin/programme`).
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
    (`admin.secretariat.reports`) : il les lit, les améliore et les **valide**
    (la validation verrouille le rapport côté rapporteur). Le module Programme
    n'en porte **aucune copie** - deux écrans pour le même objet, c'est deux
    endroits où chercher. `admin.program.reports` redirige vers le Secrétariat.
  - Chaque page de sélecteur d'édition utilise **`currentEditionId()`** du trait
    `ScopesEditionAccess` (il était privé à un seul contrôleur).
- **Fiche d'édition : plus d'onglet « Programme »** (2026-09-28). **Dates clés**
  est passé dans l'onglet **Général**. ⚠️ La carte « Programme » (déroulé
  éditorial) a été **supprimée** avec son réglage `settings['programme']` :
  personne ne le lisait. Le programme public (`#programme` de la page colloque)
  est construit à partir des **sessions**, des **ateliers** et des **créneaux**
  du module Programme (`colloque.blade.php` : `$plenaries` + `$ateliers->flatMap->sessions`,
  groupés par jour). Ne pas réintroduire un
  déroulé saisi à la main : il annonçait « affiché sur la page publique » alors
  qu'il n'apparaissait nulle part.
  - ⚠️ **Le public ne montre PAS le détail des sessions** : chaque séance
    affiche heure + type (Plénière/Atelier) + nom de l'atelier + « En parallèle »
    + libellé + salle, **mais pas** la liste des communications ni les
    modérateurs/rapporteurs (c'est du back-office). Ne pas réafficher
    `$commsBySession` ni les rôles dans `#programme`. Test :
    `ProgramPublicTest` (`assertDontSee` le titre d'une communication).

- **Un seul colloque de démo, à l'échelle** : `migrate:fresh --seed` ne crée plus
  qu'**un** colloque (le VIIIe conjoint Parakou-Kara, `avenir-durable-2025`), au
  volume d'un vrai événement. `DatabaseSeeder` enchaîne `ColloqueAvenirSeeder`
  (colloque + édition **27 sept - 4 oct 2026**, ateliers, staff, relecteurs,
  direct) puis `ColloqueAvenirVolumeSeeder` (**200 communications** réf. `AVD-###`,
  un auteur correspondant chacune ; statuts variés ; créneaux pour les acceptées ;
  relectures dans tous les états ; **93 % de paiements de participation validés
  manuellement**, passerelle `manual`, reçu numéroté). Montants via
  `Fees::resolveAmount()`, grades via `ParticipantTypes::all()`. Seeders idempotents ;
  le seed prend ~45 s (200 comptes). Un test de garde vérifie les 200 et les 93 %.
  ⚠️ Les anciens seeders de colloques de démo (Showcase, Live, DemoColloque,
  DemoScenario, DemoStaff) ont été **supprimés** : ce colloque est la référence unique.
  - **Programme du seed = ateliers + plénières SANS conflit** (l'exclusivité des
    plénières est respectée dans les données de démo). Les **ateliers** de volume
    occupent « Salle 1 » à « Salle 6 » (6 en parallèle) par blocs matin **08:30-11:30**
    et après-midi **14:00-17:00** (`ColloqueAvenirVolumeSeeder::buildSessions()`, 7 blocs
    dim.→jeu., 42 séances de 3 h). Les **4 sessions plénières** (`ColloqueAvenirSeeder::buildPlenaries()`,
    salle « Grand amphithéâtre », `type=plenary`) sont calées **hors** de ces fenêtres :
    inauguration **dim. 27/09 09:00-10:30**, table ronde **mer. 30/09 08:00-10:00**,
    formation **jeu. 01/10 14:00-17:00**, clôture **ven. 02/10 11:00-12:30** (le programme
    s'étend donc jusqu'au vendredi). Les **séances de démo** de la base (`Session - {atelier}`,
    rapports/Q&A) sont dans le **créneau médian 12:00-14:00** (laissé libre par le volume),
    et la **séance en direct** est dans un « Auditorium » dédié : aucune ne collisionne en
    salle avec le volume. ⚠️ En touchant à ces horaires, garder ces fenêtres disjointes -
    sinon la vue Planning affiche des conflits de salle / de plénière dès le seed.
### Règles métier - limites communications & attestations

- **Limite de communications** (`settings.max_communications`) : compte **seulement** les
  communications où le compte est **auteur correspondant** (participant déposant,
  `Submission::usedCountFor()`). Illimité en tant que **co-auteur**.
- **Attestation de communication = par communication ET par personne** : sur chaque
  communication acceptée, l'auteur correspondant **et** chaque co-auteur **payé** ont une
  attestation à leur nom, liée à la communication (`attestations.submission_id`). Deux
  plafonds : `settings.coauthors_max` (co-auteurs attestés / communication) et
  `settings.attestation_max` (attestations de communication / personne - quand on dépasse,
  on **privilégie les communications où la personne est auteur correspondant**, puis les
  autres par ordre chronologique). Calcul dans `App\Support\CommunicationAttestations` ;
  émission via `IssueAttestation::handle($participant, 'communication', $submission)`.
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
  `/verifier/{uuid}` : vérifie la signature + existence + non-révocation, et propose le
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
- **Salle visible partout (vue Jour)** : la **plénière** (bandeau) affiche sa salle dans sa
  ligne meta (`📍 room`) ; chaque **carte d'atelier** affiche sa salle sur sa propre ligne
  (`.plan-ev--atelier .ahead .room`, pictogramme `pin`), en plus de l'en-tête de colonne
  (salle) resté collant.
- **Édition d'une session = panneau latéral (slideover)**, pas une modale : `openSession()`
  pose `modal='session'` et `_modals.blade.php` rend un `<x-ukc.slideover open="modal==='session'">`
  (form `id="sessionForm"`, bouton *Enregistrer* dans le pied via `form=`), au même schéma que
  « Nouvelle tâche » (Projet) et l'éditeur de formulaire. Les autres formulaires (atelier,
  créneau, salle, espace) restent des `.ukc-modal`.
- ⚠️ **Plus de bouton « Nouveau créneau »** dans la barre d'actions de `admin/programme` (et
  l'évènement `ukc-new-slot` a été retiré) : on crée/place les créneaux depuis le board
  (clic sur un créneau libre, glisser-déposer) et l'onglet Créneaux.
Les créneaux « libres » et le pool ouvrent la modale/le slideover (`openSlot`/`openSession`).
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
  sans grade ni défaut n'est jamais appliquée (l'écran d'édition le signale).
  Ancienne forme `['label' => string, 'amount' => float]` (jamais un
  tableau associatif `grade => montant`). Toujours lire via **`App\Support\Fees::grid()`**
  / **`Fees::normalize()`** (qui ignorent les entrées malformées) - jamais l'accès brut,
  sinon `$fee['label']` sur une valeur scalaire fait planter la page publique en 500.
- **Pint** applique un style projet-wide (concat sans espaces `'a'.$b`) - lancer `pint`
  avant de commiter.
